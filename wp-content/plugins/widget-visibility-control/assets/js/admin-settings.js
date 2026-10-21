/**
 * Widget Visibility Control - Admin Settings
 *
 * @package ayudawp/widget-visibility-control
 */

/* global jQuery, ajaxurl, ayudawp_wvc_settings */

( function( $ ) {
	'use strict';

	$( function() {
		$( '#ayudawp-wvc-clean-legacy' ).on( 'click', function() {
			var $button = $( this );
			var $result = $( '#ayudawp-wvc-clean-result' );

			if ( ! window.confirm( ayudawp_wvc_settings.confirm_clean ) ) {
				return;
			}

			$button.prop( 'disabled', true ).text( ayudawp_wvc_settings.cleaning );

			$.post( ajaxurl, {
				action: 'ayudawp_wvc_clean_legacy',
				nonce: ayudawp_wvc_settings.clean_nonce
			}, function( response ) {
				if ( response.success ) {
					$result.html( '<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>' );
					$button.text( ayudawp_wvc_settings.clean_button );
				} else {
					$result.html( '<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>' );
					$button.prop( 'disabled', false ).text( ayudawp_wvc_settings.clean_button );
				}
			} );
		} );

		$( '#ayudawp-wvc-remigrate' ).on( 'click', function() {
			var $button = $( this );
			var $result = $( '#ayudawp-wvc-remigrate-result' );

			$button.prop( 'disabled', true ).text( ayudawp_wvc_settings.importing );

			$.post( ajaxurl, {
				action: 'ayudawp_wvc_remigrate',
				nonce: ayudawp_wvc_settings.remigrate_nonce
			}, function( response ) {
				$button.prop( 'disabled', false ).text( ayudawp_wvc_settings.reimport_button );
				if ( response.success ) {
					$result.html( '<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>' );
				} else {
					$result.html( '<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>' );
				}
			} );
		} );

		$( '#ayudawp-wvc-clean-wl' ).on( 'click', function() {
			var $button = $( this );
			var $result = $( '#ayudawp-wvc-clean-wl-result' );

			if ( ! window.confirm( ayudawp_wvc_settings.confirm_wl ) ) {
				return;
			}

			$button.prop( 'disabled', true ).text( ayudawp_wvc_settings.cleaning );

			$.post( ajaxurl, {
				action: 'ayudawp_wvc_clean_wl',
				nonce: ayudawp_wvc_settings.clean_wl_nonce
			}, function( response ) {
				if ( response.success ) {
					$result.html( '<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>' );
					$button.text( ayudawp_wvc_settings.clean_wl_button );
				} else {
					$result.html( '<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>' );
					$button.prop( 'disabled', false ).text( ayudawp_wvc_settings.clean_wl_button );
				}
			} );
		} );
	} );

}( jQuery ) );
