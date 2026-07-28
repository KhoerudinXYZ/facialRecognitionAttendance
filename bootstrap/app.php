<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway (dan PaaS sejenis) menaruh app di belakang edge proxy yang
        // terminate TLS lalu forward HTTP polos ke container -- tanpa ini,
        // Laravel salah deteksi request sebagai HTTP (redirect loop, asset/
        // URL ke-generate http:// padahal domainnya sudah https://). IP
        // proxy Railway tidak tetap/tidak diketahui di muka, jadi trust '*'
        // (standar untuk deploy PaaS semacam ini, bukan cuma taruh reverse
        // proxy sendiri yang IP-nya bisa dipastikan).
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'auth.siswa' => \App\Http\Middleware\EnsureSiswaAuthenticated::class,
            'guest.siswa' => \App\Http\Middleware\RedirectIfSiswaAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
