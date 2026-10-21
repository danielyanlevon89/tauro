<?php get_header(); ?>
    <main class="site-main">
        <?php
        if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('home-page-sidebar')) : endif;
        ?>
    </main>
<?php get_footer(); ?>