jQuery(document).ready(function($) {
    // Remove pipe separator '|' from list items
    $('.subsubsub li').each(function() {
        var html = $(this).html().replace(/\s*\|\s*/g, ''); // Remove the '|' and spaces around it
        $(this).html(html);
    });
});

jQuery(document).ready(function ($) {
    // Initialize Select2 for the Cards Integration ID
    $('#cards_integration_id').select2({
        placeholder: 'Select Integration ID(s)',
        allowClear: true,
        width: '100%', // Ensure full width
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const resetButton = document.getElementById('reset-defaults');

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            // Default values
            const defaults = {
                'font_family': 'Gotham',
                'font_size_label': '16',
                'font_size_input_fields': '16',
                'font_size_payment_button': '14',
                'font_weight_label': '400',
                'font_weight_input_fields': '200',
                'font_weight_payment_button': '600',
                'color-container': '#FFFFFF',
                'color_border_input_fields': '#D0D5DD',
                'color_border_payment_button': '#A1B8FF',
                'radius-border': '8',
                'color-disabled': '#A1B8FF',
                'color-error': '#CC1142',
                'color-primary': '#144DFF',
                'color-input-fields': '#FFFFFF',
                'text_color_for_label': '#000000',
                'text_color_for_payment_button': '#FFFFFF',
                'text_color_for_input_fields': '#000000',
                'color_for_text_placeholder': '#667085',
                'width-of-container': '100',
                'vertical_padding': '40',
                'vertical_spacing_between_components': '18',
                'container_padding': '0'
            };
            // Iterate over the default values and reset the corresponding inputs
            for (const [key, value] of Object.entries(defaults)) {
                const element = document.querySelector(`[name="woocommerce_paymob_pixel_${key.replace(/-/g, '_')}"]`);
                if (element) {
                    element.value = value;
                     // Trigger change event to mark the field as updated To Active Save Change Button
                     const event = new Event('change', { bubbles: true });
                     element.dispatchEvent(event);
                }
            }

        });
    }
});

jQuery(document).ready(function ($) {
    // Target the specific checkboxes by their IDs
    $('#show_save_card').on('change', function () {
        if ($(this).is(':checked')) {
            $('#force_save_card').prop('checked', false);
        }
    });

    $('#force_save_card').on('change', function () {
        if ($(this).is(':checked')) {
            $('#show_save_card').prop('checked', false);
        }
    });
});

