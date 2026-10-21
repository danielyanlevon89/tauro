<?php


require get_template_directory() . '/inc/custom_functions.php';
require get_template_directory() . '/inc/menu_walkers.php';
require get_template_directory() . '/inc/custom_classes.php';
require get_template_directory() . '/inc/common_options.php';
require get_template_directory() . '/inc/products_extra_options.php';
require get_template_directory() . '/inc/services_extra_options.php';
require get_template_directory() . '/inc/markets_extra_options.php';

function tauro_scripts()
{
    wp_enqueue_style('bootstrap', get_template_directory_uri() . '/css/bootstrap.css');

    wp_register_style('Font_Awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css');
    wp_enqueue_style('Font_Awesome');
    wp_enqueue_style('components', get_template_directory_uri() . '/css/components.css');
    wp_enqueue_style('font-roboto', 'https://fonts.googleapis.com/css?family=Roboto+Slab:400,300,700');
    wp_enqueue_style('font-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,600,700');
    wp_enqueue_style('font-pt-sans', 'https://fonts.googleapis.com/css?family=PT+Sans:400,600,700');
    wp_enqueue_style('poppins-font','https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
    wp_enqueue_style('bootstrap-icons','https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
    wp_enqueue_style('Montserrat', 'https://fonts.googleapis.com/css?family=Montserrat:400,600,700');
    wp_enqueue_style('style-css', get_stylesheet_uri());



    wp_enqueue_script('jquery-min', get_template_directory_uri() . '/js/jquery.min.js');
    wp_enqueue_script('bootstrap', get_template_directory_uri() . '/js/bootstrap.min.js');
    wp_enqueue_script('doubletaptogo', get_template_directory_uri() . '/js/doubletaptogo.js');
    wp_enqueue_script('functions', get_template_directory_uri() . '/js/functions.js');
    wp_enqueue_script('isotope', get_template_directory_uri() . '/js/isotope.min.js');
    wp_enqueue_script('waypoints', get_template_directory_uri() . '/js/jquery.waypoints.min.js');
    wp_enqueue_script('html5shiv', 'https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js');
    wp_enqueue_script('respond', 'https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js');
    wp_script_add_data('html5shiv', 'conditional', 'lt IE 9');
    wp_script_add_data('respond', 'conditional', 'lt IE 9');
}

add_action('wp_enqueue_scripts', 'tauro_scripts');


function tauro_setup()
{

    load_theme_textdomain('tauro', get_template_directory() . '/lang');


    add_theme_support('title-tag');

    add_theme_support('custom-logo', array(
        'height' => 50,
        'width' => 199,
        'flex-height' => true
    ));

    add_theme_support('post-thumbnails');

    set_post_thumbnail_size(730, 446);

    add_image_size('tauro-recent-post', 80, 80, true);

    add_theme_support('html5', array(
        'search_form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption'
    ));

    add_theme_support('post-formats', array(
        'aside',
        'image',
        'video',
        'gallery'
    ));


    if (function_exists('register_nav_menus')) {
        register_nav_menus(
            [
                'primary-menu' => esc_html__('Primary menu', 'tauro'),
                'footer-menu' => esc_html__('Footer menu', 'tauro'),
                'footer-bottom-menu' => esc_html__('Footer bottom menu', 'tauro'),
            ]
        );
    }
}

add_action('after_setup_theme', 'tauro_setup');


function tauro_customize_register($wp_customize)
{

    $wp_customize->add_setting('address', array(
        'default' => esc_html__('Address', 'tauro'),
        'transport' => 'refresh',
    ));
    $wp_customize->add_setting('phone_number', array(
        'default' => esc_html__('Phone Number', 'tauro'),
        'transport' => 'refresh',
    ));

    $wp_customize->add_setting('email_address', array(
        'default' => esc_html__('Email Address', 'tauro'),
        'transport' => 'refresh',
    ));
    $wp_customize->add_setting('linkedin_url', array(
        'default' => esc_html__('Linkedin URL', 'tauro'),
        'transport' => 'refresh',
    ));
    $wp_customize->add_setting('youtube_url', array(
        'default' => esc_html__('Youtube URL', 'tauro'),
        'transport' => 'refresh',
    ));
    $wp_customize->add_setting('white_logo_image', array(
            'default' => 'image.jpg',
            'capability' => 'edit_theme_options',
            'type' => 'option',
    ));

    $wp_customize->add_section('details', array(
        'title' => esc_html__('Details Settings', 'tauro'),
        'priority' => 30,
    ));


    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'logo', array(
            'label' => esc_html__('white Logo', 'tauro'),
            'section' => 'details',
            'settings' => 'white_logo_image',
    )));
    $wp_customize->add_control(
        'address',
        array(
            'label' => esc_html__('Address', 'tauro'),
            'section' => 'details',
            'settings' => 'address',
            'type' => 'text',
        ));

    $wp_customize->add_control(
        'phone_number',
        array(
            'label' => esc_html__('Phone Number', 'tauro'),
            'section' => 'details',
            'settings' => 'phone_number',
            'type' => 'text',
        ));


    $wp_customize->add_control(
        'email_address',
        array(
            'label' => esc_html__('Email Address', 'tauro'),
            'section' => 'details',
            'settings' => 'email_address',
            'type' => 'email',
        ));
    $wp_customize->add_control(
            'linkedin_url',
            array(
                    'label' => esc_html__('Linkedin URL', 'tauro'),
                    'section' => 'details',
                    'settings' => 'linkedin_url',
                    'type' => 'text',
            ));
    $wp_customize->add_control(
            'youtube_url',
            array(
                    'label' => esc_html__('Youtube URL', 'tauro'),
                    'section' => 'details',
                    'settings' => 'youtube_url',
                    'type' => 'text',
            ));


    $wp_customize->add_setting('footer_rights_text', array(
        'default' => esc_html__('Footer Rights Text', 'tauro'),
        'transport' => 'refresh',
    ));



    $wp_customize->add_section('footer_setting', array(
        'title' => esc_html__('Footer Rights Text', 'tauro'),
        'priority' => 31,
    ));
    $wp_customize->add_control(
        'footer_rights_text',
        array(
            'label' => esc_html__('Footer settings', 'tauro'),
            'section' => 'footer_setting',
            'settings' => 'footer_rights_text',
            'type' => 'textarea',
        ));



    $wp_customize->add_setting('default_meta_key', array(
        'transport' => 'refresh',
    ));
    $wp_customize->add_setting('default_meta_desc', array(
        'transport' => 'refresh',
    ));

    $wp_customize->add_section('default_seo_meta_tags', array(
        'title' => esc_html__('SEO Meta Tags', 'tauro'),
        'priority' => 31,
    ));
    $wp_customize->add_control(
        'default_meta_key',
        array(
            'label' => esc_html__('Default Meta Key', 'tauro'),
            'section' => 'default_seo_meta_tags',
            'settings' => 'default_meta_key',
            'type' => 'textarea',
        ));
    $wp_customize->add_control(
        'default_meta_desc',
        array(
            'label' => esc_html__('Default Meta Description', 'tauro'),
            'section' => 'default_seo_meta_tags',
            'settings' => 'default_meta_desc',
            'type' => 'textarea',
        ));

}

add_action('customize_register', 'tauro_customize_register');


/**
 * Add a sidebar.
 */
function tauro_widgets_init()
{
    if (function_exists('register_sidebar')) {
        register_sidebar(array(
            'name' => esc_html__('Home Page Sidebar', 'tauro'),
            'id' => 'home-page-sidebar',
            'description' => esc_html__('Widgets in this area will be shown main wrapper.', 'tauro'),
//            'before_widget' => '<div id="%1$s" class="sidebar_wrap %2$s">',
            'before_widget' => '',
            'after_widget' => '',
//            'before_title' => '<div class="side_bar_heading"><h6>',
            'before_title' => '',
            'after_title' => '',
        ));
        register_sidebar(array(
            'name' => esc_html__('Right Sidebar', 'tauro'),
            'id' => 'right-sidebar',
//            'before_widget' => '<aside class="sidebar sidebar-right col-md-3">',
            'before_widget' => '',
            'before_title' => '',
            'after_title' => '',
            'after_widget' => ''
        ));
        register_sidebar(array(
            'name' => esc_html__('Page Sidebar', 'tauro'),
            'id' => 'page-sidebar',
//            'before_widget' => '<div id="%1$s" class="sidebar_wrap %2$s">',
//            'before_title' => '<div class="side_bar_heading"><h6>',
//            'after_title' => '</h6></div>',
//            'after_widget' => '</div>',
                'before_title' => '',
                'after_title' => '',
        ));
	    register_sidebar(array(
		    'name' => esc_html__('Market Post Sidebar', 'tauro'),
		    'id' => 'market-post-sidebar',
		    'before_title' => '',
		    'after_title' => '',
	    ));
	    register_sidebar(array(
		    'name' => esc_html__('Service Post Sidebar', 'tauro'),
		    'id' => 'service-post-sidebar',
		    'before_title' => '',
		    'after_title' => '',
	    ));
	    register_sidebar(array(
		    'name' => esc_html__('Product Post Sidebar', 'tauro'),
		    'id' => 'product-post-sidebar',
		    'before_title' => '',
		    'after_title' => '',
	    ));
		register_sidebar(array(
		    'name' => esc_html__('News Post Sidebar', 'tauro'),
		    'id' => 'news-post-sidebar',
		    'before_title' => '',
		    'after_title' => '',
	    ));
		register_sidebar(array(
		    'name' => esc_html__('Blog Post Sidebar', 'tauro'),
		    'id' => 'blog-post-sidebar',
		    'before_title' => '',
		    'after_title' => '',
	    ));
    }
}

add_action('widgets_init', 'tauro_widgets_init');



function get_active_shortcodes_widget()
{
    register_widget('Get_Active_Shortcodes_Widget');
}

add_action('widgets_init', 'get_active_shortcodes_widget');


add_filter('posts_search', function ($search, \WP_Query $q) {
    if (!is_admin() && empty($search) && $q->is_search() && $q->is_main_query())
        $search .= " AND 0=1 ";
    return $search;
}, 10, 2);

add_filter( 'widget_text', 'do_shortcode' );



function wpse27856_set_content_type(){
    return "text/html";
}
add_filter( 'wp_mail_content_type','wpse27856_set_content_type' );


function reading_time() {
	$content = get_post_field( 'post_content', get_the_ID() );
	$words   = str_word_count( strip_tags( $content ) );
	$minutes = max( 1, ceil( $words / 200 ) ); // 200 слов в минуту
	return $minutes;
}


add_action('pre_get_posts', function ($query) {
	if (!is_admin() && $query->is_main_query() && $query->is_category()) {
		$query->set('posts_per_page', -1);
	}
});

add_filter('do_redirect_guess_404_permalink', '__return_false');

