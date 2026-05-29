<?php

use App\Http\Controllers\Api\V1\OrdineController;
use Illuminate\Support\Facades\Route;

/**
 * @group Stato servizio
 *
 * Health check
 *
 * Verifica che il servizio sia attivo. Pubblico (nessuna IP whitelist): usato dal deploy.
 *
 * @unauthenticated
 *
 * @response 200 {"status":"ok"}
 */
Route::get('/v1/health', fn () => response()->json(['status' => 'ok']));

// Auth di rete: l'accesso è regolato dalla IP whitelist (nessun token client).
Route::prefix('v1')
    ->middleware(['ip.whitelist'])
    ->group(function () {
        // Ordini WooCommerce (sola lettura) per store: {store} = slug in config/woocommerce.php.
        Route::prefix('woocommerce/{store}')
            ->where(['store' => '[A-Za-z0-9_-]+'])
            ->group(function () {
                Route::get('/orders', [OrdineController::class, 'index']);
                Route::get('/orders/{id}', [OrdineController::class, 'show'])->whereNumber('id');
            });
    });
