jQuery(document).ready(function ($) {
    var tableBodyHtml = window.paymob_gateways_table_body || '';
    $('#paymob_custom_gateways tbody').html(tableBodyHtml);
    
    function showConfirmationModal(title, message, confirmCallback, cancelCallback) {
        $('#confirmation-modal-title').text(title);
        $('#confirmation-modal-message').text(message);
        $('#confirmation-modal').show();
        $('#confirmation-modal-confirm').off('click').on('click', function () {
            $('#confirmation-modal').hide();
            if (typeof confirmCallback === 'function') {
                confirmCallback();
            }
        });
        $('#confirmation-modal-cancel').off('click').on('click', function () {
            $('#confirmation-modal').hide();
            if (typeof cancelCallback === 'function') {
                cancelCallback();
            }
        });
    }

    $('#paymob_custom_gateways tbody').on('click', '.remove-button', function () {
        var button = $(this);
        var gatewayId = button.data('gateway-id');
        var nonce = paymob_admin_ajax.delete_nonce;

        showConfirmationModal(
            paymob_admin_ajax.rg,
            paymob_admin_ajax.ays,
            function () {
                $.ajax({
                    url: paymob_admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_gateway',
                        security: nonce,
                        gateway_id: gatewayId,
                    },
                    success: function (response) {
                        if (response.success) {
                            button.closest('tr').remove();
                        } else {
                            alert('Failed to delete gateway: ' + response.data.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        alert('AJAX error: ' + status + ' - ' + error);
                    }
                });
            }
        );
    });

    $('#paymob_custom_gateways tbody').on('change', '.enable-checkbox', function () {
        var checkbox = $(this);
        var action = checkbox.prop('checked') ? 'enable' : 'disable';
        var gatewayId = checkbox.data('gateway-id');
        var integrationId = checkbox.data('integration-id');
        var nonce = paymob_admin_ajax.toggle_nonce;

        showConfirmationModal(
            action.charAt(0).toUpperCase() + action.slice(1) + paymob_admin_ajax.gat,
            paymob_admin_ajax.ay + action + paymob_admin_ajax.tg,
            function () {
                $.ajax({
                    url: paymob_admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'toggle_gateway',
                        security: nonce,
                        gateway_id: gatewayId,
                        integration_id: integrationId,
                        enable: checkbox.prop('checked')
                    },
                    success: function (response) {
                        if (response.success) {
                            jQuery('.wrap').prepend('<div class="notice notice-success is-dismissible"><p>' + response.data.msg + '</p></div>');
                        } else {
                            jQuery('.wrap').prepend('<div class="notice notice-error is-dismissible"><p>' + response.data.msg + '</p></div>');
                            checkbox.prop('checked', false);
                        }
                        setTimeout(function () {
                            jQuery('.notice').fadeOut();
                        }, 5000);
                    },
                    error: function (xhr, status, error) {
                        alert('AJAX error: ' + status + ' - ' + error);
                    }
                });
            },
            function () {
                checkbox.prop('checked', !checkbox.prop('checked'));
            }
        );
    });
    $('body').on('click', '.show-more', function () {
        var $this = $(this);
        var shortDescription = $this.siblings('.short-description');
        var fullDescription = $this.siblings('.full-description');

        if (fullDescription.is(':hidden')) {
            shortDescription.hide();
            fullDescription.show();
            $this.text('Show Less');
        } else {
            shortDescription.show();
            fullDescription.hide();
            $this.text('Show More');
        }
    });

    $("#paymob_custom_gateways tbody").sortable({
        items: "tr",
        dropOnEmpty: false,
        update: function (event, ui) {
            const order = $(this).sortable('toArray', { attribute: 'data-gateway-id' });
            // Send the order to the server via AJAX
            $.ajax({
                url: paymob_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'save_paymob_gateway_order',
                    order: order,
                    security: paymob_admin_ajax.save_gateway_order_nonce,
                },
                success: function (response) {
                    //alert('Order saved successfully!');
                },
                error: function () {
                    alert('Failed to save order.');
                }
            });
        }
    });
    $("#reset-paymob-gateways").click(function () {
        showConfirmationModal(
            paymob_admin_ajax.rp,
            paymob_admin_ajax.arp,
            function () {
                // Confirm callback: AJAX call to reset the gateways
                // alert('reset');return false;
                $.ajax({
                    url: paymob_admin_ajax.ajax_url,
                    type: "POST",
                    data: {
                        action: "reset_paymob_gateways",
                        security: paymob_admin_ajax.reset_paymob_gateways_nonce,
                    },
                    beforeSend: function () {
                        $(".loader_paymob").show();
                    },
                    success: function (response) {
                        
                        if(!response.success){
                            const data=JSON.parse(response.data);
                            alert(data.error);
                        }
                        location.reload();
                    },
                    complete: function () {
                        // $(".loader_paymob").hide();
                    },
                    error: function (xhr, status, error) {
                        alert('An error occurred while resetting the payment methods.');
                    }
                });
            },
            function () {
                // Cancel callback: No action needed, modal just closes
                //console.log("Reset canceled");
            }
        );
    });
    // Disable WooCommerce's and any other `beforeunload` handlers
    function disableBeforeUnload() {
        $(window).off('beforeunload');
        window.onbeforeunload = null;
    }

    // Run initially
    disableBeforeUnload();

    // Monitor changes and remove any added handlers every second
    setInterval(function () {
        disableBeforeUnload();
    }, 1000);

});