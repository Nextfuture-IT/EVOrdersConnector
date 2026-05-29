# EV Orders Connector (plugin WooCommerce)

Plugin WooCommerce che espone gli **ordini in sola lettura** via REST API, in forma
**normalizzata** (DTO con chiavi italiane), autenticata con **API key in header**.
Gira dentro lo store: legge gli ordini nativamente (`wc_get_orders()`), nessuna
consumer key/secret, nessun registry esterno.

## Installazione

1. Comprimi la cartella del plugin in `evorders.zip` (vedi sotto) oppure copiala in
   `wp-content/plugins/evorders/`.
2. WordPress → Plugin → attiva **EV Orders Connector** (richiede WooCommerce attivo).
3. Imposta l'API key (uno dei due):
   - **wp-config.php** (consigliato): `define( 'EVORDERS_API_KEY', '<stringa-casuale-lunga>' );`
   - oppure WooCommerce → **EV Orders API** → campo API key.

Pacchetto installabile:

```bash
zip -r evorders.zip evorders.php uninstall.php includes readme.txt
```

## Endpoint

Namespace `evorders/v1`. Tutte le rotte ordini richiedono l'header `X-Api-Key`.

| Metodo | Path | Auth | Descrizione |
|---|---|---|---|
| GET | `/wp-json/evorders/v1/health` | no | Health check |
| GET | `/wp-json/evorders/v1/orders` | sì | Lista ordini (filtri + paginazione) |
| GET | `/wp-json/evorders/v1/orders/{id}` | sì | Dettaglio ordine |

### Filtri lista (query string)

`page`, `per_page` (1-100, default 20), `status`
(`any`,`pending`,`processing`,`on-hold`,`completed`,`cancelled`,`refunded`,`failed`),
`after`, `before`, `modified_after`, `modified_before` (date ISO), `customer` (id),
`search`, `orderby`, `order` (`asc`/`desc`), `include` (CSV id), `exclude` (CSV id).

Paginazione nel corpo (`paginazione`) e negli header `X-WP-Total` / `X-WP-TotalPages`.

## Esempi

```bash
curl https://store.it/wp-json/evorders/v1/health
# {"status":"ok"}

curl -H 'X-Api-Key: LA_TUA_CHIAVE' \
  'https://store.it/wp-json/evorders/v1/orders?per_page=5&status=completed'

curl -H 'X-Api-Key: LA_TUA_CHIAVE' \
  https://store.it/wp-json/evorders/v1/orders/123
```

Risposta lista (estratto):

```json
{
  "dati": [
    {
      "id": 123,
      "numero": "123",
      "stato": "completed",
      "valuta": "EUR",
      "iva_inclusa": true,
      "date": { "creazione": "2026-05-01T10:00:00+02:00", "pagamento": null },
      "totali": { "totale": "49.90", "imposte": "9.00", "spedizione": "5.00", "sconto": "0.00" },
      "cliente": { "id": 7, "nome": "Mario Rossi", "email": "mario@x.it", "telefono": "0881000000" },
      "fatturazione": { "citta": "Foggia", "provincia": "FG", "cap": "71100", "paese": "IT" },
      "spedizione": { "citta": "Foggia" },
      "righe": [
        { "prodotto": "Olio EVO 1L", "sku": "OLIO-1L", "quantita": 2, "prezzo": "19.95", "totale": "39.90" }
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
| 401 | API key mancante o non valida |
| 404 | Ordine inesistente |
| 500 | API key non configurata / WooCommerce non attivo |

## Note tecniche

- Compatibile con **HPOS** (High-Performance Order Storage).
- Confronto API key con `hash_equals` (timing-safe).
- Multi-store: il plugin serve **un** store. Per più store di una stessa P.IVA, il SW
  chiamante interroga l'endpoint di ogni store (gli URL li conosce già).
