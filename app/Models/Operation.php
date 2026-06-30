<?php
    namespace App\Models;
	use CodeIgniter\Model;

	require_once APPPATH . 'Libraries/PHPMailer/src/PHPMailer.php';
	require_once APPPATH . 'Libraries/PHPMailer/src/SMTP.php';
	require_once APPPATH . 'Libraries/PHPMailer/src/Exception.php';

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

    class Operation extends Model{

		public function __construct(){
			parent::__construct();
			$this->db = \Config\Database::connect();
		}
		protected $upperLimit = 10;


		/**
			* Send an email through the Mailgun HTTP API (https://app.mailgun.com/).
			* Credentials are read from .env so secrets stay out of source control.
			* Return shape is kept identical to the old PHPMailer version:
			*   ["status" => bool, "message" => string]
			* @param string $to_email
			* @param string $subject
			* @param string $body  HTML body
			* @return array
		*/
		public function send_mail_operation($to_email, $subject, $body, $apiKeyOverride = null){

			// --- Mailgun config (set these in .env) ---
			// $apiKeyOverride lets a specific flow (e.g. forgot-password) use its own key.
			$apiKey   = !empty($apiKeyOverride) ? $apiKeyOverride : getenv('mailgun.apiKey');
			$domain   = getenv('mailgun.domain');                 // e.g. mg.wernapp.com
			$endpoint = getenv('mailgun.endpoint') ?: 'https://api.mailgun.net'; // US region
			$from     = getenv('mailgun.from');                   // e.g. WERN App <noreply@mg.wernapp.com>

			if (empty($apiKey) || empty($domain)) {
				return ["status" => false, "message" => "Mailgun is not configured. Set mailgun.apiKey and mailgun.domain in .env"];
			}

			if (empty($from)) {
				$from = "LBT First Track APP <postmaster@{$domain}>";
			}

			$url = rtrim($endpoint, '/') . '/v3/' . $domain . '/messages';

			$postFields = [
				'from'    => $from,
				'to'      => $to_email,
				'subject' => $subject,
				'html'    => $body,
				'text'    => trim(strip_tags($body)),
			];

			$ch = curl_init($url);
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_USERPWD        => 'api:' . $apiKey,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => $postFields,
				CURLOPT_TIMEOUT        => 30,
			]);

			$response  = curl_exec($ch);
			$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curlError = curl_error($ch);
			curl_close($ch);

			if ($curlError !== '') {
				return ["status" => false, "message" => "Mailgun cURL error: " . $curlError];
			}

			if ($httpCode >= 200 && $httpCode < 300) {
				return ["status" => true, "message" => "Email sent successfully", "response" => $response];
			}

			return ["status" => false, "message" => "Mailgun error (HTTP {$httpCode}): " . $response];
		}

		/**
			* Insert data in table
			* @param string $table 
			* @param array $data
			* @return boolean
		*/
		public function insert_data($table,$data){
			$builder = $this->db->table($table);
			$insert = $builder->insert($data);
			if($insert){
				return $this->db->insertID();
			}else{
				return 0;
			}
		}


		/**
			* Delete data from table
			* @param string $table 
			* @param array $condition
			* @return array
		*/
		public function delete_data($table,$condition){
			$builder = $this->db->table($table);
			$builder->delete($condition);
			return $this->db->affectedRows();
		}


		/**
			* Select data from table
			* @param string $table 
			* @param string $fields
			* @param array $condition
			* @param string $orderByField
			* @param string $orderBy
			* @param string $limit
			* @param string $start
			* @param array $likeCondition
			* @param array $whereInCondition
			* @param string $rawCondition
			* @return array
		*/
		public function get_data($table, $fields = "*", $condition = array(), $orderByField = "id",$orderBy = "DESC", $limit = "",$start = "0", $likeCondition = array(), $whereInCondition = array(), $rawCondition = ""){
			$builder = $this->db->table($table);
			// $builder->distinct();

			if($rawCondition != ''){
				$builder->where($rawCondition);
				$query = $builder->get();
            }else{
				$builder->select($fields);
				if(!empty($condition)){
					$builder->where($condition);
				}
				$builder->orderBy($orderByField,$orderBy);
				if($limit != ""){
					$builder->limit($limit, $start);
				}
				if(!empty($likeCondition)){
					$likeCount = 0;
					foreach($likeCondition as $index=>$like){
						if($likeCount==0){
							$builder->like($index,$like); 
						}else{
							$builder->orLike($index,$like); 
						}
						$likeCount ++;
					}
				}
				if(!empty($whereInCondition)){
					$whereInCount = 0;
					foreach($whereInCondition as $index=>$like){
						$builder->whereIn($index,$like);
					}
				}
				$query = $builder->get();
			}
			$result = array();
			if($query !== FALSE){
				$result['num_rows'] = count($query->getResult());
				$result['result'] = $query->getResult();
			}else{
				$result['num_rows'] = 0;
				$result['result'] = array();
			}
			
			return $result;
		}


		public function get_user_id_from_token($token){
			$jwtSecret = jwt_secret();

			try {
				$decoded = decode_jwt($token, $jwtSecret);
				$user_id = $decoded->id ?? null;

				if (!$user_id) {
					return [
						"status" => false,
						"error"  => "Invalid token: User ID missing."
					];
				}

				return [
					"status"  => true,
					"user_id" => $user_id
				];
			} catch (Exception $e) {
				return [
					"status" => false,
					"error"  => "Invalid token: " . $e->getMessage()
				];
			}
		}



		/**
			* Update data in table
			* @param string $table
			* @param array $condition
			* @param string $data
			* @return string
		*/
		public function update_data($table, $condition, $data = array()){
			$builder = $this->db->table($table);
			$builder->set($data);
			$builder->where($condition);
			$builder->update();
			return $this->db->affectedRows();
		}


		/**
			* Get pagination string
			* @param string $total
			* @param string $per_page
			* @param string $page
			* @param string $url
			* @param string $section_old
			* @return string
		*/
		public function my_pagination($total=0,$per_page=10,$page=1,$url='?',$section_old=''){
			$section='"'.$section_old.'"';
			
			$total = $total;
			$adjacents = "2"; 
			
			// $prevlabel = "&lsaquo; Prev";
			$prevlabel = "&lsaquo;";
			//$nextlabel = "Next &rsaquo;";
			$nextlabel = " &rsaquo;";
			// $lastlabel = "Last &rsaquo;&rsaquo;";
			$lastlabel = " &rsaquo;&rsaquo;";
			
			$page = ($page == 0 ? 1 : $page);  
			$start = ($page - 1) * $per_page;
			
			$prev = $page - 1;                          
			$next = $page + 1;
			
			$lastpage = ceil($total/$per_page);
		
			if($lastpage < 2){
				return '';
			}
			$lpm1 = $lastpage - 1; // //last page minus 1
			
			$pagination = "";
			if($lastpage > 1){   
				$pagination .= "<ul class='pagination'>";
				//$pagination .= "<li class='page_info'><span>Page {$page} of {$lastpage}</span></li>";
					
					if ($page > 1) $pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi' onclick='fetchDataPaginationWise($prev);' page='{$prev}'>{$prevlabel}</a></li>";
					
				if ($lastpage < 7 + ($adjacents * 2)){   
					for ($counter = 1; $counter <= $lastpage; $counter++){
						if ($counter == $page)
							$pagination.= "<li class='paginate_button page-item active'><a class='current'>{$counter}</a></li>";
						else
							$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi' onclick='fetchDataPaginationWise($counter);' page='{$counter}'>{$counter}</a></li>";                    
					}
				
				} elseif($lastpage > 5 + ($adjacents * 2)){
					
					if($page < 1 + ($adjacents * 2)) {
						
						for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++){
							if ($counter == $page)
								$pagination.= "<li class='paginate_button page-item active'><a class='current'>{$counter}</a></li>";
							else
								$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi' onclick='fetchDataPaginationWise($counter);' page='{$counter}'>{$counter}</a></li>";                    
						}
						$pagination.= "<li class='paginate_button page-item dot'>...</li>";
						$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi' onclick='fetchDataPaginationWise($lpm1);' page='{$lpm1}'>{$lpm1}</a></li>";
						$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi' onclick='fetchDataPaginationWise($lastpage);' page='{$lastpage}'>{$lastpage}</a></li>";  
							
					} elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
						
						$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi'  onclick='fetchDataPaginationWise(1);' page='1'>1</a></li>";
						$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi'  onclick='fetchDataPaginationWise(2);' page='2'>2</a></li>";
						$pagination.= "<li class='paginate_button page-item dot'>...</li>";
						for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
							if ($counter == $page)
								$pagination.= "<li class='paginate_button page-item active'><a class='current'>{$counter}</a></li>";
							else
								$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi'  onclick='fetchDataPaginationWise($counter);' page='{$counter}'>{$counter}</a></li>";                    
						}
						$pagination.= "<li class='paginate_button page-item dot'>..</li>";
						$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi'  onclick='fetchDataPaginationWise($lpm1);' page='{$lpm1}'>{$lpm1}</a></li>";
						$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi'  onclick='fetchDataPaginationWise($lastpage);' page='{$lastpage}'>{$lastpage}</a></li>";      
						
					} else {
						
						$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi'  onclick='fetchDataPaginationWise(1);' page='1'>1</a></li>";
						$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi'  onclick='fetchDataPaginationWise(2);' page='2'>2</a></li>";
						$pagination.= "<li class='paginate_button page-item dot'>..</li>";
						for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
							if ($counter == $page)
								$pagination.= "<li class='paginate_button page-item active'><a class='current'>{$counter}</a></li>";
							else
								$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi'  onclick='fetchDataPaginationWise($counter);' page='{$counter}'>{$counter}</a></li>";                    
						}
					}
				}
				
				if ($page < $counter - 1) {
					$pagination.= "<li class='paginate_button page-item'><a href='javascript:void(0);' id='GoSearchPagi'  onclick='fetchDataPaginationWise($next);' page='{$next}'>{$nextlabel}</a></li>";
				}
				$pagination.= "</ul>";        
			}
			return $pagination;
		}	


		/**
			* Fire GET API
			* @param string $apiEndpoint
			* @param array $apiData
			* @param boolean $test
			* @return JSON
		*/
		public function get_api($apiEndpoint,$apiData,$test=''){
			$apiUrl = API_BASE_PATH.$apiEndpoint;
			$curlHeaderData = array(
				'Content-Type: application/json'
			);
			$query = http_build_query($apiData); 
			$ch = curl_init($apiUrl.'?'.$query);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			$curl_response = curl_exec($ch);
			curl_close($ch);
			if($test == 'test'){
				return $curl_response;
			}
          	return json_decode($curl_response);
		}


		/**
			* Fire POST API
			* @param string $apiEndpoint
			* @param array $apiData
			* @param boolean $test
			* @return JSON
		*/
		public function post_api($apiEndpoint,$apiData,$test=''){
			$apiUrl = API_BASE_PATH.$apiEndpoint;
			$curlHeaderData = array(
				'Content-Type: application/json'
			);
			$curl = curl_init($apiUrl);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $apiData);
			// curl_setopt($curl, CURLOPT_HTTPHEADER, $curlHeaderData);
			// curl_setopt($curl, CURLOPT_SSLVERSION,3);
			$curl_response = curl_exec($curl);
			if($test == 'test'){
				echo "<pre> curl response-";print_r($curl_response);
				die();
			}
			curl_close($curl);
			return json_decode($curl_response);
		}


		/**
			* Get human readable time
			* @param string $time
			* @return string
		*/
		public function humanTiming ($time){
			$maintime = $time;
			$time = time() - $time; // to get the time since that moment
			$time = ($time<1)? 1 : $time;
			$tokens = array (
				31536000 => 'year ago ('.date("Y-m-d", $maintime).')',
				2592000 => 'month ago ('.date("Y-m-d", $maintime).')',
				604800 => 'week ago ('.date("Y-m-d", $maintime).')',
				86400 => 'day ago ('.date("Y-m-d", $maintime).')',
				3600 => 'hour ago ('.date("Y-m-d", $maintime).')',
				60 => 'minute ago',
				1 => 'second ago'
			);

			foreach ($tokens as $unit => $text) {
				if ($time < $unit) continue;
				$numberOfUnits = floor($time / $unit);
				return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'':'');
			}

		}

		public function encrypt_decrypt($action, $string, $secret_key = "contrivanceSecretKey")
		{
			$output = false;
			$encrypt_method = "AES-256-CBC";
			$secret_iv = 'randomString#12231';
			$key = hash('sha256', $secret_key);

			$iv = substr(hash('sha256', $secret_iv), 0, 16);
			if ($action == 'encrypt') {
				$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
				$output = base64_encode($output);
			} else if ($action == 'decrypt') {
				$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
			}
			return $output;
		}


		/**
			* OTP send rate limiter.
			* Allows up to $maxAttempts sends per $windowMinutes; once exceeded,
			* blocks further sends for $blockMinutes.
			* @param string $identifier  usually the email address
			* @param string $purpose     bucket name, e.g. 'verify' or 'forgot'
			* @return array ['allowed' => bool, 'message' => string]
		*/
		public function check_otp_rate_limit($identifier, $purpose = 'otp', $maxAttempts = 5, $windowMinutes = 15, $blockMinutes = 15){
			$db     = $this->db;
			$now    = time();
			$nowStr = date('Y-m-d H:i:s', $now);
			$ip     = $_SERVER['REMOTE_ADDR'] ?? null;

			$row = $db->query(
				"SELECT * FROM otp_rate_limit WHERE identifier = ? AND purpose = ?",
				[$identifier, $purpose]
			)->getRow();

			// Currently inside a block window?
			if ($row && !empty($row->blocked_until) && strtotime($row->blocked_until) > $now) {
				$wait = (int) ceil((strtotime($row->blocked_until) - $now) / 60);
				return ['allowed' => false, 'message' => "Too many OTP requests. Please try again after {$wait} minute(s)."];
			}

			// First ever request for this identifier/purpose.
			if (!$row) {
				$db->query(
					"INSERT INTO otp_rate_limit (identifier, purpose, attempt_count, window_start, blocked_until, ip_address, updated_at)
					 VALUES (?, ?, 1, ?, NULL, ?, ?)",
					[$identifier, $purpose, $nowStr, $ip, $nowStr]
				);
				return ['allowed' => true, 'message' => ''];
			}

			$windowExpired = ($now - strtotime($row->window_start)) > ($windowMinutes * 60);
			$blockLifted   = !empty($row->blocked_until) && strtotime($row->blocked_until) <= $now;

			// Window elapsed, or a previous block has just expired → start a fresh window.
			if ($windowExpired || $blockLifted) {
				$db->query(
					"UPDATE otp_rate_limit SET attempt_count = 1, window_start = ?, blocked_until = NULL, ip_address = ?, updated_at = ? WHERE id = ?",
					[$nowStr, $ip, $nowStr, $row->id]
				);
				return ['allowed' => true, 'message' => ''];
			}

			$newCount = (int) $row->attempt_count + 1;

			// Exceeded the allowance → start a block.
			if ($newCount > $maxAttempts) {
				$blockUntil = date('Y-m-d H:i:s', $now + ($blockMinutes * 60));
				$db->query(
					"UPDATE otp_rate_limit SET attempt_count = ?, blocked_until = ?, ip_address = ?, updated_at = ? WHERE id = ?",
					[$newCount, $blockUntil, $ip, $nowStr, $row->id]
				);
				return ['allowed' => false, 'message' => "Too many OTP requests. Please try again after {$blockMinutes} minutes."];
			}

			$db->query(
				"UPDATE otp_rate_limit SET attempt_count = ?, ip_address = ?, updated_at = ? WHERE id = ?",
				[$newCount, $ip, $nowStr, $row->id]
			);
			return ['allowed' => true, 'message' => ''];
		}


		/**
			* Is OTP verification currently locked for this identifier/purpose?
			* Call BEFORE checking a submitted code.
			* @return array ['blocked' => bool, 'message' => string]
		*/
		public function is_verify_blocked($identifier, $purpose){
			$row = $this->db->query(
				"SELECT blocked_until FROM otp_rate_limit WHERE identifier = ? AND purpose = ?",
				[$identifier, $purpose]
			)->getRow();

			if ($row && !empty($row->blocked_until) && strtotime($row->blocked_until) > time()) {
				$wait = (int) ceil((strtotime($row->blocked_until) - time()) / 60);
				return ['blocked' => true, 'message' => "Too many incorrect attempts. Please wait {$wait} minute(s) and request a new code."];
			}
			return ['blocked' => false, 'message' => ''];
		}

		/**
			* Record one FAILED verification attempt. After $maxAttempts failures the
			* identifier is locked for $blockMinutes.
			* @return array ['blocked' => bool, 'remaining' => int, 'message' => string]
		*/
		public function register_failed_verify($identifier, $purpose, $maxAttempts = 5, $blockMinutes = 15){
			$db     = $this->db;
			$now    = time();
			$nowStr = date('Y-m-d H:i:s', $now);
			$ip     = $_SERVER['REMOTE_ADDR'] ?? null;

			$row = $db->query(
				"SELECT * FROM otp_rate_limit WHERE identifier = ? AND purpose = ?",
				[$identifier, $purpose]
			)->getRow();

			// First failure, or a previous block has expired → start a fresh count.
			if (!$row) {
				$db->query(
					"INSERT INTO otp_rate_limit (identifier, purpose, attempt_count, window_start, blocked_until, ip_address, updated_at)
					 VALUES (?, ?, 1, ?, NULL, ?, ?)",
					[$identifier, $purpose, $nowStr, $ip, $nowStr]
				);
				return ['blocked' => false, 'remaining' => max(0, $maxAttempts - 1), 'message' => ''];
			}

			if (!empty($row->blocked_until) && strtotime($row->blocked_until) <= $now) {
				$db->query(
					"UPDATE otp_rate_limit SET attempt_count = 1, window_start = ?, blocked_until = NULL, ip_address = ?, updated_at = ? WHERE id = ?",
					[$nowStr, $ip, $nowStr, $row->id]
				);
				return ['blocked' => false, 'remaining' => max(0, $maxAttempts - 1), 'message' => ''];
			}

			$newCount = (int) $row->attempt_count + 1;

			if ($newCount >= $maxAttempts) {
				$blockUntil = date('Y-m-d H:i:s', $now + ($blockMinutes * 60));
				$db->query(
					"UPDATE otp_rate_limit SET attempt_count = ?, blocked_until = ?, ip_address = ?, updated_at = ? WHERE id = ?",
					[$newCount, $blockUntil, $ip, $nowStr, $row->id]
				);
				return ['blocked' => true, 'remaining' => 0, 'message' => "Too many incorrect attempts. Please wait {$blockMinutes} minutes and request a new code."];
			}

			$db->query(
				"UPDATE otp_rate_limit SET attempt_count = ?, ip_address = ?, updated_at = ? WHERE id = ?",
				[$newCount, $ip, $nowStr, $row->id]
			);
			return ['blocked' => false, 'remaining' => max(0, $maxAttempts - $newCount), 'message' => ''];
		}

		/**
			* Clear the failed-attempt counter after a SUCCESSFUL verification.
		*/
		public function clear_verify_attempts($identifier, $purpose){
			$this->db->query(
				"DELETE FROM otp_rate_limit WHERE identifier = ? AND purpose = ?",
				[$identifier, $purpose]
			);
		}

		
		
	}
?>