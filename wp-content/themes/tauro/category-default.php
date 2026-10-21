<?php get_header(); ?>

    <main class="site-main page-products-item">
        <?php get_template_part('breadcrumbs'); ?>

        <section class="products-section py-5">
            <div class="container">
                <div class="row g-4">

                    <?php if (have_posts()) : ?>

                        <?php while (have_posts()) : the_post(); ?>

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
                                            View details <i class="bi bi-arrow-up-right"></i>
                                        </span>
                                    </a>
                                <?php } ?>
                            </div>

                        <?php endwhile; ?>

                    <?php else : ?>

                        <div class="blog_post no-results">
                            <?php esc_html_e('Nothing found!', 'tauro'); ?>
                        </div>

                    <?php endif; ?>

                </div>

                <?php
                $pagination = paginate_links([
                        'prev_text' => '&laquo; Previous',
                        'next_text' => 'Next &raquo;',
                        'type'      => 'list',
                ]);

                if ($pagination) : ?>
                    <nav class="pagination-wrapper mt-5" aria-label="Posts pagination">
                        <?php echo $pagination; ?>
                    </nav>
                <?php endif; ?>

            </div>
        </section>
    </main>

<?php get_footer(); ?>