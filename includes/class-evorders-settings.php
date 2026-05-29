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

	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$via_costante = defined( 'EVORDERS_API_KEY' ) && EVORDERS_API_KEY;

		// Rigenerazione chiave (POST con nonce).
		if ( ! $via_costante
			&& isset( $_POST['evorders_regen'] )
			&& check_admin_referer( 'evorders_regen' ) ) {
			update_option( self::OPTION, wp_generate_password( 64, false, false ) );
			echo '<div class="notice notice-success inline"><p>Nuova API key generata.</p></div>';
		}

		$valore = get_option( self::OPTION, '' );
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

			<?php if ( $via_costante ) : ?>
				<p class="description">La chiave attiva è quella della costante <code>EVORDERS_API_KEY</code> (non mostrata qui).</p>
			<?php else : ?>
				<?php if ( '' === $valore ) : ?>
					<div class="notice notice-warning inline"><p>Nessuna API key impostata. Generala qui sotto.</p></div>
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="evorders_api_key">API key</label></th>
						<td>
							<input id="evorders_api_key" type="text" class="regular-text code"
								value="<?php echo esc_attr( $valore ); ?>" readonly autocomplete="off"
								onclick="this.select();" />
							<p class="description">
								Generata automaticamente all'attivazione del plugin. Click per selezionarla e copiarla.
								Condividila solo col SW chiamante.
							</p>
						</td>
					</tr>
				</table>

				<form method="post">
					<?php wp_nonce_field( 'evorders_regen' ); ?>
					<input type="hidden" name="evorders_regen" value="1" />
					<?php submit_button( '' === $valore ? 'Genera chiave' : 'Rigenera chiave', 'primary', 'submit', true, array( 'onclick' => "return confirm('Rigenerare l\\'API key? Il SW chiamante andrà aggiornato.');" ) ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}
