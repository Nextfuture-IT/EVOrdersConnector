<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListaOrdiniRequest;
use App\Services\WooCommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Ordini WooCommerce
 *
 * Lettura (sola lettura) degli ordini di un negozio WooCommerce via REST API v3.
 * Lo store è indicato nel path ({store} = slug in config/woocommerce.php).
 * Gli ordini sono restituiti in forma normalizzata (DTO con chiavi di dominio italiane).
 */
class OrdineController extends Controller
{
    public function __construct(private readonly WooCommerceService $service) {}

    /**
     * Lista ordini
     *
     * Ritorna gli ordini dello store indicato, filtrabili e paginati. I totali di paginazione
     * sono esposti sia nel corpo (`paginazione`) sia negli header `X-WP-Total` / `X-WP-TotalPages`.
     *
     * @urlParam store string required Slug dello store WooCommerce (config/woocommerce.php). Example: negozio1
     *
     * @queryParam page integer Pagina (default 1). Example: 1
     * @queryParam per_page integer Elementi per pagina (1-100, default 20). Example: 20
     * @queryParam status string Stato ordine: any, pending, processing, on-hold, completed, cancelled, refunded, failed, trash. Example: completed
     * @queryParam after string Ordini creati dopo questa data (ISO8601). Example: 2026-01-01
     * @queryParam before string Ordini creati prima di questa data (ISO8601). Example: 2026-12-31
     * @queryParam modified_after string Ordini modificati dopo questa data (ISO8601). Example: 2026-05-01
     * @queryParam modified_before string Ordini modificati prima di questa data (ISO8601).
     * @queryParam customer integer ID cliente WooCommerce. Example: 7
     * @queryParam search string Ricerca testuale. Example: rossi
     * @queryParam orderby string Ordinamento: date, id, title, slug, modified, include. Example: date
     * @queryParam order string Direzione: asc, desc. Example: desc
     * @queryParam include string CSV di id da includere. Example: 12,15,20
     * @queryParam exclude string CSV di id da escludere. Example: 99
     *
     * @response 200 scenario="ok" {"store":"negozio1","dati":[{"id":123,"numero":"123","stato":"completed","valuta":"EUR","totali":{"totale":"49.90"},"cliente":{"email":"mario@x.it"},"righe":[{"prodotto":"Olio EVO 1L","sku":"OLIO-1L","quantita":2}]}],"paginazione":{"totale":137,"pagine_totali":7,"pagina":1,"per_pagina":20}}
     * @response 403 scenario="IP non in whitelist" {"message":"IP non autorizzato.","ip":"203.0.113.10"}
     * @response 404 scenario="store sconosciuto" {"message":"Store 'negozioX' non riconosciuto."}
     * @response 422 scenario="filtro non valido" {"message":"Stato ordine non valido.","errors":{"status":["Stato ordine non valido."]}}
     * @response 500 scenario="store non configurato" {"message":"Store WooCommerce 'negozio1' non configurato (URL/KEY/SECRET mancanti)."}
     * @response 502 scenario="upstream WooCommerce non raggiungibile" {"message":"WooCommerce[negozio1]: lista ordini fallita (HTTP 500)."}
     */
    public function index(ListaOrdiniRequest $request, string $store): JsonResponse
    {
        $esito = $this->service->listaOrdini($store, $request->filtriWooCommerce());

        activity('connettore')
            ->withProperties([
                'operazione' => 'lista_ordini',
                'store' => $store,
                'filtri' => $request->validated(),
                'totale' => $esito['paginazione']['totale'],
                'ip' => $request->ip(),
            ])
            ->log('Lista ordini WooCommerce');

        return response()->json($esito)->withHeaders([
            'X-WP-Total' => $esito['paginazione']['totale'],
            'X-WP-TotalPages' => $esito['paginazione']['pagine_totali'],
        ]);
    }

    /**
     * Dettaglio ordine
     *
     * Ritorna il singolo ordine dello store indicato, in forma normalizzata.
     *
     * @urlParam store string required Slug dello store WooCommerce. Example: negozio1
     * @urlParam id integer required ID ordine WooCommerce. Example: 123
     *
     * @response 200 scenario="ok" {"id":123,"numero":"123","stato":"completed","valuta":"EUR","totali":{"totale":"49.90"},"cliente":{"email":"mario@x.it"}}
     * @response 403 scenario="IP non in whitelist" {"message":"IP non autorizzato.","ip":"203.0.113.10"}
     * @response 404 scenario="store o ordine inesistente" {"message":"Ordine 999 non trovato su WooCommerce[negozio1]."}
     * @response 500 scenario="store non configurato" {"message":"Store WooCommerce 'negozio1' non configurato (URL/KEY/SECRET mancanti)."}
     * @response 502 scenario="upstream WooCommerce non raggiungibile" {"message":"WooCommerce[negozio1]: dettaglio ordine 123 fallito (HTTP 500)."}
     */
    public function show(Request $request, string $store, int $id): JsonResponse
    {
        $ordine = $this->service->ordine($store, $id);

        activity('connettore')
            ->withProperties([
                'operazione' => 'dettaglio_ordine',
                'store' => $store,
                'ordine_id' => $id,
                'ip' => $request->ip(),
            ])
            ->log("Dettaglio ordine WooCommerce {$id}");

        return response()->json($ordine);
    }
}
