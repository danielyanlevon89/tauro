<?php
/**
 * Widget Logic Migrator - Detect Widget Logic data, show notice, run assisted import.
 *
 * Widget Logic (https://wordpress.org/plugins/widget-logic/) was closed on
 * the WordPress plugin directory. Both 5.x and 6.x stored visibility data
 * using the same database options, so the importer handles both families
 * with a single code path. The actual translation from Widget Logic PHP
 * snippets to structured rules lives in AyudaWP_WVC_Widget_Logic_Parser;
 * this class handles detection, the admin notice, candidate collection
 * and the form handler that applies the user's per-widget decisions.
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget Logic detection and assisted import.
 *
 * @since 1.3.0
 */
class AyudaWP_WVC_Widget_Logic_Migrator {

	/**
	 * Option storing whether the user dismissed the admin notice.
	 */
	const NOTICE_DISMISSED_OPTION = 'ayudawp_wvc_wl_notice_dismissed';

	/**
	 * Transient caching whether Widget Logic data exists in the database.
	 */
	const HAS_DATA_TRANSIENT = 'ayudawp_wvc_wl_has_data';

	/**
	 * Maximum size kept per preserved Widget Logic snippet.
	 *
	 * Real rules are one-liners; the cap is there so a pathological snippet
	 * cannot bloat the option.
	 *
	 * @since 1.4.0
	 */
	const MAX_SNIPPET_BYTES = 8192;

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_notice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_notice_script' ) );
		add_action( 'wp_ajax_ayudawp_wvc_dismiss_wl_notice', array( __CLASS__, 'ajax_dismiss_notice' ) );
		add_action( 'admin_post_ayudawp_wvc_apply_wl_import', array( __CLASS__, 'handle_apply_import_post' ) );
	}

	/**
	 * Whether Widget Logic data exists in the database.
	 *
	 * Cached for five minutes in a transient to keep admin_notices cheap.
	 *
	 * @return bool
	 */
	public static function has_widget_logic_data() {
		$cached = get_transient( self::HAS_DATA_TRANSIENT );
		if ( '1' === $cached ) {
			return true;
		}
		if ( '0' === $cached ) {
			return false;
		}

		$found = false;

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
				if ( is_string( $val ) && '' !== trim( $val ) ) {
					$found = true;
					break;
				}
			}
		}

		if ( ! $found ) {
			global $wpdb;

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
					if ( ! empty( $instance['widget_logic'] ) && is_string( $instance['widget_logic'] ) && '' !== trim( $instance['widget_logic'] ) ) {
						$found = true;
						break 2;
					}
				}
			}
		}

		set_transient( self::HAS_DATA_TRANSIENT, $found ? '1' : '0', 5 * MINUTE_IN_SECONDS );
		return $found;
	}

	/**
	 * Whether Widget Logic plugin is currently active.
	 *
	 * @return bool
	 */
	public static function is_widget_logic_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'widget-logic/widget_logic.php' );
	}

	/**
	 * Enumerate widgets that have Widget Logic data, including the parser preview.
	 *
	 * Walks both the legacy global `widget_logic` option and the per-base
	 * `widget_<base>` options. When the same widget id appears in both, the
	 * per-base instance value wins and `source` is set to `both`.
	 *
	 * @return array<int, array<string, mixed>> List of candidate entries.
	 */
	public static function collect_candidates() {
		global $wpdb;

		$logic_by_id = array();
		$source_map  = array();

		// 1) Global widget_logic option (legacy storage).
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
				$logic_by_id[ $key ] = $val;
				$source_map[ $key ]  = 'option';
			}
		}

		// 2) Per-base widget_<base> options (new storage from WL 5.7+).
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

				$logic_by_id[ $full_id ] = $instance['widget_logic'];
				if ( isset( $source_map[ $full_id ] ) ) {
					$source_map[ $full_id ] = 'both';
				} else {
					$source_map[ $full_id ] = 'instance';
				}
			}
		}

		if ( empty( $logic_by_id ) ) {
			return array();
		}

		$existing       = AyudaWP_WVC_Data_Manager::get_all_conditions();
		$sidebar_lookup = self::build_sidebar_lookup();
		$candidates     = array();

		foreach ( $logic_by_id as $widget_id => $logic_raw ) {
			$parse_result = AyudaWP_WVC_Widget_Logic_Parser::parse( $logic_raw );

			$candidates[] = array(
				'widget_id'        => $widget_id,
				'widget_label'     => self::get_widget_label( $widget_id ),
				'sidebar'          => isset( $sidebar_lookup[ $widget_id ] ) ? $sidebar_lookup[ $widget_id ] : null,
				'logic_raw'        => $logic_raw,
				'parse_result'     => $parse_result,
				'has_existing_wvc' => isset( $existing[ $widget_id ] ),
				'source'           => $source_map[ $widget_id ],
			);
		}

		usort(
			$candidates,
			static function ( $a, $b ) {
				$sa = (string) ( $a['sidebar'] ?? '' );
				$sb = (string) ( $b['sidebar'] ?? '' );
				if ( $sa !== $sb ) {
					return strnatcasecmp( $sa, $sb );
				}
				return strnatcasecmp( $a['widget_id'], $b['widget_id'] );
			}
		);

		return $candidates;
	}

	/**
	 * Apply the user's import decisions.
	 *
	 * Does NOT bail out when Widget Logic is active. The import is safe to
	 * run regardless: this plugin writes to its own option and the Widget
	 * Logic data is preserved untouched until the user runs the cleanup
	 * action separately.
	 *
	 * @param array<string, string> $decisions Map of widget_id => 'import'|'visible'|'hidden'|'skip'.
	 * @return array Summary counts (imported, set_hidden, set_visible, skipped, errors).
	 */
	public static function apply_import( $decisions ) {
		$summary = array(
			'imported'    => 0,
			'set_hidden'  => 0,
			'set_visible' => 0,
			'skipped'     => 0,
			'errors'      => array(),
		);

		if ( ! current_user_can( 'manage_options' ) ) {
			$summary['errors'][] = __( 'Permission denied.', 'widget-visibility-control' );
			return $summary;
		}

		$candidates_by_id = array();
		foreach ( self::collect_candidates() as $candidate ) {
			$candidates_by_id[ $candidate['widget_id'] ] = $candidate;
		}

		foreach ( $decisions as $widget_id => $decision ) {
			$widget_id = sanitize_text_field( $widget_id );
			$decision  = sanitize_key( $decision );

			if ( ! isset( $candidates_by_id[ $widget_id ] ) ) {
				$summary['errors'][] = sprintf(
					/* translators: %s: widget identifier. */
					__( 'Widget no longer has Widget Logic data: %s', 'widget-visibility-control' ),
					$widget_id
				);
				continue;
			}

			$candidate = $candidates_by_id[ $widget_id ];

			switch ( $decision ) {
				case 'import':
					$parse = $candidate['parse_result'];
					if ( 'ok' === $parse['status'] || 'always_hide' === $parse['status'] ) {
						$conditions = array(
							'action'    => $parse['action'],
							'rules'     => $parse['rules'],
							'match_all' => $parse['match_all'],
							'_source'   => 'widget-logic',
						);
						if ( AyudaWP_WVC_Data_Manager::save_conditions( $widget_id, $conditions ) ) {
							$summary['imported']++;
						} else {
							$summary['errors'][] = sprintf(
								/* translators: %s: widget identifier. */
								__( 'Could not save imported rules for %s', 'widget-visibility-control' ),
								$widget_id
							);
						}
					} else {
						$summary['errors'][] = sprintf(
							/* translators: 1: widget identifier, 2: parser reason. */
							__( 'Cannot import %1$s automatically (%2$s). Choose visible, hidden or skip.', 'widget-visibility-control' ),
							$widget_id,
							$parse['reason']
						);
					}
					break;

				case 'visible':
					AyudaWP_WVC_Data_Manager::delete_conditions( $widget_id );
					$summary['set_visible']++;
					break;

				case 'hidden':
					$hidden = AyudaWP_WVC_Data_Manager::synthesize_always_hide_conditions();
					$hidden['_source'] = 'widget-logic';
					AyudaWP_WVC_Data_Manager::save_conditions( $widget_id, $hidden );
					$summary['set_hidden']++;
					break;

				case 'skip':
				default:
					$summary['skipped']++;
					break;
			}

			self::preserve_snippet( $widget_id, $candidate, $decision );
		}

		delete_transient( self::HAS_DATA_TRANSIENT );

		$count_for_history = $summary['imported'] + $summary['set_hidden'] + $summary['set_visible'];
		AyudaWP_WVC_Data_Manager::record_migration( 'widget-logic', $count_for_history );

		return $summary;
	}

	/**
	 * Store the original Widget Logic code of a widget alongside the decision taken.
	 *
	 * Widget Logic data can be removed from the database at any time from the
	 * Import / Export tab, and the manual decisions (always visible, always
	 * hidden, skip) drop the original logic entirely. Keeping a copy here means
	 * the user can still see what a widget used to do, and lets a future release
	 * offer a better translation once the parser understands more rules.
	 *
	 * @since 1.4.0
	 *
	 * @param string $widget_id Widget identifier.
	 * @param array  $candidate Candidate data as collected from Widget Logic.
	 * @param string $decision  Decision applied: import, visible, hidden or skip.
	 * @return void
	 */
	private static function preserve_snippet( $widget_id, $candidate, $decision ) {
		$raw = isset( $candidate['logic_raw'] ) ? (string) $candidate['logic_raw'] : '';
		if ( '' === trim( $raw ) ) {
			return;
		}

		// Only strip invalid UTF-8: the snippet is PHP source and any further
		// sanitising would mangle newlines, backslashes or the opening tag.
		$raw = wp_check_invalid_utf8( $raw, true );

		$truncated = false;
		if ( strlen( $raw ) > self::MAX_SNIPPET_BYTES ) {
			$raw       = mb_strcut( $raw, 0, self::MAX_SNIPPET_BYTES );
			$truncated = true;
		}

		$parse  = isset( $candidate['parse_result'] ) ? $candidate['parse_result'] : array();
		$stored = get_option( AYUDAWP_WVC_WL_SNIPPETS_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$stored[ $widget_id ] = array(
			'raw'            => $raw,
			'decision'       => $decision,
			'parse_status'   => isset( $parse['status'] ) ? $parse['status'] : '',
			'parse_reason'   => isset( $parse['reason'] ) ? $parse['reason'] : '',
			'source'         => isset( $candidate['source'] ) ? $candidate['source'] : '',
			'imported_at'    => time(),
			'plugin_version' => AYUDAWP_WVC_VERSION,
			'truncated'      => $truncated,
		);

		update_option( AYUDAWP_WVC_WL_SNIPPETS_OPTION, $stored, false );
	}

	/**
	 * Admin notice: invite the user to import Widget Logic data when present.
	 *
	 * @return void
	 */
	public static function maybe_show_notice() {
		if ( ! self::should_show_notice() ) {
			return;
		}

		$settings_url = admin_url( 'themes.php?page=widget-visibility-control&tab=import-export#widget-logic' );
		$active       = self::is_widget_logic_active();
		?>
		<div class="notice notice-info is-dismissible" id="ayudawp-wvc-wl-notice">
			<p><strong><?php esc_html_e( 'Widget Visibility Control detected Widget Logic data', 'widget-visibility-control' ); ?></strong></p>
			<p>
				<?php esc_html_e( 'We found visibility rules saved by Widget Logic. You can import them into Widget Visibility Control whenever you\'re ready.', 'widget-visibility-control' ); ?>
			</p>
			<p>
				<?php
				printf(
					'%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
					esc_html__( 'Widget Logic was closed on the WordPress.org plugin directory on 14 April 2026 and no longer receives updates. Your rules are still stored in this database, so this import works even if Widget Logic is already deactivated or deleted.', 'widget-visibility-control' ),
					esc_url( 'https://wordpress.org/plugins/widget-logic/' ),
					esc_html__( 'See the plugin page on WordPress.org', 'widget-visibility-control' )
				);
				?>
			</p>
			<?php if ( $active ) : ?>
				<p>
					<em><?php esc_html_e( 'Widget Logic is currently active. You can import your rules now or later — once you disable Widget Logic, Widget Visibility Control will take over automatically.', 'widget-visibility-control' ); ?></em>
				</p>
			<?php endif; ?>
			<p>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Review and import', 'widget-visibility-control' ); ?>
				</a>
				<button type="button" class="button" id="ayudawp-wvc-wl-dismiss-notice" style="margin-left: 6px;">
					<?php esc_html_e( 'Dismiss', 'widget-visibility-control' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue the small JS that handles dismiss for the admin notice.
	 *
	 * @return void
	 */
	public static function enqueue_notice_script() {
		if ( ! self::should_show_notice() ) {
			return;
		}

		wp_enqueue_script(
			'ayudawp-wvc-wl-notice',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/js/admin-wl-notice.js',
			array( 'jquery' ),
			AYUDAWP_WVC_VERSION,
			true
		);

		wp_localize_script(
			'ayudawp-wvc-wl-notice',
			'ayudawp_wvc_wl_notice',
			array(
				'nonce'  => wp_create_nonce( 'ayudawp_wvc_wl_dismiss' ),
				'action' => 'ayudawp_wvc_dismiss_wl_notice',
			)
		);
	}

	/**
	 * AJAX handler to dismiss the Widget Logic notice.
	 *
	 * @return void
	 */
	public static function ajax_dismiss_notice() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ayudawp_wvc_wl_dismiss' ) ) {
			wp_die( -1, 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1, 403 );
		}
		update_option( self::NOTICE_DISMISSED_OPTION, true, false );
		wp_die( 1 );
	}

	/**
	 * Handle the wizard form submission. Bound to admin_post_*.
	 *
	 * @return void
	 */
	public static function handle_apply_import_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'widget-visibility-control' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'ayudawp_wvc_wl_import' );

		$raw_decisions = array();
		if ( isset( $_POST['decisions'] ) && is_array( $_POST['decisions'] ) ) {
			$raw_decisions = map_deep( wp_unslash( $_POST['decisions'] ), 'sanitize_text_field' );
		}

		$decisions = array();
		foreach ( $raw_decisions as $widget_id => $decision ) {
			if ( ! is_string( $widget_id ) || ! is_string( $decision ) ) {
				continue;
			}
			$decisions[ sanitize_text_field( $widget_id ) ] = sanitize_key( $decision );
		}

		$summary = self::apply_import( $decisions );

		$redirect = add_query_arg(
			array(
				'page'        => 'widget-visibility-control',
				'tab'         => 'import-export',
				'wl_imported' => (int) $summary['imported'],
				'wl_hidden'   => (int) $summary['set_hidden'],
				'wl_visible'  => (int) $summary['set_visible'],
				'wl_skipped'  => (int) $summary['skipped'],
				'_wpnonce'    => wp_create_nonce( 'ayudawp_wvc_wl_import_result' ),
			),
			admin_url( 'themes.php' )
		);

		$redirect .= '#widget-logic';

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Best-effort human label for a widget id, falling back to the id.
	 *
	 * @param string $widget_id Widget id like `text-3`.
	 * @return string
	 */
	public static function get_widget_label( $widget_id ) {
		global $wp_registered_widgets;

		if ( is_array( $wp_registered_widgets ) && isset( $wp_registered_widgets[ $widget_id ]['name'] ) ) {
			$name = (string) $wp_registered_widgets[ $widget_id ]['name'];
			if ( preg_match( '/^(.+)-(\d+)$/', $widget_id, $m ) ) {
				return sprintf( '%s (#%s)', $name, $m[2] );
			}
			return $name;
		}

		if ( preg_match( '/^(.+)-(\d+)$/', $widget_id, $m ) ) {
			$base = str_replace( array( '_', '-' ), ' ', $m[1] );
			return sprintf( '%s (#%s)', ucwords( $base ), $m[2] );
		}

		return $widget_id;
	}

	/**
	 * Map of widget_id => sidebar_slug for widgets currently placed in sidebars.
	 *
	 * @return array<string, string>
	 */
	private static function build_sidebar_lookup() {
		$lookup   = array();
		$sidebars = get_option( 'sidebars_widgets', array() );
		if ( ! is_array( $sidebars ) ) {
			return $lookup;
		}
		foreach ( $sidebars as $sidebar_id => $widget_ids ) {
			if ( ! is_array( $widget_ids ) ) {
				continue;
			}
			if ( 'wp_inactive_widgets' === $sidebar_id ) {
				continue;
			}
			foreach ( $widget_ids as $wid ) {
				$lookup[ $wid ] = $sidebar_id;
			}
		}
		return $lookup;
	}

	/**
	 * Whether the current admin screen should display the notice.
	 *
	 * @return bool
	 */
	private static function is_notice_screen() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}
		return in_array( $screen->id, array( 'widgets', 'plugins', 'dashboard', 'customize' ), true );
	}

	/**
	 * Predicate combining capability, screen, dismissal and data presence.
	 *
	 * @return bool
	 */
	private static function should_show_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		if ( ! self::is_notice_screen() ) {
			return false;
		}
		if ( get_option( self::NOTICE_DISMISSED_OPTION ) ) {
			return false;
		}
		return self::has_widget_logic_data();
	}
}
