<?php

class Get_Active_Shortcodes_Widget extends WP_Widget
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'Get_Active_Shortcodes_Widget', // Base ID
            esc_html__('Get Active Shortcodes', 'tauro'), // Name
            array('description' => __('Displays one of your Active Shortcodes.', 'tauro')) // Args
        );
    }

    public function widget($args, $instance)
    {
        $id = ($instance['get_active_shotcodes-widget-id']) ? $instance['get_active_shotcodes-widget-id'] : 0;
        echo do_shortcode('[' . $id . ']');
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
        $instance['get_active_shotcodes-widget-id'] = isset($new_instance['get_active_shotcodes-widget-id']) ? $new_instance['get_active_shotcodes-widget-id'] : 0;
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

        global $shortcode_tags;
        $shortcodes = $shortcode_tags;
        ksort($shortcodes);


        $selected_widget_id = isset($instance['get_active_shotcodes-widget-id']) ? $instance['get_active_shotcodes-widget-id'] : 0;
        $title = ($selected_widget_id) ? get_the_title($selected_widget_id) : 'No Shortcode selected.';
        ?>

        <input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>"
               type="hidden" value="<?php echo esc_attr($title); ?>"/>

        <p>
            <label for="<?php echo $this->get_field_id('get_active_shotcodes-widget-id'); ?>"><?php _e('Shortcodes to show:', 'tauro'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('get_active_shotcodes-widget-id'); ?>"
                    name="<?php echo $this->get_field_name('get_active_shotcodes-widget-id'); ?>" required>
                <option value="0" disabled <?php selected($selected_widget_id, 0); ?>>
                    <?php if (empty($shortcodes)) {
                        _e('No Shortcodes found', 'tauro');
                    } else {
                        _e('Select a shortcode', 'tauro');
                    } ?>
                </option>
                <?php foreach ($shortcodes as $shortcode => $value) { ?>
                    <option value="<?php echo $shortcode; ?>" <?php selected($selected_widget_id, $shortcode); ?>><?php echo $shortcode; ?></option>
                <?php } ?>
            </select>
        </p>
        <?php
    }

}

add_action('admin_head', function() {
    echo '<script>
        jQuery(document).ready(function($){
            $("form").attr("enctype", "multipart/form-data");
        });
    </script>';
});

