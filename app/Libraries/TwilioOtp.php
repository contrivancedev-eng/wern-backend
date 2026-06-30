<?php

namespace App\Libraries;

// Manually include the Twilio autoloader
require_once APPPATH . 'Libraries/twilio-php/src/Twilio/autoload.php';

use Twilio\Rest\Client;

class TwilioOtp
{
    protected $sid;
    protected $token;
    protected $twilio;

    public function __construct()
    {
        $this->sid = getenv('TWILIO_SID');
        $this->token = getenv('TWILIO_TOKEN');
        $this->twilio = new Client($this->sid, $this->token);
    }

    public function sendOtp($phone, $otp)
    {
        return $this->twilio->messages->create(
            $phone,
            [
                'from' => getenv('TWILIO_PHONE'),
                'body' => "First Track Liberte OTP code is: $otp"
            ]
        );
    }
}
