<main class="site-main py-5">
    <section class="hero-section u-mt-header-offset">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8">
                    <h1 class="hero-title"><?php single_cat_title(); ?></h1>

                    <div class="hero-desc">
                        <?= category_description(); ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="search-box">
                        <svg class="search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <circle cx="7" cy="7" r="4.5" stroke="#8a8a90" stroke-width="1.5"/>
                            <path d="M10.5 10.5L14 14" stroke="#8a8a90" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <form role="search" class="search-form" method="get" action="<?= esc_url(home_url('/')); ?>">
                            <input
                                    type="search"
                                    class="search-input"
                                    name="s"
                                    value="<?= esc_attr(get_search_query()); ?>"
                                    placeholder="Search..."
                            >
                            <input type="hidden" name="search_in_category" value="products">
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="products-section py-5">
        <div class="container">
            <div class="row g-4">
                <?php

                $current_category = get_queried_object();
                $subcategories = get_categories( [
                        'taxonomy'   => 'category',
                        'parent'     => $current_category->term_id,
                        'hide_empty' => false,
                        'meta_query' => [
                                [
                                        'relation' => 'OR',
                                        [ 'key' => 'cat_order', 'compare' => 'EXISTS' ],
                                        [ 'key' => 'cat_order', 'compare' => 'NOT EXISTS' ],
                                ],
                        ],
                        'orderby'    => 'meta_value_num',
                        'order'      => 'ASC',
                ] );
                ?>

                <?php foreach ($subcategories as $subcategory) :
                    $category_image = get_term_meta( $subcategory->term_id, 'custom_image', true );
                    if (!$category_image) { $category_image = ''; }

                    ?>

                    <div class="col-md-6 col-lg-4">

                        <a
                                href="<?php echo esc_url(get_category_link($subcategory->term_id)); ?>"
                                class="product-card"
                        >

                            <div class="product-title">
                                <?php echo esc_html($subcategory->name); ?>
                            </div>
                            <?php  if ( !empty($category_image['url'])) { ?>
                                    <div class="product-img-wrapper">
                                        <img class="product-img"
                                             title="<?php echo esc_html($subcategory->name); ?>"
                                             alt="<?php echo esc_html($subcategory->name); ?>"
                                             src="<?php echo $category_image['url']; ?>">
                                    </div>
                            <?php } ?>
                            <span class="btn-view">
                View Products
                <i class="bi bi-arrow-up-right"></i>
            </span>

                        </a>

                    </div>

                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>