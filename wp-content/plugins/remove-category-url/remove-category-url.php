<?php
/**
 * Plugin Name: Remove Category URL
 * Plugin URI: https://wordpress.org/plugins/remove-category-url/
 * Description: This plugin removes '/category' from your category URLs. (e.g. `/category/my-category/` to `/my-category/`)
 * Version: 1.2.5
 * Author: Themeisle
 * Author URI: https://themeisle.com
 * Text Domain: remove-category-url
 * Domain Path: /languages
 * WordPress Available: yes
 * Requires License: no
 */

require_once dirname( __FILE__ ) . '/admin-notices.php';
Admin_Notices::instance( __FILE__ );

/* hooks */
register_activation_hook( __FILE__, 'remove_category_url_refresh_rules' );
register_deactivation_hook( __FILE__, 'remove_category_url_deactivate' );

/* actions */
add_action( 'created_category', 'remove_category_url_refresh_rules' );
add_action( 'delete_category', 'remove_category_url_refresh_rules' );
add_action( 'edited_category', 'remove_category_url_refresh_rules' );
add_action( 'init', 'remove_category_url_permastruct' );

/* filters */
add_filter( 'category_rewrite_rules', 'remove_category_url_rewrite_rules' );
add_filter( 'query_vars', 'remove_category_url_query_vars' );    // Adds 'category_redirect' query variable
add_filter( 'request', 'remove_category_url_request' );       // Redirects if 'category_redirect' is set

function remove_category_url_refresh_rules() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function remove_category_url_deactivate() {
	remove_filter( 'category_rewrite_rules', 'remove_category_url_rewrite_rules' ); // We don't want to insert our custom rules again
	remove_category_url_refresh_rules();
}

/**
 * Removes category base.
 *
 * @return void
 */
function remove_category_url_permastruct() {
	global $wp_rewrite, $wp_version;

	if ( 3.4 <= $wp_version ) {
		$wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
	} else {
		$wp_rewrite->extra_permastructs['category'][0] = '%category%';
	}
}

/**
 * Adds our custom category rewrite rules.
 *
 * @param array $category_rewrite Category rewrite rules.
 *
 * @return array
 */
function remove_category_url_rewrite_rules( $category_rewrite ) {
	global $wp_rewrite;

	$category_rewrite = array();

	/* WPML is present: temporary disable terms_clauses filter to get all categories for rewrite */
	if ( class_exists( 'Sitepress' ) ) {
		global $sitepress;

		remove_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10 );
		$categories = get_categories( array( 'hide_empty' => false, '_icl_show_all_langs' => true ) );
		add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );
	} else {
		$categories = get_categories( array( 'hide_empty' => false ) );
	}

	foreach ( $categories as $category ) {
		$category_nicename = $category->slug;
		if ( $category->parent == $category->cat_ID ) {
			$category->parent = 0;
		} elseif ( 0 != $category->parent ) {
			$category_nicename = get_category_parents( $category->parent, false, '/', true ) . $category_nicename;
		}
		$category_rewrite[ '(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
		$category_rewrite[ '(' . $category_nicename . ')/page/?([0-9]{1,})/?$' ]                  = 'index.php?category_name=$matches[1]&paged=$matches[2]';
		$category_rewrite[ '(' . $category_nicename . ')/?$' ]                                    = 'index.php?category_name=$matches[1]';
	}

	// Redirect support from Old Category Base
	$old_category_base                                 = get_option( 'category_base' ) ? get_option( 'category_base' ) : 'category';
	$old_category_base                                 = trim( $old_category_base, '/' );
	$category_rewrite[ $old_category_base . '/(.*)$' ] = 'index.php?category_redirect=$matches[1]';

	return $category_rewrite;
}

function remove_category_url_query_vars( $public_query_vars ) {
	$public_query_vars[] = 'category_redirect';

	return $public_query_vars;
}

/**
 * Handles category redirects.
 *
 * @param $query_vars Current query vars.
 *
 * @return array $query_vars, or void if category_redirect is present.
 */
function remove_category_url_request( $query_vars ) {
	if ( isset( $query_vars['category_redirect'] ) ) {
		$catlink = trailingslashit( get_option( 'home' ) ) . user_trailingslashit( $query_vars['category_redirect'], 'category' );
		status_header( 301 );
		header( "Location: " . esc_url_raw( $catlink ) );
		exit;
	}

	return $query_vars;
}

require_once trailingslashit( __DIR__ ) . '/vendor/autoload.php';


add_filter( 'themeisle_sdk_products', function ( $products ) {
	$products[] = __FILE__;

	return $products;
} );

/**
 * Filters Themeisle SDK Black Friday / promotional notice data for this product.
 *
 * @param array<string, array<string, mixed>> $configs Configurations keyed by product slug.
 * @return array<string, array<string, mixed>> Modified configs with Remove Category URL–specific notice data.
 */
add_filter( 'themeisle_sdk_blackfriday_data', function ( $configs ) {
	$config = isset( $configs['default'] ) ? $configs['default'] : array();

	if ( defined( 'NEVE_VERSION' ) ) {
		return $configs;
	}

	// translators: 1. Number of free licenses, 2. The price of the product.
	$config['message'] = sprintf( __( 'You\'re using Remove Category URL, and the team behind it is celebrating Black Friday by giving away %1$s licences of Neve Pro. A premium WordPress theme worth %2$s, packed with starter sites, a header builder, and WooCommerce layouts. Claim yours before they run out.', 'remove-category-url' ), 100, '$69' );
	$config['cta_label'] = __( 'Get Neve Pro free', 'remove-category-url' );
	$config['plugin_meta_message'] = __( 'Black Friday Sale - Get Neve Pro free', 'remove-category-url' );
	$config['sale_url']  = tsdk_translate_link( tsdk_utmify( 'https://themeisle.link/neve-claim-bf', 'bfcm', 'remove-cat-url' ) );

	$configs[ basename( dirname( __FILE__ ) ) ] = $config;

	return $configs;
} );

/**
 * Adds plugin meta links.
 *
 * @param array $meta_fields The plugin meta fields.
 * @param string $file The plugin file.
 *
 * @return array
 */
function add_plugin_review_link( $meta_fields, $file ) {
	if ( plugin_basename( __FILE__ ) === $file && is_array( $meta_fields ) ) {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

		$meta_fields[] = sprintf(
			'<a href="%s" target="_blank">%s<span class="dashicons dashicons-star-filled" style="font-size: 15px;"></span></a>',
			esc_url( 'https://wordpress.org/support/plugin/remove-category-url/reviews/#new-post' ),
			esc_html__( 'Found it useful? Rate the plugin', 'remove-category-url' ),
		);
	}

	return $meta_fields;
}

add_filter( 'plugin_row_meta', 'add_plugin_review_link', 10, 2 );
