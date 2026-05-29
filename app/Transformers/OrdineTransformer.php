<?php

namespace App\Transformers;

/**
 * Normalizza un ordine WooCommerce (schema REST API v3) nel DTO curato del Connettore.
 *
 * Chiavi di dominio in italiano: il DTO è il contratto stabile verso i sistemi interni
 * NextFuture, disaccoppiato dallo schema WooCommerce. Campi non essenziali
 * (meta_data, tax_lines, fee_lines, coupon_lines) sono volutamente esclusi.
 */
final class OrdineTransformer
{
    /**
     * @param  array<string,mixed>  $o  ordine WooCommerce raw
     * @return array<string,mixed> DTO normalizzato
     */
    public static function daWooCommerce(array $o): array
    {
        $billing = $o['billing'] ?? [];

        return [
            'id' => $o['id'] ?? null,
            'numero' => $o['number'] ?? null,
            'stato' => $o['status'] ?? null,
            'valuta' => $o['currency'] ?? null,
            'iva_inclusa' => (bool) ($o['prices_include_tax'] ?? false),

            'date' => [
                'creazione' => $o['date_created_gmt'] ?? $o['date_created'] ?? null,
                'modifica' => $o['date_modified_gmt'] ?? $o['date_modified'] ?? null,
                'pagamento' => $o['date_paid_gmt'] ?? $o['date_paid'] ?? null,
                'completato' => $o['date_completed_gmt'] ?? $o['date_completed'] ?? null,
            ],

            'totali' => [
                'totale' => $o['total'] ?? null,
                'imposte' => $o['total_tax'] ?? null,
                'spedizione' => $o['shipping_total'] ?? null,
                'sconto' => $o['discount_total'] ?? null,
            ],

            'cliente' => [
                'id' => $o['customer_id'] ?? null,
                'nome' => self::nomeCompleto($billing),
                'email' => $billing['email'] ?? null,
                'telefono' => $billing['phone'] ?? null,
            ],

            'fatturazione' => self::indirizzo($billing),
            'spedizione' => self::indirizzo($o['shipping'] ?? []),

            'righe' => array_map(static fn (array $r): array => [
                'prodotto' => $r['name'] ?? null,
                'product_id' => $r['product_id'] ?? null,
                'variation_id' => $r['variation_id'] ?? null,
                'sku' => $r['sku'] ?? null,
                'quantita' => $r['quantity'] ?? null,
                'prezzo' => $r['price'] ?? null,
                'subtotale' => $r['subtotal'] ?? null,
                'totale' => $r['total'] ?? null,
            ], $o['line_items'] ?? []),

            'pagamento' => [
                'metodo' => $o['payment_method_title'] ?? $o['payment_method'] ?? null,
                'metodo_codice' => $o['payment_method'] ?? null,
                'transaction_id' => $o['transaction_id'] ?? null,
            ],

            'note' => $o['customer_note'] ?? null,
        ];
    }

    /**
     * Nome + cognome dal blocco billing (null se entrambi vuoti).
     *
     * @param  array<string,mixed>  $b
     */
    private static function nomeCompleto(array $b): ?string
    {
        $nome = trim(($b['first_name'] ?? '').' '.($b['last_name'] ?? ''));

        return $nome !== '' ? $nome : null;
    }

    /**
     * Normalizza un blocco indirizzo WooCommerce (billing/shipping).
     *
     * @param  array<string,mixed>  $a
     * @return array<string,mixed>
     */
    private static function indirizzo(array $a): array
    {
        return [
            'nome' => $a['first_name'] ?? null,
            'cognome' => $a['last_name'] ?? null,
            'azienda' => $a['company'] ?? null,
            'indirizzo_1' => $a['address_1'] ?? null,
            'indirizzo_2' => $a['address_2'] ?? null,
            'citta' => $a['city'] ?? null,
            'provincia' => $a['state'] ?? null,
            'cap' => $a['postcode'] ?? null,
            'paese' => $a['country'] ?? null,
        ];
    }
}
