# CLAUDE.md — Connettore WooCommerce NextFuture

## Cosa è

Gateway HTTP **standalone** che espone, in sola lettura, gli ordini di uno o più negozi
WooCommerce (**multi-store**). Progetto separato da `connettore-sw`, `api-nextfuture` e
`server-mcp-nextfuture`, di cui replica le convenzioni.

Lo store è scelto dal chiamante nel path: `/api/v1/woocommerce/{store}/orders`, dove
`{store}` è uno slug definito in `config/woocommerce.php`. Pattern config-driven mutuato
da `connettore-sw/config/sistemi.php`.

- **Modalità = pull / on-demand**: a ogni chiamata HTTP il servizio interroga live la
  REST API WooCommerce e restituisce gli ordini. Nessun webhook.
- **Output = normalizzato**: gli ordini sono mappati in un DTO curato con chiavi di
  dominio italiane (`OrdineTransformer`), disaccoppiato dallo schema WooCommerce.
- **Persistenza = nessuna**: puro proxy. L'unico dato salvato è l'audit-log delle
  chiamate (spatie/laravel-activitylog, DB sqlite di default).

## Stack

Laravel 13, PHP 8.4+. spatie/laravel-activitylog (audit), knuckleswtf/scribe (docs),
laravel/sanctum (installato, NON applicato alle rotte). DB applicativo proprio
(sqlite di default: solo tabella `activity_log`).

## Architettura (flusso)

```
Client → [ip.whitelist] → OrdineController({store}) → WooCommerceService → Http (Basic Auth) → WooCommerce REST API v3 (store risolto da slug)
                                                     ↘ OrdineTransformer::daWooCommerce() → DTO normalizzato
```

- **Controller thin**: `app/Http/Controllers/Api/V1/OrdineController.php`
  (`index`=lista, `show`=dettaglio). Registra audit `activity('connettore')` e per la
  lista ri-emette gli header `X-WP-Total`/`X-WP-TotalPages`.
- **Service**: `app/Services/WooCommerceService.php`. Risolve la config dello store dallo
  slug (`config("woocommerce.stores.$store")`, **404** se sconosciuto) e costruisce il
  client `Http` con Basic Auth (`consumer_key:consumer_secret`) e baseUrl
  `store_url + /wp-json/{api_version}`. `listaOrdini(string $store, array $filtri)` →
  `GET /orders` (totali da header), `ordine(string $store, int $id)` → `GET /orders/{id}`.
  `storeDisponibili()` espone gli slug noti. Error mapping con `abort_if`/`abort_unless`
  come `ZohoService`: **404** store sconosciuto / ordine inesistente, **500** store non
  configurato (creds mancanti), **502** upstream non-2xx.
- **Transformer**: `app/Transformers/OrdineTransformer.php`. WC order array → DTO:
  `id, numero, stato, valuta, iva_inclusa, date{}, totali{}, cliente{}, fatturazione{},
  spedizione{}, righe[], pagamento{}, note`. Esclusi (aggiungibili): `meta_data`,
  `tax_lines`, `fee_lines`, `coupon_lines`.
- **FormRequest**: `app/Http/Requests/ListaOrdiniRequest.php`. Valida i filtri lista
  (status enum, date, `per_page` 1-100, customer, orderby/order, include/exclude CSV) e
  con `filtriWooCommerce()` li mappa ai parametri WC (date ISO8601, CSV→array int, null rimossi).

## Endpoint

- `GET /api/v1/health` — pubblico (no whitelist), per deploy.
- `GET /api/v1/woocommerce/{store}/orders` — lista ordini dello store con filtri/paginazione.
- `GET /api/v1/woocommerce/{store}/orders/{id}` — dettaglio ordine (`{id}` numerico).

`{store}` = slug `[A-Za-z0-9_-]+` presente in `config/woocommerce.php`. La risposta lista
include il campo `store`.

Filtri lista (query): `page`, `per_page` (≤100), `status`
(`any,pending,processing,on-hold,completed,cancelled,refunded,failed,trash`),
`after`, `before`, `modified_after`, `modified_before`, `customer`, `search`,
`orderby`, `order`, `include` (CSV), `exclude` (CSV).

## Sicurezza

- **Ingresso**: solo `ip.whitelist` (`app/Http/Middleware/IpWhitelist.php`, alias in
  `bootstrap/app.php`). Lista da `config/connettore.php` ← `CONNETTORE_IP_WHITELIST`
  (CSV; `*`=tutti dev; vuoto=deny-all). `trustProxies('*')` per X-Forwarded-For.
- **Uscita**: Basic Auth `consumer_key:consumer_secret` su HTTPS. Credenziali solo in
  `.env` (mai nel repo).
- ⚠️ Con auth solo-IP dietro proxy, l'IP dipende da `X-Forwarded-For`. `trustProxies('*')`
  è permissivo: in produzione valutare di fidarsi solo degli IP proxy reali.

## Config / env (multi-store)

`config/woocommerce.php`: legge `WC_STORES` (CSV di slug) e costruisce la mappa `stores`.
Per ogni slug `negozio1` legge env dedicate (slug UPPERCASE, `-`→`_`):
`WC_NEGOZIO1_URL`, `WC_NEGOZIO1_KEY`, `WC_NEGOZIO1_SECRET`, e opzionali
`WC_NEGOZIO1_API_VERSION` / `WC_NEGOZIO1_TIMEOUT` (fallback su `WC_API_VERSION` /
`WC_TIMEOUT`, default `wc/v3` / 15). `WC_DEFAULT_STORE` opzionale.

**Aggiungere uno store = aggiungere lo slug a `WC_STORES` + le sue env (+ GitHub Secrets).
Nessuna modifica al codice.** IP whitelist da `CONNETTORE_IP_WHITELIST`.

## Test

`tests/Feature/OrdiniTest.php` (PHPUnit, `RefreshDatabase` per il DB audit). Tutto via
`Http::fake`: **nessuna chiamata reale** a WooCommerce. Copre: lista→DTO, dettaglio,
mapping filtri, header paginazione, include CSV→array, Basic Auth, 404/500/502/422/403,
audit. `php artisan test` per eseguire, `vendor/bin/pint` per lo style.

## Convenzioni

Italiano per il naming di dominio (chiavi DTO, messaggi). Controller thin → Service per la
logica → FormRequest per la validazione. Pattern allineati a `connettore-sw`
(`ZohoService`, `IpWhitelist`, `bootstrap/app.php`).

## Riferimenti

- Progetto gemello/gold-standard: `../connettore-sw`.
- WooCommerce REST API v3: https://woocommerce.github.io/woocommerce-rest-api-docs/#orders
