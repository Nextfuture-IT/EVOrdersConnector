<?php

/*
|--------------------------------------------------------------------------
| Store WooCommerce (multi-store)
|--------------------------------------------------------------------------
|
| Lista degli store gestiti. Lo slug è il parametro nel path:
|   GET /api/v1/woocommerce/{store}/orders
|
| Gli slug attivi sono in WC_STORES (CSV). Per ogni slug "negozio1" si leggono
| le env dedicate (slug in UPPERCASE, '-' → '_'):
|   WC_NEGOZIO1_URL, WC_NEGOZIO1_KEY, WC_NEGOZIO1_SECRET
|   WC_NEGOZIO1_API_VERSION (opz.), WC_NEGOZIO1_TIMEOUT (opz.)
|
| Aggiungere uno store = aggiungere lo slug a WC_STORES + le sue env (e i
| relativi GitHub Secrets). Nessuna modifica al codice.
|
*/

$slugs = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('WC_STORES', ''))
)));

$stores = [];

foreach ($slugs as $slug) {
    $prefix = 'WC_'.strtoupper(str_replace('-', '_', $slug));

    $stores[$slug] = [
        'store_url' => env("{$prefix}_URL"),
        'consumer_key' => env("{$prefix}_KEY"),
        'consumer_secret' => env("{$prefix}_SECRET"),
        'api_version' => env("{$prefix}_API_VERSION", env('WC_API_VERSION', 'wc/v3')),
        'timeout' => (int) env("{$prefix}_TIMEOUT", env('WC_TIMEOUT', 15)),
    ];
}

return [

    // Store usato se nel path non viene indicato (rotte senza {store}). Opzionale.
    'default' => env('WC_DEFAULT_STORE'),

    // Mappa slug => credenziali. Vuota = nessuno store configurato.
    'stores' => $stores,

];
