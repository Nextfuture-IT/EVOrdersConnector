<?php
/**
 * Pagina impostazioni: gestione dell'API key (opzione 'evorders_api_key').
 * Submenu sotto WooCommerce. Se è definita la costante EVORDERS_API_KEY in
 * wp-config.php, quella ha precedenza e il campo è solo informativo.
 *
 * @package EVOrders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EVOrders_Settings {

	const OPTION = 'evorders_api_key';

	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
	}

	public function menu() {
		add_submenu_page(
			'woocommerce',
			'EV Orders API',
			'EV Orders API',
			'manage_woocommerce',
			'evorders',
			array( $this, 'render' )
		);
	}

	public function settings() {
		register_setting( 'evorders', self::OPTION, array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}

	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$via_costante = defined( 'EVORDERS_API_KEY' ) && EVORDERS_API_KEY;
		$valore       = get_option( self::OPTION, '' );
		?>
		<div class="wrap">
			<h1>EV Orders API</h1>
			<p>Endpoint ordini (sola lettura): <code><?php echo esc_html( rest_url( EVOrders_REST::NS . '/orders' ) ); ?></code></p>
			<p>Autenticazione: header <code>X-Api-Key: &lt;chiave&gt;</code></p>

			<?php if ( $via_costante ) : ?>
				<div class="notice notice-info inline"><p>
					La chiave è definita dalla costante <code>EVORDERS_API_KEY</code> in <code>wp-config.php</code> e ha precedenza su questo campo.
				</p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'evorders' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="evorders_api_key">API key</label></th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>" id="evorders_api_key" type="text"
								class="regular-text code" value="<?php echo esc_attr( $valore ); ?>" autocomplete="off" />
							<p class="description">Consigliata una stringa casuale lunga (es. <code>wp_generate_password(48,false)</code>). Condividila solo col SW chiamante.</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
