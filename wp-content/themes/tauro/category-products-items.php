
    <main class="site-main page-products-item">
        <?php get_template_part('breadcrumbs'); ?>
        <section class="hero-section u-mt-header-offset">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <h1 class="hero-title"><?php single_cat_title(); ?></h1>
                        <div class="hero-desc">
                            <?= category_description(); ?>
                        </div>
                    </div>
                    <div class="col-lg-6 text-center">
                        <?php
                        $category_image = get_term_meta( get_queried_object()->term_id, 'custom_image', true );
                        if (!$category_image) { $category_image = ''; }
                        if ( !empty($category_image['url'])) { ?>
                            <div class="category-img-wrapper">
                                <img class="hero-img"
                                     title="<?php single_cat_title(); ?>"
                                     alt="<?php single_cat_title(); ?>"
                                     src="<?php echo $category_image['url']; ?>">
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </section>
        <section class="products-section py-5">
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

                    if ($posts_query->have_posts()) :
                        while ($posts_query->have_posts()) :
                            $posts_query->the_post();
                            ?>
                                <div class="col-md-6 col-lg-4">
                                    <?php
                                    $post = get_post();
                                    if (has_post_thumbnail($post)) {
                                    $post_thumbnail_id = get_post_thumbnail_id($post);
                                    $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);

                                    ?>

                                    <a href="<?php the_permalink(); ?>" class="product-card">
                                        <div class="product-title"><?= apply_filters('the_title', $post->post_title, $post->ID); ?></div>
                                        <div class="product-img-wrapper">
                                            <img class="product-img"
                                                 title="<?php echo apply_filters('the_title', $post->post_title, $post->ID); ?>"
                                                 alt="<?php echo apply_filters('the_title', $post->post_title, $post->ID); ?>"
                                                 src="<?php echo $post_thumbnail_url; ?>">
                                        </div>
                                        <span class="btn-view">
                                            View Product <i class="bi bi-arrow-up-right"></i>
                                        </span>
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
