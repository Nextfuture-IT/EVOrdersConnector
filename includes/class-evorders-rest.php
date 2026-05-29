<?php
/**
 * Rotte REST di EV Orders (sola lettura) + autenticazione via API key in header.
 *
 * Namespace: evorders/v1
 *   GET /wp-json/evorders/v1/health           (pubblica)
 *   GET /wp-json/evorders/v1/orders           (protetta) lista filtrabile/paginata
 *   GET /wp-json/evorders/v1/orders/{id}      (protetta) dettaglio
 *
 * Auth: header X-Api-Key confrontato (hash_equals) con la chiave configurata
 * (costante EVORDERS_API_KEY in wp-config.php, oppure opzione 'evorders_api_key').
 *
 * @package EVOrders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EVOrders_REST {

	const NS = 'evorders/v1';

	/**
	 * Aggancia la registrazione delle rotte.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Definisce le rotte.
	 */
	public function routes() {
		register_rest_route(
			self::NS,
			'/health',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'health' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NS,
			'/orders',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'lista' ),
				'permission_callback' => array( $this, 'autorizza' ),
				'args'                => $this->args_lista(),
			)
		);

		register_rest_route(
			self::NS,
			'/orders/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'dettaglio' ),
				'permission_callback' => array( $this, 'autorizza' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => static function ( $v ) {
							return is_numeric( $v );
						},
					),
				),
			)
		);
	}

	/**
	 * Health check pubblico.
	 *
	 * @return WP_REST_Response
	 */
	public function health() {
		return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
	}

	/**
	 * Permission callback: valida l'header X-Api-Key.
	 *
	 * @param WP_REST_Request $request
	 * @return true|WP_Error
	 */
	public function autorizza( WP_REST_Request $request ) {
		$attesa = $this->api_key();

		if ( empty( $attesa ) ) {
			return new WP_Error( 'evorders_non_configurato', 'API key non configurata (EVORDERS_API_KEY o opzione evorders_api_key).', array( 'status' => 500 ) );
		}

		$ricevuta = (string) $request->get_header( 'X-Api-Key' );

		if ( '' === $ricevuta || ! hash_equals( $attesa, $ricevuta ) ) {
			return new WP_Error( 'evorders_non_autorizzato', 'API key mancante o non valida.', array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Lista ordini.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function lista( WP_REST_Request $request ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return new WP_Error( 'evorders_no_wc', 'WooCommerce non attivo.', array( 'status' => 500 ) );
		}

		$page     = max( 1, (int) $request->get_param( 'page' ) ?: 1 );
		$per_page = (int) $request->get_param( 'per_page' ) ?: 20;
		$per_page = max( 1, min( 100, $per_page ) );

		$query = array(
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'orderby'  => $request->get_param( 'orderby' ) ?: 'date',
			'order'    => strtoupper( $request->get_param( 'order' ) ?: 'DESC' ),
		);

		$status = $request->get_param( 'status' );
		if ( $status && 'any' !== $status ) {
			$query['status'] = sanitize_text_field( $status );
		}

		$customer = $request->get_param( 'customer' );
		if ( null !== $customer && '' !== $customer ) {
			$query['customer_id'] = (int) $customer;
		}

		$search = $request->get_param( 'search' );
		if ( $search ) {
			$query['s'] = sanitize_text_field( $search );
		}

		$query = $this->applica_date( $query, 'date_created', $request->get_param( 'after' ), $request->get_param( 'before' ) );
		$query = $this->applica_date( $query, 'date_modified', $request->get_param( 'modified_after' ), $request->get_param( 'modified_before' ) );

		$include = $this->csv_int( $request->get_param( 'include' ) );
		if ( $include ) {
			$query['include'] = $include;
		}
		$exclude = $this->csv_int( $request->get_param( 'exclude' ) );
		if ( $exclude ) {
			$query['exclude'] = $exclude;
		}

		$risultato = wc_get_orders( $query );

		$dati = array();
		foreach ( $risultato->orders as $order ) {
			$dati[] = EVOrders_Transformer::da_ordine( $order );
		}

		$response = new WP_REST_Response(
			array(
				'dati'        => $dati,
				'paginazione' => array(
					'totale'        => (int) $risultato->total,
					'pagine_totali' => (int) $risultato->max_num_pages,
					'pagina'        => $page,
					'per_pagina'    => $per_page,
				),
			),
			200
		);

		$response->header( 'X-WP-Total', (int) $risultato->total );
		$response->header( 'X-WP-TotalPages', (int) $risultato->max_num_pages );

		return $response;
	}

	/**
	 * Dettaglio ordine.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function dettaglio( WP_REST_Request $request ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return new WP_Error( 'evorders_no_wc', 'WooCommerce non attivo.', array( 'status' => 500 ) );
		}

		$id    = (int) $request->get_param( 'id' );
		$order = wc_get_order( $id );

		if ( ! $order instanceof WC_Order ) {
			return new WP_Error( 'evorders_non_trovato', sprintf( 'Ordine %d non trovato.', $id ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( EVOrders_Transformer::da_ordine( $order ), 200 );
	}

	/**
	 * Definizione args (per validazione/sanitizzazione e doc).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function args_lista() {
		return array(
			'page'            => array( 'type' => 'integer', 'default' => 1 ),
			'per_page'        => array( 'type' => 'integer', 'default' => 20 ),
			'status'          => array( 'type' => 'string' ),
			'after'           => array( 'type' => 'string' ),
			'before'          => array( 'type' => 'string' ),
			'modified_after'  => array( 'type' => 'string' ),
			'modified_before' => array( 'type' => 'string' ),
			'customer'        => array( 'type' => 'integer' ),
			'search'          => array( 'type' => 'string' ),
			'orderby'         => array( 'type' => 'string', 'default' => 'date' ),
			'order'           => array( 'type' => 'string', 'default' => 'desc' ),
			'include'         => array( 'type' => 'string' ),
			'exclude'         => array( 'type' => 'string' ),
		);
	}

	/**
	 * Applica un filtro data (after/before) su un campo (date_created|date_modified)
	 * nel formato accettato da wc_get_orders (timestamp + operatori/range).
	 *
	 * @param array       $query
	 * @param string      $campo
	 * @param string|null $after
	 * @param string|null $before
	 * @return array
	 */
	private function applica_date( $query, $campo, $after, $before ) {
		$ts_after  = $after ? strtotime( $after ) : false;
		$ts_before = $before ? strtotime( $before ) : false;

		if ( $ts_after && $ts_before ) {
			$query[ $campo ] = $ts_after . '...' . $ts_before;
		} elseif ( $ts_after ) {
			$query[ $campo ] = '>=' . $ts_after;
		} elseif ( $ts_before ) {
			$query[ $campo ] = '<=' . $ts_before;
		}

		return $query;
	}

	/**
	 * CSV di interi → array di int (vuoto se assente).
	 *
	 * @param string|null $csv
	 * @return int[]
	 */
	private function csv_int( $csv ) {
		if ( empty( $csv ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'intval', explode( ',', $csv ) ) ) );
	}

	/**
	 * API key configurata: costante EVORDERS_API_KEY (preferita) o opzione.
	 *
	 * @return string
	 */
	private function api_key() {
		if ( defined( 'EVORDERS_API_KEY' ) && EVORDERS_API_KEY ) {
			return (string) EVORDERS_API_KEY;
		}

		return (string) get_option( 'evorders_api_key', '' );
	}
}
