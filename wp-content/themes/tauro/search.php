<?php get_header(); ?>
    <main class="site-main  page-products-item">
        <?php get_template_part('breadcrumbs'); ?>
        <?php
        $searchInCategory = $_GET['search_in_category'] ?? '';
        $search = isset($_GET['s'])
                ? sanitize_text_field($_GET['s'])
                : '';

        $args = [
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 12,
                's' => $search,
        ];

        if ($search && $searchInCategory) {

            $products_category = get_category_by_slug($searchInCategory);

            if ($products_category) {
                $category_ids[] = $products_category->term_id;
                $subcategories = get_categories([
                        'taxonomy' => 'category',
                        'parent' => $products_category->term_id,
                        'hide_empty' => false,
                ]);

                $subcategory_ids = wp_list_pluck($subcategories, 'term_id');

                $category_ids = array_unique(
                        array_merge($category_ids, $subcategory_ids)
                );

                if (!empty($category_ids)) {
                    $args['category__in'] = $category_ids;
                }

            }
        }

        $searchResult = new WP_Query($args);

        ?>
        <section class="products-section py-5">
            <div class="container">
                <div class="row g-4"> <?php if ($searchResult->have_posts()) : ?><?php while ($searchResult->have_posts()) : $searchResult->the_post(); ?>
                        <div class="col-md-6 col-lg-4"> <?php if (has_post_thumbnail()) : ?> <a
                                    href="<?php the_permalink(); ?>" class="product-card">
                                <div class="product-title"> <?php the_title(); ?> </div>
                                <div class="product-img-wrapper"> <?php the_post_thumbnail('full', ['class' => 'product-img', 'alt' => get_the_title(), 'title' => get_the_title(),]); ?> </div>
                                <span class="btn-view">View Details <i class="bi bi-arrow-up-right"></i> </span>
                            </a> <?php endif; ?> </div> <?php endwhile; ?><?php else : ?>
                        <div class="blog_post no-results"> <?php esc_html_e('Nothing found!', 'tauro'); ?> </div> <?php endif; ?>
                </div>
            </div>
        </section> <?php wp_reset_postdata(); ?>
    </main>
<?php get_footer(); ?>