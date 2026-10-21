<?php get_header(); ?>

    <main class="site-main">
        <?php get_template_part('breadcrumbs'); ?>



        <!-- Recent News -->
        <?php
        $recent_news = new WP_Query([
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => 3,
                'category_name'       => 'news',
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => true,
        ]);
        $recent_news_ids = wp_list_pluck($recent_news->posts, 'ID');
        if ($recent_news->have_posts()) :
            $news_posts = $recent_news->posts;
            $featured_post = $news_posts[0];
            ?>

            <section class="page-news pb-5">
                <div class="container">
                    <h2 class="section-title">Recent News</h2>

                    <div class="row g-4">

                        <!-- Featured Large -->
                        <div class="col-lg-6">
                            <?php
                            $featured_image = get_the_post_thumbnail_url(
                                    $featured_post->ID,
                                    'large'
                            );
                            ?>

                            <div  class="featured-card">

                                <?php if ($featured_image) : ?>
                                    <img
                                            src="<?php echo esc_url($featured_image); ?>"
                                            alt="<?php echo esc_attr(
                                                    get_post_meta(
                                                            get_post_thumbnail_id($featured_post->ID),
                                                            '_wp_attachment_image_alt',
                                                            true
                                                    ) ?: get_the_title($featured_post)
                                            ); ?>"
                                            class="featured-img"
                                    >
                                <?php endif; ?>

                                <div class="featured-body">
                        <span class="card-date">
                            <?php echo esc_html(
                                    get_the_date('d M Y', $featured_post)
                            ); ?>
                        </span>

                                    <div class="card-title">
                                        <a href="<?php echo esc_url(get_permalink($featured_post)); ?>"> <?php echo esc_html(get_the_title($featured_post)); ?></a>
                                        <i class="bi bi-arrow-up-right"></i>
                                    </div>

                                    <p class="card-desc">
                                        <?php echo esc_html(
                                                wp_trim_words(
                                                        get_the_excerpt($featured_post),
                                                        25
                                                )
                                        ); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Side Cards -->
                        <div class="col-lg-6 d-flex flex-column gap-4">
                            <?php foreach (array_slice($news_posts, 1) as $post) : ?>
                                <?php
                                $side_image = get_the_post_thumbnail_url(
                                        $post->ID,
                                        'medium'
                                );
                                ?>

                                <a href="<?php echo esc_url(get_permalink($post)); ?>"
                                   class="side-card">

                                    <?php if ($side_image) : ?>
                                        <img
                                                src="<?php echo esc_url($side_image); ?>"
                                                alt="<?php echo esc_attr(
                                                        get_post_meta(
                                                                get_post_thumbnail_id($post->ID),
                                                                '_wp_attachment_image_alt',
                                                                true
                                                        ) ?: get_the_title($post)
                                                ); ?>"
                                                class="side-img"
                                        >
                                    <?php endif; ?>

                                    <div class="side-body">
                            <span class="card-date">
                                <?php echo esc_html(
                                        get_the_date('d M Y', $post)
                                ); ?>
                            </span>

                                        <div class="side-title">
                                            <?php echo esc_html(get_the_title($post)); ?>
                                        </div>

                                        <p class="side-desc">
                                            <?php echo esc_html(
                                                    wp_trim_words(
                                                            get_the_excerpt($post),
                                                            15
                                                    )
                                            ); ?>
                                        </p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>
            </section>

        <?php
        endif;
        wp_reset_postdata();
        ?>


        <section class="page-news py-5">
            <div class="container">
                <h2 class="section-title">All News</h2>
                <div class="row g-4">

                    <?php
                    $paged = max(1, get_query_var('paged'), get_query_var('page'));

                    $all_news = new WP_Query([
                            'post_type'           => 'post',
                            'post_status'         => 'publish',
                            'posts_per_page'      => 6,
                            'category_name'       => 'news',
                            'post__not_in'        => $recent_news_ids,
                            'orderby'             => 'date',
                            'order'               => 'DESC',
                            'ignore_sticky_posts' => true,
                            'paged'               => $paged,
                    ]);
                    ?>

                    <?php if ($all_news->have_posts()) : ?>

                        <?php while ($all_news->have_posts()) : $all_news->the_post(); ?>

                            <div class="col-md-6 col-lg-4">
                                <div  class="grid-card">
                                <?php if (has_post_thumbnail()) : ?>

                                    <div class="news-img-wrapper">
                                        <?php the_post_thumbnail('full', [
                                                'class' => 'grid-img',
                                                'alt'   => get_the_title(),
                                                'title' => get_the_title(),
                                        ]); ?>
                                    </div>

                                    <div class="grid-body">
                                        <span class="card-date"><?= get_the_date('j M Y'); ?></span>
                                        <div class="grid-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            <i class="bi bi-arrow-up-right"></i>
                                        </div>
                                        <p class="grid-desc"> <?= wp_strip_all_tags(get_the_excerpt($post)); ?></p>
                                    </div>

                                <?php endif; ?>

                                </div>
                            </div>

                        <?php endwhile; ?>

                    <?php else : ?>

                        <div class="news_post no-results">
                            <?php esc_html_e('Nothing found!', 'tauro'); ?>
                        </div>

                    <?php endif; ?>

                </div>
            </div>



            <!-- Pagination -->
            <?php
            $total_pages = (int) $all_news->max_num_pages;
            $current_page = (int) $paged;

            if ($total_pages > 1) :
                $pagination_links = paginate_links([
                        'base'      => str_replace(
                                999999999,
                                '%#%',
                                esc_url(get_pagenum_link(999999999))
                        ),
                        'format'    => '?paged=%#%',
                        'current'   => $current_page,
                        'total'     => $total_pages,
                        'prev_next' => false,
                        'type'      => 'array',
                        'end_size'  => 1,
                        'mid_size'  => 1,
                ]);
                ?>

                <section class="pagination-section">
                    <div class="container">
                        <div class="pagination-custom">

                            <?php if ($current_page > 1) : ?>
                                <a href="<?php echo esc_url(get_pagenum_link($current_page - 1)); ?>"
                                   class="pagination-nav">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                    Previous
                                </a>
                            <?php else : ?>
                                <span class="pagination-nav disabled">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    Previous
                </span>
                            <?php endif; ?>

                            <div class="pagination-pages">
                                <?php foreach ($pagination_links as $link) : ?>
                                    <?php
                                    if (strpos($link, 'dots') !== false) {
                                        continue;
                                    }

                                    if (strpos($link, 'current') !== false) {
                                        preg_match('/>(.*?)</', $link, $matches);
                                        $page_number = $matches[1] ?? $current_page;
                                        ?>
                                        <span class="pagination-page active"
                                              aria-current="page">
                            <?php echo esc_html($page_number); ?>
                        </span>
                                        <?php
                                    } else {
                                        preg_match('/href="([^"]+)"/', $link, $url_match);
                                        preg_match('/>(.*?)<\/a>/', $link, $text_match);

                                        $page_url = $url_match[1] ?? '';
                                        $page_number = $text_match[1] ?? '';
                                        ?>
                                        <a href="<?php echo esc_url($page_url); ?>"
                                           class="pagination-page">
                                            <?php echo esc_html(wp_strip_all_tags($page_number)); ?>
                                        </a>
                                    <?php } ?>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($current_page < $total_pages) : ?>
                                <a href="<?php echo esc_url(get_pagenum_link($current_page + 1)); ?>"
                                   class="pagination-nav">
                                    Next
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </a>
                            <?php else : ?>
                                <span class="pagination-nav disabled">
                    Next
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </span>
                            <?php endif; ?>

                        </div>
                    </div>
                </section>

            <?php endif; ?>



        </section>
    </main>

<?php get_footer(); ?>