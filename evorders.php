<?php
/**
 * Plugin Name:       EV Orders Connector
 * Plugin URI:        https://github.com/Nextfuture-IT/EVOrdersConnector
 * Description:       Espone gli ordini WooCommerce (sola lettura) via REST API, in forma normalizzata, autenticata con API key in header. Per integrazione con software NextFuture.
 * Version:           1.3.1
 * Author:            NextFuture
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * License:           GPL-2.0-or-later
 * Text Domain:       evorders
 * Update URI:        https://github.com/Nextfuture-IT/EVOrdersConnector
 *
 * @package EVOrders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EVORDERS_VERSION', '1.3.1' );
define( 'EVORDERS_DIR', plugin_dir_path( __FILE__ ) );

require_once EVORDERS_DIR . 'includes/class-evorders-transformer.php';
require_once EVORDERS_DIR . 'includes/class-evorders-rest.php';
require_once EVORDERS_DIR . 'includes/class-evorders-settings.php';
require_once EVORDERS_DIR . 'includes/class-evorders-updater.php';

// Auto-update da GitHub Releases (repo pubblico). Indipendente da WooCommerce.
( new EVOrders_Updater( __FILE__ ) )->register();

/**
 * All'attivazione genera un'API key casuale, se non già presente e se non è imposta
 * dalla costante EVORDERS_API_KEY in wp-config.php. Non sovrascrive una chiave esistente
 * (riattivazione safe).
 */
register_activation_hook(
	__FILE__,
	static function () {
		if ( defined( 'EVORDERS_API_KEY' ) && EVORDERS_API_KEY ) {
			return;
		}

		if ( '' === (string) get_option( 'evorders_api_key', '' ) ) {
			add_option( 'evorders_api_key', wp_generate_password( 64, false, false ) );
		}
	}
);

/**
 * Dichiara compatibilità con HPOS (High-Performance Order Storage).
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Avvio: registra rotte REST e pagina impostazioni. Richiede WooCommerce attivo.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p><strong>EV Orders Connector</strong> richiede WooCommerce attivo.</p></div>';
				}
			);

			return;
		}

		// Self-heal: garantisce un'API key anche dopo un aggiornamento dei file
		// (register_activation_hook scatta solo all'attivazione). Saltato se è
		// definita la costante EVORDERS_API_KEY o se la chiave esiste già.
		if ( ! ( defined( 'EVORDERS_API_KEY' ) && EVORDERS_API_KEY )
			&& '' === (string) get_option( 'evorders_api_key', '' ) ) {
			add_option( 'evorders_api_key', wp_generate_password( 64, false, false ) );
		}

		( new EVOrders_REST() )->register();

		if ( is_admin() ) {
			( new EVOrders_Settings() )->register();
		}
	}
);
