<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="height=device-height,
                      width=device-width, initial-scale=1.0,
                      minimum-scale=1.0, maximum-scale=1.0,
                      user-scalable=no, target-densitydpi=device-dpi">
    <?php wp_head(); ?>
    <?php
    $meta_key=get_theme_mod('default_meta_key');
    $meta_desc=get_theme_mod('default_meta_desc');
    $white_logo=get_option('white_logo_image');

        if(isset($post) && isset($post->ID)){
            $custom_meta_tags = get_post_meta($post->ID, 'custom_meta_tags', true);
            $custom_meta_tags_arr = explode("[--1--]", $custom_meta_tags);
            if($custom_meta_tags_arr && is_array($custom_meta_tags_arr)){
                ($custom_meta_tags_arr[0])??'';
                ($custom_meta_tags_arr[1])??'';
            }
        }



        $titleInBackground='';
        $headerStyles='';
        $headerClass='classic';
        $term = get_queried_object();
        $background = get_term_meta($term->term_id ?? '', 'custom_background', true) ?: get_post_meta($post->ID ?? '', 'custom_background', true);
        $show_background = get_term_meta($term->term_id ?? '', 'show_background', true) ?: get_post_meta($post->ID ?? '', 'show_background', true);




        if ($show_background && !empty($background['url'])) {
            $headerClass='white';
            $headerStyles = '
                style="
                height: 350px;
                background:url('.esc_url($background['url']).') no-repeat center / cover
                "
            ';
            $titleInBackground = "<div class='container title_in_background'><h1></h1><p>".esc_html($term->category_description)."</p></div>";
        }
     ?>
    <meta name="description" content="<?php echo $meta_desc; ?>">
    <meta name="keywords" content="<?php echo $meta_key; ?>">

</head>
<body>
<div class="ghost-nav-wrap empty site-navigation"></div>
<!-- Site Header -->
<header <?=$headerStyles;?> class="site-header <?=$headerClass?> relative style-4">
<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <div class="logo-section header-logo">
            <?= the_custom_logo(); ?>
            <div>
                <?= strtoupper(get_bloginfo('name')); ?>
                <div><?= strtoupper(get_bloginfo('description')); ?></div>
            </div>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <?php
            wp_nav_menu([
                    'theme_location' => 'primary-menu',
                    'menu_id' => 'primary-menu',
                    'container' => false,
                    'items_wrap'     => '%3$s',
                    'walker' => new Header_Menu_Walker()
            ]);
            ?>
            </ul>

            <div class="d-flex gap-2">
                <a href="/customer-portal/" class="btn btn-outline-light-custom"><?= __('Customer portal', 'tauro');?></a>
                <a href="/contact-us/" class="btn btn-primary-custom"><?= __('Contact us', 'tauro');?></a>
            </div>
        </div>
    </div>
</nav>
<!--<div class="site">-->
</header>
<!-- /Site Header -->






