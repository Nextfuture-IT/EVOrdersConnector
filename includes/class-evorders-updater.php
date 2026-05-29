<?php
/**
 * Auto-update da GitHub Releases (repo PUBBLICO, nessun token).
 * Confronta EVORDERS_VERSION con l'ultima release; se più recente, propone
 * l'update di WordPress usando l'asset evorders.zip della release.
 *
 * @package EVOrders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EVOrders_Updater {

	const REPO      = 'Nextfuture-IT/EVOrdersConnector';
	const TRANSIENT = 'evorders_release_cache';
	const TTL       = 6 * HOUR_IN_SECONDS;

	/** @var string percorso del file principale del plugin */
	private $file;

	/**
	 * @param string $file plugin main file (__FILE__)
	 */
	public function __construct( $file ) {
		$this->file = $file;
	}

	public function register() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check' ) );
		add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
		// Forza un refresh della cache quando si visita la pagina aggiornamenti.
		add_action( 'upgrader_process_complete', array( $this, 'svuota_cache' ) );
	}

	/**
	 * Inietta l'update disponibile nel transient di WordPress.
	 *
	 * @param mixed $transient
	 * @return mixed
	 */
	public function check( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$rel = $this->ultima_release();
		if ( ! $rel ) {
			return $transient;
		}

		$nuova = $this->versione( $rel );
		$zip   = $this->zip_url( $rel );
		$base  = plugin_basename( $this->file );

		if ( $nuova && $zip && version_compare( $nuova, EVORDERS_VERSION, '>' ) ) {
			$transient->response[ $base ] = (object) array(
				'slug'        => dirname( $base ),
				'plugin'      => $base,
				'new_version' => $nuova,
				'url'         => 'https://github.com/' . self::REPO,
				'package'     => $zip,
			);
		}

		return $transient;
	}

	/**
	 * Popola la scheda "Visualizza dettagli" del plugin.
	 *
	 * @param mixed  $res
	 * @param string $action
	 * @param object $args
	 * @return mixed
	 */
	public function info( $res, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		$base = plugin_basename( $this->file );
		if ( empty( $args->slug ) || $args->slug !== dirname( $base ) ) {
			return $res;
		}

		$rel = $this->ultima_release();
		if ( ! $rel ) {
			return $res;
		}

		return (object) array(
			'name'          => 'EV Orders Connector',
			'slug'          => dirname( $base ),
			'version'       => $this->versione( $rel ),
			'author'        => 'NextFuture',
			'homepage'      => 'https://github.com/' . self::REPO,
			'download_link' => $this->zip_url( $rel ),
			'sections'      => array(
				'changelog' => nl2br( esc_html( isset( $rel['body'] ) ? $rel['body'] : '' ) ),
			),
		);
	}

	public function svuota_cache() {
		delete_transient( self::TRANSIENT );
	}

	/**
	 * Ultima release da GitHub (cache 6h). null su errore.
	 *
	 * @return array<string,mixed>|null
	 */
	private function ultima_release() {
		$cache = get_transient( self::TRANSIENT );
		if ( false !== $cache ) {
			return is_array( $cache ) ? $cache : null;
		}

		$res = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'EVOrders-Updater',
				),
			)
		);

		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			set_transient( self::TRANSIENT, 'none', HOUR_IN_SECONDS ); // backoff su errore
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		set_transient( self::TRANSIENT, is_array( $data ) ? $data : 'none', self::TTL );

		return is_array( $data ) ? $data : null;
	}

	/**
	 * Versione (tag senza 'v').
	 *
	 * @param array<string,mixed> $rel
	 * @return string
	 */
	private function versione( $rel ) {
		return isset( $rel['tag_name'] ) ? ltrim( $rel['tag_name'], 'v' ) : '';
	}

	/**
	 * URL dell'asset evorders.zip della release (fallback: zipball).
	 *
	 * @param array<string,mixed> $rel
	 * @return string|null
	 */
	private function zip_url( $rel ) {
		if ( ! empty( $rel['assets'] ) && is_array( $rel['assets'] ) ) {
			foreach ( $rel['assets'] as $asset ) {
				if ( isset( $asset['name'] ) && '.zip' === substr( $asset['name'], -4 ) ) {
					return $asset['browser_download_url'];
				}
			}
		}

		return isset( $rel['zipball_url'] ) ? $rel['zipball_url'] : null;
	}
}
