<?php get_header();
?>
    <main class="site-main py-5">
        <section class="hero-section u-mt-header-offset">
            <div class="container">
                <h1 class="hero-title"><?php single_cat_title(); ?></h1>
                <p class="hero-subtitle">
                    <?= category_description(); ?>
                </p>
            </div>
        </section>
        <section class="page-services py-5">
            <div class="container">
                <div class="row g-4">

                    <?php

                    $args = [
                            'post_type'      => 'post',
                            'posts_per_page' => get_option('posts_per_page'),
                            'cat' => get_queried_object()->term_id,
                            'meta_query'     => [
                                    'relation' => 'OR',
                                    [
                                            'key'     => 'post_position',
                                            'compare' => 'EXISTS',
                                    ],
                                    [
                                            'key'     => 'post_position',
                                            'compare' => 'NOT EXISTS',
                                    ],
                            ],
                            'orderby'    => 'meta_value_num',
                            'order'      => 'ASC',
                    ];
                    $posts_query = new WP_Query($args);
                    $i = 0;
                    if ($posts_query->have_posts()) :
                        while ($posts_query->have_posts()) :
                            $posts_query->the_post();
                            $category = get_queried_object();
                            $i++;
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <?php
                                $post = get_post();
                                if (has_post_thumbnail($post)) {
                                    $post_thumbnail_id = get_post_thumbnail_id($post);
                                    $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);

                                    ?>
                                    <a href="<?php the_permalink(); ?>" class="service-card">
                                        <div class="service-img-wrapper">
                                            <img class="service-img"
                                                 title="<?php echo apply_filters('the_title', $post->post_title, $post->ID); ?>"
                                                 alt="<?php echo apply_filters('the_title', $post->post_title, $post->ID); ?>"
                                                 src="<?php echo $post_thumbnail_url; ?>">
                                            <span class="service-number"><?= sprintf('%02d', $i); ?>/<?= sprintf('%02d', $category->count) ?></span>
                                        </div>
                                        <div class="service-body">
                                            <div class="service-header">
                                                <div class="service-title"><?= apply_filters('the_title', $post->post_title, $post->ID); ?></div>
                                                <div class="service-dot"></div>
                                            </div>
                                            <p class="service-desc">
                                                <?= wp_strip_all_tags(get_the_excerpt($post)); ?>
                                            </p>
                                            <span class="service-link">
                                                    View details <i class="bi bi-arrow-up-right"></i>
                                                </span>
                                        </div>
                                    </a>

                                <?php } ?>
                            </div>
                        <?php
                        endwhile; ?>
                    <?php else: ?>
                        <div class="blog_post no-results">
                            <?php esc_html_e('Nothing found!', 'tauro'); ?>
                        </div>
                    <?php endif; ?>


                </div>
            </div>
        </section>
    </main>
<?php get_footer(); ?>