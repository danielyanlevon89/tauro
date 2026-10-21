<?php get_header(); ?>

    <main class="site-main">
        <?php get_template_part('breadcrumbs'); ?>

        <section class="page-blog py-5">
            <div class="container">
                <div class="row g-4">

                    <?php
                    $paged = max(1, get_query_var('paged'), get_query_var('page'));

                    $all_blogs = new WP_Query([
                            'post_type'           => 'post',
                            'post_status'         => 'publish',
                            'posts_per_page'      => 9,
                            'category_name'       => 'blogs',
                            'orderby'             => 'date',
                            'order'               => 'DESC',
                            'ignore_sticky_posts' => true,
                            'paged'               => $paged,
                    ]);
                    ?>

                    <?php if ($all_blogs->have_posts()) : ?>

                        <?php while ($all_blogs->have_posts()) : $all_blogs->the_post(); ?>

                            <div class="col-md-6 col-lg-4">
                                <article class="blog-card">
                                <?php if (has_post_thumbnail()) : ?>

                                    <div class="blog-card-image">
                                        <?php the_post_thumbnail('full', [
                                                'class' => 'product-img',
                                                'alt'   => get_the_title(),
                                                'title' => get_the_title(),
                                        ]); ?>
                                    </div>
                                    <p class="blog-card-date"><?= get_the_date('j M Y'); ?></p>
                                    <h3 class="blog-card-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        <i class="bi bi-arrow-up-right"></i>
                                    </h3>
                                    <div class="blog-card-tags">
                                        <?php
                                        $post = get_post();
                                        $tags = get_the_tags($post->ID);
                                        if ($tags) :
                                            $tags = array_slice($tags, 0, 3);
                                            foreach ($tags as $tag) : ?>
                                                <a href="<?= esc_url(get_tag_link($tag->term_id)); ?>" class="tag tag-blue"><?= esc_html($tag->name); ?></a>
                                            <?php endforeach;
                                        endif;
                                        ?>
                                    </div>

                                <?php endif; ?>

                                </article>
                            </div>

                        <?php endwhile; ?>

                    <?php else : ?>

                        <div class="blog_post no-results">
                            <?php esc_html_e('Nothing found!', 'tauro'); ?>
                        </div>

                    <?php endif; ?>

                </div>
            </div>




            <!-- Pagination -->
            <?php
            $total_pages = (int) $all_blogs->max_num_pages;
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