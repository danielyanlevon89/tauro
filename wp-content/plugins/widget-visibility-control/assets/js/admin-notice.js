/**
 * Widget Visibility Control - Admin Notice
 *
 * @package ayudawp/widget-visibility-control
 */

/* global jQuery, ajaxurl, ayudawp_wvc_notice */

( function( $ ) {
	'use strict';

	$( function() {
		$( '#ayudawp-wvc-dismiss-notice, #ayudawp-wvc-notice .notice-dismiss' ).on( 'click', function() {
			$.post( ajaxurl, {
				action: 'ayudawp_wvc_dismiss_jetpack_notice',
				nonce: ayudawp_wvc_notice.nonce
			} );
			$( '#ayudawp-wvc-notice' ).fadeOut();
		} );
	} );

}( jQuery ) );
