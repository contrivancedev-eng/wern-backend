<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Models\Operation;

class Review extends ApiController
{
    /**
     * Mobile API — submit a star rating (required) + optional text response.
     * Accepts JSON body or form-post.
     * Required: token (JWT), star (1-5)
     * Optional: response (text)
     */
    public function submit_review()
    {
        $operation = new Operation();

        $rawData = file_get_contents("php://input");
        $decoded = json_decode($rawData, true) ?: [];

        $token    = $decoded['token']    ?? $this->input_post('token');
        $starIn   = $decoded['star']     ?? $this->input_post('star');
        $respIn   = $decoded['response'] ?? $this->input_post('response');

        if (!$token) {
            return $this->error_response("Token is required.");
        }

        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);
        }

        $user_id  = (int) $jwtData['user_id'];
        $star     = (int) $starIn;
        $response = trim((string) ($respIn ?? ''));

        if ($star < 1 || $star > 5) {
            return $this->error_response("Star rating must be between 1 and 5.");
        }

        $insertData = [
            "user_id"    => $user_id,
            "star"       => $star,
            "response"   => $response !== '' ? $response : null,
            "created_at" => date("Y-m-d H:i:s"),
        ];

        $id = $operation->insert_data("user_reviews", $insertData);
        if (!$id) {
            return $this->error_response("Failed to submit review.");
        }

        return $this->success_response("Review submitted successfully.", [
            "id"         => (int) $id,
            "user_id"    => $user_id,
            "star"       => $star,
            "response"   => $insertData["response"],
            "created_at" => $insertData["created_at"],
        ]);
    }
}
