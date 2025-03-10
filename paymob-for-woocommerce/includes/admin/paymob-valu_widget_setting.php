<?php
if (!defined('ABSPATH')) {
    exit;
}
// Fetch the ValU integration IDs
$valu_integration_ids = PaymobAutoGenerate::get_valu_integration_ids();
$option_valu_widget = get_option('woocommerce_valu_widget_settings'); 

$should_uncheck = ($option_valu_widget === false || empty($option_valu_widget['dark_mode'])) ? 'true' : 'false';

add_action('admin_footer', function() use ($should_uncheck) {
    ?>
    <script>
        jQuery(document).ready(function($) {
            console.log("Dark mode script loaded"); // Debugging

            let shouldUncheck = <?php echo $should_uncheck; ?>; // Pass PHP variable to JS
            console.log("Should uncheck:", shouldUncheck);

            if (shouldUncheck) {
                setTimeout(function() {
                    let darkModeCheckbox = $('input[name="dark_mode"]');
                    let enable_widget=$('input[name="enable"]');
                    if (darkModeCheckbox.length) {
                        darkModeCheckbox.prop('checked', false); // Force uncheck
                    }
                    if (enable_widget.length) {
                        enable_widget.prop('checked', false); // Force uncheck
                    }
                }, 500); // Delay to allow WooCommerce settings to load
            }
        });
    </script>
    <?php
});


// Determine the description based on whether integration IDs exist
$integration_description = empty($valu_integration_ids)
    ? '<strong style="color: red;">Please enable a ValU Integration ID from the Payment Integrations section to use the ValU Widget.</strong>'
    : 'Choose the Integration ID for the ValU Widget.';
$settings = array(
    'section_title' => array(
        'title' => __( 'ValU Widget Settings', 'paymob-woocommerce' ),
        'type'  => 'title',
        'id'    => 'valu_widget_section_title',
    ),
    'enable_widget' => array(
        'title'       => __( 'Enable ValU Widget', 'paymob-woocommerce' ),
        'type'        => 'checkbox',
        'id'          => 'enable',  // ✅ Added ID
        'default'     => 'no',
        'label'       => ' ',
        'description' => __( 'Enable or disable the ValU widget.', 'paymob-woocommerce' ),
    ),
    'integration_id' => array(
        'title'       => __( 'Select Integration ID for ValU Widget', 'paymob-woocommerce' ),
        'type'        => 'select',
        'id'          => 'integration_id',  // ✅ Added ID
        'options'     => $valu_integration_ids,
        'default'     => '',
        'label'       => ' ',
      
        'description' => empty($valu_integration_ids) 
        ? '<div style="background: #ffecec; border-left: 4px solid red; padding: 10px; margin-bottom: 10px;">
                <strong style="color: red;">Warning:</strong> Please enable a ValU Integration ID from the Payment Integrations section to use the ValU Widget.
           </div>' 
        : 'Choose the Integration ID for the ValU Widget.',
  
        
    ),
    'dark_mode' => array(
        'title'       => __( 'Enable Dark Mode', 'paymob-woocommerce' ),
        'type'        => 'checkbox',
        'id'          => 'dark_mode',  // ✅ Added ID
        'default'     => 'no',
        'label'       => ' ',
        'description' => __( 'This controls the style mode for ValuWidget.', 'paymob-woocommerce' ),
    ),
    'section_end' => array(
        'type' => 'sectionend',
        'id'   => 'valu_widget_section_end',
    ),
);

return $settings;



