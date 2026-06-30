<?php
    namespace App\Controllers\Api\v1;
    use App\Controllers\Api\ApiController;


    class Test extends ApiController{

        public function test(){
            return $this->success_response("Hello from test");
        }


    }

?>