=== EV Orders Connector ===
Contributors: nextfuture
Tags: woocommerce, orders, rest-api, integration
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 7.0
Stable tag: 1.0.0
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
2. Attiva il plugin (richiede WooCommerce).
3. Definisci EVORDERS_API_KEY in wp-config.php oppure imposta la chiave da
   WooCommerce > EV Orders API.

== Changelog ==

= 1.0.0 =
* Prima versione: rotte health/orders/orders/{id}, auth API key, DTO normalizzato, HPOS.
