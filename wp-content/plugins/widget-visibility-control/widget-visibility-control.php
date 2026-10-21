<?php
/**
 * Plugin Name: Widget Visibility Control
 * Plugin URI: https://servicios.ayudawp.com
 * Description: Lightweight conditional widget visibility. Show or hide widgets by page, URL, post type, category, user role, login status, and schedule.
 * Version: 1.5.0
 * Author: Fernando Tellado
 * Author URI: https://ayudawp.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: widget-visibility-control
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 7.1
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AYUDAWP_WVC_VERSION', '1.5.0' );
define( 'AYUDAWP_WVC_PLUGIN_FILE', __FILE__ );
define( 'AYUDAWP_WVC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AYUDAWP_WVC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AYUDAWP_WVC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Option names.
define( 'AYUDAWP_WVC_CONDITIONS_OPTION', 'ayudawp_wvc_conditions' );
define( 'AYUDAWP_WVC_SETTINGS_OPTION', 'ayudawp_wvc_settings' );
define( 'AYUDAWP_WVC_MIGRATED_OPTION', 'ayudawp_wvc_migrated' );
define( 'AYUDAWP_WVC_WL_SNIPPETS_OPTION', 'ayudawp_wvc_wl_snippets' );

/**
 * Suppress the wp-editor dependency notice.
 *
 * WordPress core triggers a "doing_it_wrong" notice when wp-block-editor
 * is enqueued because it depends on wp-editor. This is a known core issue
 * and not a plugin error.
 *
 * @since 1.0.1
 * @param bool   $trigger       Whether to trigger the error.
 * @param string $function_name The function that triggered the error.
 * @param string $message       The error message.
 * @return bool Whether to trigger the error.
 */
function ayudawp_wvc_suppress_wp_editor_notice( $trigger, $function_name, $message ) {
	if ( false !== strpos( $message, 'wp-editor' ) ) {
		return false;
	}
	return $trigger;
}
add_filter( 'doing_it_wrong_trigger_error', 'ayudawp_wvc_suppress_wp_editor_notice', 10, 3 );

// Load core files.
require_once AYUDAWP_WVC_PLUGIN_DIR . 'includes/class-data-manager.php';
require_once AYUDAWP_WVC_PLUGIN_DIR . 'includes/class-widget-conditions.php';
require_once AYUDAWP_WVC_PLUGIN_DIR . 'includes/class-jetpack-migrator.php';
require_once AYUDAWP_WVC_PLUGIN_DIR . 'includes/class-widget-logic-parser.php';
require_once AYUDAWP_WVC_PLUGIN_DIR . 'includes/class-widget-logic-migrator.php';
require_once AYUDAWP_WVC_PLUGIN_DIR . 'includes/class-block-editor.php';
require_once AYUDAWP_WVC_PLUGIN_DIR . 'includes/class-promo-banner.php';
require_once AYUDAWP_WVC_PLUGIN_DIR . 'includes/class-settings.php';

/**
 * Plugin activation.
 *
 * @since 1.0.0
 * @return void
 */
function ayudawp_wvc_activate() {
	// Run migration from Jetpack/legacy data if not already done.
	if ( ! get_option( AYUDAWP_WVC_MIGRATED_OPTION ) ) {
		$result = AyudaWP_WVC_Data_Manager::migrate_from_legacy();
		AyudaWP_WVC_Data_Manager::record_migration( 'jetpack', isset( $result['migrated'] ) ? (int) $result['migrated'] : 0 );
	}

	// Initialize default settings.
	if ( ! get_option( AYUDAWP_WVC_SETTINGS_OPTION ) ) {
		update_option( AYUDAWP_WVC_SETTINGS_OPTION, array(
			'keep_legacy_data' => true,
		) );
	}
}
register_activation_hook( __FILE__, 'ayudawp_wvc_activate' );

/**
 * Plugin deactivation.
 *
 * @since 1.0.0
 * @return void
 */
function ayudawp_wvc_deactivate() {
	// Nothing to do on deactivation.
	// Data is preserved for potential reactivation.
}
register_deactivation_hook( __FILE__, 'ayudawp_wvc_deactivate' );

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 * @return void
 */
function ayudawp_wvc_init() {
	AyudaWP_Widget_Conditions::init();

	if ( is_admin() ) {
		AyudaWP_Jetpack_Migrator::init();
		AyudaWP_WVC_Widget_Logic_Migrator::init();
		AyudaWP_WVC_Settings::init();
	}
}
add_action( 'plugins_loaded', 'ayudawp_wvc_init' );

/**
 * Add settings link to plugins page.
 *
 * @since 1.0.0
 * @param array $links Plugin action links.
 * @return array Modified links.
 */
function ayudawp_wvc_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'themes.php?page=widget-visibility-control' ) ),
		esc_html__( 'Settings', 'widget-visibility-control' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . AYUDAWP_WVC_PLUGIN_BASENAME, 'ayudawp_wvc_plugin_action_links' );
