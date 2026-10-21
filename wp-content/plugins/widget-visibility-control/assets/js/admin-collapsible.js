/**
 * Collapsible cards for the Import / Export tab.
 *
 * Each card heading becomes a button that folds its body away. The open or
 * closed state is remembered per browser with localStorage, so a card the user
 * collapsed stays collapsed on the next visit.
 *
 * @package ayudawp/widget-visibility-control
 * @since 1.4.0
 */

/* global ayudawp_wvc_collapsible */

( function () {
	'use strict';

	var STORAGE_KEY = 'ayudawp_wvc_collapsed_cards';
	var L = window.ayudawp_wvc_collapsible || {};

	/**
	 * Read the list of collapsed card ids.
	 *
	 * @return {Array} Card ids.
	 */
	function getCollapsed() {
		try {
			var raw = window.localStorage.getItem( STORAGE_KEY );
			var parsed = raw ? JSON.parse( raw ) : [];
			return Array.isArray( parsed ) ? parsed : [];
		} catch ( e ) {
			// Private mode, disabled storage or corrupt value: just behave as
			// if nothing had been collapsed before.
			return [];
		}
	}

	/**
	 * Persist the list of collapsed card ids.
	 *
	 * @param {Array} ids Card ids.
	 */
	function setCollapsed( ids ) {
		try {
			window.localStorage.setItem( STORAGE_KEY, JSON.stringify( ids ) );
		} catch ( e ) {
			// Storage unavailable: the toggle still works for this page view.
		}
	}

	/**
	 * Apply the open or closed state to one card.
	 *
	 * @param {Element} heading  The card heading acting as the toggle.
	 * @param {Element} body     The collapsible body.
	 * @param {boolean} collapsed Whether it should be collapsed.
	 */
	function apply( heading, body, collapsed ) {
		body.hidden = collapsed;
		heading.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
		heading.classList.toggle( 'is-collapsed', collapsed );

		var label = collapsed ? L.expand : L.collapse;
		if ( label ) {
			heading.setAttribute( 'title', label );
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var headings = document.querySelectorAll( '.ayudawp-wvc-card-toggle' );
		if ( ! headings.length ) {
			return;
		}

		var collapsed = getCollapsed();

		Array.prototype.forEach.call( headings, function ( heading ) {
			var card = heading.getAttribute( 'data-card' );
			var body = heading.parentNode.querySelector( '.ayudawp-wvc-card-body' );

			if ( ! card || ! body ) {
				return;
			}

			// The heading is the control, so give it button semantics.
			heading.setAttribute( 'role', 'button' );
			heading.setAttribute( 'tabindex', '0' );
			heading.setAttribute( 'aria-controls', 'ayudawp-wvc-body-' + card );
			body.id = 'ayudawp-wvc-body-' + card;

			apply( heading, body, collapsed.indexOf( card ) !== -1 );

			function toggle() {
				var isCollapsed = body.hidden;
				var ids = getCollapsed();
				var at = ids.indexOf( card );

				if ( isCollapsed ) {
					if ( at !== -1 ) {
						ids.splice( at, 1 );
					}
				} else if ( at === -1 ) {
					ids.push( card );
				}

				setCollapsed( ids );
				apply( heading, body, ! isCollapsed );
			}

			heading.addEventListener( 'click', toggle );

			heading.addEventListener( 'keydown', function ( event ) {
				if ( 'Enter' === event.key || ' ' === event.key || 'Spacebar' === event.key ) {
					event.preventDefault();
					toggle();
				}
			} );
		} );
	} );
}() );
