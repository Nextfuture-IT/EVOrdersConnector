# EV Orders Connector (plugin WooCommerce)

Plugin WooCommerce che espone gli **ordini in sola lettura** via REST API, in forma
**normalizzata** (DTO con chiavi italiane), autenticata con **API key in header**.
Gira dentro lo store: legge gli ordini nativamente (`wc_get_orders()`), nessuna
consumer key/secret, nessun registry esterno.

## Installazione

1. Comprimi la cartella del plugin in `evorders.zip` (vedi sotto) oppure copiala in
   `wp-content/plugins/evorders/`.
2. WordPress → Plugin → attiva **EV Orders Connector** (richiede WooCommerce attivo).
   All'attivazione viene **generata automaticamente** un'API key casuale.
3. Recupera/gestisci l'API key in **WooCommerce → EV Orders API** (mostrata lì, con
   pulsante "Rigenera"). In alternativa, per tenerla fuori dal DB, definiscila in
   **wp-config.php** (ha precedenza): `define( 'EVORDERS_API_KEY', '<stringa-casuale-lunga>' );`

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
| POST | `/wp-json/evorders/v1/orders/letti` | sì | Conferma lettura (ack) degli id elaborati |

### Filtri lista (query string)

`page`, `per_page` (1-100, default 20), `status`
(`any`,`pending`,`processing`,`on-hold`,`completed`,`cancelled`,`refunded`,`failed`),
`after`, `before`, `modified_after`, `modified_before` (date ISO), `customer` (id),
`search`, `orderby`, `order` (`asc`/`desc`), `include` (CSV id), `exclude` (CSV id),
`nuovi` (`1` = solo ordini non ancora letti).

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
        {
          "prodotto": "Olio EVO 1L", "sku": "OLIO-1L", "quantita": 2, "prezzo": "19.95", "totale": "39.90",
          "prodotto_dettaglio": {
            "id": 55, "sku": "OLIO-1L", "nome": "Olio EVO 1L", "tipo": "simple",
            "prezzo_attuale": "19.95", "prezzo_listino": "24.90", "prezzo_scontato": "19.95", "in_offerta": true,
            "stato_stock": "instock", "giacenza": 42, "gestione_stock": true,
            "peso": "1.2", "dimensioni": { "lunghezza": "", "larghezza": "", "altezza": "" },
            "categorie": ["Oli", "Bio"], "immagine": "https://store.it/.../olio.jpg",
            "permalink": "https://store.it/prodotto/olio-evo-1l/",
            "descrizione_breve": "Olio extravergine 1L",
            "attributi": { "Formato": ["1L"] }
          }
        }
      ],
      "pagamento": { "metodo": "Carta di credito", "metodo_codice": "stripe", "transaction_id": "pi_123" },
      "note": "Lasciare al vicino"
    }
  ],
  "paginazione": { "totale": 137, "pagine_totali": 7, "pagina": 1, "per_pagina": 20 }
}
```

## Consumo incrementale (ogni ordine una sola volta)

Per leggere ogni ordine **una sola volta**, con garanzia di non perdita:

```
1. GET  /orders?nuovi=1&per_page=100        → solo ordini NON ancora letti
2. (elabori gli ordini)
3. POST /orders/letti  body {"ids":[21,22]} → marca letti SOLO quelli confermati
→ la GET successiva non li restituisce più
```

- Se il consumer crasha prima del passo 3, gli ordini **riappaiono** alla GET successiva (rilettura sicura).
- L'ack è **idempotente**: `{ "marcati":[...], "gia_letti":[...], "non_trovati":[...] }`.
- Lo stato "letto" è un meta interno (`_evorders_letto`), HPOS-safe. **Nessuna configurazione lato store.**

```bash
# nuovi
curl -H 'X-Api-Key: CHIAVE' 'https://store.it/wp-json/evorders/v1/orders?nuovi=1&per_page=100'
# ack
curl -X POST -H 'X-Api-Key: CHIAVE' -H 'Content-Type: application/json' \
  -d '{"ids":[21,22]}' https://store.it/wp-json/evorders/v1/orders/letti
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
