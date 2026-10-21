<?php
function tauro_template_directory_uri(){
    echo get_template_directory_uri();
}


function tauro_the_breadcrumb(){
    global $post;
    if(!is_home()){
        echo '<li class="breadcrumb-item"><a href="'.site_url().'"><i class="bi bi-house-door-fill"></i></a></li> ';

        if (is_single()) { // posts

            $categories = get_the_category();

            if (!empty($categories)) {

                // Get the first/main category
                $category = $categories[0];

                // Build category hierarchy
                $category_ids = [];

                while ($category) {

                    $category_ids[] = $category->term_id;

                    if ($category->parent) {
                        $category = get_category($category->parent);
                    } else {
                        break;
                    }
                }

                // Parent -> child
                $category_ids = array_reverse($category_ids);

                foreach ($category_ids as $category_id) {

                    $category = get_category($category_id);

                    echo '<li class="breadcrumb-item">';
                    echo '<a href="' . esc_url(get_category_link($category->term_id)) . '">';
                    echo esc_html($category->name);
                    echo '</a>';
                    echo '</li>';
                }
            }

            // Current post
            echo '<li class="breadcrumb-item active" aria-current="page">';
            echo esc_html(get_the_title());
            echo '</li>';
        }
        elseif (is_page()) { // pages
            if ($post->post_parent ) {
                $parent_id  = $post->post_parent;
                $breadcrumbs = array();
                while ($parent_id) {
                    $page = get_page($parent_id);
                    $breadcrumbs[] = '<li class="breadcrumb-item"><a href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a></li>';
                    $parent_id  = $page->post_parent;
                }
                $breadcrumbs = array_reverse($breadcrumbs);
                foreach ($breadcrumbs as $crumb) echo $crumb . '<li class="breadcrumb-item ">  </li> ';
            }
            echo the_title();
            echo " <li>  </li> ";
        }
        elseif (is_category()) { // category

            $current_cat = get_queried_object();
            // Build parent categories
            $parents = []; $parent_id = $current_cat->parent;
            while ($parent_id) { $parent = get_category($parent_id); if (!$parent || is_wp_error($parent)) { break; }
                $parents[] = $parent; $parent_id = $parent->parent; }
            // Reverse so the top-level category comes first
            $parents = array_reverse($parents);
            // Output parents
            foreach ($parents as $parent) {
                echo '<li class="breadcrumb-item">'; echo '<a href="' . esc_url(get_category_link($parent->term_id)) . '">';
                echo esc_html($parent->name); echo '</a>'; echo '</li>'; }
            // Current category
            echo '<li class="breadcrumb-item active" aria-current="page">';
            echo esc_html($current_cat->name);
            echo '</li>';
        }
        elseif (is_search()) { // search pages
            echo '<li class="breadcrumb-item active" aria-current="page">';
            echo esc_html__('Search results "', 'tauro') . get_search_query() . '"';
            echo '</li>';
        }
        elseif (is_tag()) { // tags
	        echo '<li class="breadcrumb-item">Tag - ' . single_tag_title('', false).'</li>';
            echo single_tag_title('', false);
        }
        elseif (is_day()) { // archive (days)
            echo '<li class="breadcrumb-item"><a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a></li> <li>  </li> ';
            echo '<li class="breadcrumb-item"><a href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '">' . get_the_time('F') . '</a></li> <li>  </li> ';
            echo get_the_time('d');
        }
        elseif (is_month()) { // archive (months)
            echo '<li class="breadcrumb-item"><a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a></li> <li>  </li>';
            echo get_the_time('F');
        }
        elseif (is_year()) { // archive (years)
            echo get_the_time('Y');
        }
        elseif (is_author()) { // authors
            global $author;
            $userdata = get_userdata($author);
            echo '<li class="breadcrumb-item">Posted ' . $userdata->display_name . '</li>';
        } elseif (is_404()) { // if page not found
            echo '<li class="breadcrumb-item">'. esc_html__('Error 404', 'tauro'). '</li>';

        }

        if (get_query_var('paged')) // number of page
            echo '<li class="breadcrumb-item"> Page - (' . get_query_var('paged').')</li>';

    } else { // home
        $pageNum=(get_query_var('paged')) ? get_query_var('paged') : 1;
        if($pageNum>1)
            echo '<li class="breadcrumb-item"><a href="'.site_url().'"></i><i class="bi bi-house-door-fill"></i></a></li> <li> </li> <li>'.$pageNum.'- page</li>';
        else
            echo '<li class="breadcrumb-item"></i><i class="bi bi-house-door-fill"></i></li>';
    }
}

function add_mce_markup( $initArray ) {
    $ext = 'i[id|name|class|style]';
    if ( isset( $initArray['extended_valid_elements'] ) ) {
        $initArray['extended_valid_elements'] .= ',' . $ext;
    } else {
        $initArray['extended_valid_elements'] = $ext;
    }
    return $initArray;
}
add_filter( 'tiny_mce_before_init', 'add_mce_markup' );


function wp_upload_bits_check_if_exist( $file ) {
    $upload = wp_upload_dir(  );
    $filename = sanitize_file_name( $file['name'] );
    $new_file = $upload['path'] . "/".$filename;
    if(file_exists($new_file)){
        return false;
    }
return $file;
}
add_filter( 'wp_upload_bits', 'wp_upload_bits_check_if_exist' );


//fix wp_redirect header error
function callback($buffer) {
    // You can modify $buffer here, and then return the updated code
    return $buffer;
}
function buffer_start() { ob_start("callback"); }
function buffer_end() { ob_end_flush(); }
add_action('init', 'buffer_start');
add_action('wp_footer', 'buffer_end');








/* Enqueue jQuery UI Sortable on the Category Order page */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( $hook !== 'posts_page_tt-category-order' ) return;
    wp_enqueue_script( 'jquery-ui-sortable' );
});

/* ============================================================
   1. DRAG-AND-DROP CATEGORY ORDERING
   ============================================================ */

add_action( 'admin_menu', function () {
    add_submenu_page(
        'edit.php',
        'Category Order',
        'Category Order',
        'manage_options',
        'tt-category-order',
        'tt_category_order_page'
    );
});

function tt_category_order_page() {
    $cats = get_terms( [
        'taxonomy'   => 'category',
        'hide_empty' => false,
        'orderby'    => 'term_order',
        'order'      => 'ASC',
    ] );

    $cats = get_terms( [
        'taxonomy'   => 'category',
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

    $by_parent = [];
    foreach ( $cats as $c ) $by_parent[ $c->parent ][] = $c;
    ?>
    <div class="wrap">
        <h1>Category Order</h1>
        <p>Drag categories into the desired order, then click "Save".</p>
        <ul  data-parent="0" style="max-width:420px">
            <?php foreach ( $by_parent[0] ?? [] as $cat ) : ?>
                <li data-id="<?php echo $cat->term_id; ?>" style="padding:10px 14px;margin:6px 0;background:#fff;border:1px solid #dcdcde;border-radius:6px;cursor:move">
                    ☰ <?php echo esc_html( $cat->name ); ?>
                    <?php if ( ! empty( $by_parent[ $cat->term_id ] ) ) : ?>
                        <ul class="tt-sortable" data-parent="<?php echo $cat->term_id; ?>" style="margin-top:8px">
                            <?php foreach ( $by_parent[ $cat->term_id ] as $sub ) : ?>
                                <li data-id="<?php echo $sub->term_id; ?>" style="padding:8px 14px;margin:4px 0;background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px;cursor:move">
                                    — <?php echo esc_html( $sub->name ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <button id="tt-save-cat-order" class="button button-primary">Save Order</button>
    </div>

    <script>
        jQuery(function($){
            $('.tt-sortable').sortable({
                placeholder: 'ui-state-highlight',
                connectWith: '.tt-sortable',   // items can be moved between levels
            }).disableSelection();

            $('#tt-save-cat-order').on('click', function(){
                const groups = {};
                $('.tt-sortable').each(function(){
                    groups[ $(this).data('parent') ] = $(this).children('li').map(function(){
                        return $(this).data('id');
                    }).get();
                });
                $.post(ajaxurl, {
                    action: 'tt_save_cat_order',
                    _ajax_nonce: '<?php echo wp_create_nonce('tt_cat_order'); ?>',
                    groups: groups
                }, function(res){
                    if (res.success) location.reload();
                });
            });
        });
    </script>
    <?php
}

add_action( 'wp_ajax_tt_save_cat_order', function () {
    check_ajax_referer( 'tt_cat_order' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

    foreach ( $_POST['groups'] ?? [] as $parent => $ids ) {
        $pos = 0;
        foreach ( (array) $ids as $id ) {
            $id = intval( $id );
            update_term_meta( $id, 'cat_order', ++$pos );
            wp_update_term( $id, 'category', [ 'parent' => intval( $parent ) ] );
        }
    }
    wp_send_json_success();
});


/* ============================================================
   2. POST ORDERING (post_position meta + quick edit)
   ============================================================ */

// "Order" column
add_filter( 'manage_post_posts_columns', function( $cols ) {
    $cols['post_position'] = 'Order';
    return $cols;
});
add_action( 'manage_post_posts_custom_column', function( $col, $post_id ) {
    if ( $col === 'post_position' ) {
        echo '<span class="tt-pos">' . intval( get_post_meta( $post_id, 'post_position', true ) ) . '</span>';
    }
}, 10, 2 );
add_filter( 'manage_edit-post_sortable_columns', function( $cols ) {
    $cols['post_position'] = 'post_position';
    return $cols;
});

// quick edit field
add_action( 'quick_edit_custom_box', function( $col ) {
    if ( $col !== 'post_position' ) return;
    echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">
            <label><span class="title">Order</span>
            <input type="number" name="post_position" value=""></label>
          </div></fieldset>';
});
add_action( 'admin_footer-edit.php', function () {
    ?>
    <script>
        jQuery(function($){
            // populate the quick edit form with the current value
            $('#the-list').on('click', '.editinline', function(){
                const id = $(this).closest('tr').attr('id').replace('post-','');
                const val = $('#post-' + id + ' .tt-pos').text().trim();
                $('input[name="post_position"]').val(val);
            });
        });
    </script>
    <?php
});

// save
add_action( 'save_post', function( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( isset( $_POST['post_position'] ) ) {
        update_post_meta( $post_id, 'post_position', intval( $_POST['post_position'] ) );
    }
});

// sort by column in admin
add_action( 'pre_get_posts', function( $q ) {
    if ( is_admin() && $q->is_main_query() && $q->get('orderby') === 'post_position' ) {
        $q->set( 'meta_key', 'post_position' );
        $q->set( 'orderby', 'meta_value_num' );
    }
});


/* ============================================================
   3. FRONT-END OUTPUT
   ============================================================ */

// categories — use this order when displaying:
//   get_terms([ 'orderby' => 'term_order', 'order' => 'ASC', ... ])
//   wp_list_categories([ 'orderby' => 'term_order', ... ])

// posts in category archives — by post_position, posts without value go last
add_action( 'pre_get_posts', function( $q ) {
    if ( is_admin() || ! $q->is_main_query() || ! ( is_category() || is_tag() || is_archive() ) ) return;
    $q->set( 'meta_query', [[
        'relation' => 'OR',
        [ 'key' => 'post_position', 'compare' => 'EXISTS' ],
        [ 'key' => 'post_position', 'compare' => 'NOT EXISTS' ],
    ]] );
    $q->set( 'orderby', [ 'meta_value_num' => 'ASC', 'date' => 'DESC' ] );
    $q->set( 'order', 'ASC' );
});


















