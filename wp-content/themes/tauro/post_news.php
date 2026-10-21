<?php
/*
Template Name: News Item
Template Post Type: post
*/
?>
<?php get_header(); ?>
    <main class="site-main page-news-item">
        <?php get_template_part('breadcrumbs'); ?>
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php $id = get_the_ID();?>

                <!-- Article Header -->
                <div class="container">
                    <div class="content-wrapper">
                        <header class="article-header">
                            <h1 class="article-title"><?php the_title(); ?></h1>

                        </header>
                    </div>
                </div>


                <!-- Article Content -->
                <article class="container pb-5">
                    <div class="content-wrapper article-body">
                        <?php the_content(); ?>
                    </div>
                </article>

            <?php endwhile; ?>
        <?php endif; ?>
        <?php
        if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('news-post-sidebar')) : endif;
        ?>

    </main>
<?php get_footer(); ?>



