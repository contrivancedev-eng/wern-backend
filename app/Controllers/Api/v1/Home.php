<?php
    namespace App\Controllers\Api\v1;
    use App\Controllers\Api\ApiController;
    use App\Models\Operation;

    class Home extends ApiController{

        public function login_action() {
            $operation = new Operation();
    
            $rawData = file_get_contents("php://input");
    
            $decodedData = json_decode($rawData, true);

            
    
            if (!$decodedData || !isset($decodedData['user_email']) || !isset($decodedData['user_password'])) {
                return $this->error_response("Email and Password Required!");
            }
    
            $user_email = $decodedData['user_email'];
            $user_password = $decodedData['user_password'];
    
            if (empty($user_email) || empty($user_password)) {
                return $this->error_response("Email and Password cannot be empty!");
            }
    
            $searchCondition = [
                "email" => $user_email,
                "password" => md5($user_password)
            ];


            // print_r($searchCondition);
            // die();

            $total_data = $operation->get_data("user_login", "*", $searchCondition, "id", "DESC");
    
            if ($total_data['num_rows'] > 0) {
                $user = $total_data['result'][0];
    
                $data_arr = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_picture' => USER_PATH . $user->profile_picture,
                    'status' => $user->status
                ];
    
                $encrypted_data = $operation->encrypt_decrypt('encrypt', json_encode($data_arr));
    
                $response_data = [
                    'token' => $encrypted_data,
                    'name' => $user->name,
                    'profile_picture' => USER_PATH . $user->profile_picture
                ];
    
                return $this->success_response("Login successful.", $response_data);
            } else {
                return $this->error_response("User not found!");
            }
        }

        public function user_registration() {
            $operation = new Operation();
        
            $rawData = file_get_contents("php://input");
        
            $decodedData = json_decode($rawData, true);
        
            if (!$decodedData || !isset($decodedData['name']) || !isset($decodedData['email']) || !isset($decodedData['password'])) {
                return $this->error_response("Name, Email, and Password are required!");
            }
        
            $name = $decodedData['name'];
            $email = $decodedData['email'];
            $password = $decodedData['password'];
        
            if (empty($name) || empty($email) || empty($password)) {
                return $this->error_response("Name, Email, and Password cannot be empty!");
            }
        
            $searchCondition = [
                "email" => $email
            ];
        
            $existing_user = $operation->get_data("user_login", "*", $searchCondition, "id", "DESC");
        
            if ($existing_user['num_rows'] > 0) {
                return $this->error_response("User already exists with the given email!");
            }
        
            $hashed_password = md5($password);
        
            $user_data = [
                "name" => $name,
                "email" => $email,
                "password" => $hashed_password,
                "status" => 1 
            ];
        
            $insert_status = $operation->insert_data("user_login", $user_data);
        
            if ($insert_status) {
                return $this->success_response("Registration successful!", []);
            } else {
                return $this->error_response("Failed to register the user. Please try again!");
            }
        }

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
        
        


    }

?>