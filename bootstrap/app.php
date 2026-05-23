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
    ->withMiddleware(function (Middleware $middleware): void {

        // استثناء مسارات الـ API من فحص الـ CSRF (إعدادك الأصلي)
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // === تسجيل الاسم المستعار للـ Middleware المخصص للكاشير هنا ===
        $middleware->alias([
            'validate.cashier.token' => \App\Http\Middleware\ValidateCashierToken::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
