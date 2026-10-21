<?php

class Custom_Text_Widgets_Admin
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_box'), 20);
        add_action('save_post', array($this, 'custom_texts_meta_save'), 10, 2);
    }

    public function add_meta_box()
    {
        add_meta_box(
            'custom_texts-widget-meta-box',
            __('More..', 'tauro'),
            array($this, 'custom_texts_meta_box'),
            'custom_text-widget',
            'side',
            'low'
        );
    }

    public function custom_texts_meta_box($post)
    {
        $background_class = get_post_meta($post->ID, "background_class", true);
        // Nonce to verify intention later
        wp_nonce_field('save_post', 'custom_nonce');
        ?>
        <div style="margin-top: 30px;">
            <h4><?php _e('Background Class', 'tauro'); ?></h4>
            <input type="text" name="background_class" value="<?php echo $background_class; ?>"/>
        </div>

        <?php
    }

//save the meta box
    function custom_texts_meta_save($post_id, $post)
    {
        if (wp_verify_nonce($_POST['custom_nonce'] ?? '', 'save_post')) {
            update_post_meta($post_id, "background_class", $_POST['background_class']);
        }
    }
}