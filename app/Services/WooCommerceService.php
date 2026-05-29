<?php

namespace App\Services;

use App\Transformers\OrdineTransformer;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Lettura ordini da WooCommerce (REST API v3) in sola lettura, multi-store.
 *
 * Lo store è scelto dal chiamante nel path ({store}); le credenziali del singolo
 * store stanno in config/woocommerce.php (mappa 'stores' keyed da slug).
 *
 * Auth verso WooCommerce: HTTP Basic (consumer_key:consumer_secret) su HTTPS.
 * Niente persistenza: ogni chiamata interroga live lo store e ritorna DTO normalizzati.
 *
 * Codici: 404 store sconosciuto / ordine inesistente, 500 store non configurato,
 * 502 upstream WooCommerce non-2xx.
 */
class WooCommerceService
{
    /**
     * Lista ordini di uno store con filtri/paginazione (già mappati ai parametri WooCommerce).
     * I totali di paginazione arrivano dagli header X-WP-Total / X-WP-TotalPages.
     *
     * @param  array<string,mixed>  $filtri
     * @return array{store: string, dati: list<array<string,mixed>>, paginazione: array{totale:int, pagine_totali:int, pagina:int, per_pagina:int}}
     */
    public function listaOrdini(string $store, array $filtri): array
    {
        $res = $this->client($store)->get('/orders', $filtri);

        abort_unless($res->successful(), 502, "WooCommerce[{$store}]: lista ordini fallita (HTTP {$res->status()}).");

        $ordini = array_map(
            static fn (array $o): array => OrdineTransformer::daWooCommerce($o),
            $res->json() ?? []
        );

        return [
            'store' => $store,
            'dati' => $ordini,
            'paginazione' => [
                'totale' => (int) $res->header('X-WP-Total'),
                'pagine_totali' => (int) $res->header('X-WP-TotalPages'),
                'pagina' => (int) ($filtri['page'] ?? 1),
                'per_pagina' => (int) ($filtri['per_page'] ?? 20),
            ],
        ];
    }

    /**
     * Dettaglio di un singolo ordine di uno store. 404 se non esiste, 502 altri errori upstream.
     *
     * @return array<string,mixed> DTO normalizzato
     */
    public function ordine(string $store, int $id): array
    {
        $res = $this->client($store)->get("/orders/{$id}");

        abort_if($res->status() === 404, 404, "Ordine {$id} non trovato su WooCommerce[{$store}].");
        abort_unless($res->successful(), 502, "WooCommerce[{$store}]: dettaglio ordine {$id} fallito (HTTP {$res->status()}).");

        return OrdineTransformer::daWooCommerce($res->json() ?? []);
    }

    /**
     * Elenco degli slug store configurati (per validazione del path param).
     *
     * @return list<string>
     */
    public function storeDisponibili(): array
    {
        return array_keys((array) config('woocommerce.stores', []));
    }

    /**
     * Client HTTP autenticato (Basic Auth) per lo store indicato.
     * baseUrl = store_url + /wp-json/{api_version}.
     */
    private function client(string $store): PendingRequest
    {
        $cfg = $this->cfg($store);

        abort_if(
            empty($cfg['store_url']) || empty($cfg['consumer_key']) || empty($cfg['consumer_secret']),
            500,
            "Store WooCommerce '{$store}' non configurato (URL/KEY/SECRET mancanti)."
        );

        return Http::timeout($cfg['timeout'])
            ->acceptJson()
            ->baseUrl(rtrim($cfg['store_url'], '/').'/wp-json/'.$cfg['api_version'])
            ->withBasicAuth($cfg['consumer_key'], $cfg['consumer_secret']);
    }

    /**
     * Config dello store risolto per slug. 404 se lo slug non è in mappa.
     *
     * @return array<string,mixed>
     */
    private function cfg(string $store): array
    {
        $cfg = config("woocommerce.stores.{$store}");

        abort_if($cfg === null, 404, "Store '{$store}' non riconosciuto.");

        return $cfg;
    }
}
