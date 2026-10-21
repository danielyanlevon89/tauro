<?php

class Custom_Meta_Tags
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_custom_meta_tags_meta_boxes'));
        add_action('save_post', array($this, 'save_custom_meta_tags_meta_data'));
    }

    function add_custom_meta_tags_meta_boxes()
    {
        add_meta_box(
            'custom_meta_tags',
            esc_html__('SEO Meta Tags', 'tauro'),
            array($this, 'custom_meta_tags'),
            array('post', 'page'),
            'side'
        );
    }

    function custom_meta_tags()
    {
        $html = '';
        $custom_meta_tags = get_post_meta(get_the_ID(), 'custom_meta_tags', true);
        $custom_meta_tags_arr = explode("[--1--]", $custom_meta_tags);
        $html .= '<table><tr><td>' . esc_html__('Keyword', 'tauro') . '</td><td><textarea  name="custom_meta_tags_key" />' . $custom_meta_tags_arr[0] . '</textarea></textarea></td></tr>';
        $html .= '<tr><td>' . esc_html__('Description', 'tauro') . '</td><td><textarea  name="custom_meta_tags_desc" />' . $custom_meta_tags_arr[1] . '</textarea></td></tr></table>';


        echo $html;
    }

    function save_custom_meta_tags_meta_data($id)
    {
        $custom_meta_tags_arr = array($_POST['custom_meta_tags_key'] ?? '',$_POST['custom_meta_tags_desc'] ?? '');
        $custom_meta_tags =  implode("[--1--]",$custom_meta_tags_arr);
        update_post_meta($id, 'custom_meta_tags', $custom_meta_tags);
    }

}
$custom_meta_tags = new Custom_Meta_Tags();

class Custom_Background_Image
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_custom_backgroung_meta_boxes'));
        add_action('post_edit_form_tag', array($this, 'update_edit_form'));
        add_action('save_post', array($this, 'save_custom_background_meta_data'));
    }

    function add_custom_backgroung_meta_boxes()
    {
        add_meta_box(
            'custom_background',
            esc_html__('Background Image', 'tauro'),
            array($this, 'custom_background'),
            array('post', 'page', 'custom_text-widget', 'features-widget'),
            'side'
        );
    }

//page background
    function custom_background()
    {


        $html = '';
        $filearray = get_post_meta(get_the_ID(), 'custom_background', true);
        $show_background = get_post_meta(get_the_ID(), 'show_background', true);
        $show_background_checked = '';
        if ($show_background) {
            $show_background_checked = 'checked';
        }
        if (isset($filearray['url'])) {

            $this_file = $filearray['url'];

            if ($this_file != '') {
                $html = '<div><p> ' . esc_html__('Current file path:', 'tauro') . $this_file . '</p></div>';
                $html .= '<img src="' . $this_file . '" style="width:100%">';
            }
        }
        $html .= '<p class="description">' . esc_html__('Show Background.', 'tauro') . '</p>';
        $html .= '<input type="checkbox" name="show_background" ' . $show_background_checked . ' "/>';
        $html .= '<p class="description">' . esc_html__('Upload your Page Background here.', 'tauro') . '</p>';
        $html .= '<input id="custom_background" name="custom_background" size="25" type="file" value="" />';

        echo $html;
    }

    function save_custom_background_meta_data($id)
    {

        update_post_meta($id, 'show_background', $_POST['show_background'] ?? '');
        if (!empty($_FILES['custom_background']['name'])) {
            $supported_types = array('image/png', 'image/jpg', 'image/jpeg','image/webp');
            $arr_file_type = wp_check_filetype(basename($_FILES['custom_background']['name']));
            $uploaded_type = $arr_file_type['type'];
            if (in_array($uploaded_type, $supported_types)) {

                if (
                    isset($_FILES['custom_background']) &&
                    $_FILES['custom_background']['error'] === UPLOAD_ERR_OK
                ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';

                    $upload = wp_handle_upload(
                        $_FILES['custom_background'],
                        array('test_form' => false)
                    );

                    //$upload = wp_upload_bits($_FILES['custom_background']['name'], null, file_get_contents($_FILES['custom_background']['tmp_name']));
                    $ext = pathinfo($upload['url'], PATHINFO_EXTENSION);
                    if (isset($upload['error']) && $upload['error'] != 0) {
                        wp_die(esc_html__('There was an error uploading your file. The error is: ', 'tauro') . $upload['error']);
                    } else {
                        if ($ext == '') {
                            $wp_filetype = wp_check_filetype($_FILES['custom_background']['name']);
                            $upload['url'] = $upload['url'] . '/' . sanitize_file_name($_FILES['custom_background']['name']);
                            $upload['file'] = $upload['path'] . '/' . sanitize_file_name($_FILES['custom_background']['name']);
                            $upload['type'] = $wp_filetype['type'];
                        }

                        update_post_meta($id, 'custom_background', $upload);
                    }

                    if (isset($upload['error'])) {
                        error_log('Background upload error: ' . $upload['error']);
                        wp_die('Background upload error: ' . $upload['error']);
                    }
                }



            } else {
                wp_die(esc_html__("The file type that you've uploaded is not a supported.", 'tauro'));
            }
        }

    }


    function update_edit_form()
    {
        echo ' enctype="multipart/form-data"';
    }
}
$custom_background = new Custom_Background_Image();

class Custom_Featured_Image
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_custom_featured_meta_boxes'));
        add_action('post_edit_form_tag', array($this, 'update_edit_form'));
        add_action('save_post', array($this, 'save_custom_featured_meta_data'));
    }

    function add_custom_featured_meta_boxes()
    {
        add_meta_box(
            'custom_featured_image',
            esc_html__('Featured Image', 'tauro'),
            array($this, 'custom_featured_image'),
            array('post'),
            'side'
        );
    }


    function custom_featured_image()
    {


        $html = '';
        $filearray = get_post_meta(get_the_ID(), 'custom_featured_image', true);

        if (isset($filearray['url'])) {

            $this_file = $filearray['url'];

            if ($this_file != '') {
                $html = '<div><p> ' . esc_html__('Current file path:', 'tauro') . $this_file . '</p></div>';
                $html .= '<img src="' . $this_file . '" style="width:48px">';
            }
        }
        $html .= '<p class="description">' . esc_html__('Upload featured image here.', 'tauro') . '</p>';
        $html .= '<input id="custom_featured_image" name="custom_featured_image" size="25" type="file" value="" />';

        echo $html;
    }

    function save_custom_featured_meta_data($id)
    {

        if (!empty($_FILES['custom_featured_image']['name'])) {
            $supported_types = array('image/png', 'image/jpg', 'image/jpeg','image/webp');
            $arr_file_type = wp_check_filetype(basename($_FILES['custom_featured_image']['name']));
            $uploaded_type = $arr_file_type['type'];
            if (in_array($uploaded_type, $supported_types)) {

                if (
                    isset($_FILES['custom_featured_image']) &&
                    $_FILES['custom_featured_image']['error'] === UPLOAD_ERR_OK
                ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';

                    $upload = wp_handle_upload(
                        $_FILES['custom_featured_image'],
                        array('test_form' => false)
                    );

                    $ext = pathinfo($upload['url'], PATHINFO_EXTENSION);
                    if (isset($upload['error']) && $upload['error'] != 0) {
                        wp_die(esc_html__('There was an error uploading your file. The error is: ', 'tauro') . $upload['error']);
                    } else {
                        if ($ext == '') {
                            $wp_filetype = wp_check_filetype($_FILES['custom_featured_image']['name']);
                            $upload['url'] = $upload['url'] . '/' . sanitize_file_name($_FILES['custom_featured_image']['name']);
                            $upload['file'] = $upload['path'] . '/' . sanitize_file_name($_FILES['custom_featured_image']['name']);
                            $upload['type'] = $wp_filetype['type'];
                        }

                        update_post_meta($id, 'custom_featured_image', $upload);
                    }

                    if (isset($upload['error'])) {
                        error_log('featured image upload error: ' . $upload['error']);
                        wp_die('featured image upload error: ' . $upload['error']);
                    }
                }



            } else {
                wp_die(esc_html__("The file type that you've uploaded is not a supported.", 'tauro'));
            }
        }

    }


    function update_edit_form()
    {
        echo ' enctype="multipart/form-data"';
    }
}
$custom_featured_image = new Custom_Featured_Image();


class Custom_Gallery_Images
{
    public function __construct()
    {
        add_action('add_meta_boxes_post', array($this, 'add_custom_gallery_meta_boxes'), 10, 2);
        add_action('post_edit_form_tag', array($this, 'update_edit_form'));
        add_action('save_post', array($this, 'save_custom_gallery_meta_data'));
    }

    function add_custom_gallery_meta_boxes($post)
    {


        if (!$post instanceof WP_Post || $post->post_type !== 'post') {
            return;
        }

        $products_category = get_category_by_slug('products');

        if (!$products_category) {
            return;
        }

        $categories = get_the_category($post->ID);

        foreach ($categories as $category) {
            if (
                $category->term_id === $products_category->term_id ||
                cat_is_ancestor_of($products_category->term_id, $category->term_id)
            ) {
                add_meta_box(
                    'custom_gallery',
                    esc_html__('Custom Gallery', 'tauro'),
                    array($this, 'custom_gallery'),
                    array('post'),
                    'normal'
                );

                break;
            }
        }



    }


    function custom_gallery()
    {
        $html = '';
        $filearray = get_post_meta(get_the_ID(), 'custom_gallery', true);
        if ($filearray) {

            foreach ($filearray as $file) {
                $this_file = $file['url'];
                if ($this_file != '') {
                    $html .= '<img src="' . $this_file . '" style="width:33%;float: left;">';
                }
            }
        }

        $html .= '<p class="description" style="clear: both;">' . esc_html__('Upload your Gallery Images here.', 'tauro') . '</p>';
        $html .= '<input id="custom_gallery" name="custom_gallery[]" size="25" type="file" multiple value="" />';

        echo $html;
    }

    function save_custom_gallery_meta_data($id)
    {
        $uploaded_arr = array();
        if ($_FILES['custom_gallery'] ?? '') {
            $countfiles = count($_FILES['custom_gallery']['name']);
            for ($i = 0; $i < $countfiles; $i++) {
                if (!empty($_FILES['custom_gallery']['name'][$i])) {
                    $supported_types = array('image/png', 'image/jpg', 'image/jpeg','image/webp');
                    $arr_file_type = wp_check_filetype(basename($_FILES['custom_gallery']['name'][$i]));
                    $uploaded_type = $arr_file_type['type'];
                    if (in_array($uploaded_type, $supported_types)) {
                        $upload = wp_upload_bits($_FILES['custom_gallery']['name'][$i], null, file_get_contents($_FILES['custom_gallery']['tmp_name'][$i]));
                        $ext = pathinfo($upload['url'], PATHINFO_EXTENSION);
                        if (isset($upload['error']) && $upload['error'] != 0) {
                            wp_die(esc_html__('There was an error uploading your file. The error is: ', 'tauro') . $upload['error']);
                        } else {
                            if ($ext == '') {
                                $wp_filetype = wp_check_filetype($_FILES['custom_gallery']['name'][$i]);
                                $upload['url'] = $upload['url'] . '/' . sanitize_file_name($_FILES['custom_gallery']['name'][$i]);
                                $upload['file'] = $upload['path'] . '/' . sanitize_file_name($_FILES['custom_gallery']['name'][$i]);
                                $upload['type'] = $wp_filetype['type'];
                            }
                            $uploaded_arr[] = $upload;
                        }
                    } else {
                        wp_die(esc_html__("The file type that you've uploaded is not a supported.", 'tauro'));
                    }
                }
            }
        }

        if ($uploaded_arr) {
            update_post_meta($id, 'custom_gallery', $uploaded_arr);
        }

    }


    function update_edit_form()
    {
        echo ' enctype="multipart/form-data"';
    }
}
$custom_gallery = new Custom_Gallery_Images();

class Custom_Options
{
    public function __construct()
    {
        add_action(
            'add_meta_boxes',
            array($this, 'add_custom_options_meta_boxes')
        );

        add_action(
            'save_post',
            array($this, 'save_custom_options_meta_data')
        );
    }

    public function add_custom_options_meta_boxes()
    {
        add_meta_box(
            'custom_options',
            __('Custom Options', 'tauro'),
            array($this, 'custom_options_callback'),
            'post',
            'normal',
            'default'
        );
    }

    public function custom_options_callback($post)
    {
        wp_nonce_field(
            'save_custom_options_meta_data',
            'save_custom_options_meta_data_nonce'
        );

        $value = get_post_meta(
            $post->ID,
            'always_show_in_list',
            true
        );
        ?>

        <label>
            <input
                type="checkbox"
                name="always_show_in_list"
                value="1"
                <?php checked($value, '1'); ?>
            >
            <?php esc_html_e('Always show in a list', 'tauro'); ?>
        </label>

        <?php
    }

    public function save_custom_options_meta_data($post_id)
    {
        // Nonce
        if (
            !isset($_POST['save_custom_options_meta_data_nonce']) ||
            !wp_verify_nonce(
                $_POST['save_custom_options_meta_data_nonce'],
                'save_custom_options_meta_data'
            )
        ) {
            return;
        }

        // Autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Revision
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save checkbox
        if (isset($_POST['always_show_in_list'])) {
            update_post_meta(
                $post_id,
                'always_show_in_list',
                '1'
            );
        } else {
            delete_post_meta(
                $post_id,
                'always_show_in_list'
            );
        }
    }
}
$custom_options = new Custom_Options();


class Custom_Category_Image
{
    public function __construct()
    {
        // Add field to Add Category page
        add_action('category_add_form_fields', array($this, 'add_field'));

        // Add field to Edit Category page
        add_action('category_edit_form_fields', array($this, 'edit_field'));

        // Save meta
        add_action('created_category', array($this, 'save_meta'));
        add_action('edited_category', array($this, 'save_meta'));
    }

    // Add field (Add Category screen)
    public function add_field()
    {
        ?>
        <div class="form-field">
            <label for="custom_background"><?php esc_html_e('Background Image', 'tauro'); ?></label>
            <input type="file" name="custom_background" id="custom_background" />
            <p>
                <label>
                    <input type="checkbox" name="show_background" value="1">
                    <?php esc_html_e('Show Background', 'tauro'); ?>
                </label>
            </p>
        </div>
        <div class="form-field">
            <label for="custom_image"><?php esc_html_e('Image', 'tauro'); ?></label>
            <input type="file" name="custom_image" id="custom_image" />
        </div>
        <?php
    }

    // Edit field (Edit Category screen)
    public function edit_field($term)
    {
        $filearray = get_term_meta($term->term_id, 'custom_background', true);
        $show_background = get_term_meta($term->term_id, 'show_background', true);
        $custom_image = get_term_meta($term->term_id, 'custom_image', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="custom_background"><?php esc_html_e('Background Image', 'tauro'); ?></label>
            </th>
            <td>
                <?php if (!empty($filearray['url'])) : ?>
                    <p><?php esc_html_e('Current file:', 'tauro'); ?></p>
                    <img src="<?php echo esc_url($filearray['url']); ?>" style="max-width:200px;">
                <?php endif; ?>

                <input type="file" name="custom_background" id="custom_background" />

                <p>
                    <label>
                        <input type="checkbox" name="show_background" value="1" <?php checked($show_background, 1); ?>>
                        <?php esc_html_e('Show Background', 'tauro'); ?>
                    </label>
                </p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="custom_image"><?php esc_html_e('Image', 'tauro'); ?></label>
            </th>
            <td>
                <?php if (!empty($custom_image['url'])) : ?>
                    <p><?php esc_html_e('Current file:', 'tauro'); ?></p>
                    <img src="<?php echo esc_url($custom_image['url']); ?>" style="max-width:200px;">
                <?php endif; ?>

                <input type="file" name="custom_image" id="custom_image" />
            </td>
        </tr>
        <?php
    }

    // Save meta
    public function save_meta($term_id)
    {
        // Save checkbox
        $show = isset($_POST['show_background']) ? 1 : 0;
        update_term_meta($term_id, 'show_background', $show);

        // Handle file upload
        if (!empty($_FILES['custom_background']['name'])) {

            $supported_types = array('image/png', 'image/jpg', 'image/jpeg','image/webp');
            $filetype = wp_check_filetype(basename($_FILES['custom_background']['name']));
            $uploaded_type = $filetype['type'];

            if (in_array($uploaded_type, $supported_types)) {

                $upload = wp_upload_bits(
                    $_FILES['custom_background']['name'],
                    null,
                    file_get_contents($_FILES['custom_background']['tmp_name'])
                );

                if (empty($upload['error'])) {
                    update_term_meta($term_id, 'custom_background', $upload);
                } else {
                    wp_die('Upload error: ' . $upload['error']);
                }

            } else {
                wp_die(__('Unsupported file type.', 'tauro'));
            }
        }

        // Handle file upload
        if (!empty($_FILES['custom_image']['name'])) {

            $supported_types = array('image/png', 'image/jpg', 'image/jpeg','image/webp');
            $custom_image = wp_check_filetype(basename($_FILES['custom_image']['name']));
            $uploaded_type = $custom_image['type'];

            if (in_array($uploaded_type, $supported_types)) {

                $upload = wp_upload_bits(
                    $_FILES['custom_image']['name'],
                    null,
                    file_get_contents($_FILES['custom_image']['tmp_name'])
                );

                if (empty($upload['error'])) {
                    update_term_meta($term_id, 'custom_image', $upload);
                } else {
                    wp_die('Upload error: ' . $upload['error']);
                }

            } else {
                wp_die(__('Unsupported file type.', 'tauro'));
            }
        }
    }
}
new Custom_Category_Image();