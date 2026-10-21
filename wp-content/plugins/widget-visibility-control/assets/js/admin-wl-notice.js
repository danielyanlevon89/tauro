/**
 * Widget Visibility Control - Widget Logic admin notice dismiss handler.
 *
 * @package ayudawp/widget-visibility-control
 */

/* global jQuery, ajaxurl, ayudawp_wvc_wl_notice */

( function( $ ) {
	'use strict';

	$( function() {
		$( '#ayudawp-wvc-wl-dismiss-notice, #ayudawp-wvc-wl-notice .notice-dismiss' ).on( 'click', function() {
			$.post( ajaxurl, {
				action: ayudawp_wvc_wl_notice.action,
				nonce: ayudawp_wvc_wl_notice.nonce
			} );
			$( '#ayudawp-wvc-wl-notice' ).fadeOut();
		} );
	} );

}( jQuery ) );
