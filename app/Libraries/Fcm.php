<?php

namespace App\Libraries;

require_once APPPATH . 'Libraries/php-jwt/src/JWT.php';
require_once APPPATH . 'Libraries/php-jwt/src/Key.php';

use Firebase\JWT\JWT;

/**
 * Firebase Cloud Messaging sender (HTTP v1 API).
 *
 * Auth flow:
 *   1. Read the Firebase service-account JSON (path from .env: fcm.serviceAccountPath).
 *   2. Sign a short-lived JWT (RS256) with the service account's private key.
 *   3. Exchange it at Google's OAuth2 token endpoint for an access token (cached ~1h).
 *   4. POST messages to https://fcm.googleapis.com/v1/projects/<projectId>/messages:send
 *
 * Config (.env):
 *   fcm.projectId           = your-firebase-project-id   (optional; falls back to JSON project_id)
 *   fcm.serviceAccountPath  = writable/firebase-service-account.json
 */
class Fcm
{
    private $projectId   = '';
    private $clientEmail = '';
    private $privateKey  = '';
    private $tokenUri    = 'https://oauth2.googleapis.com/token';
    private $cacheFile;
    private $error       = '';

    public function __construct()
    {
        $this->cacheFile = WRITEPATH . 'fcm_access_token.json';

        $path = getenv('fcm.serviceAccountPath');
        if (empty($path)) {
            $this->error = 'fcm.serviceAccountPath is not set in .env';
            return;
        }

        // Allow a path relative to the project root.
        if (!preg_match('/^([A-Za-z]:[\\\\\/]|\/)/', $path)) {
            $path = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . $path;
        }

        if (!is_file($path)) {
            $this->error = "Service account file not found: {$path}";
            return;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json)) {
            $this->error = 'Service account file is not valid JSON.';
            return;
        }

        $this->projectId   = getenv('fcm.projectId') ?: ($json['project_id'] ?? '');
        $this->clientEmail = $json['client_email'] ?? '';
        $this->privateKey  = $json['private_key'] ?? '';
        $this->tokenUri    = $json['token_uri'] ?? $this->tokenUri;

        if (empty($this->projectId) || empty($this->clientEmail) || empty($this->privateKey)) {
            $this->error = 'Service account JSON is missing project_id / client_email / private_key.';
        }
    }

    /** True when credentials loaded and usable. */
    public function isReady(): bool
    {
        return $this->error === '';
    }

    public function getError(): string
    {
        return $this->error;
    }

    /** Get (and cache) a Google OAuth2 access token for FCM. */
    private function getAccessToken()
    {
        if (is_file($this->cacheFile)) {
            $cache = json_decode((string) file_get_contents($this->cacheFile), true);
            if (!empty($cache['access_token']) && !empty($cache['expires_at']) && $cache['expires_at'] > (time() + 60)) {
                return $cache['access_token'];
            }
        }

        $now     = time();
        $payload = [
            'iss'   => $this->clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => $this->tokenUri,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        $jwt = JWT::encode($payload, $this->privateKey, 'RS256');

        $ch = curl_init($this->tokenUri);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_TIMEOUT        => 20,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $res, true);
        if ($code === 200 && !empty($data['access_token'])) {
            @file_put_contents($this->cacheFile, json_encode([
                'access_token' => $data['access_token'],
                'expires_at'   => time() + (int) ($data['expires_in'] ?? 3600),
            ]));
            return $data['access_token'];
        }

        $this->error = 'Failed to get FCM access token (HTTP ' . $code . '): ' . $res;
        return null;
    }

    /**
     * Send a push to a single device token.
     * @return array ['status' => bool, 'code' => int, 'response' => string]
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): array
    {
        if (!$this->isReady()) {
            return ['status' => false, 'code' => 0, 'response' => $this->error];
        }

        $access = $this->getAccessToken();
        if (!$access) {
            return ['status' => false, 'code' => 0, 'response' => $this->error];
        }

        // FCM data payload values must all be strings.
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[$k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        $message = [
            'message' => [
                'token'        => $deviceToken,
                'notification' => ['title' => $title, 'body' => $body],
                'data'         => $stringData,
                'android'      => ['priority' => 'high'],
                'apns'         => ['payload' => ['aps' => ['sound' => 'default']]],
            ],
        ];

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $access,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($message),
            CURLOPT_TIMEOUT        => 20,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status'   => ($code >= 200 && $code < 300),
            'code'     => $code,
            'response' => (string) $res,
        ];
    }
}
