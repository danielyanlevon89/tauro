<?php get_header(); ?>
    <main class="site-main  py-5">
        <?php
        the_content();
        if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('page-sidebar')) : endif;
        ?>
    </main>

<?php get_footer(); ?>