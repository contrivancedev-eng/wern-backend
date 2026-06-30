<?php

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class AdminController extends Controller
{
    protected $request;
    protected $helpers = ['cookie', 'date', 'url'];
    protected $session;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->session = \Config\Services::session();

        if (!$this->session->has('authID')) {
            $this->session->start();
        }

        log_message('debug', 'Session Data: ' . json_encode($this->session->get()));

        $this->setCorsHeaders();
    }

    private function setCorsHeaders()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }
    }

    public function load_admin_view($pages = [], $data = [], $scriptPages = [], $type = "afterLogin")
    {
        $session = session();

        if (empty($pages)) {
            log_message('error', 'No pages provided to load_admin_view.');
            return false;
        }

        $headerData = ['pageTitle' => $data['pageTitle'] ?? ''];
        $footerData = ['scriptPages' => $scriptPages];
        $pageData = $data;
        unset($pageData['pageTitle']);

        if ($type === "beforeLogin") {
            echo view('Admin/includes/login_header', $headerData);
            foreach ($pages as $page) {
                echo view($page, $pageData);
            }
            echo view('Admin/includes/login_footer', $footerData);
        } elseif ($type === "afterLogin") {
            if (!$session->get('authID')) {
                log_message('warning', 'Session data missing. Redirecting to login.');
                return redirect()->to('/admin/login');
            }

            $authID = $session->get('authID');
            $MemberName = $session->get('authName') ?? 'Admin';
            $MemberImage = $session->get('authImage') ?? 'default.png';

            log_message('debug', "Session Data: authID={$authID}, name={$MemberName}");

            echo view('Admin/includes/header', $headerData);
            echo view('Admin/includes/topbar', [
                'MemberName' => $MemberName,
                'MemberImage' => $MemberImage
            ]);
            echo view('Admin/includes/sidebar', [
                'MemberName' => $MemberName,
                'MemberImage' => $MemberImage
            ]);
            foreach ($pages as $page) {
                if (!$this->view_exists($page)) {
                    log_message('error', "View not found: {$page}");
                } else {
                    echo view($page, $pageData);
                }
            }
            echo view('Admin/includes/footer', $footerData);
        }

        return true;
    }

    protected function view_exists(string $viewPath): bool
    {
        $viewPath = str_replace('.', DIRECTORY_SEPARATOR, $viewPath);
        $viewFile = APPPATH . 'Views/' . $viewPath . '.php';
        return file_exists($viewFile);
    }

    public function input_get($variableName = null)
    {
        $request = \Config\Services::request();
        return $request->getGet($variableName);
    }

    public function input_post($variableName = null)
    {
        $request = \Config\Services::request();
        return $request->getPost($variableName);
    }
}
