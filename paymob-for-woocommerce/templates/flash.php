<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<input type="hidden" disabled data-paymobVersion="<?php echo esc_attr( PAYMOB_VERSION ); ?>" />
<?php
$this->get_parent_payment_fields();
?>