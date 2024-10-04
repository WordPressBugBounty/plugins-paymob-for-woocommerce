jQuery( document ).ready(
	function () {

		jQuery( ".loader_paymob" ).fadeOut(
			1500,
			function () {
				jQuery( '#woocommerce_paymob_integration_id' ).html( '' );
				var integration_hidden = ajax_object.integration_hidden.split( "," );
				jQuery( '#woocommerce_paymob_hmac' ).val( ajax_object.hmac_hidden );
				if (ajax_object.integration_hidden.length > 0) {
					jQuery.each(
						integration_hidden,
						function (av_i, av_id) {
							var selected = '';
							if (av_id !== '') {
								var integrationId = av_id.split( " :" );
								jQuery.each(
									ajax_object.integration_id,
									function (i, id) {
										if (integrationId === id || parseInt( integrationId ) === parseInt( id )) {
											selected = 'selected';
										}
									}
								);
							}
							jQuery( '#woocommerce_paymob_integration_id' ).append( "<option " + selected + " value=" + av_id + ">" + av_id + "</option>" );

						}
					);
				}
			}
		);

		jQuery( '#accept-login' ).click(
			function () {
				callAjax();
			}
		);
		function callAjax() {
			if (jQuery( '#woocommerce_paymob_api_key' ).val().length === 0
				|| jQuery( '#woocommerce_paymob_pub_key' ).val().length === 0
				|| jQuery( '#woocommerce_paymob_sec_key' ).val().length === 0) {
				alert( 'Please provide Paymob API, public and secret keys' );
			} else {
				jQuery( ".loader_paymob" ).css( 'display', 'block' );
				let post = wp.ajax.post(
					"get_paymob_info",
					{
						api_key: jQuery( '#woocommerce_paymob_api_key' ).val(),
						pub_key: jQuery( '#woocommerce_paymob_pub_key' ).val(),
						sec_key: jQuery( '#woocommerce_paymob_sec_key' ).val()
					}
				);
				post.done(
					function (response) {
						var data = JSON.parse( response );
						if (data.success === true) {
							jQuery( '#woocommerce_paymob_hmac' ).val( data.data.hmac );
							jQuery( '#woocommerce_paymob_hmac_hidden' ).val( data.data.hmac );
							var html = '';
							var ids  = '';

							jQuery.each(
								data.data.integrationIDs,
								function (i, integration) {
									var text = integration.id + " : " + integration.name + " (" + integration.type + " : " + integration.currency + " )";
									ids      = ids + text + ',';
									if (ajax_object.integration_id.length > 0) {
										var selected = '';
										jQuery.each(
											ajax_object.integration_id,
											function (ii, id) {
												if (integration.id === id || parseInt( integration.id ) === parseInt( id )) {
													selected = 'selected';
												}
											}
										);
									}
									html = html + "<option " + selected + " value=" + integration.id + ">" + text + "</option>";
								}
							);
							jQuery( '#woocommerce_paymob_integration_id_hidden' ).val( ids );
							if (html) {
								jQuery( '#woocommerce_paymob_integration_id' ).html( html );
							}
						}
						jQuery( ".loader_paymob" ).fadeOut( 10 );
						// jQuery(".success_load").css('display', 'block');
						// jQuery(".success_load").fadeOut(500);

						jQuery( '#paymob-not-valid' ).css( 'display', 'none' );
						jQuery( '#paymob-valid' ).css( 'display', 'inline-block' );
					}
				);
				post.fail(
					function (response) {
						var data = JSON.parse( response );
						jQuery( ".loader_paymob" ).fadeOut( 10 );
						alert( data.error );
						// jQuery(".failed_load").css('display', 'block');
						// jQuery(".failed_load").fadeOut(500);
						jQuery( '#paymob-not-valid' ).css( 'display', 'inline-block' );
						jQuery( '#paymob-valid' ).css( 'display', 'none' );
					}
				);

			}
		}
	}
);
jQuery( '#cpicon' ).click(
	function () {
		var copyText = document.getElementById( 'cburl' ).innerText;
		prompt( "Copy link, then click OK.", copyText );
	}
);
