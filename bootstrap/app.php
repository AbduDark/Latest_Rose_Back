<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\LocalizationMiddleware::class,
            \App\Http\Middleware\SecurityLogMiddleware::class,
            \App\Http\Middleware\ApiErrorHandler::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
            'gender.content' => \App\Http\Middleware\GenderContentMiddleware::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'check.session' => \App\Http\Middleware\CheckSessionMiddleware::class,
            'api.errors' => \App\Http\Middleware\ApiErrorHandler::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $locale = $request->header('Accept-Language', 'ar');
                $locale = in_array($locale, ['ar', 'en']) ? $locale : 'ar';

                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => [
                        'ar' => 'غير مصرح لك بالوصول - يجب تسجيل الدخول',
                        'en' => 'Unauthorized access - Login required'
                    ]
                ], 401);
            }

            return redirect()->route('login');
        });
    })->create();
