<?php
/**
 * Uninstall Widget Visibility Control
 *
 * Removes all plugin data when uninstalled via WordPress admin.
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if not called by WordPress uninstaller.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'ayudawp_wvc_conditions' );
delete_option( 'ayudawp_wvc_settings' );
delete_option( 'ayudawp_wvc_migrated' );
delete_option( 'ayudawp_wvc_jetpack_notice_dismissed' );
delete_option( 'ayudawp_wvc_wl_notice_dismissed' );
delete_option( 'ayudawp_wvc_wl_snippets' );

// Note: Legacy data in widget instances is NOT deleted.
// This allows users to switch back to Jetpack without losing their rules.
