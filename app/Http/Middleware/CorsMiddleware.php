<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    protected $allowedOrigins = [
        'https://www.preleasecanada.ca',
        'https://preleasecanada.ca',
        'https://dev.preleasecanada.ca',
        'http://localhost:3000',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
        } else {
            try {
                $response = $next($request);
            } catch (\Throwable $e) {
                $response = response()->json([
                    'status' => 500,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }

        $origin = $request->headers->get('Origin');
        if ($origin && in_array($origin, $this->allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }

        return $response;
    }
}
