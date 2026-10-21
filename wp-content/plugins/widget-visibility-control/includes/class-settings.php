<?php
/**
 * Settings Page - Plugin configuration, data management and Widget Logic import wizard.
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the plugin settings page.
 *
 * @since 1.0.0
 */
class AyudaWP_WVC_Settings {

	/**
	 * Initialize settings.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_ayudawp_wvc_clean_legacy', array( __CLASS__, 'ajax_clean_legacy' ) );
		add_action( 'wp_ajax_ayudawp_wvc_remigrate', array( __CLASS__, 'ajax_remigrate' ) );
		add_action( 'wp_ajax_ayudawp_wvc_clean_wl', array( __CLASS__, 'ajax_clean_wl' ) );
		add_action( 'admin_post_ayudawp_wvc_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_ayudawp_wvc_import_json', array( __CLASS__, 'handle_import_json' ) );
	}

	/**
	 * Add settings page to admin menu.
	 *
	 * @return void
	 */
	public static function add_settings_page() {
		add_theme_page(
			__( 'Widget Visibility Control', 'widget-visibility-control' ),
			__( 'Widget Visibility', 'widget-visibility-control' ),
			'manage_options',
			'widget-visibility-control',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'ayudawp_wvc_settings',
			AYUDAWP_WVC_SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Settings input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$sanitized                     = array();
		$sanitized['keep_legacy_data'] = ! empty( $input['keep_legacy_data'] );
		return $sanitized;
	}

	/**
	 * Enqueue admin scripts for the settings page.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function enqueue_admin_scripts( $hook_suffix ) {
		if ( 'appearance_page_widget-visibility-control' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'ayudawp-wvc-admin-settings',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/css/admin-settings.css',
			array( 'dashicons' ),
			AYUDAWP_WVC_VERSION
		);

		wp_enqueue_script(
			'ayudawp-wvc-admin-settings',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/js/admin-settings.js',
			array( 'jquery' ),
			AYUDAWP_WVC_VERSION,
			true
		);

		wp_localize_script(
			'ayudawp-wvc-admin-settings',
			'ayudawp_wvc_settings',
			array(
				'clean_nonce'     => wp_create_nonce( 'ayudawp_wvc_clean_legacy' ),
				'remigrate_nonce' => wp_create_nonce( 'ayudawp_wvc_remigrate' ),
				'clean_wl_nonce'  => wp_create_nonce( 'ayudawp_wvc_clean_wl' ),
				'confirm_clean'   => __( 'Are you sure? This will remove Jetpack format data from all widgets. This action cannot be undone.', 'widget-visibility-control' ),
				'confirm_wl'      => __( 'Are you sure? This will remove all Widget Logic data from the database. This action cannot be undone.', 'widget-visibility-control' ),
				'cleaning'        => __( 'Cleaning...', 'widget-visibility-control' ),
				'importing'       => __( 'Importing...', 'widget-visibility-control' ),
				'clean_button'    => __( 'Remove Jetpack format data', 'widget-visibility-control' ),
				'clean_wl_button' => __( 'Remove Widget Logic data', 'widget-visibility-control' ),
				'reimport_button' => __( 'Re-import visibility rules', 'widget-visibility-control' ),
			)
		);

		// Wizard-specific JS only loads when the Import/Export tab is active and there are candidates.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only: picks which tab of the settings page to show.
		if ( 'import-export' === $tab ) {
			wp_enqueue_script(
				'ayudawp-wvc-admin-collapsible',
				AYUDAWP_WVC_PLUGIN_URL . 'assets/js/admin-collapsible.js',
				array(),
				AYUDAWP_WVC_VERSION,
				true
			);

			wp_localize_script(
				'ayudawp-wvc-admin-collapsible',
				'ayudawp_wvc_collapsible',
				array(
					'collapse' => __( 'Collapse this section', 'widget-visibility-control' ),
					'expand'   => __( 'Expand this section', 'widget-visibility-control' ),
				)
			);


			wp_enqueue_script(
				'ayudawp-wvc-admin-wl-import',
				AYUDAWP_WVC_PLUGIN_URL . 'assets/js/admin-wl-import.js',
				array( 'jquery' ),
				AYUDAWP_WVC_VERSION,
				true
			);

			wp_localize_script(
				'ayudawp-wvc-admin-wl-import',
				'ayudawp_wvc_wl_import',
				array(
					'confirm_submit' => __( "You're about to apply the following:\n  %1\$d to import,\n  %2\$d as always visible,\n  %3\$d as always hidden,\n  %4\$d to skip.\nContinue?", 'widget-visibility-control' ),
					'no_decisions'   => __( 'No decisions selected.', 'widget-visibility-control' ),
				)
			);
		}
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only: picks which tab of the settings page to show.
		if ( ! in_array( $tab, array( 'overview', 'import-export' ), true ) ) {
			$tab = 'overview';
		}

		$base_url       = admin_url( 'themes.php?page=widget-visibility-control' );
		$overview_url   = $base_url . '&tab=overview';
		$import_url     = $base_url . '&tab=import-export';

		?>
		<div class="wrap ayudawp-wvc-wrap">
			<h1><?php esc_html_e( 'Widget Visibility Control', 'widget-visibility-control' ); ?></h1>

			<nav class="nav-tab-wrapper ayudawp-wvc-tabs" aria-label="<?php esc_attr_e( 'Sections', 'widget-visibility-control' ); ?>">
				<a href="<?php echo esc_url( $overview_url ); ?>" class="nav-tab <?php echo 'overview' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Overview', 'widget-visibility-control' ); ?>
				</a>
				<a href="<?php echo esc_url( $import_url ); ?>" class="nav-tab <?php echo 'import-export' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Import / Export', 'widget-visibility-control' ); ?>
				</a>
			</nav>

			<div class="ayudawp-wvc-layout">
				<div class="ayudawp-wvc-main">
					<?php
					if ( 'overview' === $tab ) {
						self::render_tab_overview( $import_url );
					} else {
						self::render_tab_import_export();
					}
					?>
				</div>

				<aside class="ayudawp-wvc-sidebar" aria-label="<?php esc_attr_e( 'AyudaWP services', 'widget-visibility-control' ); ?>">
					<?php self::render_promo_banner(); ?>
				</aside>
			</div>
		</div>
		<?php
	}

	/**
	 * Overview tab: data status, "Coming from..." CTAs, how it works.
	 *
	 * @param string $import_url URL of the Import/Export tab.
	 * @return void
	 */
	private static function render_tab_overview( $import_url ) {
		$by_source     = AyudaWP_WVC_Data_Manager::count_conditions_by_source();
		$jetpack_count = AyudaWP_WVC_Data_Manager::count_legacy_conditions();
		$wl_count      = AyudaWP_WVC_Data_Manager::count_widget_logic_candidates();
		$migrations    = AyudaWP_WVC_Data_Manager::get_migrations();
		$date_fmt      = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		?>
		<div class="ayudawp-wvc-tab-content">

			<div class="card ayudawp-wvc-card ayudawp-wvc-summary">
				<h2><?php esc_html_e( 'Visibility rules', 'widget-visibility-control' ); ?></h2>

				<div class="ayudawp-wvc-summary-total">
					<span class="ayudawp-wvc-summary-number"><?php echo esc_html( $by_source['total'] ); ?></span>
					<span class="ayudawp-wvc-summary-label">
						<?php
						echo esc_html(
							_n( 'widget with rules', 'widgets with rules', $by_source['total'], 'widget-visibility-control' )
						);
						?>
					</span>
				</div>

				<?php if ( $by_source['total'] > 0 ) : ?>
					<ul class="ayudawp-wvc-summary-breakdown">
						<?php
						$rows = array(
							'widget-logic' => array(
								'label' => __( 'Imported from Widget Logic', 'widget-visibility-control' ),
								'count' => $by_source['widget-logic'],
							),
							'jetpack'      => array(
								'label' => __( 'Imported from Jetpack', 'widget-visibility-control' ),
								'count' => $by_source['jetpack'],
							),
							'manual'       => array(
								'label' => __( 'Created manually', 'widget-visibility-control' ),
								'count' => $by_source['manual'],
							),
						);
						foreach ( $rows as $src_key => $row ) :
							if ( 0 === $row['count'] ) {
								continue;
							}
							$migration_meta = isset( $migrations[ $src_key ] ) ? $migrations[ $src_key ] : null;
							$when           = '';
							if ( $migration_meta && ! empty( $migration_meta['timestamp'] ) ) {
								$when = date_i18n( $date_fmt, (int) $migration_meta['timestamp'] );
							}
							?>
							<li>
								<span class="ayudawp-wvc-summary-source"><?php echo esc_html( $row['label'] ); ?></span>
								<span class="ayudawp-wvc-summary-count"><?php echo esc_html( $row['count'] ); ?></span>
								<?php if ( '' !== $when ) : ?>
									<span class="ayudawp-wvc-summary-meta">&middot; <?php echo esc_html( $when ); ?></span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No visibility rules yet. Configure them per widget in Appearance > Widgets, or import from another plugin below.', 'widget-visibility-control' ); ?></p>
				<?php endif; ?>

				<?php if ( $jetpack_count > 0 || $wl_count > 0 ) : ?>
					<div class="ayudawp-wvc-summary-pending">
						<h3><?php esc_html_e( 'Pending data', 'widget-visibility-control' ); ?></h3>
						<ul>
							<?php if ( $wl_count > 0 ) : ?>
								<li>
									<span class="dashicons dashicons-warning" aria-hidden="true"></span>
									<strong><?php echo esc_html( $wl_count ); ?></strong>
									<?php esc_html_e( 'widgets with Widget Logic data ready to import', 'widget-visibility-control' ); ?>
									<a href="<?php echo esc_url( $import_url . '#widget-logic' ); ?>" class="button button-small"><?php esc_html_e( 'Review', 'widget-visibility-control' ); ?></a>
								</li>
							<?php endif; ?>
							<?php if ( $jetpack_count > 0 ) : ?>
								<li>
									<span class="dashicons dashicons-warning" aria-hidden="true"></span>
									<strong><?php echo esc_html( $jetpack_count ); ?></strong>
									<?php esc_html_e( 'widgets with Jetpack-format data', 'widget-visibility-control' ); ?>
									<a href="<?php echo esc_url( $import_url . '#jetpack' ); ?>" class="button button-small"><?php esc_html_e( 'Manage', 'widget-visibility-control' ); ?></a>
								</li>
							<?php endif; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>

			<div class="card ayudawp-wvc-card">
				<h2><?php esc_html_e( 'Coming from another plugin?', 'widget-visibility-control' ); ?></h2>
				<p><?php esc_html_e( 'Bring your existing visibility rules over. Each tool has its own section in Import / Export.', 'widget-visibility-control' ); ?></p>
				<ul class="ayudawp-wvc-cta-list">
					<li>
						<div class="ayudawp-wvc-cta-text">
							<strong><?php esc_html_e( 'Coming from Jetpack?', 'widget-visibility-control' ); ?></strong>
							<span><?php esc_html_e( 'Jetpack rules are imported automatically. Re-run the import or clean the legacy data here.', 'widget-visibility-control' ); ?></span>
						</div>
						<a href="<?php echo esc_url( $import_url . '#jetpack' ); ?>" class="button"><?php esc_html_e( 'Open section', 'widget-visibility-control' ); ?></a>
					</li>
					<li>
						<div class="ayudawp-wvc-cta-text">
							<strong><?php esc_html_e( 'Coming from Widget Logic?', 'widget-visibility-control' ); ?></strong>
							<span><?php esc_html_e( 'Assisted import that translates Widget Logic PHP rules (5.x and 6.x). You decide per widget what to do with code we cannot translate.', 'widget-visibility-control' ); ?></span>
						</div>
						<a href="<?php echo esc_url( $import_url . '#widget-logic' ); ?>" class="button"><?php esc_html_e( 'Open section', 'widget-visibility-control' ); ?></a>
					</li>
					<li>
						<div class="ayudawp-wvc-cta-text">
							<strong><?php esc_html_e( 'Coming from Widget Options?', 'widget-visibility-control' ); ?></strong>
							<span><?php esc_html_e( 'No automatic importer yet. The most common visibility scenarios are supported here — configure them manually.', 'widget-visibility-control' ); ?></span>
						</div>
						<a href="<?php echo esc_url( $import_url . '#widget-options' ); ?>" class="button"><?php esc_html_e( 'Open section', 'widget-visibility-control' ); ?></a>
					</li>
				</ul>
			</div>

			<div class="card ayudawp-wvc-card">
				<h2><?php esc_html_e( 'How it works', 'widget-visibility-control' ); ?></h2>
				<p><?php esc_html_e( 'Your visibility rules are stored independently from Jetpack, Widget Logic, or other plugins. That keeps your data portable and safe from third-party changes.', 'widget-visibility-control' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php esc_html_e( 'Jetpack rules are imported automatically on first activation; you can re-run the import any time.', 'widget-visibility-control' ); ?></li>
					<li><?php esc_html_e( 'Widget Logic rules are imported on demand via the assisted wizard.', 'widget-visibility-control' ); ?></li>
					<li><?php esc_html_e( 'New rules you create live in our own option — original third-party data is preserved.', 'widget-visibility-control' ); ?></li>
					<li><?php esc_html_e( 'Cleanup actions per source let you delete legacy data when you are ready.', 'widget-visibility-control' ); ?></li>
				</ul>
			</div>

		</div>
		<?php
	}

	/**
	 * Import / Export tab: export and restore, Jetpack section, Widget Logic wizard, Widget Options placeholder.
	 *
	 * @return void
	 */
	private static function render_tab_import_export() {
		self::render_json_import_notice();

		// Success notice after applying a Widget Logic import. The redirect issued by the
		// wizard handler signs the counters, so the nonce is verified on its own before
		// any of the feedback flags is read.
		$wl_feedback_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( '' !== $wl_feedback_nonce && wp_verify_nonce( $wl_feedback_nonce, 'ayudawp_wvc_wl_import_result' ) ) {
			$imported = isset( $_GET['wl_imported'] ) ? absint( wp_unslash( $_GET['wl_imported'] ) ) : 0;
			$hidden   = isset( $_GET['wl_hidden'] ) ? absint( wp_unslash( $_GET['wl_hidden'] ) ) : 0;
			$visible  = isset( $_GET['wl_visible'] ) ? absint( wp_unslash( $_GET['wl_visible'] ) ) : 0;
			$skipped  = isset( $_GET['wl_skipped'] ) ? absint( wp_unslash( $_GET['wl_skipped'] ) ) : 0;
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					printf(
						/* translators: 1: imported count, 2: set-hidden count, 3: set-visible count, 4: skipped count. */
						esc_html__( 'Widget Logic import applied: %1$d imported, %2$d set as always hidden, %3$d as always visible, %4$d skipped.', 'widget-visibility-control' ),
						(int) $imported,
						(int) $hidden,
						(int) $visible,
						(int) $skipped
					);
					?>
				</p>
			</div>
			<?php
		}

		$jetpack_count = AyudaWP_WVC_Data_Manager::count_legacy_conditions();
		$wl_count      = AyudaWP_WVC_Data_Manager::count_widget_logic_candidates();
		$wl_active     = AyudaWP_WVC_Widget_Logic_Migrator::is_widget_logic_active();
		?>
		<div class="ayudawp-wvc-tab-content">

			<?php
			// Export goes first: once the imports are done they stop being useful,
			// while exporting stays relevant for backups and staging to production.
			self::render_export_card();
			?>

			<div class="card ayudawp-wvc-card" id="jetpack">
				<h2 class="ayudawp-wvc-card-toggle" data-card="jetpack"><?php esc_html_e( 'Jetpack', 'widget-visibility-control' ); ?></h2>
				<div class="ayudawp-wvc-card-body">
				<p><?php esc_html_e( 'Jetpack Widget Visibility uses the same data format as this plugin. Rules are imported automatically on activation; you can re-run the import or remove the legacy data here.', 'widget-visibility-control' ); ?></p>

				<?php if ( 0 === $jetpack_count ) : ?>
					<p class="ayudawp-wvc-last-import">
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<?php esc_html_e( 'No Jetpack-format data left in widget instances. Any imported rules now live in this plugin\'s own storage.', 'widget-visibility-control' ); ?>
					</p>
				<?php endif; ?>

				<p>
					<button type="button" class="button" id="ayudawp-wvc-remigrate" <?php disabled( 0 === $jetpack_count ); ?>>
						<?php esc_html_e( 'Re-import visibility rules', 'widget-visibility-control' ); ?>
					</button>
					<button type="button" class="button" id="ayudawp-wvc-clean-legacy" <?php disabled( 0 === $jetpack_count ); ?>>
						<?php esc_html_e( 'Remove Jetpack format data', 'widget-visibility-control' ); ?>
					</button>
				</p>
				<p class="description">
					<?php esc_html_e( 'Re-import re-reads conditions from widgets in Jetpack format. Remove deletes the legacy keys from each widget instance. Both act on the legacy copies only — your imported rules in this plugin are not affected.', 'widget-visibility-control' ); ?>
				</p>
				<div id="ayudawp-wvc-remigrate-result" style="margin-top:10px;"></div>
				<div id="ayudawp-wvc-clean-result" style="margin-top:10px;"></div>
				</div>
			</div>

			<div class="card ayudawp-wvc-card" id="widget-logic">
				<h2 class="ayudawp-wvc-card-toggle" data-card="widget-logic"><?php esc_html_e( 'Widget Logic', 'widget-visibility-control' ); ?></h2>
				<div class="ayudawp-wvc-card-body">

				<div class="ayudawp-wvc-inline-notice ayudawp-wvc-inline-notice--warning">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<p>
						<?php
						printf(
							'%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
							esc_html__( 'Widget Logic was closed on the WordPress.org plugin directory on 14 April 2026 and no longer receives updates. This import reads the rules Widget Logic left in your database, so it works even if the plugin is already deactivated or deleted.', 'widget-visibility-control' ),
							esc_url( 'https://wordpress.org/plugins/widget-logic/' ),
							esc_html__( 'See the plugin page on WordPress.org', 'widget-visibility-control' )
						);
						?>
					</p>
				</div>

				<?php
				$wl_snippets = get_option( AYUDAWP_WVC_WL_SNIPPETS_OPTION, array() );
				$wl_snippets = is_array( $wl_snippets ) ? $wl_snippets : array();
				if ( $wl_snippets ) :
					?>
					<p class="description">
						<?php
						printf(
							/* translators: %d: number of widgets whose original Widget Logic code is stored. */
							esc_html( _n( 'The original Widget Logic code is kept for %d widget, so you can still check what it used to do.', 'The original Widget Logic code is kept for %d widgets, so you can still check what they used to do.', count( $wl_snippets ), 'widget-visibility-control' ) ),
							count( $wl_snippets )
						);
						?>
					</p>
				<?php endif; ?>

				<?php
				$wl_migrations    = AyudaWP_WVC_Data_Manager::get_migrations();
				$wl_active_counts = AyudaWP_WVC_Data_Manager::count_conditions_by_source();
				if (
					isset( $wl_migrations['widget-logic'] )
					&& $wl_active_counts['widget-logic'] > 0
				) :
					$wl_meta = $wl_migrations['widget-logic'];
					$wl_when = ! empty( $wl_meta['timestamp'] )
						? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $wl_meta['timestamp'] )
						: '';
					$wl_n    = isset( $wl_meta['count'] ) ? (int) $wl_meta['count'] : 0;
					?>
					<p class="ayudawp-wvc-last-import">
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<?php
						if ( $wl_n > 0 && '' !== $wl_when ) {
							printf(
								/* translators: 1: number of widgets imported, 2: formatted date and time. */
								esc_html( _n( 'Last import: %1$d widget on %2$s.', 'Last import: %1$d widgets on %2$s.', $wl_n, 'widget-visibility-control' ) ),
								(int) $wl_n,
								esc_html( $wl_when )
							);
						} elseif ( '' !== $wl_when ) {
							printf(
								/* translators: %s: formatted date and time. */
								esc_html__( 'Last import on %s.', 'widget-visibility-control' ),
								esc_html( $wl_when )
							);
						}
						?>
					</p>
					<?php
				endif;
				?>

				<?php if ( $wl_active ) : ?>
					<div class="ayudawp-wvc-inline-notice ayudawp-wvc-inline-notice--info">
						<span class="dashicons dashicons-info" aria-hidden="true"></span>
						<p>
							<strong><?php esc_html_e( 'Widget Logic is currently active.', 'widget-visibility-control' ); ?></strong>
							<?php esc_html_e( 'You can import your rules now or later. Once you disable Widget Logic, Widget Visibility Control will take over automatically.', 'widget-visibility-control' ); ?>
						</p>
					</div>
				<?php endif; ?>

				<?php if ( $wl_count > 0 ) : ?>
					<?php self::render_wl_wizard(); ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No Widget Logic data detected on this site.', 'widget-visibility-control' ); ?></p>
				<?php endif; ?>

				<hr style="margin:20px 0;">

				<p>
					<button type="button" class="button" id="ayudawp-wvc-clean-wl" <?php disabled( 0 === $wl_count ); ?>>
						<?php esc_html_e( 'Remove Widget Logic data', 'widget-visibility-control' ); ?>
					</button>
				</p>
				<p class="description">
					<?php esc_html_e( 'Deletes the global widget_logic option and removes the widget_logic key from every widget instance. Imported rules in our own option are not touched.', 'widget-visibility-control' ); ?>
				</p>
				<div id="ayudawp-wvc-clean-wl-result" style="margin-top:10px;"></div>
				</div>
			</div>

			<div class="card ayudawp-wvc-card" id="widget-options">
				<h2 class="ayudawp-wvc-card-toggle" data-card="widget-options"><?php esc_html_e( 'Widget Options', 'widget-visibility-control' ); ?></h2>
				<div class="ayudawp-wvc-card-body">
					<p><?php esc_html_e( 'Widget Options stores its visibility data in a format that is very different from Widget Logic and Jetpack. An automatic importer is not available yet.', 'widget-visibility-control' ); ?></p>
					<p><?php esc_html_e( 'If you only used Widget Options for visibility, you can configure equivalent rules here manually — the most common scenarios (pages, post types, taxonomies, user roles, login state) are all supported.', 'widget-visibility-control' ); ?></p>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Result notice after importing a JSON file.
	 *
	 * The redirect that brings the user back here is signed, so the nonce is
	 * verified on its own before any feedback flag is read.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	private static function render_json_import_notice() {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'ayudawp_wvc_import_result' ) ) {
			return;
		}

		$error = isset( $_GET['wvc_import_error'] ) ? sanitize_key( wp_unslash( $_GET['wvc_import_error'] ) ) : '';

		if ( '' !== $error ) {
			$messages = array(
				'nofile'     => __( 'No file was uploaded, or the upload did not complete. Please choose a JSON file and try again.', 'widget-visibility-control' ),
				'toobig'     => __( 'That file is too large to be an export from this plugin.', 'widget-visibility-control' ),
				'unreadable' => __( 'The uploaded file could not be read.', 'widget-visibility-control' ),
				'badjson'    => __( 'That file is not valid JSON.', 'widget-visibility-control' ),
				'badformat'  => __( 'That JSON file was not exported by Widget Visibility Control.', 'widget-visibility-control' ),
			);
			$message  = isset( $messages[ $error ] ) ? $messages[ $error ] : $messages['badformat'];
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
			return;
		}

		$imported = isset( $_GET['wvc_imported'] ) ? absint( wp_unslash( $_GET['wvc_imported'] ) ) : 0;
		$skipped  = isset( $_GET['wvc_skipped'] ) ? absint( wp_unslash( $_GET['wvc_skipped'] ) ) : 0;
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: 1: number of widgets imported, 2: number of entries skipped. */
					esc_html__( 'Import finished: rules restored for %1$d widgets, %2$d entries skipped.', 'widget-visibility-control' ),
					(int) $imported,
					(int) $skipped
				);
				?>
			</p>
			<?php if ( $skipped > 0 ) : ?>
				<p class="description">
					<?php esc_html_e( 'Skipped entries used a rule type this version does not understand, or did not look like a widget entry.', 'widget-visibility-control' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Export card: download every visibility rule as a JSON file.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	private static function render_export_card() {
		$conditions = get_option( AYUDAWP_WVC_CONDITIONS_OPTION, array() );
		$conditions = is_array( $conditions ) ? $conditions : array();
		$total      = count( $conditions );
		?>
		<div class="card ayudawp-wvc-card" id="export">
			<h2 class="ayudawp-wvc-card-toggle" data-card="export">
				<?php esc_html_e( 'Export', 'widget-visibility-control' ); ?>
			</h2>
			<div class="ayudawp-wvc-card-body">
				<p><?php esc_html_e( 'Download every visibility rule as a JSON file. Useful as a backup before making changes, and to move your setup from a staging site to production.', 'widget-visibility-control' ); ?></p>

				<?php if ( 0 === $total ) : ?>
					<p class="ayudawp-wvc-last-import">
						<span class="dashicons dashicons-info" aria-hidden="true"></span>
						<?php esc_html_e( 'There are no visibility rules to export yet.', 'widget-visibility-control' ); ?>
					</p>
				<?php else : ?>
					<p class="ayudawp-wvc-last-import">
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<?php
						printf(
							/* translators: %d: number of widgets with visibility rules. */
							esc_html( _n( '%d widget with rules ready to export.', '%d widgets with rules ready to export.', $total, 'widget-visibility-control' ) ),
							(int) $total
						);
						?>
					</p>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ayudawp_wvc_export" />
					<?php wp_nonce_field( 'ayudawp_wvc_export' ); ?>
					<p>
						<button type="submit" class="button button-primary" <?php disabled( 0 === $total ); ?>>
							<?php esc_html_e( 'Download JSON', 'widget-visibility-control' ); ?>
						</button>
					</p>
				</form>

				<p class="description">
					<?php esc_html_e( 'The file records which widget each rule belongs to, along with the plugin version and the site it came from.', 'widget-visibility-control' ); ?>
				</p>

				<hr style="margin:20px 0;">

				<h3><?php esc_html_e( 'Restore from a file', 'widget-visibility-control' ); ?></h3>
				<p><?php esc_html_e( 'Load a JSON file exported from this plugin. Rules are matched to widgets by their identifier, so restoring works best on the same site or on a copy of it.', 'widget-visibility-control' ); ?></p>

				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ayudawp_wvc_import_json" />
					<?php wp_nonce_field( 'ayudawp_wvc_import_json' ); ?>

					<p>
						<label class="screen-reader-text" for="ayudawp-wvc-import-file"><?php esc_html_e( 'JSON file', 'widget-visibility-control' ); ?></label>
						<input type="file" name="ayudawp_wvc_import_file" id="ayudawp-wvc-import-file" accept=".json,application/json" required />
					</p>

					<fieldset style="margin-bottom:12px;">
						<legend class="screen-reader-text"><?php esc_html_e( 'What to do with existing rules', 'widget-visibility-control' ); ?></legend>
						<p>
							<label>
								<input type="radio" name="ayudawp_wvc_import_mode" value="merge" checked="checked" />
								<strong><?php esc_html_e( 'Merge', 'widget-visibility-control' ); ?></strong>
								&mdash; <?php esc_html_e( 'add the rules from the file and overwrite only the widgets it contains. Everything else is left alone.', 'widget-visibility-control' ); ?>
							</label>
						</p>
						<p>
							<label>
								<input type="radio" name="ayudawp_wvc_import_mode" value="replace" />
								<strong><?php esc_html_e( 'Replace', 'widget-visibility-control' ); ?></strong>
								&mdash; <?php esc_html_e( 'delete every current rule first, so the site ends up exactly as the file describes.', 'widget-visibility-control' ); ?>
							</label>
						</p>
					</fieldset>

					<p>
						<button type="submit" class="button">
							<?php esc_html_e( 'Import file', 'widget-visibility-control' ); ?>
						</button>
					</p>
				</form>

				<p class="description">
					<?php esc_html_e( 'Rules that do not match anything this version understands are skipped and reported, never imported half-way. Download a backup with the button above before replacing.', 'widget-visibility-control' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle the export request and stream the JSON file to the browser.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You are not allowed to export visibility rules.', 'widget-visibility-control' ),
				esc_html__( 'Permission denied', 'widget-visibility-control' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'ayudawp_wvc_export' );

		$conditions = get_option( AYUDAWP_WVC_CONDITIONS_OPTION, array() );
		$conditions = is_array( $conditions ) ? $conditions : array();

		$payload = array(
			'format'         => 'widget-visibility-control',
			'format_version' => 1,
			'plugin_version' => AYUDAWP_WVC_VERSION,
			'exported_at'    => gmdate( 'c' ),
			'site_url'       => home_url(),
			'widget_count'   => count( $conditions ),
			'conditions'     => $conditions,
		);

		if ( false === wp_json_encode( $payload ) ) {
			wp_die(
				esc_html__( 'Could not build the export file.', 'widget-visibility-control' ),
				esc_html__( 'Export failed', 'widget-visibility-control' ),
				array( 'response' => 500 )
			);
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$name = sprintf(
			'widget-visibility-control-%s-%s.json',
			sanitize_file_name( $host ? $host : 'site' ),
			gmdate( 'Y-m-d' )
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );

		// A JSON file download, not HTML: wp_json_encode() is the escaping here.
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Maximum accepted size of an uploaded export file, in bytes.
	 *
	 * @since 1.4.0
	 */
	const MAX_IMPORT_BYTES = 2097152;

	/**
	 * Rule types this version knows how to evaluate.
	 *
	 * Anything else in an uploaded file is skipped: importing a rule the
	 * evaluator does not understand would silently hide widgets.
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	private static function allowed_majors() {
		return array( 'category', 'loggedin', 'author', 'role', 'capability', 'tag', 'date', 'page', 'url', 'taxonomy', 'always' );
	}

	/**
	 * Validate and clean one widget entry coming from an uploaded file.
	 *
	 * Everything is rebuilt from scratch instead of trusting the incoming
	 * array, so an edited file cannot introduce unexpected keys or values.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed $entry Raw entry from the JSON file.
	 * @return array|null Clean conditions array, or null when unusable.
	 */
	private static function sanitize_imported_entry( $entry ) {
		if ( ! is_array( $entry ) ) {
			return null;
		}

		$clean = array(
			'action'    => ( isset( $entry['action'] ) && 'hide' === $entry['action'] ) ? 'hide' : 'show',
			'rules'     => array(),
			'match_all' => ( isset( $entry['match_all'] ) && '1' === (string) $entry['match_all'] ) ? '1' : '0',
		);

		$allowed_majors = self::allowed_majors();

		if ( isset( $entry['rules'] ) && is_array( $entry['rules'] ) ) {
			foreach ( $entry['rules'] as $rule ) {
				if ( ! is_array( $rule ) || ! isset( $rule['major'] ) ) {
					return null;
				}

				$major = sanitize_key( $rule['major'] );
				if ( ! in_array( $major, $allowed_majors, true ) ) {
					return null;
				}

				if ( 'url' === $major ) {
					// Same cleaner as the widget form. A URL rule without a
					// pattern is not a rule, so the entry is refused.
					$minor = AyudaWP_Widget_Conditions::sanitize_url_pattern( isset( $rule['minor'] ) ? $rule['minor'] : '' );
					if ( '' === $minor ) {
						return null;
					}
				} else {
					$minor = isset( $rule['minor'] ) ? sanitize_text_field( (string) $rule['minor'] ) : '';
				}

				$clean['rules'][] = array(
					'major'        => $major,
					'minor'        => $minor,
					'has_children' => empty( $rule['has_children'] ) ? '' : '1',
				);
			}
		}

		// Schedule, rebuilt key by key with the shape the evaluator expects.
		if ( isset( $entry['schedule'] ) && is_array( $entry['schedule'] ) ) {
			$schedule        = $entry['schedule'];
			$schedule_action = isset( $schedule['action'] ) ? sanitize_key( $schedule['action'] ) : '';

			if ( in_array( $schedule_action, array( 'show', 'hide' ), true ) ) {
				$clean['schedule'] = array(
					'enabled'     => ! empty( $schedule['enabled'] ),
					'action'      => $schedule_action,
					'from'        => isset( $schedule['from'] ) ? sanitize_text_field( (string) $schedule['from'] ) : '',
					'to'          => isset( $schedule['to'] ) ? sanitize_text_field( (string) $schedule['to'] ) : '',
					'no_end_date' => ! empty( $schedule['no_end_date'] ),
				);
			}
		}

		// An entry with neither rules nor schedule would be dropped on save.
		if ( empty( $clean['rules'] ) && empty( $clean['schedule']['action'] ) ) {
			return null;
		}

		if ( isset( $entry['_source'] ) ) {
			$source = sanitize_key( $entry['_source'] );
			if ( in_array( $source, array( 'widget-logic', 'jetpack' ), true ) ) {
				$clean['_source'] = $source;
			}
		}

		return $clean;
	}

	/**
	 * Handle the import of a previously exported JSON file.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public static function handle_import_json() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You are not allowed to import visibility rules.', 'widget-visibility-control' ),
				esc_html__( 'Permission denied', 'widget-visibility-control' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'ayudawp_wvc_import_json' );

		$mode = isset( $_POST['ayudawp_wvc_import_mode'] ) ? sanitize_key( wp_unslash( $_POST['ayudawp_wvc_import_mode'] ) ) : 'merge';
		if ( ! in_array( $mode, array( 'merge', 'replace' ), true ) ) {
			$mode = 'merge';
		}

		if ( ! isset( $_FILES['ayudawp_wvc_import_file'] ) || ! isset( $_FILES['ayudawp_wvc_import_file']['error'] ) ) {
			self::redirect_after_import( 'nofile' );
		}

		$upload_error = (int) $_FILES['ayudawp_wvc_import_file']['error'];
		if ( UPLOAD_ERR_OK !== $upload_error ) {
			self::redirect_after_import( UPLOAD_ERR_INI_SIZE === $upload_error || UPLOAD_ERR_FORM_SIZE === $upload_error ? 'toobig' : 'nofile' );
		}

		$tmp_name = isset( $_FILES['ayudawp_wvc_import_file']['tmp_name'] )
			? sanitize_text_field( wp_unslash( $_FILES['ayudawp_wvc_import_file']['tmp_name'] ) )
			: '';

		// Only a genuine PHP upload is ever read.
		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
			self::redirect_after_import( 'nofile' );
		}

		$size = isset( $_FILES['ayudawp_wvc_import_file']['size'] ) ? (int) $_FILES['ayudawp_wvc_import_file']['size'] : 0;
		if ( $size <= 0 || $size > self::MAX_IMPORT_BYTES ) {
			self::redirect_after_import( 'toobig' );
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$raw = $wp_filesystem ? $wp_filesystem->get_contents( $tmp_name ) : false;
		if ( false === $raw || '' === $raw ) {
			self::redirect_after_import( 'unreadable' );
		}

		$data = json_decode( $raw, true, 32 );
		if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
			self::redirect_after_import( 'badjson' );
		}

		if ( ! isset( $data['format'] ) || 'widget-visibility-control' !== $data['format'] ) {
			self::redirect_after_import( 'badformat' );
		}

		if ( ! isset( $data['conditions'] ) || ! is_array( $data['conditions'] ) ) {
			self::redirect_after_import( 'badformat' );
		}

		$imported = 0;
		$skipped  = 0;
		$clean    = array();

		foreach ( $data['conditions'] as $widget_id => $entry ) {
			$widget_id = sanitize_text_field( (string) $widget_id );

			// Widget ids always look like {base}-{instance number}.
			if ( ! preg_match( '/^[a-z0-9_\-]+-\d+$/i', $widget_id ) ) {
				++$skipped;
				continue;
			}

			$entry = self::sanitize_imported_entry( $entry );
			if ( null === $entry ) {
				++$skipped;
				continue;
			}

			$clean[ $widget_id ] = $entry;
			++$imported;
		}

		if ( 'replace' === $mode ) {
			$final = $clean;
		} else {
			$existing = get_option( AYUDAWP_WVC_CONDITIONS_OPTION, array() );
			$existing = is_array( $existing ) ? $existing : array();
			$final    = array_merge( $existing, $clean );
		}

		update_option( AYUDAWP_WVC_CONDITIONS_OPTION, $final );
		AyudaWP_WVC_Data_Manager::record_migration( 'json', $imported );

		self::redirect_after_import( '', $imported, $skipped );
	}

	/**
	 * Redirect back to the Import / Export tab with a signed result.
	 *
	 * @since 1.4.0
	 *
	 * @param string $error    Error code, or empty string on success.
	 * @param int    $imported Number of widgets imported.
	 * @param int    $skipped  Number of entries skipped.
	 * @return void
	 */
	private static function redirect_after_import( $error = '', $imported = 0, $skipped = 0 ) {
		$args = array(
			'page'     => 'widget-visibility-control',
			'tab'      => 'import-export',
			'_wpnonce' => wp_create_nonce( 'ayudawp_wvc_import_result' ),
		);

		if ( '' !== $error ) {
			$args['wvc_import_error'] = $error;
		} else {
			$args['wvc_imported'] = (int) $imported;
			$args['wvc_skipped']  = (int) $skipped;
		}

		$redirect  = add_query_arg( $args, admin_url( 'themes.php' ) );
		$redirect .= '#export';

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Inline Widget Logic import wizard rendered inside the Import/Export tab.
	 *
	 * @return void
	 */
	private static function render_wl_wizard() {
		$candidates = AyudaWP_WVC_Widget_Logic_Migrator::collect_candidates();

		$count_mappable   = 0;
		$count_decide     = 0;
		$count_empty      = 0;
		$count_alwayshide = 0;
		$count_existing   = 0;

		foreach ( $candidates as $c ) {
			$status = $c['parse_result']['status'];
			if ( 'ok' === $status ) {
				$count_mappable++;
			} elseif ( 'unsupported' === $status ) {
				$count_decide++;
			} elseif ( 'always_hide' === $status ) {
				$count_alwayshide++;
			} elseif ( 'empty' === $status || 'always_show' === $status ) {
				$count_empty++;
			}
			if ( ! empty( $c['has_existing_wvc'] ) ) {
				$count_existing++;
			}
		}

		$total      = count( $candidates );
		$action_url = admin_url( 'admin-post.php' );
		?>
		<h3><?php esc_html_e( 'Import from Widget Logic', 'widget-visibility-control' ); ?></h3>
		<p>
			<?php
			printf(
				/* translators: 1: total widgets, 2: mappable count, 3: decide count, 4: empty count, 5: always-hide count. */
				esc_html__( 'Total: %1$d widgets — %2$d mappable · %3$d need a decision · %4$d empty · %5$d marked as always hidden.', 'widget-visibility-control' ),
				(int) $total,
				(int) $count_mappable,
				(int) $count_decide,
				(int) $count_empty,
				(int) $count_alwayshide
			);
			?>
		</p>
		<?php if ( $count_existing > 0 ) : ?>
			<p class="ayudawp-wvc-wl-already-imported">
				<?php
				if ( $count_existing === $total ) {
					esc_html_e( 'You already imported all of these. Re-running the wizard will overwrite the existing rules for any widget you mark as Import.', 'widget-visibility-control' );
				} else {
					printf(
						/* translators: 1: number of widgets already imported, 2: total widgets, 3: pending count. */
						esc_html( _n( '%1$d of %2$d widget is already imported · %3$d pending.', '%1$d of %2$d widgets are already imported · %3$d pending.', $total, 'widget-visibility-control' ) ),
						(int) $count_existing,
						(int) $total,
						(int) ( $total - $count_existing )
					);
				}
				?>
			</p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( $action_url ); ?>" id="ayudawp-wvc-wl-form">
			<input type="hidden" name="action" value="ayudawp_wvc_apply_wl_import" />
			<?php wp_nonce_field( 'ayudawp_wvc_wl_import' ); ?>

			<p class="ayudawp-wvc-wl-shortcuts">
				<button type="button" class="button button-secondary" id="ayudawp-wvc-wl-mark-all-import"><?php esc_html_e( 'Mark mappable as Import', 'widget-visibility-control' ); ?></button>
				<button type="button" class="button button-secondary" id="ayudawp-wvc-wl-mark-all-skip"><?php esc_html_e( 'Mark all as Skip', 'widget-visibility-control' ); ?></button>
			</p>

			<?php
			$groups = array();
			foreach ( $candidates as $c ) {
				$key            = (string) ( $c['sidebar'] ?? '__no_sidebar' );
				$groups[ $key ] = $groups[ $key ] ?? array();
				$groups[ $key ][] = $c;
			}

			$sidebars = $GLOBALS['wp_registered_sidebars'] ?? array();
			?>

			<?php foreach ( $groups as $sidebar_id => $group ) : ?>
				<?php
				$sidebar_label = '__no_sidebar' === $sidebar_id
					? __( 'Inactive / unassigned widgets', 'widget-visibility-control' )
					: ( isset( $sidebars[ $sidebar_id ]['name'] ) ? (string) $sidebars[ $sidebar_id ]['name'] : $sidebar_id );
				?>
				<h4 class="ayudawp-wvc-wl-sidebar"><?php echo esc_html( $sidebar_label ); ?></h4>
				<table class="widefat striped ayudawp-wvc-wl-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Widget', 'widget-visibility-control' ); ?></th>
							<th><?php esc_html_e( 'Widget Logic PHP', 'widget-visibility-control' ); ?></th>
							<th><?php esc_html_e( 'Decision', 'widget-visibility-control' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $group as $c ) : ?>
							<?php self::render_wl_candidate_row( $c ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<p style="margin-top:20px;">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Apply import', 'widget-visibility-control' ); ?>
				</button>
			</p>
		</form>
		<?php
	}

	/**
	 * Render a single candidate row inside the wizard table.
	 *
	 * @param array $c Candidate data.
	 * @return void
	 */
	private static function render_wl_candidate_row( $c ) {
		$widget_id   = $c['widget_id'];
		$widget_lbl  = $c['widget_label'];
		$logic_raw   = $c['logic_raw'];
		$parse       = $c['parse_result'];
		$status      = $parse['status'];
		$has_existing = ! empty( $c['has_existing_wvc'] );

		$name_attr = sprintf( 'decisions[%s]', $widget_id );
		?>
		<tr class="ayudawp-wvc-wl-row ayudawp-wvc-wl-status-<?php echo esc_attr( $status ); ?>">
			<td class="ayudawp-wvc-wl-widget">
				<code><?php echo esc_html( $widget_id ); ?></code>
				<br>
				<span class="ayudawp-wvc-wl-label"><?php echo esc_html( $widget_lbl ); ?></span>
				<?php if ( 'both' === $c['source'] ) : ?>
					<br><small class="description"><?php esc_html_e( 'Data found in both legacy and per-widget storage', 'widget-visibility-control' ); ?></small>
				<?php endif; ?>
			</td>
			<td class="ayudawp-wvc-wl-logic">
				<pre><?php echo esc_html( $logic_raw ); ?></pre>
			</td>
			<td class="ayudawp-wvc-wl-decision">
				<?php if ( 'ok' === $status ) : ?>
					<span class="ayudawp-wvc-badge ayudawp-wvc-badge-ok"><?php esc_html_e( 'Mappable', 'widget-visibility-control' ); ?></span>
					<div class="ayudawp-wvc-wl-preview">
						<strong><?php esc_html_e( 'Will import as:', 'widget-visibility-control' ); ?></strong>
						<?php echo esc_html( self::describe_rules( $parse ) ); ?>
					</div>
					<?php if ( $has_existing ) : ?>
						<div class="ayudawp-wvc-wl-warn">
							<span class="dashicons dashicons-warning" aria-hidden="true"></span>
							<?php esc_html_e( 'This widget already has rules. Importing will overwrite them.', 'widget-visibility-control' ); ?>
						</div>
					<?php endif; ?>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name_attr ); ?>" value="import" class="ayudawp-wvc-wl-radio-import" <?php checked( ! $has_existing ); ?> />
						<?php esc_html_e( 'Import', 'widget-visibility-control' ); ?>
					</label>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name_attr ); ?>" value="skip" class="ayudawp-wvc-wl-radio-skip" <?php checked( $has_existing ); ?> />
						<?php esc_html_e( 'Skip', 'widget-visibility-control' ); ?>
					</label>
				<?php elseif ( 'unsupported' === $status ) : ?>
					<span class="ayudawp-wvc-badge ayudawp-wvc-badge-decide"><?php esc_html_e( 'Decide', 'widget-visibility-control' ); ?></span>
					<div class="ayudawp-wvc-wl-reason"><?php echo esc_html( $parse['reason'] ); ?></div>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name_attr ); ?>" value="visible" class="ayudawp-wvc-wl-radio-visible" />
						<?php esc_html_e( 'Import as always visible', 'widget-visibility-control' ); ?>
					</label>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name_attr ); ?>" value="hidden" class="ayudawp-wvc-wl-radio-hidden" />
						<?php esc_html_e( 'Import as always hidden', 'widget-visibility-control' ); ?>
					</label>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name_attr ); ?>" value="skip" class="ayudawp-wvc-wl-radio-skip" checked />
						<?php esc_html_e( 'Skip', 'widget-visibility-control' ); ?>
					</label>
				<?php elseif ( 'always_hide' === $status ) : ?>
					<span class="ayudawp-wvc-badge ayudawp-wvc-badge-alwayshide"><?php esc_html_e( 'Always hidden', 'widget-visibility-control' ); ?></span>
					<div class="ayudawp-wvc-wl-preview">
						<?php esc_html_e( 'Logic is "false". Will be imported as always hidden.', 'widget-visibility-control' ); ?>
					</div>
					<?php if ( $has_existing ) : ?>
						<div class="ayudawp-wvc-wl-warn">
							<span class="dashicons dashicons-warning" aria-hidden="true"></span>
							<?php esc_html_e( 'This widget already has rules. Importing will overwrite them.', 'widget-visibility-control' ); ?>
						</div>
					<?php endif; ?>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name_attr ); ?>" value="import" class="ayudawp-wvc-wl-radio-import" <?php checked( ! $has_existing ); ?> />
						<?php esc_html_e( 'Import as always hidden', 'widget-visibility-control' ); ?>
					</label>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name_attr ); ?>" value="skip" class="ayudawp-wvc-wl-radio-skip" <?php checked( $has_existing ); ?> />
						<?php esc_html_e( 'Skip', 'widget-visibility-control' ); ?>
					</label>
				<?php else : ?>
					<span class="ayudawp-wvc-badge ayudawp-wvc-badge-empty"><?php esc_html_e( 'Empty', 'widget-visibility-control' ); ?></span>
					<div class="ayudawp-wvc-wl-preview">
						<?php esc_html_e( 'No logic. Widget will remain visible. Nothing to import.', 'widget-visibility-control' ); ?>
					</div>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Build a one-line human description of the parsed rules.
	 *
	 * @param array $parse Parser result.
	 * @return string
	 */
	private static function describe_rules( $parse ) {
		$action_label = 'show' === $parse['action']
			? __( 'Show if', 'widget-visibility-control' )
			: __( 'Hide if', 'widget-visibility-control' );

		$connector_label = '1' === $parse['match_all']
			? __( 'AND', 'widget-visibility-control' )
			: __( 'OR', 'widget-visibility-control' );

		$pieces = array();
		foreach ( $parse['rules'] as $rule ) {
			$pieces[] = self::describe_rule( $rule );
		}

		return $action_label . ': ' . implode( ' ' . $connector_label . ' ', $pieces );
	}

	/**
	 * Build a human label for a single rule.
	 *
	 * @param array $rule Rule descriptor.
	 * @return string
	 */
	private static function describe_rule( $rule ) {
		$major = $rule['major'];
		$minor = $rule['minor'];

		switch ( $major ) {
			case 'always':
				return __( 'always', 'widget-visibility-control' );
			case 'loggedin':
				return 'loggedin' === $minor
					? __( 'user is logged in', 'widget-visibility-control' )
					: __( 'user is logged out', 'widget-visibility-control' );
			case 'capability':
				/* translators: %s: capability name, for example manage_options. */
				return sprintf( __( 'user can "%s"', 'widget-visibility-control' ), $minor );
			case 'category':
				if ( '' === $minor ) {
					return __( 'any category page', 'widget-visibility-control' );
				}
				/* translators: %s: category term ID. */
				return sprintf( __( 'category #%s', 'widget-visibility-control' ), $minor );
			case 'tag':
				if ( '' === $minor ) {
					return __( 'any tag page', 'widget-visibility-control' );
				}
				/* translators: %s: tag term ID. */
				return sprintf( __( 'tag #%s', 'widget-visibility-control' ), $minor );
			case 'author':
				if ( '' === $minor ) {
					return __( 'any author page', 'widget-visibility-control' );
				}
				/* translators: %s: author user ID. */
				return sprintf( __( 'author #%s', 'widget-visibility-control' ), $minor );
			case 'date':
				if ( '' === $minor ) {
					return __( 'any date archive', 'widget-visibility-control' );
				}
				/* translators: %s: archive granularity (year, month, day). */
				return sprintf( __( '%s archive', 'widget-visibility-control' ), $minor );
			case 'page':
				if ( 'front' === $minor ) {
					return __( 'front page', 'widget-visibility-control' );
				}
				if ( 'posts' === $minor ) {
					return __( 'blog index', 'widget-visibility-control' );
				}
				if ( 'archive' === $minor ) {
					return __( 'any archive', 'widget-visibility-control' );
				}
				if ( '404' === $minor ) {
					return __( '404 page', 'widget-visibility-control' );
				}
				if ( 'search' === $minor ) {
					return __( 'search results', 'widget-visibility-control' );
				}
				if ( 'singular' === $minor ) {
					return __( 'any single content', 'widget-visibility-control' );
				}
				if ( 'sticky' === $minor ) {
					return __( 'sticky post', 'widget-visibility-control' );
				}
				if ( 'paged' === $minor ) {
					return __( 'paginated archive (page 2+)', 'widget-visibility-control' );
				}
				if ( 0 === strpos( $minor, 'post_type-' ) ) {
					/* translators: %s: post type slug. */
					return sprintf( __( 'post type "%s"', 'widget-visibility-control' ), substr( $minor, 10 ) );
				}
				if ( 0 === strpos( $minor, 'post_type_archive-' ) ) {
					/* translators: %s: post type slug for the archive. */
					return sprintf( __( '"%s" archive', 'widget-visibility-control' ), substr( $minor, 18 ) );
				}
				/* translators: %s: page ID. */
				return sprintf( __( 'page #%s', 'widget-visibility-control' ), $minor );
			case 'url':
				/* translators: %s: URL pattern set in the rule. */
				return sprintf( __( 'URL matches "%s"', 'widget-visibility-control' ), $minor );
			case 'taxonomy':
				if ( '' === $minor ) {
					return __( 'any taxonomy', 'widget-visibility-control' );
				}
				/* translators: %s: taxonomy slug or term identifier. */
				return sprintf( __( 'taxonomy "%s"', 'widget-visibility-control' ), $minor );
		}

		return sprintf( '%s = %s', $major, $minor );
	}

	/**
	 * Render the AyudaWP services promo banner (sidebar, 3 random cards).
	 *
	 * @return void
	 */
	private static function render_promo_banner() {
		if ( ! class_exists( 'AyudaWP_WVC_Promo_Banner', false ) ) {
			return;
		}
		$banner = new AyudaWP_WVC_Promo_Banner( 'ayudawp-wvc' );
		$banner->render_vertical();
	}

	/**
	 * AJAX: remove Jetpack format data from widget instances.
	 *
	 * @return void
	 */
	public static function ajax_clean_legacy() {
		check_ajax_referer( 'ayudawp_wvc_clean_legacy', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'widget-visibility-control' ) ) );
		}

		$result = AyudaWP_WVC_Data_Manager::clean_legacy_data();

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of widgets cleaned */
					_n(
						'Cleaned legacy data from %d widget.',
						'Cleaned legacy data from %d widgets.',
						$result['cleaned'],
						'widget-visibility-control'
					),
					$result['cleaned']
				),
				'cleaned' => $result['cleaned'],
			)
		);
	}

	/**
	 * AJAX: re-run the Jetpack migration.
	 *
	 * @return void
	 */
	public static function ajax_remigrate() {
		check_ajax_referer( 'ayudawp_wvc_remigrate', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'widget-visibility-control' ) ) );
		}

		$result = AyudaWP_WVC_Data_Manager::migrate_from_legacy();

		AyudaWP_WVC_Data_Manager::record_migration( 'jetpack', isset( $result['migrated'] ) ? (int) $result['migrated'] : 0 );

		wp_send_json_success(
			array(
				'message'  => sprintf(
					/* translators: %d: number of widgets imported */
					_n(
						'Imported visibility rules from %d widget.',
						'Imported visibility rules from %d widgets.',
						$result['migrated'],
						'widget-visibility-control'
					),
					$result['migrated']
				),
				'migrated' => $result['migrated'],
			)
		);
	}

	/**
	 * AJAX: remove all Widget Logic data from the database.
	 *
	 * @return void
	 */
	public static function ajax_clean_wl() {
		check_ajax_referer( 'ayudawp_wvc_clean_wl', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'widget-visibility-control' ) ) );
		}

		$result = AyudaWP_WVC_Data_Manager::clean_widget_logic_data();

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of Widget Logic locations cleaned */
					_n(
						'Cleaned Widget Logic data from %d location.',
						'Cleaned Widget Logic data from %d locations.',
						$result['cleaned'],
						'widget-visibility-control'
					),
					$result['cleaned']
				),
				'cleaned' => $result['cleaned'],
			)
		);
	}
}
