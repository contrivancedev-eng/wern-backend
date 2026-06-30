<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Models\Operation;

class AdminAuth extends ApiController
{
    public function login_action()
    {
        $operation = new Operation();

        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        if (!$decodedData) {
            $decodedData = [
                'email'    => $this->input_post('email'),
                'password' => $this->input_post('password'),
            ];
        }

        $email    = trim($decodedData['email'] ?? '');
        $password = trim($decodedData['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->error_response("Email and Password are required.");
        }

        // Look the admin up by email only; verify the password in PHP so we
        // never expose whether the email exists and can support hash upgrades.
        $result = $operation->get_data("admin", "*", [
            "email" => $email,
        ]);

        if ($result['num_rows'] == 0) {
            return $this->error_response("Invalid email or password.");
        }

        $admin  = $result['result'][0];
        $stored = (string) $admin->password;

        $valid = false;
        if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
            // Legacy md5 hash. Verify, then transparently upgrade to bcrypt.
            if (hash_equals($stored, md5($password))) {
                $valid = true;
                $operation->update_data(
                    "admin",
                    ["id" => $admin->id],
                    ["password" => password_hash($password, PASSWORD_DEFAULT)]
                );
            }
        } else {
            // Modern password_hash() value.
            $valid = password_verify($password, $stored);
        }

        if (!$valid) {
            return $this->error_response("Invalid email or password.");
        }

        if ((int) $admin->status !== 1) {
            return $this->error_response("Account is inactive. Contact superadmin.");
        }

        // The real authentication boundary: a server-side session. The cookie
        // that carries it is HttpOnly, so page JavaScript can never read it.
        // Regenerate the ID once on privilege elevation to prevent session
        // fixation (periodic regeneration is disabled in Config\Session).
        session()->regenerate(false);
        session()->set([
            'authID'    => $admin->id,
            'authName'  => $admin->name,
            'authEmail' => $admin->email,
            'authImage' => $admin->image ?? 'default.png',
        ]);

        return $this->success_response("Login successful.", [
            'admin' => [
                'id'    => $admin->id,
                'name'  => $admin->name,
                'email' => $admin->email,
            ],
        ]);
    }
}
