<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (OAuthServerException $e, $request) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated.',
            ], 401);
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                return response()->json([
                    'status' => $status,
                    'message' => $e->getMessage() ?: 'Internal Server Error',
                    'error_class' => class_basename($e),
                ], $status);
            }
        });
    }
}
