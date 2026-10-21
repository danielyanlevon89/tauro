<?php
/**
 * Data Manager - Handles storage and retrieval of visibility conditions.
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages widget visibility data independently from Jetpack.
 *
 * Data structure in option 'ayudawp_wvc_conditions':
 * array(
 *     'categories-2' => array(
 *         'action'    => 'show',
 *         'rules'     => array(...),
 *         'match_all' => '0'
 *     ),
 *     'text-3' => array(...),
 * )
 *
 * @since 1.0.0
 */
class AyudaWP_WVC_Data_Manager {

	/**
	 * Cache for conditions data.
	 *
	 * @var array|null
	 */
	private static $conditions_cache = null;

	/**
	 * Get all stored conditions.
	 *
	 * @since 1.0.0
	 * @return array All widget visibility conditions.
	 */
	public static function get_all_conditions() {
		if ( null === self::$conditions_cache ) {
			self::$conditions_cache = get_option( AYUDAWP_WVC_CONDITIONS_OPTION, array() );
		}
		return self::$conditions_cache;
	}

	/**
	 * Get conditions for a specific widget.
	 *
	 * @since 1.0.0
	 * @param string $widget_id Widget ID (e.g., 'categories-2').
	 * @return array|null Conditions array or null if not set.
	 */
	public static function get_conditions( $widget_id ) {
		$all_conditions = self::get_all_conditions();
		return isset( $all_conditions[ $widget_id ] ) ? $all_conditions[ $widget_id ] : null;
	}

	/**
	 * Save conditions for a specific widget.
	 *
	 * @since 1.0.0
	 * @param string $widget_id  Widget ID (e.g., 'categories-2').
	 * @param array  $conditions Conditions array.
	 * @return bool True on success, false on failure.
	 */
	public static function save_conditions( $widget_id, $conditions ) {
		$all_conditions = self::get_all_conditions();

		$has_rules    = ! empty( $conditions['rules'] );
		$has_schedule = ! empty( $conditions['schedule']['action'] );

		if ( ! empty( $conditions ) && ( $has_rules || $has_schedule ) ) {
			// Preserve _source from a prior save if the caller didn't set one,
			// so editing an imported widget from the UI keeps its origin.
			if (
				! isset( $conditions['_source'] )
				&& isset( $all_conditions[ $widget_id ]['_source'] )
			) {
				$conditions['_source'] = $all_conditions[ $widget_id ]['_source'];
			}
			$all_conditions[ $widget_id ] = $conditions;
		} else {
			unset( $all_conditions[ $widget_id ] );
		}

		self::$conditions_cache = $all_conditions;
		return update_option( AYUDAWP_WVC_CONDITIONS_OPTION, $all_conditions );
	}

	/**
	 * Delete conditions for a specific widget.
	 *
	 * @since 1.0.0
	 * @param string $widget_id Widget ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_conditions( $widget_id ) {
		$all_conditions = self::get_all_conditions();

		if ( isset( $all_conditions[ $widget_id ] ) ) {
			unset( $all_conditions[ $widget_id ] );
			self::$conditions_cache = $all_conditions;
			return update_option( AYUDAWP_WVC_CONDITIONS_OPTION, $all_conditions );
		}

		return true;
	}

	/**
	 * Clear all stored conditions.
	 *
	 * @since 1.0.0
	 * @return bool True on success.
	 */
	public static function clear_all_conditions() {
		self::$conditions_cache = array();
		return update_option( AYUDAWP_WVC_CONDITIONS_OPTION, array() );
	}

	/**
	 * Clear the conditions cache.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function clear_cache() {
		self::$conditions_cache = null;
	}

	/**
	 * Get count of widgets with conditions.
	 *
	 * @since 1.0.0
	 * @return int Number of widgets with visibility conditions.
	 */
	public static function get_conditions_count() {
		$all_conditions = self::get_all_conditions();
		return count( $all_conditions );
	}

	/**
	 * Migrate conditions from legacy (Jetpack) format.
	 *
	 * Reads existing widget options and extracts any 'conditions' data,
	 * storing it in our own option.
	 *
	 * @since 1.0.0
	 * @return array Migration results with counts.
	 */
	public static function migrate_from_legacy() {
		global $wpdb;

		$migrated   = 0;
		$conditions = array();

		// Get all widget options - direct query needed for bulk migration.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wvc_db_results = $wpdb->get_results(
			"SELECT option_name, option_value 
			FROM {$wpdb->options} 
			WHERE option_name LIKE 'widget_%'"
		);

		foreach ( $wvc_db_results as $wvc_row ) {
			// Skip non-widget options.
			if ( strpos( $wvc_row->option_name, 'widget_' ) !== 0 ) {
				continue;
			}

			$wvc_instances = maybe_unserialize( $wvc_row->option_value );

			if ( ! is_array( $wvc_instances ) ) {
				continue;
			}

			// Extract the widget base ID (remove 'widget_' prefix).
			$wvc_base_id = substr( $wvc_row->option_name, 7 );

			foreach ( $wvc_instances as $instance_id => $instance ) {
				// Skip non-numeric keys (like '_multiwidget').
				if ( ! is_numeric( $instance_id ) ) {
					continue;
				}

				// Ensure instance is an array (can be object in some cases).
				if ( is_object( $instance ) ) {
					$instance = (array) $instance;
				}

				if ( ! is_array( $instance ) ) {
					continue;
				}

				if ( isset( $instance['conditions'] ) && ! empty( $instance['conditions']['rules'] ) ) {
					$wvc_full_id = $wvc_base_id . '-' . $instance_id;
					$entry       = $instance['conditions'];
					if ( is_array( $entry ) && ! isset( $entry['_source'] ) ) {
						$entry['_source'] = 'jetpack';
					}
					$conditions[ $wvc_full_id ] = $entry;
					$migrated++;
				}
			}
		}

		if ( ! empty( $conditions ) ) {
			update_option( AYUDAWP_WVC_CONDITIONS_OPTION, $conditions );
			self::$conditions_cache = $conditions;
		}

		return array(
			'migrated' => $migrated,
			'total'    => count( $conditions ),
		);
	}

	/**
	 * Clean legacy (Jetpack) conditions from widget options.
	 *
	 * Removes the 'conditions' key from all widget instances,
	 * leaving our own storage as the only source.
	 *
	 * @since 1.0.0
	 * @return array Cleanup results with counts.
	 */
	public static function clean_legacy_data() {
		global $wpdb;

		$cleaned = 0;

		// Get all widget options - direct query needed for bulk cleanup.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wvc_db_results = $wpdb->get_results(
			"SELECT option_name, option_value 
			FROM {$wpdb->options} 
			WHERE option_name LIKE 'widget_%'"
		);

		foreach ( $wvc_db_results as $wvc_row ) {
			if ( strpos( $wvc_row->option_name, 'widget_' ) !== 0 ) {
				continue;
			}

			$wvc_instances = maybe_unserialize( $wvc_row->option_value );

			if ( ! is_array( $wvc_instances ) ) {
				continue;
			}

			$modified = false;

			foreach ( $wvc_instances as $instance_id => $instance ) {
				if ( ! is_numeric( $instance_id ) ) {
					continue;
				}

				// Ensure instance is an array (can be object in some cases).
				if ( is_object( $instance ) ) {
					$instance = (array) $instance;
					$wvc_instances[ $instance_id ] = $instance;
					$modified = true;
				}

				if ( ! is_array( $instance ) ) {
					continue;
				}

				if ( isset( $instance['conditions'] ) ) {
					unset( $wvc_instances[ $instance_id ]['conditions'] );
					$modified = true;
					$cleaned++;
				}
			}

			if ( $modified ) {
				update_option( $wvc_row->option_name, $wvc_instances );
			}
		}

		return array(
			'cleaned' => $cleaned,
		);
	}

	/**
	 * Count widgets with legacy conditions.
	 *
	 * @since 1.0.0
	 * @return int Number of widget instances with legacy conditions.
	 */
	public static function count_legacy_conditions() {
		global $wpdb;

		$count = 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wvc_db_results = $wpdb->get_results(
			"SELECT option_value 
			FROM {$wpdb->options} 
			WHERE option_name LIKE 'widget_%'"
		);

		foreach ( $wvc_db_results as $wvc_row ) {
			$wvc_instances = maybe_unserialize( $wvc_row->option_value );

			if ( ! is_array( $wvc_instances ) ) {
				continue;
			}

			foreach ( $wvc_instances as $instance_id => $instance ) {
				if ( ! is_numeric( $instance_id ) ) {
					continue;
				}

				// Ensure instance is an array (can be object in some cases).
				if ( is_object( $instance ) ) {
					$instance = (array) $instance;
				}

				if ( ! is_array( $instance ) ) {
					continue;
				}

				if ( isset( $instance['conditions'] ) && ! empty( $instance['conditions']['rules'] ) ) {
					$count++;
				}
			}
		}

		return $count;
	}

	/**
	 * Sync a widget's conditions from our storage back to legacy format.
	 *
	 * This allows Jetpack to read conditions set by our plugin if needed.
	 *
	 * @since 1.0.0
	 * @param string $widget_id  Widget ID.
	 * @param array  $conditions Conditions to sync.
	 * @return bool True on success.
	 */
	public static function sync_to_legacy( $widget_id, $conditions ) {
		// Parse widget ID (e.g., 'categories-2' -> base='categories', num=2).
		if ( ! preg_match( '/^(.+)-(\d+)$/', $widget_id, $matches ) ) {
			return false;
		}

		$wvc_base_id   = $matches[1];
		$instance_id   = (int) $matches[2];
		$wvc_opt_name  = 'widget_' . $wvc_base_id;

		$wvc_instances = get_option( $wvc_opt_name, array() );

		if ( ! is_array( $wvc_instances ) || ! isset( $wvc_instances[ $instance_id ] ) ) {
			return false;
		}

		$has_rules    = ! empty( $conditions['rules'] );
		$has_schedule = ! empty( $conditions['schedule']['action'] );

		if ( ! empty( $conditions ) && ( $has_rules || $has_schedule ) ) {
			$wvc_instances[ $instance_id ]['conditions'] = $conditions;
		} else {
			unset( $wvc_instances[ $instance_id ]['conditions'] );
		}

		return update_option( $wvc_opt_name, $wvc_instances );
	}

	/**
	 * Build a synthetic conditions array that hides a widget unconditionally.
	 *
	 * Uses the dedicated `always` rule type registered in the evaluator. Used
	 * by the Widget Logic importer when the user marks an unsupported rule as
	 * "import as always hidden".
	 *
	 * @since 1.3.0
	 * @return array Conditions array ready for save_conditions().
	 */
	public static function synthesize_always_hide_conditions() {
		return array(
			'action'    => 'hide',
			'rules'     => array(
				array(
					'major'        => 'always',
					'minor'        => '',
					'has_children' => '',
				),
			),
			'match_all' => '0',
		);
	}

	/**
	 * Count widgets that still have Widget Logic data in the database.
	 *
	 * Looks both in the global `widget_logic` option and inside each
	 * `widget_<base>` option's instances. A widget counts when it has a
	 * non-empty `widget_logic` string.
	 *
	 * @since 1.3.0
	 * @return int Number of widget instances with Widget Logic data.
	 */
	public static function count_widget_logic_candidates() {
		global $wpdb;

		$count = 0;
		$seen  = array();

		$wl_option = get_option( 'widget_logic' );
		if ( is_array( $wl_option ) ) {
			foreach ( $wl_option as $key => $val ) {
				if ( ! is_string( $key ) ) {
					continue;
				}
				if ( 0 === strpos( $key, 'widget_logic-options-' ) ) {
					continue;
				}
				if ( 'msg' === $key ) {
					continue;
				}
				if ( ! is_string( $val ) || '' === trim( $val ) ) {
					continue;
				}
				$count++;
				$seen[ $key ] = true;
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT option_name, option_value
			FROM {$wpdb->options}
			WHERE option_name LIKE 'widget\\_%'"
		);

		foreach ( $rows as $row ) {
			if ( 'widget_logic' === $row->option_name ) {
				continue;
			}
			$instances = maybe_unserialize( $row->option_value );
			if ( ! is_array( $instances ) ) {
				continue;
			}
			$base = substr( $row->option_name, 7 );
			foreach ( $instances as $instance_id => $instance ) {
				if ( ! is_numeric( $instance_id ) ) {
					continue;
				}
				if ( is_object( $instance ) ) {
					$instance = (array) $instance;
				}
				if ( ! is_array( $instance ) ) {
					continue;
				}
				if ( empty( $instance['widget_logic'] ) || ! is_string( $instance['widget_logic'] ) ) {
					continue;
				}
				if ( '' === trim( $instance['widget_logic'] ) ) {
					continue;
				}
				$full_id = $base . '-' . $instance_id;
				if ( isset( $seen[ $full_id ] ) ) {
					continue;
				}
				$count++;
				$seen[ $full_id ] = true;
			}
		}

		return $count;
	}

	/**
	 * Remove all Widget Logic data from the database.
	 *
	 * Deletes the global `widget_logic` option entirely (including the
	 * widget_logic-options-* configuration keys) and removes the
	 * `widget_logic` key from every widget instance inside `widget_<base>`
	 * options.
	 *
	 * @since 1.3.0
	 * @return array{cleaned:int} Number of locations cleaned (option deletions + per-instance removals).
	 */
	public static function clean_widget_logic_data() {
		global $wpdb;

		$cleaned = 0;

		if ( false !== get_option( 'widget_logic', false ) ) {
			delete_option( 'widget_logic' );
			$cleaned++;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT option_name, option_value
			FROM {$wpdb->options}
			WHERE option_name LIKE 'widget\\_%'"
		);

		foreach ( $rows as $row ) {
			if ( 'widget_logic' === $row->option_name ) {
				continue;
			}
			$instances = maybe_unserialize( $row->option_value );
			if ( ! is_array( $instances ) ) {
				continue;
			}
			$modified = false;
			foreach ( $instances as $instance_id => $instance ) {
				if ( ! is_numeric( $instance_id ) ) {
					continue;
				}
				if ( is_object( $instance ) ) {
					$instance                      = (array) $instance;
					$instances[ $instance_id ]     = $instance;
					$modified                      = true;
				}
				if ( ! is_array( $instance ) ) {
					continue;
				}
				if ( array_key_exists( 'widget_logic', $instance ) ) {
					unset( $instances[ $instance_id ]['widget_logic'] );
					$modified = true;
					$cleaned++;
				}
			}
			if ( $modified ) {
				update_option( $row->option_name, $instances );
			}
		}

		delete_transient( 'ayudawp_wvc_wl_has_data' );

		return array( 'cleaned' => $cleaned );
	}

	/**
	 * Record a migration event for a given source.
	 *
	 * Stores the import metadata (version, timestamp, count) keyed by source
	 * inside AYUDAWP_WVC_MIGRATED_OPTION. Preserves entries from other sources.
	 * Reads the old flat shape (just version/timestamp/source) and converts it
	 * on the fly so upgrades don't lose history.
	 *
	 * @since 1.4.0
	 * @param string $source 'jetpack' | 'widget-logic' | other importer key.
	 * @param int    $count  How many widgets were imported in this run.
	 * @return void
	 */
	public static function record_migration( $source, $count = 0 ) {
		$migrations = self::get_migrations();

		$migrations[ $source ] = array(
			'version'   => AYUDAWP_WVC_VERSION,
			'timestamp' => time(),
			'count'     => (int) $count,
		);

		update_option( AYUDAWP_WVC_MIGRATED_OPTION, $migrations );
	}

	/**
	 * Get migration history per source.
	 *
	 * Returns a map of source => array{version,timestamp,count}. Handles the
	 * legacy single-record shape (version+timestamp+source at the top level)
	 * by mapping it under its `source` key (or 'jetpack' if missing).
	 *
	 * @since 1.4.0
	 * @return array<string, array{version:string, timestamp:int, count:int}>
	 */
	public static function get_migrations() {
		$raw = get_option( AYUDAWP_WVC_MIGRATED_OPTION, array() );

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return array();
		}

		// Detect legacy flat shape: has 'timestamp' or 'version' at top level.
		if ( isset( $raw['timestamp'] ) || isset( $raw['version'] ) ) {
			$src = isset( $raw['source'] ) && is_string( $raw['source'] ) ? $raw['source'] : 'jetpack';
			return array(
				$src => array(
					'version'   => isset( $raw['version'] ) ? (string) $raw['version'] : '',
					'timestamp' => isset( $raw['timestamp'] ) ? (int) $raw['timestamp'] : 0,
					'count'     => 0,
				),
			);
		}

		return $raw;
	}

	/**
	 * Count widgets in our own conditions option grouped by their _source meta.
	 *
	 * Each saved conditions array can carry an optional `_source` key written
	 * by the importers ('jetpack', 'widget-logic'). Anything without that key
	 * is assumed to be created manually from the UI.
	 *
	 * @since 1.4.0
	 * @return array{manual:int, jetpack:int, widget-logic:int, total:int}
	 */
	public static function count_conditions_by_source() {
		$conditions = self::get_all_conditions();
		$counts     = array(
			'manual'       => 0,
			'jetpack'      => 0,
			'widget-logic' => 0,
			'total'        => 0,
		);

		foreach ( $conditions as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$counts['total']++;
			$source = isset( $entry['_source'] ) ? (string) $entry['_source'] : 'manual';
			if ( ! isset( $counts[ $source ] ) ) {
				$counts[ $source ] = 0;
			}
			$counts[ $source ]++;
		}

		return $counts;
	}

	/**
	 * Delete all plugin data (for uninstall).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function delete_all_plugin_data() {
		delete_option( AYUDAWP_WVC_CONDITIONS_OPTION );
		delete_option( AYUDAWP_WVC_SETTINGS_OPTION );
		delete_option( AYUDAWP_WVC_MIGRATED_OPTION );
		delete_option( 'ayudawp_wvc_jetpack_notice_dismissed' );
		delete_option( 'ayudawp_wvc_wl_notice_dismissed' );
		delete_option( AYUDAWP_WVC_WL_SNIPPETS_OPTION );
		delete_transient( 'ayudawp_wvc_wl_has_data' );
	}
}