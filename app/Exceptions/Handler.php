<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                \Sentry\Laravel\Facade\Sentry::captureException($e);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Resource not found',
                    'message' => 'The requested resource does not exist.',
                    'status' => 404,
                ], 404);
            }

            // Check redirects table for 404 → redirect
            if (tenancy()->initialized) {
                $redirect = \App\Models\Tenant\Redirect::where('source_url', '/' . $request->path())
                    ->where('is_active', true)
                    ->first();
                if ($redirect) {
                    $redirect->incrementHits();
                    return redirect()->to($redirect->destination_url, $redirect->status_code);
                }
            }

            return response()->view('errors.404', [], 404);
        });

        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Model not found',
                    'message' => 'The requested resource does not exist.',
                    'status' => 404,
                ], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                    'status' => 422,
                ], 422);
            }
        });

        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'Authentication is required.',
                    'status' => 401,
                ], 401);
            }
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Method not allowed',
                    'message' => 'The HTTP method is not allowed for this endpoint.',
                    'status' => 405,
                ], 405);
            }
        });

        $this->renderable(function (HttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => class_basename($e),
                    'message' => $e->getMessage() ?: 'HTTP error',
                    'status' => $e->getStatusCode(),
                ], $e->getStatusCode());
            }
        });
    }
}
