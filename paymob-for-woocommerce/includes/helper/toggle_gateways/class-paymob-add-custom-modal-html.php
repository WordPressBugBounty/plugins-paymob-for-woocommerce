<?php
class Paymob_Custom_Model {

	public static function add_custom_modal_html() {
		$screen = get_current_screen();
		global $wpdb;
		$gateways = PaymobAutoGenerate::get_db_gateways_data();

		if ( 'woocommerce_page_wc-settings' === $screen->id && Paymob::filterVar( 'tab' ) && 'checkout' === Paymob::filterVar( 'tab' ) ) {
			?>
			<!-- Custom Confirmation Modal -->
			<div id="confirmationModal">
				<h2>Disable Paymob Gateway</h2>
				<p>If you disable this gateway, all Paymob gateways will be disabled. Do you want to continue?</p>
				<ul>
					<?php
					foreach ( $gateways as $gateway ) {
						$options = get_option( 'woocommerce_' . $gateway->gateway_id . '_settings', array() );
						$enabled = isset( $options['enabled'] ) && 'yes' === $options['enabled'];
						if ( $enabled ) {
							$logo  = isset( $options['logo'] ) ? esc_url( $options['logo'] ) : '';
							$title = isset( $options['title'] ) ? esc_html( $options['title'] ) : 'Unknown Gateway';
							?>
							<li>
								<img src="<?php echo esc_url( $logo ); ?>" width="36" height="23" alt="<?php echo esc_attr( $title ); ?>">
								<?php echo esc_html( $title ); ?>
							</li>
							<?php
						}
					}
					?>
				</ul>
				<button id="confirmDisable">Disable</button>
				<button id="confirmCancel">Cancel</button>
			</div>
			<div id="overlay"></div>
			<?php
		}
	}
}