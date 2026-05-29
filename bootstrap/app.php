<?php

use App\Http\Middleware\IpWhitelist;
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
        // Fida dei proxy (nginx) per risolvere correttamente l'IP client (X-Forwarded-For).
        $middleware->trustProxies(at: '*');

        // Alias middleware IP whitelist.
        $middleware->alias([
            'ip.whitelist' => IpWhitelist::class,
        ]);

        // API pura: niente redirect a 'login' per i guest → 401 JSON.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API pura: ogni errore su /api/* esce come JSON (mai redirect a 'login').
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $throwable) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
