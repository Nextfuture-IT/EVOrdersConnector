# CLAUDE.md — EV Orders Connector (plugin WooCommerce)

## Cosa è

Plugin WordPress/WooCommerce che espone gli **ordini in sola lettura** via REST API,
in forma **normalizzata** (DTO chiavi italiane), autenticato con **API key in header**
(`X-Api-Key`). Gira dentro lo store: legge gli ordini nativamente, niente consumer
key/secret, niente registry/credenziali esterne.

> Storia: nato come connettore Laravel standalone (vedi git history), poi convertito in
> plugin perché deve girare dentro lo store ed esporre i suoi ordini.

## Struttura

```
evorders.php                         header plugin + bootstrap (check WC, require, hook)
uninstall.php                        rimuove l'opzione api key alla disinstallazione
includes/
  class-evorders-rest.php            rotte REST evorders/v1 + permission (X-Api-Key)
  class-evorders-transformer.php     WC_Order → DTO normalizzato (chiavi IT)
  class-evorders-settings.php        pagina admin per l'API key (sotto WooCommerce)
readme.txt / README.md               doc (WP + repo)
```

## Endpoint (namespace `evorders/v1`)

- `GET /wp-json/evorders/v1/health` — pubblico
- `GET /wp-json/evorders/v1/orders` — protetto, lista filtrabile/paginata
- `GET /wp-json/evorders/v1/orders/{id}` — protetto, dettaglio
- `POST /wp-json/evorders/v1/orders/letti` — protetto, ack lettura (body `{ids:[...]}`)

## Consumo incrementale (read-once)

`?nuovi=1` sulla lista filtra gli ordini **senza** il meta `_evorders_letto`
(meta_query `NOT EXISTS`). Dopo l'elaborazione, `POST /orders/letti {ids:[...]}` marca
quegli ordini come letti (`$order->update_meta_data('_evorders_letto', gmdate('c'))` +
`save()`, **HPOS-safe**, idempotente). Crash prima dell'ack → riappaiono (no perdita).
Flag globale (singolo consumer). Nessuna config lato store.

Filtri lista: `page`, `per_page` (1-100), `status`, `after`/`before`,
`modified_after`/`modified_before`, `customer`, `search`, `orderby`, `order`,
`include`/`exclude` (CSV id). Mappati su `wc_get_orders()` (`paginate=true` per i totali).
Paginazione nel body (`paginazione`) + header `X-WP-Total`/`X-WP-TotalPages`.

## Auth

Header `X-Api-Key`, confronto `hash_equals` con la chiave configurata:
costante `EVORDERS_API_KEY` in `wp-config.php` (preferita) **oppure** opzione
`evorders_api_key`. L'opzione è **generata automaticamente all'attivazione**
(`register_activation_hook` → `wp_generate_password(64)`, non sovrascrive una chiave
esistente; saltata se la costante è definita), visibile/rigenerabile in WooCommerce →
EV Orders API. Niente chiave → 500; chiave errata → 401.

## Dati

`EVOrders_Transformer::da_ordine(WC_Order)` → `id, numero, stato, valuta, iva_inclusa,
date{}, totali{}, cliente{}, fatturazione{}, spedizione{}, righe[], pagamento{}, note`.
Date in ISO8601 (`WC_DateTime::format('c')`). Esclusi meta/tax/fee/coupon lines.

## Note

- Compatibile **HPOS** (dichiarata in `before_woocommerce_init`).
- Multi-store: il plugin serve **un** store. Aggregazione per P.IVA su più store = a
  carico del SW chiamante (conosce gli URL degli store).
- Convenzioni: prefisso classi `EVOrders_`, naming dominio in italiano, escaping output admin.
