<?php
/**
 * Jetpack Migrator - Handles detection of Jetpack Widget Visibility.
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Jetpack Widget Visibility detection.
 *
 * @since 1.0.0
 */
class AyudaWP_Jetpack_Migrator {

	/**
	 * Initialize the migrator.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_jetpack_notice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_notice_script' ) );
		add_action( 'wp_ajax_ayudawp_wvc_dismiss_jetpack_notice', array( __CLASS__, 'dismiss_jetpack_notice' ) );
	}

	/**
	 * Check if Jetpack is active.
	 *
	 * @since 1.0.0
	 * @return bool True if Jetpack plugin is active.
	 */
	public static function is_jetpack_active() {
		// Check if Jetpack class exists.
		if ( class_exists( 'Jetpack' ) ) {
			return true;
		}

		// Check if plugin is active via include.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'jetpack/jetpack.php' );
	}

	/**
	 * Check if Jetpack Widget Visibility module is active.
	 *
	 * @since 1.0.0
	 * @return bool True if Jetpack Widget Visibility is enabled.
	 */
	public static function is_jetpack_widget_visibility_active() {
		if ( ! self::is_jetpack_active() ) {
			return false;
		}

		// Check via Jetpack API (most reliable method).
		if ( class_exists( 'Jetpack' ) && method_exists( 'Jetpack', 'is_module_active' ) ) {
			return Jetpack::is_module_active( 'widget-visibility' );
		}

		// Check active modules option directly (fallback).
		$active_modules = get_option( 'jetpack_active_modules', array() );
		if ( is_array( $active_modules ) && in_array( 'widget-visibility', $active_modules, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current screen should show the notice.
	 *
	 * @since 1.0.0
	 * @return bool True if notice should be shown on this screen.
	 */
	private static function is_notice_screen() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		// Show on widgets screen, plugins screen, and dashboard.
		$allowed_screens = array(
			'widgets',
			'plugins',
			'dashboard',
			'customize',
		);

		return in_array( $screen->id, $allowed_screens, true );
	}

	/**
	 * Check if notice should be displayed.
	 *
	 * @since 1.0.0
	 * @return bool True if notice should be displayed.
	 */
	private static function should_show_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! self::is_notice_screen() ) {
			return false;
		}

		if ( get_option( 'ayudawp_wvc_jetpack_notice_dismissed' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue notice dismiss script.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function enqueue_notice_script() {
		if ( ! self::should_show_notice() ) {
			return;
		}

		wp_enqueue_script(
			'ayudawp-wvc-admin-notice',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/js/admin-notice.js',
			array( 'jquery' ),
			AYUDAWP_WVC_VERSION,
			true
		);

		wp_localize_script(
			'ayudawp-wvc-admin-notice',
			'ayudawp_wvc_notice',
			array(
				'nonce' => wp_create_nonce( 'ayudawp_wvc_dismiss' ),
			)
		);
	}

	/**
	 * Show admin notice about the plugin.
	 * 
	 * Shows different content based on whether Jetpack Widget Visibility is active.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function maybe_show_jetpack_notice() {
		if ( ! self::should_show_notice() ) {
			return;
		}

		$settings_url     = admin_url( 'themes.php?page=widget-visibility-control&tab=import-export#jetpack' );
		$jetpack_active   = self::is_jetpack_widget_visibility_active();
		$conditions_count = 0;

		if ( class_exists( 'AyudaWP_WVC_Data_Manager' ) ) {
			$conditions_count = AyudaWP_WVC_Data_Manager::get_conditions_count();
		}

		if ( $jetpack_active ) {
			// Jetpack Widget Visibility is active - show warning.
			?>
			<div class="notice notice-warning is-dismissible" id="ayudawp-wvc-notice">
				<p><strong><?php esc_html_e( 'Widget Visibility Control', 'widget-visibility-control' ); ?></strong></p>
				<p>
					<?php esc_html_e( 'Jetpack Widget Visibility module is currently active. To avoid conflicts, our visibility interface is disabled while Jetpack\'s module is active. Your existing visibility rules have been imported to our independent storage.', 'widget-visibility-control' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'You can continue using Jetpack\'s interface, or disable the Jetpack module to switch to our lightweight alternative.', 'widget-visibility-control' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=jetpack#/settings?term=widget' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Disable Jetpack Module', 'widget-visibility-control' ); ?>
					</a>
					<a href="<?php echo esc_url( $settings_url ); ?>" class="button">
						<?php esc_html_e( 'Open Import / Export', 'widget-visibility-control' ); ?>
					</a>
					<button type="button" class="button" id="ayudawp-wvc-dismiss-notice" style="margin-left: 6px;">
						<?php esc_html_e( 'Dismiss', 'widget-visibility-control' ); ?>
					</button>
				</p>
			</div>
			<?php
		} else {
			// Jetpack not active - show welcome/info notice.
			?>
			<div class="notice notice-info is-dismissible" id="ayudawp-wvc-notice">
				<p><strong><?php esc_html_e( 'Widget Visibility Control activated!', 'widget-visibility-control' ); ?></strong></p>
				<p>
					<?php
					if ( $conditions_count > 0 ) {
						printf(
							/* translators: %d: number of widgets with visibility rules */
							esc_html( _n(
								'%d widget with visibility rules has been imported. You can review the imported data in the plugin settings.',
								'%d widgets with visibility rules have been imported. You can review the imported data in the plugin settings.',
								$conditions_count,
								'widget-visibility-control'
							) ),
							absint( $conditions_count )
						);
					} else {
						esc_html_e( 'Control which widgets appear on specific pages, posts, categories, and more. Click the "Visibility" button on any widget to configure its display rules.', 'widget-visibility-control' );
					}
					?>
				</p>
				<p>
					<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary">
						<?php esc_html_e( 'Open Import / Export', 'widget-visibility-control' ); ?>
					</a>
					<button type="button" class="button" id="ayudawp-wvc-dismiss-notice" style="margin-left: 6px;">
						<?php esc_html_e( 'Dismiss', 'widget-visibility-control' ); ?>
					</button>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * AJAX handler to dismiss the Jetpack notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function dismiss_jetpack_notice() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ayudawp_wvc_dismiss' ) ) {
			wp_die( -1, 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1, 403 );
		}

		update_option( 'ayudawp_wvc_jetpack_notice_dismissed', true, false );
		wp_die( 1 );
	}
}
