<?php
/**
 * Plugin Name: Features Content and Slider
 * Description: Easy to add and display what features your company, product or service offers, using our shortcode OR template code
 * Author: Custom
 * Version: 1.3.3
 * @package WordPress
 * @author WP OnlineSupport
 */

if (!defined('ABSPATH')) {
    exit;
}
if (!defined('FEATURES_PLUGIN_DIR')) {
    define('FEATURES_PLUGIN_DIR', dirname(__FILE__)); // Plugin dir
}

function features_load_textdomain()
{
    load_theme_textdomain('tauro', get_template_directory() . '/lang');
}

add_action('plugins_loaded', 'features_load_textdomain');


/**
 * Register the Widget class
 */
function features_register_widget()
{
    require_once FEATURES_PLUGIN_DIR . '/includes/class-widget.php';
    register_widget('Features_Widget');
}

add_action('widgets_init', 'features_register_widget');


function features_register_post_type()
{
    $labels = array(
        'name' => __('Features', 'tauro'),
        'singular_name' => __('Feature', 'tauro'),
        'add_new' => __('New Feature', 'tauro'),
        'add_new_item' => __('Add New Feature', 'tauro'),
        'edit_item' => __('Edit Feature', 'tauro'),
        'new_item' => __('New Feature', 'tauro'),
        'all_items' => __('Show All Features', 'tauro'),
        'view_item' => __('View Feature', 'tauro'),
        'search_items' => __('Search Features', 'tauro'),
        'not_found' => __('No Features found', 'tauro'),
        'not_found_in_trash' => __('No Features found in Trash', 'tauro'),
        'menu_name' => __('Features', 'tauro'),
    );
    $args = array(
        'public' => false,
        'show_ui' => true,
        'labels' => $labels,
        'supports' => array('title', 'editor')
    );
    register_post_type('features-widget', $args);
}

add_action('init', 'features_register_post_type');


// add necessary content filters
add_filter('ww_content', 'wptexturize');
add_filter('ww_content', 'convert_smilies');
add_filter('ww_content', 'convert_chars');
add_filter('ww_content', 'wpautop');
add_filter('ww_content', 'shortcode_unautop');
add_filter('ww_content', 'do_shortcode', 11);
add_filter('ww_content', array($GLOBALS['wp_embed'], 'run_shortcode'), 8);
add_filter('ww_content', array($GLOBALS['wp_embed'], 'autoembed'), 8);

// only load admin class for non-ajax requests to the admin section
if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {

    require_once FEATURES_PLUGIN_DIR . '/includes/class-admin.php';
    new
    Features_Widgets_Admin();
}

