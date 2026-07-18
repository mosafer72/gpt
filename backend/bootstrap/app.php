<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\CorsMiddleware::class);
    })
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });








    })->withExceptions(function ($exceptions) {

        $exceptions->render(function (Throwable $e, $request) {

            if (!$request->is('api/*')) {

                return null;

            }

            if ($e instanceof ValidationException) {

                return response()->json([

                    'success' => false,

                    'message' => 'Validation failed',

                    'errors' => $e->errors(),

                ], 422);

            }

            if ($e instanceof HttpExceptionInterface) {

                return response()->json([

                    'success' => false,

                    'message' => $e->getMessage() ?: 'HTTP Error',

                ], $e->getStatusCode());

            }

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

                'exception' => class_basename($e),

                'file' => basename($e->getFile()),

                'line' => $e->getLine(),

            ], 500);

        });
    })
    ->create();


