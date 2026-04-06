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

    protected $allowedPatterns = [
        '#^https://.*\.vercel\.app$#',
        '#^https://.*\.preleasecanada\.ca$#',
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
                \Illuminate\Support\Facades\Log::error('CorsMiddleware caught error', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $response = response()->json([
                    'status' => 500,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }

        $origin = $request->headers->get('Origin');
        if ($origin && $this->isAllowedOrigin($origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }

        return $response;
    }

    protected function isAllowedOrigin(string $origin): bool
    {
        if (in_array($origin, $this->allowedOrigins)) {
            return true;
        }
        foreach ($this->allowedPatterns as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }
        return false;
    }
}
