<?php

class Features_Widget extends WP_Widget
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'features_widget', // Base ID
            __('Features Widget', 'tauro'), // Name
            array('description' => __('Displays one of your Features Widgets.', 'tauro')) // Args
        );

        add_filter('excerpt_more', array($this, 'feature_excerpt_more'));
    }


    function feature_excerpt_more($more)
    {
        return ' ...';
    }


    function feature_get_excerpt($post, $type = 'default')
    {
        $length = $type === 'short' ? 8 : 17;

        $filter = function () use ($length) {
            return $length;
        };

        add_filter('excerpt_length', $filter);

        $excerpt = get_the_excerpt($post);

        remove_filter('excerpt_length', $filter);

        return $excerpt;
    }

    public function widget($args, $instance)
    {
        $id = ($instance['feature-widget-id']) ? $instance['feature-widget-id'] : 0;
        $show_title = (isset($instance['show_title'])) ? $instance['show_title'] : 1;
        $post = get_post($id);

        $checkfield = maybe_unserialize(get_post_meta($post->ID, "checkfield", true));
        $checkfield_category = maybe_unserialize(get_post_meta($post->ID, "checkfield_category", true));
        $features_type = get_post_meta($post->ID, "features_type", true);
        $background_class = get_post_meta($post->ID, "background_class", true);
        $view_all_button = get_post_meta($post->ID, "view_all_button", true);
        $show_feature_read_more = get_post_meta($post->ID, "show_feature_read_more", true);
        $background_image = get_post_meta($post->ID, 'custom_background', true);
        $show_background = get_post_meta($post->ID, 'show_background', true);

        $section_class = ' ';
        $background = ' ';
        if (isset($background_image['url']) && isset($show_background) && $show_background) {
            $background = 'style="background: url(' . $background_image['url'] . ')"';
        }

        if ($background_class) {
            $section_class .= $background_class;
        }

        echo $args['before_widget'];
        echo '<section ' . $background . '  class="features ' . $section_class . '"><div class="container">';
        if (!empty($id) && $post) {
            $featureTypePostsCount = [
                    'blog' => 3,
                    'news' => 3,
                    'related_products' => 4,
            ];

            if ($checkfield_category) {

                $posts_count = $featureTypePostsCount[$features_type] ?? -1;

                if ($posts_count === -1) {

                    $postfilter = [
                            'post_type'      => 'post',
                            'post_status'    => 'publish',
                            'category'       => $checkfield_category[0],
                            'posts_per_page' => -1,
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


                    $category = get_the_category(get_the_ID());
                    if($features_type == 'related_products' && $category[0]->term_id) {
                        $postfilter['tax_query'] = [ [ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => [$category[0]->term_id], 'operator' => 'IN', ], ];
                    }
                    // Get all posts, but always-show posts first
                    $pages = get_posts($postfilter);

                } else {

                    $postfilter = [
                            'post_type'      => 'post',
                            'post_status'    => 'publish',
                            'category'       => $checkfield_category[0],
                            'posts_per_page' => $posts_count,
                            'meta_query'     => [
                                    [
                                            'key'     => 'always_show_in_list',
                                            'value'   => '1',
                                            'compare' => '=',
                                    ],
                            ],
                            'orderby' => 'date',
                            'order'   => 'DESC',
                    ];


                    // 1. Get ALWAYS posts
                    $always_pages = get_posts($postfilter);

                    // If we already have enough always-show posts
                    if (count($always_pages) >= $posts_count) {

                        $pages = array_slice($always_pages, 0, $posts_count);

                    } else {

                        // How many random posts do we need?
                        $remaining = $posts_count - count($always_pages);

                        // IDs of always-show posts
                        $always_ids = wp_list_pluck($always_pages, 'ID');

                        $postfilter = [
                                'post_type'      => 'post',
                                'post_status'    => 'publish',
                                'category'       => $checkfield_category[0],
                                'posts_per_page' => $remaining,
                                'post__not_in'   => $always_ids,
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
                        $category = get_the_category(get_the_ID());
                        if($features_type == 'related_products' && $category[0]->term_id) {
                            $postfilter['tax_query'] = [ [ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => [$category[0]->term_id], 'operator' => 'IN', ], ];
                        }


                        // 2. Get RANDOM posts excluding always-show posts
                        $random_pages = get_posts($postfilter);

                        // 3. Always first, random after
                        $pages = array_merge(
                                $always_pages,
                                $random_pages
                        );
                    }
                }


            } elseif ($checkfield) {

                $pages = get_pages(array(
                    'include' => implode( ',',$checkfield)
                ));

            }


            if ($show_title) {

                $description = $post->post_content ?? '';
                $title = (isset($instance['title'])) ? $instance['title'] : $post->post_title;
                $title = apply_filters('widget_title', $title);
                echo $args['before_title'] . ' <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-bold mb-2" style="font-size:32px;">' . $title . '</h2>
                    <p class="text-muted mb-0" style="font-size:15px;">'.$description.'</p>
                </div>';
                if($view_all_button && $checkfield_category){
                    $category_url = get_category_link($checkfield_category[0]);
                    echo $args['before_title'] . '  <a href="'.esc_url($category_url).'" class="btn btn-outline-light-custom d-none d-md-inline-flex align-items-center gap-2 view_all">
                        '.$view_all_button.' <i class="bi bi-arrow-up-right"></i>
                    </a>';
                }
                echo $args['before_title'] . '</div>' . $args['after_title'];
            }

            if ($pages) {
                if ($features_type == 'static') {
                    echo '<div class="row g-3">';
                            foreach ($pages as $page) :
                                if (has_post_thumbnail($page)) {
                                $post_thumbnail_id = get_post_thumbnail_id($page);
                                $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);
                                ?>
                                <div class="col-md-6 col-lg-4" >
                                    <a href="<?= get_permalink($page->ID); ?>" class="market-card">
                                        <div class="card-body-custom">
                                            <h5 class="card-title-custom"><?= apply_filters('the_title', $page->post_title, $page->ID); ?></h5>
                                            <?php
                                            if ($show_feature_read_more) {
                                                $more = sprintf(' <span class="card-link-custom">%1$s <i class="bi bi-arrow-up-right"></i></span>',
                                                        __('View details', 'tauro')
                                                );
                                                echo $more;
                                            } ?>
                                        </div>
                                        <div class="market-img-wrapper d-flex align-items-center justify-content-center">
                                            <img src="<?php echo $post_thumbnail_url; ?>" alt="<?php echo apply_filters('the_title', $page->post_title, $page->ID); ?>" class="static-img">
                                        </div>

                                    </a>
                                </div>
                                <?php  }
                             endforeach;
                    echo '</div>';
                }
                elseif ($features_type == 'blog') {

                    echo '<div class="row g-4">';
                    foreach ($pages as $page) : ?>
                        <?php

                        if (has_post_thumbnail($page)) {
                            $post_thumbnail_id = get_post_thumbnail_id($page);
                            $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);
                            ?>
                            <div class="col-md-6 col-lg-4" >
                                <a href="<?= get_permalink($page->ID); ?>" class="blog-card">
                                    <div class="blog-img-wrapper d-flex align-items-center justify-content-center">
                                        <img src="<?= $post_thumbnail_url; ?>" alt="<?= apply_filters('the_title', $page->post_title, $page->ID); ?>" class="blog-img">
                                    </div>
                                    <div class="card-body-custom">
                                        <div class="blog-date"><?= get_the_date('j M Y'); ?></div>
                                        <div class="blog-title-row">
                                            <span class="blog-title-text"><?= apply_filters('the_title', $page->post_title, $page->ID); ?></span>
                                                <i class="bi bi-arrow-up-right flex-shrink-0 mt-1" style="color:var(--text-muted); font-size:16px;"></i>

                                        </div>

                                        <div class="d-flex flex-wrap gap-2">
                                        <?php
                                        $tags = get_the_tags($page->ID);
                                        if ($tags) :
                                            $tags = array_slice($tags, 0, 3);
                                            foreach ($tags as $tag) : ?>
                                                <span class="blog-tag"><?= esc_html($tag->name); ?></span>
                                            <?php endforeach;
                                        endif;
                                        ?>
                                        </div>
                                    </div>

                                </a>
                            </div>
                        <?php } ?>
                    <?php endforeach;
                     echo '</div>';
                }elseif ($features_type == 'news') {

                    echo '<div class="row g-4">';
                    foreach ($pages as $page) : ?>
                        <?php

                        if (has_post_thumbnail($page)) {
                            $post_thumbnail_id = get_post_thumbnail_id($page);
                            $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);
                            ?>
                            <div class="col-md-6 col-lg-4" >
                                <a href="<?= get_permalink($page->ID); ?>" class="blog-card">
                                    <div class="blog-img-wrapper d-flex align-items-center justify-content-center">
                                        <img src="<?= $post_thumbnail_url; ?>" alt="<?= apply_filters('the_title', $page->post_title, $page->ID); ?>" class="blog-img">
                                    </div>
                                    <div class="card-body-custom">
                                        <div class="blog-date"><?= get_the_date('j M Y'); ?></div>
                                        <div class="blog-title-row">
                                            <span class="blog-title-text"><?= apply_filters('the_title', $page->post_title, $page->ID); ?></span>
                                            <i class="bi bi-arrow-up-right flex-shrink-0 mt-1" style="color:var(--text-muted); font-size:16px;"></i>

                                        </div>
                                        <p class="blog-desc"> <?= wp_strip_all_tags(get_the_excerpt($post)); ?></p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php
                                            $tags = get_the_tags($page->ID);
                                            if ($tags) :
                                                $tags = array_slice($tags, 0, 3);
                                                foreach ($tags as $tag) : ?>
                                                    <span class="blog-tag"><?= esc_html($tag->name); ?></span>
                                                <?php endforeach;
                                            endif;
                                            ?>
                                        </div>
                                    </div>

                                </a>
                            </div>
                        <?php } ?>
                    <?php endforeach;
                    echo '</div>';
                }
                elseif ($features_type == 'slider') {
                    echo '<div class="scrollbar-hide">';
                    foreach ($pages as $page) : ?>
                        <?php

                        if (has_post_thumbnail($page)) {
                            $post_thumbnail_id = get_post_thumbnail_id($page);
                            $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);
                            ?>
                        <div class="flex-shrink-0" >
                            <a href="<?= get_permalink($page->ID); ?>" class="product-card">
                                <div class="product-img-wrapper">
                                    <img src="<?php echo $post_thumbnail_url; ?>" alt="<?php echo apply_filters('the_title', $page->post_title, $page->ID); ?>" class="slider-img">
                                </div>
                                <div class="card-body-custom">
                                    <h5 class="card-title-custom"><?php echo apply_filters('the_title', $page->post_title, $page->ID); ?></h5>
                                    <p class="card-specs"><?= wp_strip_all_tags($this->feature_get_excerpt($page,'short')); ?></p>
                                    <?php
                                    if ($show_feature_read_more) {
                                        $more = sprintf(' <span class="card-link-custom">%1$s <i class="bi bi-arrow-up-right"></i></span>',
                                                __('View details', 'tauro')
                                        );
                                        echo $more;
                                    } ?>
                                </div>

                            </a>
                        </div>
                        <?php } ?>
                    <?php endforeach;
                    echo '</div>
                            <div class="d-flex gap-3 mt-4">
                                <button class="btn btn-outline-light-custom rounded-circle d-flex align-items-center justify-content-center slider-prev" style="width:40px; height:40px; padding:0;"><i class="bi bi-chevron-left"></i></button>
                                <button class="btn btn-outline-light-custom rounded-circle d-flex align-items-center justify-content-center slider-next" style="width:40px; height:40px; padding:0;"><i class="bi bi-chevron-right"></i></button>
                            </div>';
                }elseif ($features_type == 'related_products') {
                    echo '<div class="row g-4 flex-nowrap overflow-auto pb-2">';
                    foreach ($pages as $page) : ?>
                        <?php

                        if (has_post_thumbnail($page)) {
                            $post_thumbnail_id = get_post_thumbnail_id($page);
                            $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);
                            ?>

                            <div class="col-9 col-md-5 col-lg-3">
                                <a href="<?= get_permalink($page->ID); ?>" class="related-card">
                                    <div class="related-title"><?= apply_filters('the_title', $page->post_title, $page->ID); ?></div>
                                    <div class="related-img-wrap">
                                        <img src="<?php echo $post_thumbnail_url; ?>" alt="<?php echo apply_filters('the_title', $page->post_title, $page->ID); ?>" class="related-img">
                                    </div>
                                    <?php
                                    if ($show_feature_read_more) {
                                        $more = sprintf(' <span class="btn-details">%1$s <i class="bi bi-arrow-up-right"></i></span>',
                                                __('Details', 'tauro')
                                        );
                                        echo $more;
                                    } ?>

                                </a>
                            </div>
                        <?php } ?>
                    <?php endforeach;
                }
                elseif ($features_type == 'service') {
                    echo '<div class="row g-4">';
                    foreach ($pages as $page) : ?>
                        <?php
                        $custom_featured_image = get_post_meta($page->ID, 'custom_featured_image', true);
                        $featuredImage = '';
                        if (!empty($custom_featured_image['url'])) {
                            $featuredImage = '
                                style="
                                width: 44px;
                                background:url('.esc_url($custom_featured_image['url']).') no-repeat center / cover
                                "
                            ';
                       }
                        ?>
                            <div class="col-lg-6" >
                                    <div class="service-item-home">
                                        <div class="service-icon-home" <?=$featuredImage;?>></div>
                                        <div>
                                            <h4 class="fw-semibold mb-1" style="font-size:15px;"><a href="<?= get_permalink($page->ID); ?>"><?= apply_filters('the_title', $page->post_title, $page->ID); ?></a></h4>
                                            <p class="text-muted mb-0" style="font-size:13px; line-height:1.6;"><?= wp_strip_all_tags($this->feature_get_excerpt($page)); ?></p>
                                        </div>
                                    </div>
                            </div>

                    <?php endforeach;
                    echo '</div>';
                }
            }


        } elseif (current_user_can('manage_options')) { ?>
            <p>
                <?php if (empty($id)) {
                    _e('Please select a feature to show in this area.', 'tauro');
                } else {
                    printf(__('No feature found with ID %d, please select an existing feature in the widget settings.', 'tauro'), $id);
                } ?>
            </p>
            <?php
        }

        echo $args['after_widget'];
        echo '</div></section>';

    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     * @see WP_Widget::update()
     *
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['feature-widget-id'] = isset($new_instance['feature-widget-id']) ? $new_instance['feature-widget-id'] : 0;
        $instance['show_title'] = (isset($new_instance['show_title']) && $new_instance['show_title'] == 1) ? 1 : 0;
        return $instance;
    }

    /**
     * Back-end widget form.
     *
     * @param array $instance Previously saved values from database.
     * @return string|void
     * @see WP_Widget::form()
     *
     */
    public function form($instance)
    {

        $posts = (array)get_posts(array(
            'post_type' => 'features-widget',
            'numberposts' => -1
        ));

        $show_title = isset($instance['show_title']) ? $instance['show_title'] : 1;
        $selected_widget_id = isset($instance['feature-widget-id']) ? $instance['feature-widget-id'] : 0;
        $title = ($selected_widget_id) ? get_the_title($selected_widget_id) : __('No feature selected.', 'tauro');
        ?>

        <input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>"
               type="hidden" value="<?php echo esc_attr($title); ?>"/>

        <p>
            <label for="<?php echo $this->get_field_id('feature-widget-id'); ?>"><?php _e('Feature to show:', 'tauro'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('feature-widget-id'); ?>"
                    name="<?php echo $this->get_field_name('feature-widget-id'); ?>" required>
                <option value="0" disabled <?php selected($selected_widget_id, 0); ?>>
                    <?php if (empty($posts)) {
                        _e('No feature found', 'tauro');
                    } else {
                        _e('Select a feature', 'tauro');
                    } ?>
                </option>
                <?php foreach ($posts as $p) { ?>
                    <option value="<?php echo $p->ID; ?>" <?php selected($selected_widget_id, $p->ID); ?>><?php echo $p->post_title; ?></option>
                <?php } ?>
            </select>
        </p>

        <p>
            <label><input type="checkbox" id="<?php echo $this->get_field_id('show_title'); ?>"
                          name="<?php echo $this->get_field_name('show_title'); ?>"
                          value="1" <?php checked($show_title, 1); ?> /> <?php _e("Show title?", "tauro"); ?>
            </label>
        </p>

        <p class="help"><?php printf(__('Manage your features %shere%s', 'tauro'), '<a target="_blank" href="' . admin_url('post.php?post=' . $selected_widget_id . '&action=edit') . '">', '</a>'); ?></p>
        <?php
    }

}