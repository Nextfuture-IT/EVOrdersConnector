=== EV Orders Connector ===
Contributors: nextfuture
Tags: woocommerce, orders, rest-api, integration
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 7.0
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Espone gli ordini WooCommerce in sola lettura via REST API, normalizzati, autenticati con API key in header. Per integrazione con software NextFuture.

== Description ==

Plugin che aggiunge il namespace REST `evorders/v1` con:

* GET /wp-json/evorders/v1/health (pubblica)
* GET /wp-json/evorders/v1/orders (protetta) — lista filtrabile e paginata
* GET /wp-json/evorders/v1/orders/{id} (protetta) — dettaglio

Gli ordini sono restituiti in forma normalizzata (chiavi di dominio italiane),
disaccoppiati dallo schema interno di WooCommerce. Lettura nativa via wc_get_orders().

Autenticazione: header `X-Api-Key`, confrontato (hash_equals) con la chiave configurata
tramite la costante EVORDERS_API_KEY in wp-config.php (consigliata) oppure l'opzione
impostabile in WooCommerce > EV Orders API.

Compatibile con HPOS (High-Performance Order Storage).

== Installation ==

1. Copia la cartella in wp-content/plugins/evorders/ (o carica lo zip).
2. Attiva il plugin (richiede WooCommerce). All'attivazione viene generata
   automaticamente un'API key casuale.
3. Recupera/rigenera la chiave in WooCommerce > EV Orders API. In alternativa
   definisci EVORDERS_API_KEY in wp-config.php (ha precedenza).

== Changelog ==

= 1.3.0 =
* Auto-update da GitHub Releases (repo pubblico, nessun token): WordPress mostra l'aggiornamento e lo installa con un click. Header Update URI.

= 1.2.0 =
* Ogni riga ordine include prodotto_dettaglio (sku, prezzi listino/scontato/offerta, categorie, immagine, permalink, peso/dimensioni, stock, attributi, descrizione breve). Gestione varianti.

= 1.1.0 =
* Consumo incrementale: GET /orders?nuovi=1 ritorna solo i non letti; POST /orders/letti (ack) marca come letti (meta _evorders_letto, idempotente, HPOS-safe).

= 1.0.0 =
* Prima versione: rotte health/orders/orders/{id}, auth API key, DTO normalizzato, HPOS.
