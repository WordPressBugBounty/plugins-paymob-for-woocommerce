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

document.addEventListener("DOMContentLoaded", function () {
    function addWarningMessage(selectId, messageText, email, emailColor, bgColor, borderColor) {
        const selectElement = document.getElementById(selectId);
        if (!selectElement) {
            console.error(`Element with ID ${selectId} not found!`);
            return;
        }

        // Prevent duplicates
        if (selectElement.parentNode.querySelector(".warning-message")) {
            return;
        }

        // Create the warning message container
        const warningMessage = document.createElement("div");
        warningMessage.className = "warning-message"; 
        warningMessage.innerHTML = `⚠️ <strong>Warning:</strong> ${messageText} 
            <a href='mailto:${email}' style='color: ${emailColor}; font-weight: bold; text-decoration: underline;'>${email}</a>.`;

        // Apply improved styling
        warningMessage.style.display = "none";
        warningMessage.style.backgroundColor = bgColor;
        warningMessage.style.border = `1px solid ${borderColor}`;
        warningMessage.style.color = "#333"; 
        warningMessage.style.padding = "8px";
        warningMessage.style.marginTop = "6px";
        warningMessage.style.borderRadius = "5px";
        warningMessage.style.fontSize = "13px";
        warningMessage.style.fontWeight = "500";
        warningMessage.style.boxShadow = `0px 0px 4px ${borderColor}`;
        warningMessage.style.width = `${selectElement.offsetWidth}px`; // Set width same as input field
        warningMessage.style.maxWidth = "100%"; // Prevent overflow
        warningMessage.style.boxSizing = "border-box"; // Ensure proper sizing

        // Append message
        selectElement.parentNode.appendChild(warningMessage);

        // Show/hide message on change
        selectElement.addEventListener("change", function () {
            warningMessage.style.display = selectElement.value ? "block" : "none";
            warningMessage.style.width = `${selectElement.offsetWidth}px`; // Adjust width on change
        });
    }

    // Apple Pay - Blue Theme
    addWarningMessage(
        "apple_pay_integration_id", 
        "Please do not select an Integration ID if your domain is not verified with Apple Pay. To verify your domain, reach out to your account manager or contact us at", 
        "support@paymob.com",
        "#007bff",   // Email color (Blue)
        "#e7f1ff",   // Background color (Light Blue)
        "#007bff"    // Border color (Blue)
    );

    // Google Pay - Green Theme
    addWarningMessage(
        "google_pay_integration_id", 
        "Please do not select an Integration ID if your domain is not verified with Google Pay. To verify your domain, reach out to your account manager or contact us at", 
        "support@paymob.com",
        "#007bff",   // Email color (Blue)
        "#e7f1ff",   // Background color (Light Blue)
        "#007bff"    // Border color (Blue)
    );
});

jQuery(document).ready(function($) {
   
    let shouldUncheck = valuWidgetData.shouldUncheck === 'true';
    if (shouldUncheck) {
        setTimeout(function() {
            let darkModeCheckbox = $('input[name="dark_mode"]');
            let enable_widget=$('input[name="enable"]');
            let integrationSelect = $('select[name="integration_id"]');
            if (darkModeCheckbox.length) {
                darkModeCheckbox.prop('checked', false); // Force uncheck
            }
            if (enable_widget.length) {
                enable_widget.prop('checked', false); // Force uncheck
            }
             // Ensure Integration ID dropdown has "Please Select" as default
            if (integrationSelect.length) {
                let integrationOptions = integrationSelect.find('option'); // Get all option elements
                if (integrationOptions.length > 1) {
                    integrationSelect.prepend('<option value="" selected>Please Select Enabled Valu integration</option>');
                    integrationSelect.val(''); 
                }
            }
        }, 500); // Delay to allow WooCommerce settings to load
    }
});



