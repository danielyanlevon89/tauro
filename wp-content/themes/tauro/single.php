<?php get_header(); ?>
    <main class="site-main">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php $id = get_the_ID();

                $background_image = get_post_meta($post->ID, 'custom_background', true);
                $show_background = get_post_meta($post->ID, 'show_background', true);
                $background = '';
                if (isset($background_image['url']) && isset($show_background) && $show_background) {
                    $background = 'style="background: url(' . $background_image['url'] . ')"';
                }
                ?>
                <div class="page-header-sm" <?php echo $background; ?>>
                    <h1 class="text-uppercase page-title"></h1>
                </div>
                <?php get_template_part('breadcrumbs'); ?>
                <section class="section padding-small">
                    <div class="container">
                        <div class="row">


                            <div class="col-sm-6">
                                <h3 class="title text-uppercase"><?php the_title(); ?></h3>
                                <?php the_content(); ?>
                            </div>
                            <div class="col-sm-6">
                                <?php
                                if (has_post_thumbnail($post)) {
                                    $post_thumbnail_id = get_post_thumbnail_id($post);
                                    $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);

                                    ?>
                                    <img class="shadow"
                                         title="<?php echo apply_filters('the_title', $post->post_title, $post->ID); ?>"
                                         alt="<?php echo apply_filters('the_title', $post->post_title, $post->ID); ?>"
                                         src="<?php echo $post_thumbnail_url; ?>">
                                <?php } ?>
                            </div>

                        </div>
                    </div>
                </section>
            <?php endwhile; ?>
        <?php endif; ?>
        <?php
        if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('page-sidebar')) : endif;
        ?>
    </main>

<?php get_footer(); ?>