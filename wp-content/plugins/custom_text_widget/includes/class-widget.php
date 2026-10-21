<?php

class Custom_Text_Widget extends WP_Widget
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'Custom_Text_Widget', // Base ID
            __('Custom Text Widget', 'tauro'), // Name
            array('description' => __('Displays one of your Custom Text Widgets.', 'tauro')) // Args
        );
    }


    public function widget($args, $instance)
    {
        $id = ($instance['custom_text-widget-id']) ? $instance['custom_text-widget-id'] : 0;
        $show_title = (isset($instance['show_title'])) ? $instance['show_title'] : 1;
        $post = get_post($id);


        $background_class = get_post_meta($post->ID, "background_class", true);
        $background_image = get_post_meta($post->ID, 'custom_background', true);
        $show_background = get_post_meta($post->ID, 'show_background', true);


        $section_class = ' ';
        $background = ' ';
        $title_class = 'title ';

        if (isset($background_image['url']) && isset($show_background) && $show_background) {
            $background = 'style="background-size: cover;background-image: url(' . $background_image['url'] . ')"';
        }

        if ($background_class) {
            $section_class .= $background_class;
        }

        echo $args['before_widget'];
        echo '<section id="custom_text_' . $id . '" class="' . $section_class . '" ' . $background . '  >';
        if (!empty($id) && $post) {


            if ($show_title) {
                $title = (isset($instance['title'])) ? $instance['title'] : $post->post_title;
                $title = apply_filters('widget_title', $title);
                echo $args['before_title'] . '
                     <div class="col-md-12">
                        <h2 class="' . $title_class . '">' . $title . '</h2>
                     </div>
                  ' . $args['after_title'];
            }

            echo do_shortcode(get_post_field('post_content', $post->ID));


        } elseif (current_user_can('manage_options')) { ?>
            <p>
                <?php if (empty($id)) {
                    _e('Please select a custom text to show in this area.', 'tauro');
                } else {
                    printf(__('No custom text found with ID %d, please select an existing custom text in the widget settings.', 'tauro'), $id);
                } ?>
            </p>
            <?php
        }

        echo $args['after_widget'];
        echo '
</section>';

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
        $instance['custom_text-widget-id'] = isset($new_instance['custom_text-widget-id']) ? $new_instance['custom_text-widget-id'] : 0;
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
            'post_type' => 'custom_text-widget',
            'numberposts' => -1
        ));

        $show_title = isset($instance['show_title']) ? $instance['show_title'] : 1;
        $selected_widget_id = isset($instance['custom_text-widget-id']) ? $instance['custom_text-widget-id'] : 0;
        $title = ($selected_widget_id) ? get_the_title($selected_widget_id) : __('No custom text selected.', 'tauro');
        ?>

        <input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>"
               type="hidden" value="<?php echo esc_attr($title); ?>"/>

        <p>
            <label for="<?php echo $this->get_field_id('custom_text-widget-id'); ?>"><?php _e('Custom Text To Show:', 'tauro'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('custom_text-widget-id'); ?>"
                    name="<?php echo $this->get_field_name('custom_text-widget-id'); ?>" required>
                <option value="0" disabled <?php selected($selected_widget_id, 0); ?>>
                    <?php if (empty($posts)) {
                        _e('No Custom Text Found', 'tauro');
                    } else {
                        _e('Select a Custom Text', 'tauro');
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

        <p class="help"><?php printf(__('Manage your custom text %shere%s', 'tauro'), '<a target="_blank" href="' . admin_url('post.php?post=' . $selected_widget_id . '&action=edit') . '">', '</a>'); ?></p>
        <?php
    }

}