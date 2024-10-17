<?php
// Add a custom tab for 'Saved Cards' to My Account page
add_filter( 'woocommerce_account_menu_items', 'paymob_add_saved_cards_tab', 40 );
function paymob_add_saved_cards_tab( $menu_links ) {
	$main_options    = get_option( 'woocommerce_paymob-main_settings' );
	$default_enabled = isset( $main_options['enabled'] ) ? $main_options['enabled'] : '';

	if ( 'yes' === $default_enabled ) {
		$menu_links = array_slice( $menu_links, 0, 5, true )
			+ array( 'saved-cards' => __( 'Paymob Saved Cards', 'paymob-woocommerce' ) )
			+ array_slice( $menu_links, 5, null, true );

	}
	return $menu_links;
}

// Add the endpoint for the 'Saved Cards' tab
add_action( 'init', 'paymob_add_saved_cards_endpoint' );
function paymob_add_saved_cards_endpoint() {
	add_rewrite_endpoint( 'saved-cards', EP_ROOT | EP_PAGES );
}
// Ensure WooCommerce recognizes the 'saved-cards' endpoint
add_filter( 'woocommerce_get_query_vars', 'paymob_add_saved_cards_query_vars', 0 );
function paymob_add_saved_cards_query_vars( $vars ) {
	$vars['saved-cards'] = 'saved-cards';
	return $vars;
}

// Set the title for the custom tab
add_filter( 'the_title', 'paymob_saved_cards_title', 10, 2 );
function paymob_saved_cards_title( $title, $id ) {
	if ( is_wc_endpoint_url( 'saved-cards' ) && in_the_loop() ) {
		$title = __( 'Paymob Saved Credit Cards', 'paymob-woocommerce' );
	}
	return $title;
}

// Content for the 'Saved Cards' tab
add_action( 'woocommerce_account_saved-cards_endpoint', 'paymob_display_saved_cards' );
function paymob_display_saved_cards() {
	// Get the current user ID
	$user_id = get_current_user_id();

	global $wpdb;
	// Fetch card tokens for the current user
	$cards = $wpdb->get_results( $wpdb->prepare( "SELECT id, masked_pan, card_subtype FROM {$wpdb->prefix}paymob_cards_token WHERE user_id = %d", $user_id ), OBJECT );

	if ( $cards ) {

		echo '<h4>' . esc_html( __( 'Saved Cards', 'paymob-woocommerce' ) ) . '</h4>';
		echo '<table class="shop_table shop_table_responsive">';
		echo '<thead><tr><th>' . esc_html( __( 'Card', 'paymob-woocommerce' ) ) . '</th><th>' . esc_html( __( 'Actions', 'paymob-woocommerce' ) ) . '</th></tr></thead>';
		echo '<tbody>';
		foreach ( $cards as $card ) {
			echo '<tr>';
			echo '<td>' . esc_html( $card->masked_pan ) . ' (' . esc_html( ucfirst( $card->card_subtype ) ) . ')</td>';
			echo '<td><a href="' . esc_url( wp_nonce_url( add_query_arg( 'delete_card_id', $card->id ), 'delete_card_' . $card->id ) ) . '" class="delete-card-icon"><img src="' . esc_url( plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/delete-icon.png' ) . '" alt="' . esc_attr__( 'Delete', 'paymob-woocommerce' ) . '" /></a></td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	} else {
		echo '<p>' . esc_html( __( 'No saved cards.', 'paymob-woocommerce' ) ) . '</p>';
	}
}

// Hook to handle card deletion
add_action( 'template_redirect', 'paymob_handle_card_deletion' );
function paymob_handle_card_deletion() {
	if ( Paymob::filterVar( 'delete_card_id' ) && is_user_logged_in() ) {
		$card_id = intval( Paymob::filterVar( 'delete_card_id' ) );
		$user_id = get_current_user_id();

		// Verify nonce for security
		if ( ! wp_verify_nonce( Paymob::filterVar( '_wpnonce' ), 'delete_card_' . $card_id ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'paymob_cards_token';

		// Ensure the card belongs to the logged-in user before deleting
		$wpdb->delete(
			$table_name,
			array(
				'id'      => $card_id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);

		// Optionally add a message or redirect after deletion
		wc_add_notice( __( 'Card deleted successfully.', 'paymob-woocommerce' ), 'success' );
		wp_safe_redirect( wc_get_account_endpoint_url( 'saved-cards' ) );
		exit;
	}
}

add_action( 'wp_footer', 'paymob_add_delete_confirmation_modal' );
function paymob_add_delete_confirmation_modal() {
	if ( is_wc_endpoint_url( 'saved-cards' ) ) {
		?>
		<div id="paymob-delete-modal" class="paymob-modal">
			<div class="paymob-modal-content">
				<span class="paymob-close">&times;</span>
				<p><?php echo esc_html( __( 'Are you sure you want to delete this card? This action cannot be undone.', 'paymob-woocommerce' ) ); ?></p>
				<button id="paymob-confirm-delete" class="button"><?php echo esc_html( __( 'Delete', 'paymob-woocommerce' ) ); ?></button>
				<button id="paymob-cancel-delete" class="button"><?php echo esc_html( __( 'Cancel', 'paymob-woocommerce' ) ); ?></button>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				var deleteUrl = ''; // Store the URL to delete the card

				// When the delete icon is clicked
				$('.delete-card-icon').on('click', function (e) {
					e.preventDefault(); // Prevent default action
					deleteUrl = $(this).attr('href'); // Store the deletion URL

					// Show the modal
					$('#paymob-delete-modal').fadeIn();
				});

				// Close modal when clicking the 'X' or 'Cancel' button
				$('.paymob-close, #paymob-cancel-delete').on('click', function () {
					$('#paymob-delete-modal').fadeOut();
				});

				// When the 'Delete' button is clicked
				$('#paymob-confirm-delete').on('click', function () {
					// Redirect to the delete URL to perform the deletion
					window.location.href = deleteUrl;
				});
			});
		</script>
		<?php
	}
}
add_action( 'wp_enqueue_scripts', 'paymob_enqueue_saved_cards_styles' );
function paymob_enqueue_saved_cards_styles() {
	if ( is_wc_endpoint_url( 'saved-cards' ) ) {
		wp_enqueue_style( 'paymob-save-cards', plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/css/save-cards.css', array(), PAYMOB_VERSION );
	}
}
