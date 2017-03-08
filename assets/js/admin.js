(function ( $ ) {
	'use strict';

	$( function () {

		/**
		 * Switch user data for sandbox and production.
		 *
		 * @param {String} checked
		 */
		function AsaasSwitchTokenData( checked ) {
			var token        = $( '#woocommerce_asaas_token' ).closest( 'tr' ),
				sandboxToken = $( '#woocommerce_asaas_sandbox_token' ).closest( 'tr' );

			if ( checked ) {
				token.hide();
				sandboxToken.show();
			} else {
				token.show();
				sandboxToken.hide();
			}
		}


		AsaasSwitchTokenData( $( '#woocommerce_asaas_sandbox' ).is( ':checked' ) );
		$( 'body' ).on( 'change', '#woocommerce_asaas_sandbox', function () {
			AsaasSwitchTokenData( $( this ).is( ':checked' ) );
		});
	});

}( jQuery ));
