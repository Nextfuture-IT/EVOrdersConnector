<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist
    |--------------------------------------------------------------------------
    |
    | Elenco degli IP autorizzati a chiamare gli endpoint del Connettore.
    | Valore da .env (CSV). Esempi:
    |   "127.0.0.1,::1,10.0.0.5"  → solo questi IP
    |   "*"                        → tutti (SOLO sviluppo)
    |   ""                         → nessun IP (deny-all)
    |
    */

    'ip_whitelist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CONNETTORE_IP_WHITELIST', ''))
    ))),

];
