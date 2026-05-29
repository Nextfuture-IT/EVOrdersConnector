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
				'prodotto'           => $item->get_name(),
				'product_id'         => $item->get_product_id(),
				'variation_id'       => $item->get_variation_id(),
				'sku'                => $product ? $product->get_sku() : null,
				'quantita'           => $item->get_quantity(),
				'prezzo'             => $product ? $product->get_price() : null,
				'subtotale'          => $item->get_subtotal(),
				'totale'             => $item->get_total(),
				'prodotto_dettaglio' => self::prodotto_dettaglio( $product ),
			);
		}

		return $righe;
	}

	/**
	 * Dettaglio completo del prodotto (campi principali). null se il prodotto
	 * non esiste più (es. eliminato dopo l'ordine).
	 *
	 * @param WC_Product|false|null $product
	 * @return array<string,mixed>|null
	 */
	private static function prodotto_dettaglio( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		// Per le varianti: prezzo/sku/attributi dalla variante, categorie/immagine/descrizione dal padre.
		$padre = $product->is_type( 'variation' )
			? wc_get_product( $product->get_parent_id() )
			: $product;

		return array(
			'id'              => $product->get_id(),
			'sku'             => $product->get_sku(),
			'nome'            => $product->get_name(),
			'tipo'            => $product->get_type(),

			'prezzo_attuale'  => $product->get_price(),
			'prezzo_listino'  => $product->get_regular_price(),
			'prezzo_scontato' => $product->get_sale_price(),
			'in_offerta'      => $product->is_on_sale(),

			'stato_stock'     => $product->get_stock_status(),
			'giacenza'        => $product->get_stock_quantity(),
			'gestione_stock'  => $product->managing_stock(),

			'peso'            => $product->get_weight(),
			'dimensioni'      => array(
				'lunghezza'  => $product->get_length(),
				'larghezza'  => $product->get_width(),
				'altezza'    => $product->get_height(),
			),

			'categorie'         => $padre instanceof WC_Product ? self::categorie( $padre ) : array(),
			'immagine'          => $padre instanceof WC_Product ? self::immagine( $padre ) : null,
			'permalink'         => $padre instanceof WC_Product ? get_permalink( $padre->get_id() ) : null,
			'descrizione_breve' => $padre instanceof WC_Product ? $padre->get_short_description() : '',

			'attributi'       => self::attributi( $product ),
		);
	}

	/**
	 * Nomi delle categorie del prodotto.
	 *
	 * @param WC_Product $product
	 * @return string[]
	 */
	private static function categorie( WC_Product $product ) {
		$nomi = array();

		foreach ( $product->get_category_ids() as $cid ) {
			$term = get_term( $cid );
			if ( $term && ! is_wp_error( $term ) ) {
				$nomi[] = $term->name;
			}
		}

		return $nomi;
	}

	/**
	 * URL immagine principale del prodotto (o null).
	 *
	 * @param WC_Product $product
	 * @return string|null
	 */
	private static function immagine( WC_Product $product ) {
		$url = wp_get_attachment_image_url( $product->get_image_id(), 'full' );

		return $url ? $url : null;
	}

	/**
	 * Attributi del prodotto: nome => valore/i.
	 *
	 * @param WC_Product $product
	 * @return array<string,mixed>
	 */
	private static function attributi( WC_Product $product ) {
		$attr = array();

		// Variante: valori scelti per attributo.
		if ( $product->is_type( 'variation' ) ) {
			foreach ( $product->get_variation_attributes() as $key => $val ) {
				$nome          = wc_attribute_label( str_replace( 'attribute_', '', $key ) );
				$attr[ $nome ] = $val;
			}

			return $attr;
		}

		// Prodotto semplice/variabile: elenco attributi (tassonomia o custom).
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof WC_Product_Attribute ) {
				continue;
			}

			$valori = $attribute->is_taxonomy()
				? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) )
				: $attribute->get_options();

			$attr[ wc_attribute_label( $attribute->get_name() ) ] = $valori;
		}

		return $attr;
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
