<?php
/**
 * Normalizza un WC_Order nel DTO curato di EV Orders (chiavi di dominio italiane),
 * disaccoppiato dallo schema interno di WooCommerce.
 *
 * @package EVOrders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EVOrders_Transformer {

	/**
	 * @param WC_Order $order
	 * @return array<string,mixed>
	 */
	public static function da_ordine( WC_Order $order ) {
		return array(
			'id'          => $order->get_id(),
			'numero'      => $order->get_order_number(),
			'stato'       => $order->get_status(),
			'valuta'      => $order->get_currency(),
			'iva_inclusa' => $order->get_prices_include_tax(),

			'date' => array(
				'creazione'  => self::iso( $order->get_date_created() ),
				'modifica'   => self::iso( $order->get_date_modified() ),
				'pagamento'  => self::iso( $order->get_date_paid() ),
				'completato' => self::iso( $order->get_date_completed() ),
			),

			'totali' => array(
				'totale'     => $order->get_total(),
				'imposte'    => $order->get_total_tax(),
				'spedizione' => $order->get_shipping_total(),
				'sconto'     => $order->get_discount_total(),
			),

			'cliente' => array(
				'id'       => $order->get_customer_id(),
				'nome'     => self::nome_completo( $order->get_billing_first_name(), $order->get_billing_last_name() ),
				'email'    => $order->get_billing_email(),
				'telefono' => $order->get_billing_phone(),
			),

			'fatturazione' => array(
				'nome'        => $order->get_billing_first_name(),
				'cognome'     => $order->get_billing_last_name(),
				'azienda'     => $order->get_billing_company(),
				'indirizzo_1' => $order->get_billing_address_1(),
				'indirizzo_2' => $order->get_billing_address_2(),
				'citta'       => $order->get_billing_city(),
				'provincia'   => $order->get_billing_state(),
				'cap'         => $order->get_billing_postcode(),
				'paese'       => $order->get_billing_country(),
			),

			'spedizione' => array(
				'nome'        => $order->get_shipping_first_name(),
				'cognome'     => $order->get_shipping_last_name(),
				'azienda'     => $order->get_shipping_company(),
				'indirizzo_1' => $order->get_shipping_address_1(),
				'indirizzo_2' => $order->get_shipping_address_2(),
				'citta'       => $order->get_shipping_city(),
				'provincia'   => $order->get_shipping_state(),
				'cap'         => $order->get_shipping_postcode(),
				'paese'       => $order->get_shipping_country(),
			),

			'righe' => self::righe( $order ),

			'pagamento' => array(
				'metodo'         => $order->get_payment_method_title() ? $order->get_payment_method_title() : $order->get_payment_method(),
				'metodo_codice'  => $order->get_payment_method(),
				'transaction_id' => $order->get_transaction_id(),
			),

			'note' => $order->get_customer_note(),
		);
	}

	/**
	 * Righe ordine (line items) normalizzate.
	 *
	 * @param WC_Order $order
	 * @return array<int,array<string,mixed>>
	 */
	private static function righe( WC_Order $order ) {
		$righe = array();

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			$righe[] = array(
				'prodotto'     => $item->get_name(),
				'product_id'   => $item->get_product_id(),
				'variation_id' => $item->get_variation_id(),
				'sku'          => $product ? $product->get_sku() : null,
				'quantita'     => $item->get_quantity(),
				'prezzo'       => $product ? $product->get_price() : null,
				'subtotale'    => $item->get_subtotal(),
				'totale'       => $item->get_total(),
			);
		}

		return $righe;
	}

	/**
	 * WC_DateTime|null → stringa ISO8601 (o null).
	 *
	 * @param WC_DateTime|null $dt
	 * @return string|null
	 */
	private static function iso( $dt ) {
		return $dt ? $dt->format( 'c' ) : null;
	}

	/**
	 * Nome + cognome (null se entrambi vuoti).
	 *
	 * @param string $nome
	 * @param string $cognome
	 * @return string|null
	 */
	private static function nome_completo( $nome, $cognome ) {
		$completo = trim( $nome . ' ' . $cognome );

		return '' !== $completo ? $completo : null;
	}
}
