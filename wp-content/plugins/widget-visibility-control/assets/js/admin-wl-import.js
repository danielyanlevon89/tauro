/**
 * Widget Visibility Control - Widget Logic import wizard
 *
 * Adds the bulk-mark shortcuts and a pre-submit confirmation summary.
 *
 * @package ayudawp/widget-visibility-control
 */

/* global jQuery, ayudawp_wvc_wl_import */

( function( $ ) {
	'use strict';

	function bulkSelect( selector ) {
		$( '.ayudawp-wvc-wl-row' ).each( function() {
			var $row = $( this );
			var $radio = $row.find( selector );
			if ( $radio.length ) {
				$radio.prop( 'checked', true );
			}
		} );
	}

	function formatSummary( template, n1, n2, n3, n4 ) {
		return template
			.replace( '%1$d', n1 )
			.replace( '%2$d', n2 )
			.replace( '%3$d', n3 )
			.replace( '%4$d', n4 );
	}

	$( function() {
		$( '#ayudawp-wvc-wl-mark-all-import' ).on( 'click', function( e ) {
			e.preventDefault();
			bulkSelect( '.ayudawp-wvc-wl-radio-import' );
		} );

		$( '#ayudawp-wvc-wl-mark-all-skip' ).on( 'click', function( e ) {
			e.preventDefault();
			bulkSelect( '.ayudawp-wvc-wl-radio-skip' );
		} );

		$( '#ayudawp-wvc-wl-form' ).on( 'submit', function( e ) {
			var imports = 0;
			var visible = 0;
			var hidden = 0;
			var skipped = 0;

			$( this ).find( 'input[type="radio"]:checked' ).each( function() {
				var val = $( this ).val();
				if ( 'import' === val ) {
					imports++;
				} else if ( 'visible' === val ) {
					visible++;
				} else if ( 'hidden' === val ) {
					hidden++;
				} else if ( 'skip' === val ) {
					skipped++;
				}
			} );

			$( this ).find( 'input[type="hidden"]' ).each( function() {
				if ( 'import' === $( this ).val() && $( this ).attr( 'name' ) && 0 === $( this ).attr( 'name' ).indexOf( 'decisions[' ) ) {
					imports++;
				}
			} );

			if ( 0 === ( imports + visible + hidden ) ) {
				// Nothing to do — let the form submit (the server will report no-op),
				// but warn the user first so they don't think they "lost" their work.
				if ( ! window.confirm( ayudawp_wvc_wl_import.no_decisions ) ) {
					e.preventDefault();
					return;
				}
			}

			var msg = formatSummary( ayudawp_wvc_wl_import.confirm_submit, imports, visible, hidden, skipped );
			if ( ! window.confirm( msg ) ) {
				e.preventDefault();
			}
		} );
	} );

}( jQuery ) );
