<?php

namespace App\Controllers\Web;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\Operation;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class WebController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
    }

    public function load_web_view($pages=array(),$data=array()){
        if(empty($pages)){
			return false;
		}else{
            $headerData = array();
			$pageData = $data;
			if(!empty($data["pageTitle"])){
				$headerData["pageTitle"] = $data["pageTitle"];
				unset($pageData["pageTitle"]);
			}else{
				$headerData["pageTitle"] = "";
			}
			echo view('Web/includes/header',$headerData);
            // echo view('Web/includes/sidebar',array());
            foreach($pages as $pagesRow){
                echo view('Web/'.$pagesRow,$pageData);
            }
            echo view('Web/includes/footer');
        }
    }

    public function load_dashboard_view($pages=array(),$data=array(), $token){

        

        if(empty($pages)){
			return false;
		}else{
            $headerData = array();
            if(!empty($token)){
                $operation = new Operation();
                $userapiUrl = 'get-user-details';
                $userapiData = array('token'=>$token);
                $userresult = $operation->get_api($userapiUrl, $userapiData);
                $headerData['user_data'] = !empty($userresult->data) ? $userresult->data : array();
    
                $apiUrl = 'member-wise-point-transaction';
                $apiData = array('token'=>$token);
                $result = $operation->get_api($apiUrl, $apiData);
                $headerData['transaction_list'] = !empty($result->data) ? $result->data : array();
            }


            
			$pageData = $data;
			if(!empty($data["pageTitle"])){
				$headerData["pageTitle"] = $data["pageTitle"];
				unset($pageData["pageTitle"]);
			}else{
				$headerData["pageTitle"] = "";
			}
			echo view('Web/includes/header1',$headerData);
            echo view('Web/includes/topbar1',array());
            foreach($pages as $pagesRow){
                echo view('Web/'.$pagesRow,$pageData);
            }
            echo view('Web/includes/footer1');
        }
    }


    public function load_custom_view($pages=array(),$data=array()){
        if(empty($pages)){
			return false;
		}else{
            $headerData = array();
			$pageData = $data;
			if(!empty($data["pageTitle"])){
				$headerData["pageTitle"] = $data["pageTitle"];
				unset($pageData["pageTitle"]);
			}else{
				$headerData["pageTitle"] = "";
			}
			echo view('Web/includes/custom_header',$headerData);
            echo view('Web/includes/custom_topbar',array());
            foreach($pages as $pagesRow){
                echo view('Web/'.$pagesRow,$pageData);
            }
            echo view('Web/includes/custom_footer');
        }
    }

    public function input_get($variableName=null){
		$request = \Config\Services::request();
		$getData = trim($request->getGet(($variableName)));
		return $getData;
	}

	public function input_post($variableName=null){
		$request = \Config\Services::request();
		$getData = trim($request->getPost(($variableName)));
		return $getData;
	}


	public function input_file($variableName=null){
		$request = \Config\Services::request();
		$getData = $request->getFile($variableName);
		return $getData;
	}
}
