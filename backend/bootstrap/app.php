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
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (Throwable $e, Request $request) {

            // فقط برای درخواست‌های API یا درخواست‌هایی که خروجی JSON می‌خواهند
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            // استخراج اطلاعات دقیق Route و Controller
            $route = $request->route();
            $action = $route ? $route->getActionName() : 'Unknown Action';
            $uri = $request->fullUrl();
            $method = $request->method();

            // ۱. مدیریت خطای عدم احراز هویت (401)
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'debug' => [
                        'method' => $method,
                        'url' => $uri,
                        'action' => $action,
                    ]
                ], 401);
            }

            // ۲. مدیریت خطای اعتبار سنجی (422)
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'debug' => [
                        'method' => $method,
                        'url' => $uri,
                        'action' => $action,
                    ]
                ], 422);
            }

            // ۳. تعیین کد وضعیت HTTP
            $statusCode = 500;
            if ($e instanceof HttpExceptionInterface) {
                $statusCode = $e->getStatusCode();
            }

            // ۴. خروجی جامع برای سایر خطاها (شامل نام کنترلر، متد، فایل و خط دقیق)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Server Error',
                'exception' => class_basename($e),
                'location' => [
                    'url' => $uri,
                    'method' => $method,
                    'controller_action' => $action, // مثال: App\Http\Controllers\DocumentController@index
                    'file' => $e->getFile(),         // مسیر کامل فایل
                    'line' => $e->getLine(),         // شماره خط دقیق خطا
                ],
            ], $statusCode);
        });

    })
    ->create();
