/**
 * Widget Visibility Control - Block Editor
 *
 * Labels are passed from PHP via wp_localize_script (ayudawp_wvc_labels).
 *
 * @package ayudawp/widget-visibility-control
 */

/* global wp, ayudawp_wvc_conditions_data, ayudawp_wvc_labels */

( function() {
	'use strict';

	// Verify all required dependencies are available.
	if ( typeof wp === 'undefined' ||
		typeof wp.element === 'undefined' ||
		typeof wp.blockEditor === 'undefined' ||
		typeof wp.components === 'undefined' ||
		typeof wp.compose === 'undefined' ||
		typeof wp.data === 'undefined' ||
		typeof wp.hooks === 'undefined' ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useCallback = wp.element.useCallback;
	var useMemo = wp.element.useMemo;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var Button = wp.components.Button;
	var ToggleControl = wp.components.ToggleControl;
	var BaseControl = wp.components.BaseControl;
	var PanelBody = wp.components.PanelBody;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var useSelect = wp.data.useSelect;
	var addFilter = wp.hooks.addFilter;

	// Labels from PHP.
	var L = ayudawp_wvc_labels;

	var htmlUnescapes = {
		'&amp;': '&',
		'&lt;': '<',
		'&gt;': '>',
		'&quot;': '"',
		'&#39;': "'",
		'&nbsp;': '\u00A0'
	};

	function unescape( str ) {
		if ( typeof str !== 'string' ) {
			return str;
		}
		return str.replace( /&(?:amp|lt|gt|quot|#39|nbsp);/g, function( match ) {
			return htmlUnescapes[ match ];
		} );
	}

	function blockHasVisibilitySettings( name ) {
		var disallowed = [ 'core/legacy-widget', 'core/widget-area' ];
		return disallowed.indexOf( name ) === -1;
	}

	function addVisibilityAttribute( settings, name ) {
		if ( blockHasVisibilitySettings( name ) && typeof settings.attributes !== 'undefined' ) {
			settings.attributes = Object.assign( {}, settings.attributes, {
				conditions: {
					type: 'object',
					default: {}
				}
			} );
		}
		return settings;
	}

	addFilter( 'blocks.registerBlockType', 'widget-visibility-control/visibility', addVisibilityAttribute );

	function buildOptions( options, level ) {
		level = level || 0;
		var result = [];
		var i, key, val;

		for ( i = 0; i < options.length; i++ ) {
			key = options[ i ][ 0 ];
			val = options[ i ][ 1 ];

			if ( Array.isArray( val ) ) {
				result.push( {
					label: unescape( '\u00A0'.repeat( level * 3 ) + key ),
					value: key,
					disabled: true
				} );
				result = result.concat( buildOptions( val, level + 1 ) );
			} else {
				result.push( {
					label: unescape( '\u00A0'.repeat( level * 3 ) + val ),
					value: key
				} );
			}
		}

		return result;
	}

	/**
	 * Extract taxonomy slug from a minor value.
	 */
	function getTaxonomyFromMinor( minor ) {
		if ( ! minor ) {
			return '';
		}
		var data = ayudawp_wvc_conditions_data;
		if ( ! data || ! data.taxonomy_terms ) {
			return '';
		}

		if ( data.taxonomy_terms[ minor ] ) {
			return minor;
		}

		var parts = minor.split( '_tax_' );
		if ( parts.length === 2 && data.taxonomy_terms[ parts[ 0 ] ] ) {
			return parts[ 0 ];
		}

		return '';
	}

	/**
	 * Build taxonomy picker options from taxonomy_list data.
	 */
	function buildTaxonomyPickerOptions() {
		var data = ayudawp_wvc_conditions_data;
		var options = [
			{ label: L.select_taxonomy, value: '' }
		];

		if ( data.taxonomy_list ) {
			for ( var i = 0; i < data.taxonomy_list.length; i++ ) {
				options.push( {
					label: unescape( data.taxonomy_list[ i ][ 1 ] ),
					value: data.taxonomy_list[ i ][ 0 ]
				} );
			}
		}

		return options;
	}

	/**
	 * Build term options for a specific taxonomy.
	 */
	function buildTaxonomyTermOptions( taxonomySlug ) {
		var data = ayudawp_wvc_conditions_data;

		if ( ! taxonomySlug ) {
			return [ { label: unescape( data.taxonomy[ 0 ][ 1 ] ), value: '' } ];
		}

		var terms = data.taxonomy_terms && data.taxonomy_terms[ taxonomySlug ];
		if ( ! terms ) {
			return [];
		}

		var options = [];
		for ( var i = 0; i < terms.length; i++ ) {
			options.push( {
				label: unescape( terms[ i ][ 1 ] ),
				value: terms[ i ][ 0 ]
			} );
		}

		return options;
	}

	/**
	 * Check if a minor value corresponds to a parent page.
	 */
	function pageHasChildren( minorVal ) {
		var data = ayudawp_wvc_conditions_data;
		if ( data.parent_pages && minorVal in data.parent_pages ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if a minor value corresponds to a parent taxonomy term.
	 */
	function termHasChildren( minorVal ) {
		var data = ayudawp_wvc_conditions_data;
		if ( data.taxonomy_parent_terms && minorVal in data.taxonomy_parent_terms ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if a rule should show the "include children" checkbox.
	 */
	function shouldShowHasChildren( rule ) {
		if ( ! rule.major || ! rule.minor ) {
			return false;
		}
		if ( 'page' === rule.major ) {
			return pageHasChildren( rule.minor );
		}
		if ( 'taxonomy' === rule.major ) {
			return termHasChildren( rule.minor );
		}
		return false;
	}

	function VisibilityRule( props ) {
		var rule = props.rule;
		var onDelete = props.onDelete;
		var setMajor = props.setMajor;
		var setMinor = props.setMinor;
		var setHasChildren = props.setHasChildren;

		var majorOptions = [
			{ label: L.select, value: '' },
			{ label: L.category, value: 'category' },
			{ label: L.author, value: 'author' },
			{ label: L.user, value: 'loggedin' },
			{ label: L.role, value: 'role' },
			{ label: L.capability, value: 'capability' },
			{ label: L.tag, value: 'tag' },
			{ label: L.date, value: 'date' },
			{ label: L.page, value: 'page' },
			{ label: L.url, value: 'url' }
		];

		if ( ayudawp_wvc_conditions_data.taxonomy && ayudawp_wvc_conditions_data.taxonomy.length > 1 ) {
			majorOptions.push( { label: L.taxonomy, value: 'taxonomy' } );
		}

		majorOptions.push( { label: L.always, value: 'always' } );

		// Taxonomy cascading logic.
		var isTaxonomy = rule.major === 'taxonomy';
		var isUrl = rule.major === 'url';
		var selectedTaxonomy = isTaxonomy ? getTaxonomyFromMinor( rule.minor ) : '';
		var minorOptions = [];

		if ( isTaxonomy ) {
			minorOptions = buildTaxonomyTermOptions( selectedTaxonomy );
		} else if ( rule.major && ayudawp_wvc_conditions_data[ rule.major ] ) {
			minorOptions = buildOptions( ayudawp_wvc_conditions_data[ rule.major ] );
		}

		var showHasChildren = shouldShowHasChildren( rule );

		return el( 'div', { className: 'wvc-rule' },
			el( 'div', { className: 'wvc-rule-major' },
				el( 'span', { className: 'wvc-if' }, L[ 'if' ] ),
				el( 'div', { className: 'wvc-select' },
					el( SelectControl, {
						label: L.select,
						hideLabelFromVision: true,
						value: rule.major || '',
						options: majorOptions,
						onChange: setMajor,
						__nextHasNoMarginBottom: true
					} )
				)
			),
			// Taxonomy picker.
			isTaxonomy && el( 'div', { className: 'wvc-rule-taxonomy-picker' },
				el( 'div', { className: 'wvc-select' },
					el( SelectControl, {
						label: L.taxonomy,
						hideLabelFromVision: true,
						value: selectedTaxonomy,
						options: buildTaxonomyPickerOptions(),
						onChange: function( taxSlug ) {
							setMinor( taxSlug || '' );
						},
						__nextHasNoMarginBottom: true
					} )
				)
			),
			rule.major && 'always' !== rule.major && ! isUrl && el( 'div', { className: 'wvc-rule-minor' },
				el( 'span', { className: 'wvc-is' }, L.is ),
				el( 'div', { className: 'wvc-select' },
					el( SelectControl, {
						label: L.is,
						hideLabelFromVision: true,
						value: rule.minor || '',
						options: minorOptions,
						onChange: setMinor,
						__nextHasNoMarginBottom: true
					} )
				)
			),
			// The URL rule takes free text instead of a choice from a list.
			isUrl && el( 'div', { className: 'wvc-rule-minor' },
				el( 'span', { className: 'wvc-is' }, L.is ),
				el( 'div', { className: 'wvc-select wvc-rule-url' },
					el( TextControl, {
						label: L.url,
						hideLabelFromVision: true,
						value: rule.minor || '',
						placeholder: 'shop/*',
						dir: 'ltr',
						autoComplete: 'off',
						spellCheck: false,
						onChange: setMinor,
						__nextHasNoMarginBottom: true
					} )
				)
			),
			isUrl && el( 'p', { className: 'wvc-rule-url-help' }, L.url_help ),
			// Include children checkbox.
			showHasChildren && el( 'div', { className: 'wvc-rule-has-children' },
				el( 'label', null,
					el( 'input', {
						type: 'checkbox',
						checked: !! rule.has_children,
						onChange: function( e ) {
							setHasChildren( e.target.checked );
						}
					} ),
					L.include_children
				)
			),
			el( 'div', { className: 'wvc-delete-rule' },
				el( Button, {
					onClick: onDelete,
					isSmall: true,
					variant: 'secondary'
				}, L.remove )
			)
		);
	}

	function getDefaultConditions( conditions ) {
		return Object.assign( {
			action: 'show',
			rules: [],
			match_all: '0',
			schedule: {
				enabled: false,
				action: '',
				from: '',
				to: '',
				no_end_date: false
			}
		}, conditions );
	}

	function getDefaultSchedule( schedule ) {
		return Object.assign( {
			enabled: false,
			action: '',
			from: '',
			to: '',
			no_end_date: false
		}, schedule || {} );
	}

	function parseScheduleDate( dateString ) {
		var now = new Date();
		var defaults = {
			year: now.getFullYear(),
			month: now.getMonth() + 1,
			day: now.getDate(),
			hour: 0,
			minute: 0
		};

		if ( ! dateString ) {
			return defaults;
		}

		var parts = dateString.match( /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})$/ );
		if ( ! parts ) {
			return defaults;
		}

		return {
			year: parseInt( parts[ 1 ], 10 ),
			month: parseInt( parts[ 2 ], 10 ),
			day: parseInt( parts[ 3 ], 10 ),
			hour: parseInt( parts[ 4 ], 10 ),
			minute: parseInt( parts[ 5 ], 10 )
		};
	}

	function buildDateString( year, month, day, hour, minute ) {
		var pad = function( n ) { return n < 10 ? '0' + n : '' + n; };
		return year + '-' + pad( month ) + '-' + pad( day ) + ' ' + pad( hour ) + ':' + pad( minute );
	}

	function getYearOptions() {
		var options = [];
		var currentYear = new Date().getFullYear();
		for ( var y = currentYear; y <= currentYear + 5; y++ ) {
			options.push( { label: '' + y, value: y } );
		}
		return options;
	}

	function getMonthOptions() {
		var months = L.months || [];
		return months.map( function( name, i ) {
			return { label: name, value: i + 1 };
		} );
	}

	function getDayOptions() {
		var options = [];
		for ( var d = 1; d <= 31; d++ ) {
			options.push( { label: '' + d, value: d } );
		}
		return options;
	}

	function getHourOptions() {
		var options = [];
		for ( var h = 0; h <= 23; h++ ) {
			options.push( { label: h < 10 ? '0' + h : '' + h, value: h } );
		}
		return options;
	}

	function getMinuteOptions() {
		var options = [];
		for ( var m = 0; m <= 59; m += 5 ) {
			options.push( { label: m < 10 ? '0' + m : '' + m, value: m } );
		}
		return options;
	}

	/**
	 * Schedule Controls Component.
	 */
	function ScheduleControls( props ) {
		var schedule = props.schedule;
		var onChange = props.onChange;

		var fromParts = parseScheduleDate( schedule.from );
		var toParts = parseScheduleDate( schedule.to );
		var noEndDate = schedule.no_end_date || false;

		var updateSchedule = function( key, value ) {
			var newSchedule = Object.assign( {}, getDefaultSchedule( schedule ) );
			newSchedule[ key ] = value;
			if ( key === 'action' && value ) {
				newSchedule.enabled = true;
			} else if ( key === 'action' && ! value ) {
				newSchedule.enabled = false;
			}
			onChange( newSchedule );
		};

		var updateFromDate = function( key, value ) {
			var parts = Object.assign( {}, fromParts );
			parts[ key ] = parseInt( value, 10 );
			updateSchedule( 'from', buildDateString( parts.year, parts.month, parts.day, parts.hour, parts.minute ) );
		};

		var updateToDate = function( key, value ) {
			var parts = Object.assign( {}, toParts );
			parts[ key ] = parseInt( value, 10 );
			updateSchedule( 'to', buildDateString( parts.year, parts.month, parts.day, parts.hour, parts.minute ) );
		};

		var toggleNoEndDate = function( checked ) {
			updateSchedule( 'no_end_date', checked );
		};

		return el( 'div', { className: 'wvc-schedule' },
			el( 'div', { className: 'wvc-schedule-header' }, L.schedule ),
			el( SelectControl, {
				className: 'wvc-schedule-action',
				label: L.schedule,
				hideLabelFromVision: true,
				value: schedule.action || '',
				options: [
					{ label: L.no_schedule, value: '' },
					{ label: L.show_during, value: 'show' },
					{ label: L.hide_during, value: 'hide' }
				],
				onChange: function( value ) { updateSchedule( 'action', value ); },
				__nextHasNoMarginBottom: true
			} ),
			schedule.action && el( 'div', { className: 'wvc-schedule-dates' },
				el( 'div', { className: 'wvc-schedule-row' },
					el( 'span', { className: 'wvc-schedule-label' }, L.from ),
					el( SelectControl, { className: 'wvc-schedule-day', label: L.from, hideLabelFromVision: true, value: fromParts.day, options: getDayOptions(), onChange: function( v ) { updateFromDate( 'day', v ); }, __nextHasNoMarginBottom: true } ),
					el( SelectControl, { className: 'wvc-schedule-month', label: L.from, hideLabelFromVision: true, value: fromParts.month, options: getMonthOptions(), onChange: function( v ) { updateFromDate( 'month', v ); }, __nextHasNoMarginBottom: true } ),
					el( SelectControl, { className: 'wvc-schedule-year', label: L.from, hideLabelFromVision: true, value: fromParts.year, options: getYearOptions(), onChange: function( v ) { updateFromDate( 'year', v ); }, __nextHasNoMarginBottom: true } ),
					el( 'span', { className: 'wvc-schedule-at' }, '@' ),
					el( SelectControl, { className: 'wvc-schedule-hour', label: L.from, hideLabelFromVision: true, value: fromParts.hour, options: getHourOptions(), onChange: function( v ) { updateFromDate( 'hour', v ); }, __nextHasNoMarginBottom: true } ),
					el( 'span', { className: 'wvc-schedule-colon' }, ':' ),
					el( SelectControl, { className: 'wvc-schedule-minute', label: L.from, hideLabelFromVision: true, value: fromParts.minute, options: getMinuteOptions(), onChange: function( v ) { updateFromDate( 'minute', v ); }, __nextHasNoMarginBottom: true } )
				),
				el( 'div', { className: 'wvc-schedule-row wvc-schedule-row-to' + ( noEndDate ? ' wvc-schedule-row-disabled' : '' ) },
					el( 'span', { className: 'wvc-schedule-label' }, L.to ),
					el( SelectControl, { className: 'wvc-schedule-day', label: L.to, hideLabelFromVision: true, value: toParts.day, options: getDayOptions(), onChange: function( v ) { updateToDate( 'day', v ); }, disabled: noEndDate, __nextHasNoMarginBottom: true } ),
					el( SelectControl, { className: 'wvc-schedule-month', label: L.to, hideLabelFromVision: true, value: toParts.month, options: getMonthOptions(), onChange: function( v ) { updateToDate( 'month', v ); }, disabled: noEndDate, __nextHasNoMarginBottom: true } ),
					el( SelectControl, { className: 'wvc-schedule-year', label: L.to, hideLabelFromVision: true, value: toParts.year, options: getYearOptions(), onChange: function( v ) { updateToDate( 'year', v ); }, disabled: noEndDate, __nextHasNoMarginBottom: true } ),
					el( 'span', { className: 'wvc-schedule-at' }, '@' ),
					el( SelectControl, { className: 'wvc-schedule-hour', label: L.to, hideLabelFromVision: true, value: toParts.hour, options: getHourOptions(), onChange: function( v ) { updateToDate( 'hour', v ); }, disabled: noEndDate, __nextHasNoMarginBottom: true } ),
					el( 'span', { className: 'wvc-schedule-colon' }, ':' ),
					el( SelectControl, { className: 'wvc-schedule-minute', label: L.to, hideLabelFromVision: true, value: toParts.minute, options: getMinuteOptions(), onChange: function( v ) { updateToDate( 'minute', v ); }, disabled: noEndDate, __nextHasNoMarginBottom: true } )
				),
				el( 'div', { className: 'wvc-schedule-no-end-date' },
					el( 'label', { className: 'wvc-schedule-no-end-label' },
						el( 'input', {
							type: 'checkbox',
							checked: noEndDate,
							onChange: function( e ) { toggleNoEndDate( e.target.checked ); }
						} ),
						L.no_end_date
					)
				)
			)
		);
	}

	var visibilityControls = createHigherOrderComponent( function( BlockEdit ) {
		return function( props ) {
			var clientId = props.clientId;
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var isSelected = props.isSelected;
			var name = props.name;

			var conditions = useMemo( function() {
				return attributes.conditions || {};
			}, [ attributes ] );

			var rules = useMemo( function() {
				return conditions.rules || [];
			}, [ conditions ] );

			var schedule = useMemo( function() {
				return getDefaultSchedule( conditions.schedule );
			}, [ conditions ] );

			var isTopLevelWidgetBlock = useSelect( function( select ) {
				var blockEditor = select( 'core/block-editor' );
				var parents = blockEditor.getBlockParents( clientId, true );
				var parentBlock = parents && parents.length ? blockEditor.getBlock( parents[ 0 ] ) : null;
				return ! parentBlock || ( parentBlock && parentBlock.name === 'core/widget-area' );
			}, [ clientId ] );

			var toggleMatchAll = useCallback( function() {
				setAttributes( {
					conditions: Object.assign( {}, getDefaultConditions( conditions ), {
						match_all: conditions.match_all === '0' ? '1' : '0'
					} )
				} );
			}, [ setAttributes, conditions ] );

			var setAction = useCallback( function( value ) {
				setAttributes( {
					conditions: Object.assign( {}, getDefaultConditions( conditions ), {
						action: value
					} )
				} );
			}, [ setAttributes, conditions ] );

			var addNewRule = useCallback( function() {
				setAttributes( {
					conditions: Object.assign( {}, getDefaultConditions( conditions ), {
						rules: rules.concat( [ { major: '', minor: '' } ] )
					} )
				} );
			}, [ rules, setAttributes, conditions ] );

			var deleteRule = useCallback( function( index ) {
				setAttributes( {
					conditions: Object.assign( {}, getDefaultConditions( conditions ), {
						rules: rules.slice( 0, index ).concat( rules.slice( index + 1 ) )
					} )
				} );
			}, [ rules, setAttributes, conditions ] );

			var setMajor = useCallback( function( index, majorValue ) {
				var minorValue = '';
				// For taxonomy, don't auto-set minor (cascading picker handles it).
				if ( majorValue && majorValue !== 'taxonomy' && ayudawp_wvc_conditions_data[ majorValue ] && ayudawp_wvc_conditions_data[ majorValue ].length > 0 ) {
					minorValue = ayudawp_wvc_conditions_data[ majorValue ][ 0 ][ 0 ];
				}

				setAttributes( {
					conditions: Object.assign( {}, getDefaultConditions( conditions ), {
						rules: rules.slice( 0, index ).concat( [
							{ major: majorValue, minor: minorValue }
						] ).concat( rules.slice( index + 1 ) )
					} )
				} );
			}, [ rules, setAttributes, conditions ] );

			var setMinor = useCallback( function( index, value ) {
				setAttributes( {
					conditions: Object.assign( {}, getDefaultConditions( conditions ), {
						rules: rules.slice( 0, index ).concat( [
							Object.assign( {}, rules[ index ], { minor: value, has_children: false } )
						] ).concat( rules.slice( index + 1 ) )
					} )
				} );
			}, [ rules, setAttributes, conditions ] );

			var setHasChildren = useCallback( function( index, checked ) {
				setAttributes( {
					conditions: Object.assign( {}, getDefaultConditions( conditions ), {
						rules: rules.slice( 0, index ).concat( [
							Object.assign( {}, rules[ index ], { has_children: checked } )
						] ).concat( rules.slice( index + 1 ) )
					} )
				} );
			}, [ rules, setAttributes, conditions ] );

			var setSchedule = useCallback( function( newSchedule ) {
				setAttributes( {
					conditions: Object.assign( {}, getDefaultConditions( conditions ), {
						schedule: newSchedule
					} )
				} );
			}, [ setAttributes, conditions ] );

			var mainRender = null;

			if ( rules.length === 0 ) {
				mainRender = el( BaseControl, {
					id: 'wvc-wrapper',
					className: 'wvc-wrapper',
					help: L.no_rules_help,
					__nextHasNoMarginBottom: true
				},
					el( Button, {
						variant: 'secondary',
						onClick: addNewRule,
						className: 'wvc-add-new-rule'
					}, L.add_new_rule ),
					el( ScheduleControls, {
						schedule: schedule,
						onChange: setSchedule
					} )
				);
			} else {
				var rulesElements = rules.map( function( rule, i ) {
					return el( VisibilityRule, {
						key: i,
						rule: rule,
						onDelete: function() { deleteRule( i ); },
						setMajor: function( value ) { setMajor( i, value ); },
						setMinor: function( value ) { setMinor( i, value ); },
						setHasChildren: function( checked ) { setHasChildren( i, checked ); }
					} );
				} );

				mainRender = el( BaseControl, {
					className: 'wvc-wrapper',
					id: 'wvc-wrapper',
					__nextHasNoMarginBottom: true
				},
					el( SelectControl, {
						className: 'wvc-show-hide',
						label: L.visibility,
						hideLabelFromVision: true,
						value: attributes.conditions ? attributes.conditions.action : 'show',
						options: [
							{ label: L.show_block, value: 'show' },
							{ label: L.hide_block, value: 'hide' }
						],
						onChange: setAction,
						__nextHasNoMarginBottom: true
					} ),
					rulesElements,
					rules.length > 1 && el( ToggleControl, {
						className: 'wvc-match-all',
						label: L.match_all,
						checked: conditions.match_all === '1',
						onChange: toggleMatchAll,
						__nextHasNoMarginBottom: true
					} ),
					el( Button, {
						variant: 'secondary',
						onClick: addNewRule
					}, L.add_new_rule ),
					el( ScheduleControls, {
						schedule: schedule,
						onChange: setSchedule
					} )
				);
			}

			return el( Fragment, {},
				el( BlockEdit, props ),
				isSelected && isTopLevelWidgetBlock && blockHasVisibilitySettings( name ) &&
					el( InspectorControls, {},
						el( PanelBody, { title: L.visibility, initialOpen: true },
							mainRender
						)
					),
				isSelected && ! isTopLevelWidgetBlock && blockHasVisibilitySettings( name ) &&
					el( InspectorControls, {},
						el( PanelBody, { title: L.visibility, initialOpen: false },
							el( 'p', null, L.top_level_help )
						)
					)
			);
		};
	}, 'visibilityControls' );

	addFilter( 'editor.BlockEdit', 'widget-visibility-control/visibility', visibilityControls );

}() );