# Connettore WooCommerce

Gateway HTTP in **sola lettura** sugli ordini di uno o più negozi WooCommerce
(**multi-store**). Lo store è scelto nel path (`/woocommerce/{store}/orders`). A ogni
chiamata interroga live la REST API WooCommerce e restituisce gli ordini in forma
**normalizzata** (DTO con chiavi italiane). Nessuna persistenza: solo audit-log.

Laravel 13 · PHP 8.4+ · auth in ingresso via IP whitelist · auth verso WooCommerce via
Basic Auth (consumer key/secret) su HTTPS.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate           # crea la tabella activity_log (audit)
```

Configura gli store in `.env` (multi-store):

```env
# Slug attivi (CSV). Lo slug è il parametro nel path: /woocommerce/{slug}/orders
WC_STORES=negozio1,negozio2
WC_DEFAULT_STORE=negozio1
WC_API_VERSION=wc/v3          # default globale, override con WC_<SLUG>_API_VERSION
WC_TIMEOUT=15

# Per ogni slug: WC_<SLUG_UPPERCASE>_URL / _KEY / _SECRET  ('-' nello slug → '_')
WC_NEGOZIO1_URL=https://negozio1.example.com   # senza /wp-json
WC_NEGOZIO1_KEY=ck_xxxxxxxxxxxx
WC_NEGOZIO1_SECRET=cs_xxxxxxxxxxxx

WC_NEGOZIO2_URL=https://negozio2.example.com
WC_NEGOZIO2_KEY=ck_yyyyyyyyyyyy
WC_NEGOZIO2_SECRET=cs_yyyyyyyyyyyy

CONNETTORE_IP_WHITELIST=127.0.0.1,::1      # CSV; vuoto = deny-all; '*' = tutti (solo dev)
```

> Le credenziali WooCommerce si generano in **WooCommerce → Impostazioni → Avanzate →
> REST API** con permessi **Read**.
>
> **Aggiungere uno store**: aggiungi lo slug a `WC_STORES` e definisci le sue tre env
> `WC_<SLUG>_URL/_KEY/_SECRET`. Nessuna modifica al codice.

Avvio locale:

```bash
php artisan serve
```

## Endpoint

| Metodo | Path | Descrizione |
|---|---|---|
| GET | `/api/v1/health` | Health check (pubblico) |
| GET | `/api/v1/woocommerce/{store}/orders` | Lista ordini dello store (filtri + paginazione) |
| GET | `/api/v1/woocommerce/{store}/orders/{id}` | Dettaglio ordine dello store |

`{store}` = slug definito in `WC_STORES` / `config/woocommerce.php`.

### Filtri lista (query string)

`page`, `per_page` (≤100), `status`
(`any`,`pending`,`processing`,`on-hold`,`completed`,`cancelled`,`refunded`,`failed`,`trash`),
`after`, `before`, `modified_after`, `modified_before`, `customer`, `search`,
`orderby`, `order`, `include` (CSV di id), `exclude` (CSV di id).

La paginazione è esposta sia nel corpo (`paginazione`) sia negli header
`X-WP-Total` / `X-WP-TotalPages`.

## Esempi

```bash
curl localhost:8000/api/v1/health
# {"status":"ok"}

curl 'localhost:8000/api/v1/woocommerce/negozio1/orders?per_page=5&status=completed'
curl localhost:8000/api/v1/woocommerce/negozio1/orders/123
```

Risposta lista (estratto):

```json
{
  "store": "negozio1",
  "dati": [
    {
      "id": 123,
      "numero": "123",
      "stato": "completed",
      "valuta": "EUR",
      "iva_inclusa": true,
      "date": { "creazione": "2026-05-01T10:00:00", "pagamento": null },
      "totali": { "totale": "49.90", "imposte": "9.00", "spedizione": "5.00", "sconto": "0.00" },
      "cliente": { "id": 7, "nome": "Mario Rossi", "email": "mario@x.it", "telefono": "0881000000" },
      "fatturazione": { "citta": "Foggia", "provincia": "FG", "cap": "71100", "paese": "IT" },
      "spedizione": { "citta": "Foggia" },
      "righe": [
        { "prodotto": "Olio EVO 1L", "sku": "OLIO-1L", "quantita": 2, "prezzo": 19.95, "totale": "39.90" }
      ],
      "pagamento": { "metodo": "Carta di credito", "metodo_codice": "stripe", "transaction_id": "pi_123" },
      "note": "Lasciare al vicino"
    }
  ],
  "paginazione": { "totale": 137, "pagine_totali": 7, "pagina": 1, "per_pagina": 20 }
}
```

## Codici di errore

| Codice | Significato |
|---|---|
| 403 | IP non in whitelist |
| 404 | Store sconosciuto, oppure ordine inesistente |
| 422 | Filtro non valido |
| 500 | Store configurato ma senza credenziali (URL/KEY/SECRET mancanti) |
| 502 | WooCommerce upstream non raggiungibile / non-2xx |

## Test e docs

```bash
php artisan test          # feature test (Http::fake, nessuna chiamata reale)
vendor/bin/pint           # code style
php artisan scribe:generate   # documentazione API (public/docs)
```

Deploy: vedi [DEPLOY.md](DEPLOY.md).
