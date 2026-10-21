/**
 * Widget Visibility Control - Classic Widgets
 *
 * @package ayudawp/widget-visibility-control
 */

/* global jQuery, isRtl, ayudawp_wvc_parent_pages, ayudawp_wvc_parent_terms, ayudawp_wvc_conditions_data */

( function( $ ) {
	'use strict';

	/**
	 * Decode HTML entities.
	 */
	function decodeEntities( str ) {
		if ( typeof str !== 'string' ) {
			return str;
		}
		var textarea = document.createElement( 'textarea' );
		textarea.innerHTML = str;
		return textarea.value;
	}

	/**
	 * Extract taxonomy slug from a minor value.
	 * E.g. 'product_cat' => 'product_cat', 'product_cat_tax_123' => 'product_cat'.
	 */
	function getTaxonomyFromMinor( minor ) {
		if ( ! minor ) {
			return '';
		}
		var data = ayudawp_wvc_conditions_data;
		if ( ! data || ! data.taxonomy_terms ) {
			return '';
		}

		// Direct match: minor is a taxonomy slug (the "all items" value).
		if ( data.taxonomy_terms[ minor ] ) {
			return minor;
		}

		// Parse taxonomy_slug_tax_ID format.
		var parts = minor.split( '_tax_' );
		if ( parts.length === 2 && data.taxonomy_terms[ parts[ 0 ] ] ) {
			return parts[ 0 ];
		}

		return '';
	}

	/**
	 * Check if a term minor value has children.
	 */
	function termHasChildren( minorVal ) {
		if ( typeof ayudawp_wvc_parent_terms !== 'undefined' && minorVal in ayudawp_wvc_parent_terms ) {
			return true;
		}
		return false;
	}

	/**
	 * Build the taxonomy picker dropdown (shown when major = 'taxonomy').
	 */
	function buildTaxonomyPicker( $condition, selectedTaxonomy ) {
		var $picker = $condition.find( '.conditions-rule-taxonomy-picker' );
		var data = ayudawp_wvc_conditions_data;

		$picker.empty();

		// Default option.
		$picker.append(
			$( '<option>' ).val( '' ).text(
				decodeEntities( '-- ' + ( data.taxonomy_picker_label || 'Select taxonomy' ) + ' --' )
			)
		);

		if ( data.taxonomy_list ) {
			for ( var i = 0; i < data.taxonomy_list.length; i++ ) {
				$picker.append(
					$( '<option>' )
						.val( data.taxonomy_list[ i ][ 0 ] )
						.text( decodeEntities( data.taxonomy_list[ i ][ 1 ] ) )
				);
			}
		}

		$picker.prop( 'disabled', false ).show();

		if ( selectedTaxonomy ) {
			$picker.val( selectedTaxonomy );
		}
	}

	/**
	 * Build minor terms for a specific taxonomy.
	 */
	function buildTaxonomyTerms( $condition, taxonomySlug, selectedMinor ) {
		var $select = $condition.find( 'select.conditions-rule-minor' );
		var data = ayudawp_wvc_conditions_data;

		$select.empty();

		if ( ! taxonomySlug ) {
			// No taxonomy selected: show only "All taxonomy pages".
			$select.append(
				$( '<option>' ).val( '' ).text( decodeEntities( data.taxonomy[ 0 ][ 1 ] ) )
			);
			$select.prop( 'disabled', false );

			// A rule for a taxonomy that is no longer registered keeps its value.
			if ( selectedMinor ) {
				selectStoredValue( $select, selectedMinor );
			}
			return;
		}

		var terms = data.taxonomy_terms && data.taxonomy_terms[ taxonomySlug ];
		if ( ! terms ) {
			$select.prop( 'disabled', true );
			return;
		}

		for ( var i = 0; i < terms.length; i++ ) {
			$select.append(
				$( '<option>' )
					.val( terms[ i ][ 0 ] )
					.text( decodeEntities( terms[ i ][ 1 ] ) )
			);
		}

		$select.prop( 'disabled', false );

		if ( selectedMinor ) {
			selectStoredValue( $select, selectedMinor );
		}
	}

	/**
	 * Update "include children" checkbox visibility for taxonomy terms.
	 */
	function updateTaxonomyHasChildren( $condition, minorVal, hasChildren ) {
		var $hasChildrenSpan = $condition.find( '.conditions-rule-has-children' );

		if ( termHasChildren( minorVal ) ) {
			$hasChildrenSpan.show();
			if ( hasChildren ) {
				$hasChildrenSpan.find( 'input[type="checkbox"]' ).prop( 'checked', true );
			}
		} else {
			$hasChildrenSpan.hide().find( 'input[type="checkbox"]' ).prop( 'checked', false );
		}
	}

	/**
	 * Keep exactly one value field enabled per rule.
	 *
	 * Rule types and values are posted as two lists paired by position, and a
	 * disabled field is not posted at all. Without the empty placeholder, a rule
	 * with no value (Always, or a row left unfinished) would shift the values of
	 * every rule below it.
	 */
	function syncValueFields( $condition ) {
		var $select = $condition.find( 'select.conditions-rule-minor' );
		var $url = $condition.find( 'input.conditions-rule-url' );

		// A dropdown with nothing selected posts nothing, same as a disabled one.
		var selectPosts = ! $select.prop( 'disabled' ) && $select.prop( 'selectedIndex' ) >= 0;
		var urlPosts = $url.length > 0 && ! $url.prop( 'disabled' );

		$condition.find( 'input.conditions-rule-empty' ).prop( 'disabled', selectPosts || urlPosts );
	}

	/**
	 * Select a value in a dropdown, keeping it even when the list no longer has it.
	 *
	 * A rule can point at something that is gone (a deleted term, a page that
	 * is no longer published). Setting a value the list does not have leaves the
	 * dropdown with nothing selected, and then the rule would be saved empty or
	 * not at all. The stored value is added as an option instead, so the rule is
	 * saved as it was.
	 */
	function selectStoredValue( $select, value ) {
		value = String( value );
		$select.val( value );

		if ( $select.val() !== value ) {
			$select.append( $( '<option>' ).val( value ).text( value ) );
			$select.val( value );
		}
	}

	/**
	 * Put the URL field away and give the row back to the dropdown.
	 */
	function resetUrlField( $condition ) {
		$condition.find( 'input.conditions-rule-url' ).val( '' ).hide().prop( 'disabled', true );
		$condition.find( '.conditions-rule-url-help' ).hide();
		$condition.find( 'select.conditions-rule-minor' ).show();
	}

	/**
	 * Build minor condition options based on major selection.
	 */
	function buildMinorConditions( $condition ) {
		fillMinorConditions( $condition );
		syncValueFields( $condition );
	}

	/**
	 * Fill the value field of a rule: a dropdown for most types, a text field for URL.
	 */
	function fillMinorConditions( $condition ) {
		var $select = $condition.find( 'select.conditions-rule-minor' );
		var $picker = $condition.find( '.conditions-rule-taxonomy-picker' );
		var major = $condition.attr( 'data-rule-major' ) || $condition.data( 'rule-major' ) || '';
		var minor = $condition.attr( 'data-rule-minor' ) || $condition.data( 'rule-minor' ) || '';
		var hasChildren = $condition.attr( 'data-rule-has-children' ) || $condition.data( 'rule-has-children' ) || '';

		// Clear existing options.
		$select.empty();

		// Hide taxonomy picker unless needed.
		$picker.hide().prop( 'disabled', true );

		// The URL rule takes free text instead of a choice from a list.
		if ( 'url' === major ) {
			$select.hide().prop( 'disabled', true );
			$condition.find( 'input.conditions-rule-url' ).val( String( minor ) ).prop( 'disabled', false ).show();
			$condition.find( '.conditions-rule-url-help' ).show();
			$condition.find( '.conditions-rule-has-children' ).hide()
				.find( 'input[type="checkbox"]' ).prop( 'checked', false );
			return;
		}

		resetUrlField( $condition );

		if ( ! major || 'post_type' === major || 'always' === major ) {
			$select.prop( 'disabled', true );
			$condition.find( '.conditions-rule-has-children' ).hide()
				.find( 'input[type="checkbox"]' ).prop( 'checked', false );
			return;
		}

		// Taxonomy: use cascading selector.
		if ( 'taxonomy' === major ) {
			var selectedTaxonomy = getTaxonomyFromMinor( minor );
			buildTaxonomyPicker( $condition, selectedTaxonomy );
			buildTaxonomyTerms( $condition, selectedTaxonomy, minor );
			updateTaxonomyHasChildren( $condition, minor, hasChildren );
			return;
		}

		// Check if we have data for this major type.
		if ( typeof ayudawp_wvc_conditions_data === 'undefined' || ! ayudawp_wvc_conditions_data[ major ] ) {
			$select.prop( 'disabled', true );
			return;
		}

		var majorData = ayudawp_wvc_conditions_data[ major ];
		var i, j, key, val, subkey, subval, $optgroup;

		for ( i = 0; i < majorData.length; i++ ) {
			key = majorData[ i ][ 0 ];
			val = majorData[ i ][ 1 ];

			if ( Array.isArray( val ) ) {
				$optgroup = $( '<optgroup>' ).attr( 'label', decodeEntities( key ) );

				for ( j = 0; j < val.length; j++ ) {
					subkey = val[ j ][ 0 ];
					subval = val[ j ][ 1 ];
					$optgroup.append(
						$( '<option>' )
							.val( subkey )
							.text( decodeEntities( subval.replace( /&nbsp;/g, '\u00A0' ) ) )
					);
				}

				$select.append( $optgroup );
			} else {
				$select.append(
					$( '<option>' )
						.val( key )
						.text( decodeEntities( val.replace( /&nbsp;/g, '\u00A0' ) ) )
				);
			}
		}

		$select.prop( 'disabled', false );

		if ( minor ) {
			selectStoredValue( $select, minor );
		}

		// Handle "has children" checkbox for pages.
		var $hasChildrenSpan = $condition.find( '.conditions-rule-has-children' );
		if ( 'page' === major && typeof ayudawp_wvc_parent_pages !== 'undefined' ) {
			var selectedMinor = $select.val();
			if ( selectedMinor && selectedMinor in ayudawp_wvc_parent_pages ) {
				$hasChildrenSpan.show();
				if ( hasChildren ) {
					$hasChildrenSpan.find( 'input[type="checkbox"]' ).prop( 'checked', true );
				}
			} else {
				$hasChildrenSpan.hide().find( 'input[type="checkbox"]' ).prop( 'checked', false );
			}
		} else {
			$hasChildrenSpan.hide().find( 'input[type="checkbox"]' ).prop( 'checked', false );
		}
	}

	/**
	 * Initialize conditions for a widget.
	 */
	function initWidgetConditions( $widget ) {
		$widget.find( '.condition' ).each( function() {
			var $condition = $( this );
			var major = $condition.attr( 'data-rule-major' );

			if ( major ) {
				buildMinorConditions( $condition );
			}
		} );
	}

	/**
	 * Reset rule indexes for proper form submission.
	 *
	 * The index to renumber is the last one in the name. The first number in it
	 * is the widget's own, and replacing that one sent the checkbox to a widget
	 * that does not exist, so "Include children" was lost after adding or
	 * removing a rule.
	 */
	function resetRuleIndexes( $conditions ) {
		var index = 0;
		$conditions.find( 'span.conditions-rule-has-children input[type="checkbox"]' ).each( function() {
			$( this ).attr( 'name', $( this ).attr( 'name' ).replace( /\[\d+\]$/, '[' + index + ']' ) );
			index++;
		} );
	}

	/**
	 * Adjust widget margins when visibility panel is open (classic widgets screen only).
	 */
	function setWidgetMargin( $widget ) {
		if ( $( 'body' ).hasClass( 'wp-customizer' ) || $( 'body' ).hasClass( 'block-editor-page' ) ) {
			return;
		}

		if ( ! $widget.hasClass( 'widget' ) ) {
			return;
		}

		var currentWidth, extra;

		if ( $widget.hasClass( 'expanded' ) ) {
			if ( $widget.attr( 'style' ) ) {
				$widget.data( 'original-style', $widget.attr( 'style' ) );
			}

			currentWidth = $widget.width();

			if ( currentWidth < 400 ) {
				extra = 400 - currentWidth;
				if ( typeof isRtl !== 'undefined' && isRtl ) {
					$widget.css( { position: 'relative', right: '-' + extra + 'px', width: '400px' } );
				} else {
					$widget.css( { position: 'relative', left: '-' + extra + 'px', width: '400px' } );
				}
			}
		} else if ( $widget.data( 'original-style' ) ) {
			$widget.attr( 'style', $widget.data( 'original-style' ) ).data( 'original-style', null );
		} else {
			$widget.removeAttr( 'style' );
		}
	}

	/**
	 * Move the visibility button to the correct position.
	 */
	function moveWidgetVisibilityButton( $widget ) {
		var $button = $widget.find( 'a.display-options' ).first();
		var $target = $widget.find( 'input.widget-control-save' );

		if ( 0 === $button.length ) {
			return;
		}

		if ( 0 === $target.length ) {
			$target = $widget.find( 'div.widget-conditional' );
		}

		if ( $target.length ) {
			$button.insertBefore( $target );
			$button.parent().removeClass( 'widget-control-noform' );
		}
	}

	// Toggle visibility panel.
	$( document ).on( 'click', 'a.display-options', function( e ) {
		e.preventDefault();

		var $button = $( this );
		var $widget = $button.closest( '.widget, .wp-block-legacy-widget__edit-form' );
		var $conditional = $widget.find( 'div.widget-conditional' );

		$conditional.toggleClass( 'widget-conditional-hide' );
		$button.toggleClass( 'active' );

		if ( $widget.hasClass( 'widget' ) ) {
			$widget.toggleClass( 'expanded' );
			setWidgetMargin( $widget );
		}

		if ( $button.hasClass( 'active' ) ) {
			$widget.find( 'input[name="widget-conditions-visible"]' ).val( '1' );
			initWidgetConditions( $widget );
		} else {
			$widget.find( 'input[name="widget-conditions-visible"]' ).val( '0' );
		}
	} );

	// Major condition change.
	$( document ).on( 'change', 'select.conditions-rule-major', function() {
		var $major = $( this );
		var $condition = $major.closest( '.condition' );
		var majorVal = $major.val();

		$condition.attr( 'data-rule-major', majorVal );
		$condition.attr( 'data-rule-minor', '' );

		if ( majorVal ) {
			buildMinorConditions( $condition );
		} else {
			resetUrlField( $condition );
			$condition.find( 'select.conditions-rule-minor' ).empty().prop( 'disabled', true );
			$condition.find( '.conditions-rule-taxonomy-picker' ).hide().prop( 'disabled', true );
			$condition.find( '.conditions-rule-has-children' ).hide()
				.find( 'input[type="checkbox"]' ).prop( 'checked', false );
			syncValueFields( $condition );
		}
	} );

	// Taxonomy picker change - rebuild minor with filtered terms.
	$( document ).on( 'change', 'select.conditions-rule-taxonomy-picker', function() {
		var $picker = $( this );
		var $condition = $picker.closest( '.condition' );
		var taxonomySlug = $picker.val();

		var newMinor = taxonomySlug || '';
		$condition.attr( 'data-rule-minor', newMinor );

		buildTaxonomyTerms( $condition, taxonomySlug, newMinor );
		syncValueFields( $condition );

		// Hide children checkbox when taxonomy changes.
		$condition.find( '.conditions-rule-has-children' ).hide()
			.find( 'input[type="checkbox"]' ).prop( 'checked', false );
	} );

	// URL field: keep the rule in step with what is typed.
	$( document ).on( 'input change', 'input.conditions-rule-url', function() {
		$( this ).closest( '.condition' ).attr( 'data-rule-minor', $( this ).val() );
	} );

	// Minor condition change.
	$( document ).on( 'change', 'select.conditions-rule-minor', function() {
		var $minor = $( this );
		var $condition = $minor.closest( '.condition' );
		var minorVal = $minor.val();
		var majorVal = $condition.attr( 'data-rule-major' );

		$condition.attr( 'data-rule-minor', minorVal );

		// Show/hide "has children" based on selection.
		var $hasChildrenSpan = $condition.find( '.conditions-rule-has-children' );

		if ( 'page' === majorVal && typeof ayudawp_wvc_parent_pages !== 'undefined' && minorVal in ayudawp_wvc_parent_pages ) {
			$hasChildrenSpan.show();
		} else if ( 'taxonomy' === majorVal && termHasChildren( minorVal ) ) {
			$hasChildrenSpan.show();
		} else {
			$hasChildrenSpan.hide().find( 'input[type="checkbox"]' ).prop( 'checked', false );
		}
	} );

	// Add new condition.
	$( document ).on( 'click', 'a.add-condition', function( e ) {
		e.preventDefault();

		var $condition = $( this ).closest( 'div.condition' );
		var $clone = $condition.clone();

		$clone
			.attr( 'data-rule-major', '' )
			.attr( 'data-rule-minor', '' )
			.attr( 'data-rule-has-children', '' );

		$clone.find( 'select.conditions-rule-major' ).val( '' );
		resetUrlField( $clone );
		$clone.find( 'select.conditions-rule-minor' ).empty().prop( 'disabled', true );
		$clone.find( '.conditions-rule-taxonomy-picker' ).hide().prop( 'disabled', true ).val( '' );
		$clone.find( '.conditions-rule-has-children' ).hide()
			.find( 'input[type="checkbox"]' ).prop( 'checked', false );
		syncValueFields( $clone );

		$clone.insertAfter( $condition );

		resetRuleIndexes( $condition.closest( '.conditions' ) );
	} );

	// Delete condition.
	$( document ).on( 'click', 'a.delete-condition', function( e ) {
		e.preventDefault();

		var $condition = $( this ).closest( 'div.condition' );
		var $conditions = $condition.closest( '.conditions' );
		var $widget = $condition.closest( '.widget, .wp-block-legacy-widget__edit-form' );

		if ( $condition.is( ':first-child' ) && $condition.is( ':last-child' ) ) {
			$condition.find( 'select.conditions-rule-major' ).val( '' ).trigger( 'change' );
			$widget.find( 'a.display-options' ).trigger( 'click' );
		} else {
			$condition.remove();
		}

		resetRuleIndexes( $conditions );
	} );

	// Match all toggle.
	$( document ).on( 'change', 'input.conditions-match-all', function() {
		var $conditional = $( this ).closest( '.widget-conditional' );
		$conditional.toggleClass( 'conjunction' ).toggleClass( 'intersection' );
	} );

	// Widget top click (classic widgets screen).
	$( document ).on( 'click', 'div.widget-top', function() {
		var $widget = $( this ).closest( 'div.widget' );
		var $button = $widget.find( 'a.display-options' );

		if ( $button.hasClass( 'active' ) ) {
			$button.attr( 'opened', 'true' );
		}

		if ( $button.attr( 'opened' ) ) {
			$button.removeAttr( 'opened' );
			$widget.toggleClass( 'expanded' );
			setWidgetMargin( $widget );
		}
	} );

	// Initialize on page load.
	$( function() {
		$( '.widget' ).each( function() {
			moveWidgetVisibilityButton( $( this ) );
		} );
	} );

	$( document ).on( 'widget-added', function( e, $widget ) {
		moveWidgetVisibilityButton( $widget );
	} );

	$( document ).on( 'widget-updated widget-synced', function( e, $widget ) {
		initWidgetConditions( $widget );
	} );

	// Schedule action change.
	$( document ).on( 'change', 'select.schedule-action-select', function() {
		var $select = $( this );
		var $scheduleSection = $select.closest( '.conditions-schedule' );
		var $scheduleDates = $scheduleSection.find( '.schedule-dates' );

		if ( '' === $select.val() ) {
			$scheduleDates.hide();
		} else {
			$scheduleDates.show();
		}
	} );

	/**
	 * Apply no end date checkbox state to To row.
	 */
	function applyNoEndDateState( $checkbox ) {
		var $widget = $checkbox.closest( '.widget, .wp-block-legacy-widget__edit-form, .widget-conditional' );
		var $toRow = $widget.find( '.schedule-row-to' );
		var $toSelects = $toRow.find( 'select' );

		if ( $checkbox.is( ':checked' ) ) {
			$toRow.addClass( 'schedule-row-disabled' );
			$toSelects.prop( 'disabled', true );
		} else {
			$toRow.removeClass( 'schedule-row-disabled' );
			$toSelects.prop( 'disabled', false );
		}
	}

	$( document ).on( 'change', 'input.schedule-no-end-checkbox', function() {
		applyNoEndDateState( $( this ) );
	} );

	$( function() {
		$( 'input.schedule-no-end-checkbox' ).each( function() {
			applyNoEndDateState( $( this ) );
		} );
	} );

	$( document ).on( 'widget-added widget-updated widget-synced', function( e, $widget ) {
		$widget.find( 'input.schedule-no-end-checkbox' ).each( function() {
			applyNoEndDateState( $( this ) );
		} );
	} );

}( jQuery ) );
