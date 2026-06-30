<?php
namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Models\Operation;
use Google\Client;
use Google\Service\Oauth2;

class Google extends ApiController
{
    private $operation;
    private $client;

    public function __construct()
    {
        require_once APPPATH . 'Libraries/google-api-php-client/vendor/autoload.php';

        $this->operation = new Operation();
        $this->client = new Client();
        $this->client->setClientId(getenv('google.clientId'));
        $this->client->setClientSecret(getenv('google.clientSecret'));
        $this->client->setRedirectUri(base_url('api/v1/google/google_callback'));
        $this->client->addScope(Oauth2::USERINFO_EMAIL);
        $this->client->addScope(Oauth2::USERINFO_PROFILE);
    }

    public function google_callback()
    {
        $rawData = file_get_contents("php://input");
        error_log("Raw Data Received: " . $rawData);

        $decodedData = json_decode($rawData, true);
        error_log("Decoded Google Token: " . json_encode($decodedData));

        if (!$decodedData || !isset($decodedData['credential'])) {
            return $this->error_response("Invalid request: Missing Google credential.");
        }

        $googleToken = $decodedData['credential'];
        $payload = $this->client->verifyIdToken($googleToken);

        if (!$payload) {
            error_log("Google Token Verification Failed.");
            return $this->error_response("Invalid Google token.");
        }

        $user_email = $payload['email'];
        $searchCondition = ["email" => $user_email];
        $user_data = $this->operation->get_data("user_login", "*", $searchCondition, "id", "DESC");

        if ($user_data['num_rows'] == 0) {
            $inserted_id = $this->operation->insert_data("user_login", [
                'name' => $payload['name'],
                'email' => $user_email,
                'profile_picture' => $payload['picture'],
                'type' => 1,
                'status' => 1
            ]);

            if (!$inserted_id) {
                error_log("User Registration Failed.");
                return $this->error_response("Failed to register user.");
            }

            $user = (object) [
                'id' => $inserted_id,
                'name' => $payload['name'],
                'email' => $user_email,
                'profile_picture' => $payload['picture'],
                'status' => 1
            ];
        } else {
            $user = $user_data['result'][0];
        }

        $encrypted_data = $this->operation->encrypt_decrypt('encrypt', json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_picture' => $user->profile_picture,
            'status' => $user->status
        ]));

        return $this->success_response("Google login successful.", [
            'token' => $encrypted_data,
            'name' => $user->name,
            'profile_picture' => $user->profile_picture
        ]);
    }

}


