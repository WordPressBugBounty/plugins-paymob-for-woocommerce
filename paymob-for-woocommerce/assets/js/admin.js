jQuery( document ).ready(
	function () {
		jQuery( ".loader_paymob" ).fadeOut( 1500 );
		jQuery( 'textarea' ).removeClass( 'input-text wide-input ' );
		jQuery( 'input:text,textarea,select' ).attr( 'required', 'required' ).filter( ':visible' ).each(
			function (i, requiredField) {
				(jQuery( '#' + jQuery( requiredField ).attr( 'id' ) ).after( '<span class="red-star"> *</span>' ));

			}
		);
	}
);
jQuery( '#cpicon' ).click(
	function () {
		var copyText = document.getElementById( 'cburl' ).innerText;
		prompt( "Copy link, then click OK.", copyText );
	}
);
