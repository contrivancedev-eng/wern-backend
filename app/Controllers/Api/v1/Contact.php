<?php
    namespace App\Controllers\Api\v1;
    use App\Controllers\Api\ApiController;
    use App\Models\Operation;
    require_once APPPATH . 'Libraries/PHPMailer/src/PHPMailer.php';
    require_once APPPATH . 'Libraries/PHPMailer/src/SMTP.php';
    require_once APPPATH . 'Libraries/PHPMailer/src/Exception.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    class Contact extends ApiController {

        public function contact_us_form() {
            $operation = new Operation();
            $data = [
                "name" => $this->input_post("name"),
                "email" => $this->input_post("email"),
                "subject" => $this->input_post("subject"),
                "message" => $this->input_post("message"),
                "user_ip" => $this->input_post("user_ip"),
                "added_on" => date("Y-m-d")
            ];
            $captchaResponse = $this->input_post("g-recaptcha-response");

            if (empty($captchaResponse)) {
                return $this->error_response("Please check the CAPTCHA.");
            }

            $secretKey = "6LfWEj0rAAAAAE_bu1PD4-CyVqio7LAaBoSJ8kkj";
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captchaResponse");
            if (!json_decode($response)->success) {
                return $this->error_response("CAPTCHA verification failed. Please try again.");
            }

            $contactDataExist = $operation->get_data("contact_us", "*", $data);
            if ($contactDataExist['num_rows'] > 0) {
                return $this->error_response("Thank you for reaching out again. Your previous inquiry has already been submitted, and our team will contact you shortly.");
            }

            if ($operation->insert_data("contact_us", $data)) {

                return $this->success_response("Thank you for reaching out. Your inquiry has been submitted successfully. Our team will get in touch shortly.");
            } else {
                return $this->error_response("Failed to insert data.");
            }
        }

        
    }
?>
