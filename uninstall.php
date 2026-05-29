<?php
/**
 * Pulizia alla disinstallazione del plugin: rimuove l'opzione API key.
 *
 * @package EVOrders
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'evorders_api_key' );
