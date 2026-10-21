<?php
/**
 * Plugin Name: Custom Text
 * Description: Easy to add and display custom text
 * Author: Custom
 * Version: 1.3.3
 * @package WordPress
 * @author WP OnlineSupport
 */

if (!defined('ABSPATH')) {
    exit;
}
if (!defined('CUSTOM_TEXT_PLUGIN_DIR')) {
    define('CUSTOM_TEXT_PLUGIN_DIR', dirname(__FILE__)); // Plugin dir
}

function custom_text_load_textdomain()
{
    load_theme_textdomain('tauro', get_template_directory() . '/lang');
}

add_action('plugins_loaded', 'custom_text_load_textdomain');


/**
 * Register the Widget class
 */
function custom_text_register_widget()
{
    require_once CUSTOM_TEXT_PLUGIN_DIR . '/includes/class-widget.php';
    register_widget('Custom_Text_Widget');
}

add_action('widgets_init', 'custom_text_register_widget');


function custom_text_register_post_type()
{
    $labels = array(
        'name' => __('Custom Text', 'tauro'),
        'singular_name' => __('Custom Text', 'tauro'),
        'add_new' => __('New Custom Text', 'tauro'),
        'add_new_item' => __('Add New Custom Text', 'tauro'),
        'edit_item' => __('Edit Custom Text', 'tauro'),
        'new_item' => __('New Custom Text', 'tauro'),
        'all_items' => __('Show All Custom Texts', 'tauro'),
        'view_item' => __('View Custom Text', 'tauro'),
        'search_items' => __('Search Custom Texts', 'tauro'),
        'not_found' => __('No Custom Texts found', 'tauro'),
        'not_found_in_trash' => __('No Custom Texts found in Trash', 'tauro'),
        'menu_name' => __('Custom Texts', 'tauro')
    );
    $args = array(
        'public' => false,
        'show_ui' => true,
        'labels' => $labels,
        'supports' => array('title', 'editor')
    );
    register_post_type('custom_text-widget', $args);
}

add_action('init', 'custom_text_register_post_type');


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

    require_once CUSTOM_TEXT_PLUGIN_DIR . '/includes/class-admin.php';
    new Custom_Text_Widgets_Admin();
}

