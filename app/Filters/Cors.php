<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Cors implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = service('response');
            $this->applyCors($request, $response);
            $response->setHeader('Access-Control-Max-Age', '86400');
            $response->setStatusCode(200);
            return $response;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $this->applyCors($request, $response);
    }

    /**
     * Reflect the requesting Origin and allow credentials so cookie-based
     * (admin session) requests can be read by the browser. A wildcard `*`
     * is forbidden once credentials are involved, which silently breaks
     * authenticated fetches. Non-browser callers (the mobile app) send no
     * Origin header, so they fall back to `*` with no behaviour change.
     */
    private function applyCors(RequestInterface $request, ResponseInterface $response): void
    {
        $origin = $request->getHeaderLine('Origin');
        if ($origin !== '') {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
            $response->setHeader('Vary', 'Origin');
        } else {
            $response->setHeader('Access-Control-Allow-Origin', '*');
        }
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }
}
