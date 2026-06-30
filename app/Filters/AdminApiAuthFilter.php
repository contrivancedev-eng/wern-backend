<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Guards the admin data API endpoints. The admin panel calls these via
 * same-origin fetch, so the HttpOnly session cookie is sent automatically.
 * Unlike LoginFilter (which redirects HTML pages), this returns a JSON 401
 * so the front-end fetch handlers can react instead of parsing a redirect.
 */
class AdminApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('authID')) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status_code' => 401,
                    'status'      => false,
                    'message'     => 'Unauthorized. Please log in again.',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed.
    }
}
