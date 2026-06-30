<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController; 
use App\Models\Operation;
use App\Libraries\TwilioOtp;

class Auth extends ApiController{

    function decode_json($rawData) {
        $decodedData = json_decode($rawData, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON data: ' . json_last_error_msg()
            ];
        }
    
        return [
            'success' => true,
            'data' => $decodedData
        ];
    }

    public function user_registration() {

        $operation = new Operation();
        $timestamp = date("Y-m-d H:i:s");

        // Read POST JSON
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        $full_name        = trim($decodedData['full_name'] ?? '');
        $country_code     = trim($decodedData['country_code'] ?? '');
        $phone_number     = trim($decodedData['phone_number'] ?? '');
        $email            = trim($decodedData['email'] ?? '');
        $password         = trim($decodedData['password'] ?? '');
        $confirm_password = trim($decodedData['confirm_password'] ?? '');
        $refferal_code    = trim($decodedData['refferal_code'] ?? '');


        // ------------------ REQUIRED VALIDATIONS ------------------
        if (!$full_name || !$country_code || !$email || !$password) {
            return $this->error_response("All required fields must be filled.");
        }

        if ($password !== $confirm_password) {
            return $this->error_response("Password and Confirm Password do not match.");
        }

        // Duplicate Phone
        // $duplicate_check = $operation->get_data("users", "*", ["phone_number" => $phone_number]);
        // if ($duplicate_check['num_rows'] > 0) {
        //     return $this->error_response("This phone number is already registered.");
        // }

        // Duplicate Email
        $duplicate_check = $operation->get_data("users", "*", ["email" => $email]);
        if ($duplicate_check['num_rows'] > 0) {
            $existingUser = $duplicate_check['result'][0];

            if ((int)$existingUser->status === 1) {
                // Verified account — truly a duplicate
                return $this->error_response("This email is already registered.");
            }

            // Registered earlier but OTP never verified
            return $this->error_response(
                "This email is registered but not verified. Please verify the OTP sent to your email.",
                ["email" => $email, "verified" => 0]
            );
        }


        // ------------------ REFERRAL VALIDATION ------------------
        $referral_user_id = 0;
        if (!empty($refferal_code)) {

            // referral_code must match referal_code of users table
            $referral = $operation->get_data("users", "*", [
                "referal_code" => $refferal_code,
                "status" => 1
            ]);

            if ($referral['num_rows'] == 0) {
                return $this->error_response("Invalid referral code.");
            }

            $referral_user_id = $referral['result'][0]->id;
        }


        // ------------------ SYSTEM SETTINGS ------------------
        $settings = $operation->get_data("sys_settings", "*", ["id" => 1]);

        $refferal_bonus      = 0;
        $with_refferal_bonus = 0;
        $sign_in_bonus       = 0;

        if ($settings['num_rows'] > 0) {
            $row = $settings['result'][0];
            $refferal_bonus      = $row->refferal_bonus;
            $with_refferal_bonus = $row->with_rrefferal_bonus;
            $sign_in_bonus       = $row->sign_in_bonus;
        }


        // ------------------ INSERT USER ------------------
        $insert_data = [
            "full_name"        => $full_name,
            "country_code"     => $country_code,
            "phone_number"     => $phone_number,
            "email"            => $email,
            "password"         => md5($password),
            "show_pass"        => $password,
            "referral_user_id" => $referral_user_id,
            "status"           => 0, // Not verified yet
            "user_image"       => ADMIN_PUBLIC_PATH . "no-image.png",
            "update_on"        => $timestamp
        ];

        $user_id = $operation->insert_data("users", $insert_data);

        if (!$user_id) {
            return $this->error_response("Registration failed.");
        }


        // ------------------ GENERATE MEMBER ID ------------------
        $short_name = strtoupper(substr(preg_replace("/[^A-Za-z]/", "", $full_name), 0, 3));
        $member_card = "LBT-" . $short_name . $user_id . "-001";
        $new_referral = "WERN-" . $short_name . $user_id;

        $operation->update_data("users", ["id" => $user_id], [
            "membership_card_number" => $member_card,
            "referal_code"           => $new_referral
        ]);


        // ------------------ POINTS SYSTEM ------------------
        if (!empty($refferal_code)) {

            // Add points to referrer
            $operation->insert_data("earn_litties", [
                "user_id"     => $referral_user_id,
                "points"      => $refferal_bonus,
                "type"        => 1,
                "earn_category_id"        => 1,
                "description" => "You earned {$refferal_bonus} points for referring a new member.",
                "date"   => $timestamp
            ]);

            // Add points to new user
            $operation->insert_data("earn_litties", [
                "user_id"     => $user_id,
                "points"      => $with_refferal_bonus,
                "type"        => 1,
                "earn_category_id"        => 1,
                "description" => "You earned {$with_refferal_bonus} points for using a referral code.",
                "date"   => $timestamp
            ]);

        } else {

            // Signup bonus
            $operation->insert_data("earn_litties", [
                "user_id"                 => $user_id,
                "points"                  => $sign_in_bonus,
                "type"                    => 1,
                "earn_category_id"        => 1,
                "description"             => "You earned {$sign_in_bonus} points for signing up.",
                "date"   => $timestamp
            ]);
        }


        // ------------------ OTP GENERATION ------------------
        $otp = rand(100000, 999999); // 6-digit OTP
        $otp_time = date("Y-m-d H:i:s");

        $operation->update_data("users", ["email" => $email], [
            "verify_otp"     => $otp,
            "verifyotp_time" => $otp_time
        ]);


        // ---------------- EMAIL TEMPLATE ------------------
        $templateData = $operation->get_data("email_template", "*", ["id" => 2]);
        $user_name = $full_name;

        if ($templateData["num_rows"] > 0) {

            $templateRow = $templateData["result"][0];
            $body = $templateRow->template_body;

            // OTP digits
            $digits = str_split($otp); // gives 6 digits: index 0 to 5

            // Replace OTP placeholders
            $body = str_replace(
                ['{OTP_1}', '{OTP_2}', '{OTP_3}', '{OTP_4}', '{OTP_5}', '{OTP_6}'],
                [$digits[0], $digits[1], $digits[2], $digits[3], $digits[4], $digits[5]],
                $body
            );

            // Replace variables
            $body = str_replace(
                ['{USER_NAME}', '{USER_EMAIL}', '{EXPIRY_TIME}', '{REQUEST_TIMESTAMP}', '{IP_ADDRESS}', '{DEVICE_INFO}', '{CURRENT_YEAR}'],
                [$user_name, $email, 10, date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], ($_SERVER['HTTP_USER_AGENT'] ?? ''), date("Y")],
                $body
            );

        } else {

            // Fallback Template
            $body = "
                <p>Hello $user_name,</p>
                <p>Your OTP is <b>$otp</b>. It is valid for 10 minutes.</p>
            ";
        }


        // ---------------- OTP RATE LIMIT (5 sends / 15 min, then 15-min block) ------------------
        $rate = $operation->check_otp_rate_limit($email, 'verify');
        if (!$rate['allowed']) {
            return $this->error_response($rate['message']);
        }

        // ---------------- SEND EMAIL ------------------
        // Signup / account-verification uses its own dedicated Mailgun key.
        $subject = "Account Verification OTP";
        $send = $operation->send_mail_operation($email, $subject, $body, getenv('mailgun.signupApiKey'));

        // The account is already created (status 0). If the OTP email failed,
        // tell the truth and point the user at "Resend OTP" instead of claiming success.
        if (empty($send['status'])) {
            return $this->error_response(
                "Account created, but the OTP email could not be sent: " . ($send['message'] ?? 'Unknown mail error.') . " Please use 'Resend OTP'.",
                ["email" => $email, "otp_sent" => false]
            );
        }

        return $this->success_response("Registration successful! OTP sent to your email.", ["email" => $email]);
    }

    public function verify_account() {

        $operation = new Operation();
        $timestamp = date("Y-m-d H:i:s");

        // Read POST JSON
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        $email = trim($decodedData['email'] ?? '');
        $otp   = trim($decodedData['otp'] ?? '');


        // ------------------ REQUIRED VALIDATIONS ------------------
        if (empty($email) || empty($otp)) {
            return $this->error_response("Email and OTP are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error_response("Invalid email format.");
        }

        if (!preg_match('/^\d{6}$/', $otp)) {
            return $this->error_response("OTP must be a 6-digit number.");
        }


        // ------------------ CHECK USER EXISTS ------------------
        $user = $operation->get_data("users", "*", ["email" => $email]);

        if ($user['num_rows'] == 0) {
            return $this->error_response("No account found with this email.");
        }

        $userData = $user['result'][0];


        // ------------------ CHECK IF ALREADY VERIFIED ------------------
        if ($userData->status == 1) {
            return $this->error_response("Account is already verified.");
        }


        // ------------------ BRUTE-FORCE LOCK ------------------
        $blockCheck = $operation->is_verify_blocked($email, 'signup_verify');
        if ($blockCheck['blocked']) {
            return $this->error_response($blockCheck['message']);
        }


        // ------------------ VERIFY OTP ------------------
        if (empty($userData->verify_otp) || (string)$userData->verify_otp !== (string)$otp) {
            $fail = $operation->register_failed_verify($email, 'signup_verify', 5, 15);
            if ($fail['blocked']) {
                // Invalidate the current code so it can't be brute-forced further.
                $operation->update_data("users", ["id" => $userData->id], ["verify_otp" => null, "verifyotp_time" => null]);
                return $this->error_response($fail['message']);
            }
            return $this->error_response("Invalid OTP. " . $fail['remaining'] . " attempt(s) remaining.");
        }


        // ------------------ CHECK OTP EXPIRY ------------------
        $otp_expiry_minutes = 10; // OTP valid for 10 minutes

        $otp_created_time = strtotime($userData->verifyotp_time);
        $current_time = strtotime($timestamp);
        $time_difference = ($current_time - $otp_created_time) / 60; // Difference in minutes

        if ($time_difference > $otp_expiry_minutes) {
            return $this->error_response("OTP has expired. Please request a new one.");
        }

        // OTP correct → clear the failed-attempt counter.
        $operation->clear_verify_attempts($email, 'signup_verify');


        // ------------------ ACTIVATE ACCOUNT ------------------
        $update_result = $operation->update_data("users", ["id" => $userData->id], [
            "status"         => 1,
            "verify_otp"     => null,  // Clear OTP after successful verification
            "verifyotp_time" => null,
            "update_on"      => $timestamp
        ]);

        if (!$update_result) {
            return $this->error_response("Failed to verify account. Please try again.");
        }


        // ------------------ GENERATE JWT TOKEN ------------------
        $token_payload = ["id" => $userData->id];
        $token = generate_jwt($token_payload, jwt_secret());

        $responseData = [
            "token"      => $token,
            "name"       => $userData->full_name,
            "user_image" => $userData->user_image
        ];


        // ------------------ SUCCESS RESPONSE ------------------
        return $this->success_response("Account verified successfully! You can now login.", $responseData);
    }

    public function verify_resend_otp() {

        $operation = new Operation();
        $timestamp = date("Y-m-d H:i:s");

        // Read POST JSON
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        $email = trim($decodedData['email'] ?? '');


        // ------------------ REQUIRED VALIDATIONS ------------------
        if (empty($email)) {
            return $this->error_response("Email is required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error_response("Invalid email format.");
        }


        // ------------------ CHECK USER EXISTS ------------------
        $user = $operation->get_data("users", "*", ["email" => $email]);

        if ($user['num_rows'] == 0) {
            return $this->error_response("No account found with this email.");
        }

        $userData = $user['result'][0];


        // ------------------ CHECK IF ALREADY VERIFIED ------------------
        if ($userData->status == 1) {
            return $this->error_response("Account is already verified.");
        }


        // ------------------ RATE LIMITING (Optional) ------------------
        // Prevent OTP spam - minimum 1 minute between requests
        if (!empty($userData->verifyotp_time)) {
            $last_otp_time = strtotime($userData->verifyotp_time);
            $current_time = strtotime($timestamp);
            $time_difference = ($current_time - $last_otp_time) / 60;

            if ($time_difference < 1) {
                $wait_seconds = ceil((1 - $time_difference) * 60);
                return $this->error_response("Please wait {$wait_seconds} seconds before requesting a new OTP.");
            }
        }


        // ------------------ OTP RATE LIMIT (5 sends / 15 min, then 15-min block) ------------------
        $rate = $operation->check_otp_rate_limit($email, 'verify');
        if (!$rate['allowed']) {
            return $this->error_response($rate['message']);
        }

        // ------------------ GENERATE NEW OTP ------------------
        $otp = rand(100000, 999999);

        $operation->update_data("users", ["email" => $email], [
            "verify_otp"     => $otp,
            "verifyotp_time" => $timestamp
        ]);


        // ------------------ EMAIL TEMPLATE ------------------
        $templateData = $operation->get_data("email_template", "*", ["id" => 2]);
        $user_name = $userData->full_name;

        if ($templateData["num_rows"] > 0) {

            $templateRow = $templateData["result"][0];
            $body = $templateRow->template_body;

            // OTP digits
            $digits = str_split($otp);

            // Replace OTP placeholders
            $body = str_replace(
                ['{OTP_1}', '{OTP_2}', '{OTP_3}', '{OTP_4}', '{OTP_5}', '{OTP_6}'],
                [$digits[0], $digits[1], $digits[2], $digits[3], $digits[4], $digits[5]],
                $body
            );

            // Replace variables
            $body = str_replace(
                ['{USER_NAME}', '{USER_EMAIL}', '{EXPIRY_TIME}', '{REQUEST_TIMESTAMP}', '{IP_ADDRESS}', '{DEVICE_INFO}', '{CURRENT_YEAR}'],
                [$user_name, $email, 10, $timestamp, $_SERVER['REMOTE_ADDR'], ($_SERVER['HTTP_USER_AGENT'] ?? ''), date("Y")],
                $body
            );

        } else {

            // Fallback Template
            $body = "
                <p>Hello $user_name,</p>
                <p>Your new OTP is <b>$otp</b>. It is valid for 10 minutes.</p>
            ";
        }


        // ------------------ SEND EMAIL ------------------
        // Resend uses the same dedicated signup / account-verification key.
        $subject = "Account Verification OTP";
        $send = $operation->send_mail_operation($email, $subject, $body, getenv('mailgun.signupApiKey'));

        if (empty($send['status'])) {
            return $this->error_response("Failed to send OTP: " . ($send['message'] ?? 'Unknown mail error.'));
        }

        return $this->success_response("New OTP sent to your email.", [
            "email" => $email
        ]);
    }



    public function user_login_action() {
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        $email = $decodedData['email'] ?? '';
        $newPassword = $decodedData['password'] ?? '';
        $device_id = $decodedData['device_id'] ?? '';

        if (!$email || !$newPassword) {
            return $this->error_response("Phone Number, Password, and OTP are required.");
        }

        $operation = new Operation();
        $user = $operation->get_data("users", "*", ['email' => $email]);

        if ($user['num_rows'] === 0) {
            return $this->error_response("Invalid email!");
        }

        $userData = $user['result'][0];
        if (md5($newPassword) !== $userData->password) {
            return $this->error_response('Wrong Password!');
        }

        // Block login until the email OTP has been verified (status = 1)
        if ((int)$userData->status !== 1) {
            return $this->error_response("Please verify your email with the OTP before logging in.");
        }

        $token_payload = ["id" => $userData->id];
        $token = generate_jwt($token_payload, jwt_secret());

        $member_card_status = !empty($userData->membership_card_number) ? 1 : 2;

        $newArray = [
            "token" => $token,
            "name" => $userData->full_name,
            "user_image" => $userData->user_image
        ];
        $this->track_login($operation, $userData->id, $device_id);
        $this->check_and_notify_new_device($operation, $userData, $device_id);

        return $this->success_response("Login Successful!", $newArray);
    }

    private function track_login($operation, $user_id, $device_id) {
        $operation->delete_data("tracking_login", ["user_id" => $user_id]);
        $insertData = [
            "user_id" => $user_id,
            "device_id" => $device_id,
            "date" => date("Y-m-d"),
            "status" => 0
        ];
        $operation->insert_data("tracking_login", $insertData);
    }

    /**
     * Detect logins from a new / unrecognized device or IP and email the owner.
     * Recognition is keyed on device_id when supplied, otherwise on IP address.
     * Fire-and-forget: a mail failure must not block login.
     */
    private function check_and_notify_new_device($operation, $userData, $device_id) {
        $user_id = (int) $userData->id;
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $now     = date("Y-m-d H:i:s");
        $db      = $operation->db;

        // Is this device (or IP, when no device_id) already known for the user?
        if (!empty($device_id)) {
            $known = $db->query(
                "SELECT id FROM user_login_devices WHERE user_id = ? AND device_id = ?",
                [$user_id, $device_id]
            )->getRow();
        } else {
            $known = $db->query(
                "SELECT id FROM user_login_devices WHERE user_id = ? AND ip_address = ?",
                [$user_id, $ip]
            )->getRow();
        }

        if ($known) {
            // Recognized → just refresh last_seen / latest IP.
            $db->query(
                "UPDATE user_login_devices SET ip_address = ?, user_agent = ?, last_seen = ? WHERE id = ?",
                [$ip ?: null, $ua ?: null, $now, $known->id]
            );
            return;
        }

        // New device → record it and alert the account owner.
        $db->query(
            "INSERT INTO user_login_devices (user_id, device_id, ip_address, user_agent, first_seen, last_seen)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$user_id, $device_id ?: null, $ip ?: null, $ua ?: null, $now, $now]
        );

        if (!empty($userData->email)) {
            $name     = $userData->full_name ?? '';
            $greeting = $name !== '' ? "Hello {$name}," : "Hello,";
            $body = "
                <p>{$greeting}</p>
                <p>Your account was just accessed from a new device.</p>
                <ul>
                    <li><b>Time:</b> {$now}</li>
                    <li><b>IP Address:</b> " . ($ip !== '' ? htmlspecialchars($ip) : 'Unknown') . "</li>
                    <li><b>Device:</b> " . ($ua !== '' ? htmlspecialchars($ua) : 'Unknown') . "</li>
                </ul>
                <p>If this was you, no action is needed. If you did not authorize this login,
                   please change your password immediately and contact support.</p>
            ";
            $operation->send_mail_operation($userData->email, "New device login detected", $body);
        }
    }


    public function forgot_password_send_otp() {
        $operation = new Operation();
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        $email = trim($decodedData['email'] ?? '');

        if (!$email) {
            return $this->error_response("Email is required.");
        }

        $user = $operation->get_data("users", "*", ["email" => $email]);
        if ($user['num_rows'] == 0) {
            return $this->error_response("Email not found!");
        }

        $userRow = $user["result"][0];
        $user_name = $userRow->full_name;

        // OTP RATE LIMIT (5 sends / 15 min, then 15-min block)
        $rate = $operation->check_otp_rate_limit($email, 'forgot');
        if (!$rate['allowed']) {
            return $this->error_response($rate['message']);
        }

        // Generate OTP (4 digits)
        $otp = rand(100000, 999999);
        $otp_time = date("Y-m-d H:i:s");

        // Save OTP
        $operation->update_data("users", ["email" => $email], [
            "otp" => $otp,
            "otp_time" => $otp_time
        ]);

        // Fetch email template
        $templateData = $operation->get_data("email_template", "*", ["id" => 1]);
        $body = "";

        if ($templateData["num_rows"] > 0) {
            $templateRow = $templateData["result"][0];
            $body = $templateRow->template_body;

            // Split OTP into digits
            $otp_digits = str_split($otp);

            // Replace OTP placeholders
            $body = str_replace(
                ['{OTP_1}', '{OTP_2}', '{OTP_3}', '{OTP_4}', '{OTP_5}', '{OTP_6}'],
                [
                    $otp_digits[0],
                    $otp_digits[1],
                    $otp_digits[2],
                    $otp_digits[3],
                    $otp_digits[4],
                    $otp_digits[5]
                ],
                $body
            );


            // Replace other dynamic fields
            $body = str_replace(
                ['{USER_NAME}', '{EXPIRY_TIME}', '{REQUEST_TIMESTAMP}', '{IP_ADDRESS}', '{DEVICE_INFO}', '{CURRENT_YEAR}'],
                [$user_name, 10, date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'], ($_SERVER['HTTP_USER_AGENT'] ?? ''), date("Y")],
                $body
            );
        } else {
            $body = "<p>Hello $user_name,</p>
                    <p>Your OTP is <b>$otp</b>. It is valid for 10 minutes.</p>";
        }

        $subject = "Password Reset OTP";
        // Forgot-password uses its own dedicated Mailgun sending key.
        $send = $operation->send_mail_operation($email, $subject, $body, getenv('mailgun.forgotApiKey'));

        if ($send['status']) {
            return $this->success_response("OTP sent successfully.");
        } else {
            return $this->error_response("Failed to send OTP: " . ($send['message'] ?? 'Unknown mail error.'));
        }
    }

    public function forgot_password_verify_otp() {
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        $email = trim($decodedData['email'] ?? '');
        $otp = trim($decodedData['otp'] ?? '');

        if (!$email || !$otp) {
            return $this->error_response("Email and OTP are required.");
        }

        $operation = new Operation();

        // Brute-force lock: stop if too many recent incorrect attempts.
        $blockCheck = $operation->is_verify_blocked($email, 'forgot_verify');
        if ($blockCheck['blocked']) {
            return $this->error_response($blockCheck['message']);
        }

        $user = $operation->get_data("users", "*", ["email" => $email]);
        if ($user['num_rows'] == 0) {
            return $this->error_response("Invalid OTP!");
        }

        $userData = $user['result'][0];

        // Wrong code → record a failed attempt; lock + invalidate the code after the limit.
        if (empty($userData->otp) || (string)$userData->otp !== (string)$otp) {
            $fail = $operation->register_failed_verify($email, 'forgot_verify', 5, 15);
            if ($fail['blocked']) {
                // Invalidate the current reset code so it can't be brute-forced further.
                $operation->update_data("users", ["id" => $userData->id], ["otp" => null, "otp_time" => null]);
                return $this->error_response($fail['message']);
            }
            return $this->error_response("Invalid OTP! " . $fail['remaining'] . " attempt(s) remaining.");
        }

        // OTP Expiry Check (10 minutes)
        $otpTime = strtotime($userData->otp_time);
        if ((time() - $otpTime) > 600) {
            return $this->error_response("OTP expired. Please request again.");
        }

        // Correct code → clear the failed-attempt counter.
        $operation->clear_verify_attempts($email, 'forgot_verify');

        // Create JWT Token
        $token_payload = ["id" => $userData->id];
        $token = generate_jwt($token_payload, jwt_secret());

        // Success Response
        return $this->success_response("OTP verified successfully.", ["token" => $token]);
    }


    public function forgot_password_change() {
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        $token = trim($decodedData['token'] ?? '');
        $newPassword = trim($decodedData['password'] ?? '');
        $confirmPassword = trim($decodedData['confirm_password'] ?? '');

        if (!$token || !$newPassword || !$confirmPassword) {
            return $this->error_response("Token, Password and Confirm Password are required.");
        }

        if ($newPassword !== $confirmPassword) {
            return $this->error_response("Password & Confirm Password do not match.");
        }

        $operation = new Operation();

        // Verify and extract user_id from token
        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);   // invalid token
        }

        $user_id = $jwtData['user_id'];

        // Check user exists
        $user = $operation->get_data("users", "*", ["id" => $user_id]);
        if ($user['num_rows'] == 0) {
            return $this->error_response("User not found!");
        }

        // New password must differ from the current password
        if ($user['result'][0]->password === md5($newPassword)) {
            return $this->error_response("New password cannot be the same as your current password.");
        }

        // Update password
        $operation->update_data("users", ["id" => $user_id], [
            "password" => md5($newPassword),
            "show_pass" => $newPassword,
            "otp" => null,
            "otp_time" => null
        ]);

        // Notify the account owner that their password changed.
        $this->send_password_change_notice($operation, $user['result'][0]->email, $user['result'][0]->full_name ?? '');

        return $this->success_response("Password changed successfully.");
    }

    public function change_password_user() {
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        $token = trim($decodedData['token'] ?? '');
        $oldPassword = trim($decodedData['old_password'] ?? '');
        $newPassword = trim($decodedData['new_password'] ?? '');
        $confirmPassword = trim($decodedData['confirm_password'] ?? '');

        if (!$token || !$oldPassword || !$newPassword || !$confirmPassword) {
            return $this->error_response("Token, Old Password, New Password & Confirm Password are required.");
        }

        if ($newPassword !== $confirmPassword) {
            return $this->error_response("New Password & Confirm Password do not match.");
        }

        $operation = new Operation();

        // Verify and extract user_id from token
        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);   // Invalid token
        }

        $user_id = $jwtData['user_id'];

        // Check user exists
        $user = $operation->get_data("users", "*", ["id" => $user_id]);
        if ($user['num_rows'] == 0) {
            return $this->error_response("User not found!");
        }

        $dbRow = $user['result'][0];

        // Verify old password
        if ($dbRow->password !== md5($oldPassword)) {
            return $this->error_response("Old Password is incorrect!");
        }

        // New password must differ from the current password
        if ($dbRow->password === md5($newPassword)) {
            return $this->error_response("New password cannot be the same as your current password.");
        }

        // Update new password
        $operation->update_data("users", ["id" => $user_id], [
            "password" => md5($newPassword),
            "show_pass" => $newPassword
        ]);

        // Notify the account owner that their password changed.
        $this->send_password_change_notice($operation, $dbRow->email, $dbRow->full_name ?? '');

        return $this->success_response("Password changed successfully.");
    }

    /**
     * Send a "your password was changed" security notification email.
     * Fire-and-forget: a mail failure must not fail the password change itself.
     */
    private function send_password_change_notice($operation, $email, $user_name = '') {
        if (empty($email)) return;

        $timestamp = date("Y-m-d H:i:s");
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '';
        $greeting  = $user_name !== '' ? "Hello {$user_name}," : "Hello,";

        $body = "
            <p>{$greeting}</p>
            <p>The password for your account was recently changed on <b>{$timestamp}</b>" . ($ip !== '' ? " (IP: {$ip})" : "") . ".</p>
            <p>If you did not authorize this, please contact support immediately.</p>
        ";

        $operation->send_mail_operation($email, "Your password was changed", $body);
    }






}
