<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Endpoint ordini WooCommerce multi-store (/api/v1/woocommerce/{store}/orders[/{id}]).
 * Tutto via Http::fake: NESSUNA chiamata reale a WooCommerce. Copre lista, dettaglio,
 * mapping filtri, header di paginazione, Basic Auth, 404 (store/ordine), 500/502/422/403, audit.
 */
class OrdiniTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'connettore.ip_whitelist' => ['127.0.0.1'],
            'woocommerce.stores' => [
                'negozio1' => [
                    'store_url' => 'https://store1.test',
                    'consumer_key' => 'ck',
                    'consumer_secret' => 'cs',
                    'api_version' => 'wc/v3',
                    'timeout' => 5,
                ],
            ],
        ]);
    }

    /**
     * Ordine WooCommerce raw di esempio.
     *
     * @return array<string,mixed>
     */
    private function ordineWc(int $id = 123): array
    {
        return [
            'id' => $id,
            'number' => (string) $id,
            'status' => 'completed',
            'currency' => 'EUR',
            'prices_include_tax' => true,
            'date_created_gmt' => '2026-05-01T10:00:00',
            'total' => '49.90',
            'total_tax' => '9.00',
            'shipping_total' => '5.00',
            'discount_total' => '0.00',
            'customer_id' => 7,
            'customer_note' => 'Lasciare al vicino',
            'payment_method' => 'stripe',
            'payment_method_title' => 'Carta di credito',
            'transaction_id' => 'pi_123',
            'billing' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'company' => 'Acme',
                'address_1' => 'Via Roma 1',
                'city' => 'Foggia',
                'state' => 'FG',
                'postcode' => '71100',
                'country' => 'IT',
                'email' => 'mario@x.it',
                'phone' => '0881000000',
            ],
            'shipping' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address_1' => 'Via Roma 1',
                'city' => 'Foggia',
            ],
            'line_items' => [[
                'id' => 1,
                'name' => 'Olio EVO 1L',
                'product_id' => 55,
                'variation_id' => 0,
                'sku' => 'OLIO-1L',
                'quantity' => 2,
                'price' => 19.95,
                'subtotal' => '39.90',
                'total' => '39.90',
            ]],
        ];
    }

    public function test_lista_ordini_restituisce_dto_normalizzato(): void
    {
        Http::fake([
            '*/orders*' => Http::response([$this->ordineWc()], 200, [
                'X-WP-Total' => '1',
                'X-WP-TotalPages' => '1',
            ]),
        ]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders')
            ->assertOk()
            ->assertJsonPath('store', 'negozio1')
            ->assertJsonPath('dati.0.numero', '123')
            ->assertJsonPath('dati.0.stato', 'completed')
            ->assertJsonPath('dati.0.iva_inclusa', true)
            ->assertJsonPath('dati.0.totali.totale', '49.90')
            ->assertJsonPath('dati.0.cliente.email', 'mario@x.it')
            ->assertJsonPath('dati.0.cliente.nome', 'Mario Rossi')
            ->assertJsonPath('dati.0.righe.0.prodotto', 'Olio EVO 1L')
            ->assertJsonPath('dati.0.righe.0.quantita', 2)
            ->assertJsonPath('dati.0.pagamento.metodo', 'Carta di credito');
    }

    public function test_chiama_lo_store_giusto(): void
    {
        Http::fake(['*' => Http::response([], 200, ['X-WP-Total' => '0', 'X-WP-TotalPages' => '0'])]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders')->assertOk();

        // baseUrl dello store risolto dallo slug.
        Http::assertSent(fn ($request) => str_contains($request->url(), 'https://store1.test/wp-json/wc/v3/orders'));
    }

    public function test_dettaglio_ordine(): void
    {
        Http::fake([
            '*/orders/123' => Http::response($this->ordineWc(123), 200),
        ]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders/123')
            ->assertOk()
            ->assertJsonPath('id', 123)
            ->assertJsonPath('numero', '123')
            ->assertJsonPath('fatturazione.citta', 'Foggia');
    }

    public function test_filtri_passati_a_woocommerce(): void
    {
        Http::fake(['*' => Http::response([], 200, ['X-WP-Total' => '0', 'X-WP-TotalPages' => '0'])]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders?status=processing&per_page=50&customer=7&after=2026-01-01')
            ->assertOk();

        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, 'status=processing')
                && str_contains($url, 'per_page=50')
                && str_contains($url, 'customer=7')
                && str_contains($url, 'after=2026-01-01');
        });
    }

    public function test_pagination_headers_surfacciati(): void
    {
        Http::fake([
            '*/orders*' => Http::response([$this->ordineWc()], 200, [
                'X-WP-Total' => '137',
                'X-WP-TotalPages' => '7',
            ]),
        ]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders?per_page=20')
            ->assertOk()
            ->assertJsonPath('paginazione.totale', 137)
            ->assertJsonPath('paginazione.pagine_totali', 7)
            ->assertJsonPath('paginazione.per_pagina', 20)
            ->assertHeader('X-WP-Total', '137')
            ->assertHeader('X-WP-TotalPages', '7');
    }

    public function test_include_csv_diventa_array(): void
    {
        Http::fake(['*' => Http::response([], 200, ['X-WP-Total' => '0', 'X-WP-TotalPages' => '0'])]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders?include=12,15,20')->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'include%5B0%5D=12')
            && str_contains($request->url(), 'include%5B2%5D=20'));
    }

    public function test_basic_auth_inviata(): void
    {
        Http::fake(['*' => Http::response([], 200, ['X-WP-Total' => '0', 'X-WP-TotalPages' => '0'])]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders')->assertOk();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Basic '.base64_encode('ck:cs')));
    }

    public function test_404_store_sconosciuto(): void
    {
        Http::fake();

        $this->getJson('/api/v1/woocommerce/inesistente/orders')->assertStatus(404);
        Http::assertNothingSent();
    }

    public function test_404_ordine_non_trovato(): void
    {
        Http::fake([
            '*/orders/999' => Http::response(['code' => 'woocommerce_rest_shop_order_invalid_id'], 404),
        ]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders/999')->assertStatus(404);
    }

    public function test_500_se_store_non_configurato(): void
    {
        // Store presente in mappa ma senza credenziali.
        config(['woocommerce.stores.negozio1.store_url' => null]);
        Http::fake();

        $this->getJson('/api/v1/woocommerce/negozio1/orders')->assertStatus(500);
        Http::assertNothingSent();
    }

    public function test_502_se_upstream_non_2xx(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders')->assertStatus(502);
    }

    public function test_422_filtri_non_validi(): void
    {
        Http::fake();

        $this->getJson('/api/v1/woocommerce/negozio1/orders?per_page=500')->assertStatus(422);
        $this->getJson('/api/v1/woocommerce/negozio1/orders?status=bogus')->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_403_ip_non_in_whitelist(): void
    {
        config(['connettore.ip_whitelist' => ['10.0.0.99']]);
        Http::fake();

        $this->getJson('/api/v1/woocommerce/negozio1/orders')->assertStatus(403);
        Http::assertNothingSent();
    }

    public function test_audit_registrato_con_store(): void
    {
        Http::fake([
            '*/orders*' => Http::response([$this->ordineWc()], 200, [
                'X-WP-Total' => '1',
                'X-WP-TotalPages' => '1',
            ]),
        ]);

        $this->getJson('/api/v1/woocommerce/negozio1/orders')->assertOk();

        $this->assertDatabaseHas('activity_log', ['log_name' => 'connettore']);
    }
}
