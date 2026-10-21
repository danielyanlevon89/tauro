<?php

class Products_Extra_Options
{
    public function __construct()
    {
        add_action(
            'add_meta_boxes_post',
            array($this, 'add_products_extra_options_meta_boxes'), 10, 2
        );

        add_action(
            'save_post',
            array($this, 'save_products_extra_options_meta_data')
        );
    }

    public function add_products_extra_options_meta_boxes($post)
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
                    'products_extra_options',
                    __('Products Extra Options', 'tauro'),
                    [$this, 'products_extra_options_callback'],
                    'post',
                    'normal',
                    'default'
                );

                break;
            }
        }
    }

    public function products_extra_options_callback($post)
    {
        wp_nonce_field(
            'save_products_extra_options_meta_data',
            'save_products_extra_options_meta_data_nonce'
        );


        $popup_arrow_link = get_post_meta(get_the_ID(), 'popup_arrow_link', true);
        $popup_silicon_highway_link = get_post_meta(get_the_ID(), 'popup_silicon_highway_link', true);
        $description = get_post_meta(get_the_ID(), 'description', true);
        $key_features = get_post_meta(get_the_ID(), 'key_features', true);
        $key_specifications = get_post_meta(get_the_ID(), 'key_specifications', true);
        if (!is_array($key_features)) { $key_features = []; }
        if (!is_array($key_specifications)) { $key_specifications = []; }
        ?>

        <div class="tauro-field">
            <table>
                <tr>
                    <td>
                        <input
                            id="popup_arrow_link"
                            name="popup_arrow_link"
                            class="large-text"
                            placeholder="<?php esc_attr_e('Enter Popup Arrow Link', 'tauro'); ?>" value="<?= $popup_arrow_link; ?>">
                    </td>
                    <td>
                        <input
                            id="popup_silicon_highway_link"
                            name="popup_silicon_highway_link"
                            class="large-text"
                            placeholder="<?php esc_attr_e('Enter Popup Silicon Highway Link', 'tauro'); ?>" value="<?= $popup_silicon_highway_link; ?>">

                    </td>
                </tr>
            </table>


        </div>
        <div class="tauro-field">
            <label for="description" class="tauro-field-label"> <?php esc_html_e('Description', 'tauro'); ?> </label>
            <textarea
                id="description"
                name="description"
                class="large-text"
                rows="6"
                placeholder="<?php esc_attr_e('Enter product description...', 'tauro'); ?>" ><?= $description; ?></textarea>
        </div>
        <div class="tauro-field key-features-wrapper">
            <label class="tauro-field-label"> <?php esc_html_e('Key Features', 'tauro'); ?> </label>
            <div id="key-features-list">
                <?php foreach ($key_features as $index => $feature) : ?>
                    <div class="key-feature-row">
                        <span class="key-feature-icon"> ✓ </span>
                        <input type="text" name="key_features[]" value="<?php echo esc_attr($feature); ?>" class="regular-text key-feature-input" placeholder="<?php esc_attr_e('Enter key feature...', 'tauro'); ?>" >
                        <button type="button" class="button button-link-delete remove-key-feature" > <?php esc_html_e('Remove', 'tauro'); ?> </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-key-feature" class="button button-secondary" > + <?php esc_html_e('Add Feature', 'tauro'); ?> </button>
        </div>
        <div class="tauro-field key-specifications-wrapper">

            <label class="tauro-field-label">
                <?php esc_html_e('Key Specifications', 'tauro'); ?>
            </label>

            <div id="key-specifications-list">

                <?php foreach ($key_specifications as $spec_index => $specification) : ?>

                    <div class="key-specification-row">

                        <!-- Specification name -->
                        <div class="key-specification-name">
                            <input
                                type="text"
                                name="key_specifications[<?php echo $spec_index; ?>][key]"
                                value="<?php echo esc_attr($specification['key'] ?? ''); ?>"
                                class="regular-text"
                                placeholder="Specification"
                            >
                        </div>

                        <!-- Values -->
                        <div class="key-specification-values">

                            <?php
                            $values = $specification['values'] ?? [];

                            if (!is_array($values)) {
                                $values = [$values];
                            }
                            ?>

                            <div class="specification-values-list">

                                <?php foreach ($values as $value_index => $value) : ?>

                                    <div class="specification-value-row">

                                        <input
                                            type="text"
                                            name="key_specifications[<?php echo $spec_index; ?>][values][]"
                                            value="<?php echo esc_attr($value); ?>"
                                            class="regular-text"
                                            placeholder="Value"
                                        >

                                        <button
                                            type="button"
                                            class="button button-link-delete remove-specification-value"
                                        >
                                            Remove
                                        </button>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                            <button
                                type="button"
                                class="button button-secondary add-specification-value"
                            >
                                + Add Value
                            </button>

                        </div>

                        <!-- Remove specification -->
                        <button
                            type="button"
                            class="button button-link-delete remove-key-specification"
                        >
                            Remove Specification
                        </button>

                    </div>

                <?php endforeach; ?>

            </div>

            <button
                type="button"
                id="add-key-specification"
                class="button button-primary"
            >
                + Add Specification
            </button>

        </div>

        <style>
            .tauro-field { padding-bottom: 20px;border-bottom: 2px solid #ccc; }
            .tauro-field-label { display: block; font-weight: 600; margin-bottom: 7px; }
            .key-features-wrapper { margin-top: 20px; }
            .key-feature-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
            .key-feature-icon { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; color: #2271b1; font-weight: 600; }
            .key-feature-input { flex: 1; max-width: 500px; }
            .remove-key-feature { white-space: nowrap; }
            #add-key-feature,#add-key-specification { margin-top: 5px; }
            .key-specifications-wrapper { margin-top: 20px; }
            .key-specification-row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; }
            .key-specification-row input { flex: 1; }
            .key-specification-row input:first-child { max-width: 220px; }
            .key-specification-row input:nth-child(2) { max-width: 500px; }
            .key-specification-row .remove-key-specification { white-space: nowrap; }
            .specification-value-row {margin-bottom: 5px;}
        </style>


        <script>
            document.addEventListener('DOMContentLoaded', function () {

                /*
                 * =========================
                 * Key Features
                 * =========================
                 */

                const featuresList = document.getElementById('key-features-list');
                const addFeatureButton = document.getElementById('add-key-feature');

                if (featuresList && addFeatureButton) {

                    addFeatureButton.addEventListener('click', function () {

                        const row = document.createElement('div');

                        row.className = 'key-feature-row';

                        row.innerHTML = `
                <span class="key-feature-icon">✓</span>

                <input
                    type="text"
                    name="key_features[]"
                    class="regular-text key-feature-input"
                    placeholder="Enter key feature..."
                >

                <button
                    type="button"
                    class="button button-link-delete remove-key-feature"
                >
                    Remove
                </button>
            `;

                        featuresList.appendChild(row);

                        row.querySelector('input').focus();
                    });

                    featuresList.addEventListener('click', function (event) {

                        const button = event.target.closest('.remove-key-feature');

                        if (!button) {
                            return;
                        }

                        const row = button.closest('.key-feature-row');

                        if (row) {
                            row.remove();
                        }
                    });
                }


                /*
                 * =========================
                 * Key Specifications
                 * =========================
                 */

                const specificationsList =
                    document.getElementById('key-specifications-list');

                const addSpecificationButton =
                    document.getElementById('add-key-specification');


                /*
                 * Get next specification index
                 */
                function getSpecificationIndex() {

                    return specificationsList.querySelectorAll(
                        '.key-specification-row'
                    ).length;
                }


                /*
                 * Add new specification
                 */
                if (specificationsList && addSpecificationButton) {

                    addSpecificationButton.addEventListener('click', function () {

                        const index = getSpecificationIndex();

                        const row = document.createElement('div');

                        row.className = 'key-specification-row';

                        row.innerHTML = `
                <div class="key-specification-name">

                    <input
                        type="text"
                        name="key_specifications[${index}][key]"
                        class="regular-text"
                        placeholder="Key"
                    >

                </div>

                <div class="key-specification-values">

                    <div class="specification-values-list">

                        <div class="specification-value-row">

                            <input
                                type="text"
                                name="key_specifications[${index}][values][]"
                                class="regular-text"
                                placeholder="Value"
                            >

                            <button
                                type="button"
                                class="button button-link-delete remove-specification-value"
                            >
                                Remove
                            </button>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="button button-secondary add-specification-value"
                    >
                        + Add Value
                    </button>

                </div>

                <button
                    type="button"
                    class="button button-link-delete remove-key-specification"
                >
                    Remove Specification
                </button>
            `;

                        specificationsList.appendChild(row);

                        row.querySelector('input').focus();
                    });


                    /*
                     * Handle all specification buttons
                     */
                    specificationsList.addEventListener('click', function (event) {

                        /*
                         * Add value
                         */
                        const addValueButton =
                            event.target.closest('.add-specification-value');

                        if (addValueButton) {

                            const specificationRow =
                                addValueButton.closest('.key-specification-row');

                            const valuesList =
                                specificationRow.querySelector(
                                    '.specification-values-list'
                                );

                            const firstInput =
                                specificationRow.querySelector(
                                    '.specification-value-row input'
                                );

                            const name = firstInput
                                ? firstInput.name
                                : '';

                            const row = document.createElement('div');

                            row.className = 'specification-value-row';

                            row.innerHTML = `
                    <input
                        type="text"
                        name="${name}"
                        class="regular-text"
                        placeholder="Value"
                    >

                    <button
                        type="button"
                        class="button button-link-delete remove-specification-value"
                    >
                        Remove
                    </button>
                `;

                            valuesList.appendChild(row);

                            row.querySelector('input').focus();

                            return;
                        }


                        /*
                         * Remove value
                         */
                        const removeValueButton =
                            event.target.closest('.remove-specification-value');

                        if (removeValueButton) {

                            const row =
                                removeValueButton.closest('.specification-value-row');

                            if (row) {
                                row.remove();
                            }

                            return;
                        }


                        /*
                         * Remove specification
                         */
                        const removeSpecificationButton =
                            event.target.closest('.remove-key-specification');

                        if (removeSpecificationButton) {

                            const row =
                                removeSpecificationButton.closest(
                                    '.key-specification-row'
                                );

                            if (row) {
                                row.remove();
                            }
                        }

                    });
                }

            });
        </script>


        <?php
    }

    public function save_products_extra_options_meta_data($post_id)
    {
        // Nonce
        if (
            !isset($_POST['save_products_extra_options_meta_data_nonce']) ||
            !wp_verify_nonce(
                $_POST['save_products_extra_options_meta_data_nonce'],
                'save_products_extra_options_meta_data'
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

        // Save request
        if (isset($_POST['description'])) {
            update_post_meta(
                $post_id,
                'description',
                $_POST['description']
            );
        } else {
            delete_post_meta(
                $post_id,
                'description'
            );
        }

        // Save request
        if (isset($_POST['popup_arrow_link'])) {
            update_post_meta(
                $post_id,
                'popup_arrow_link',
                $_POST['popup_arrow_link']
            );
        } else {
            delete_post_meta(
                $post_id,
                'popup_arrow_link'
            );
        }
        // Save request
        if (isset($_POST['popup_silicon_highway_link'])) {
            update_post_meta(
                $post_id,
                'popup_silicon_highway_link',
                $_POST['popup_silicon_highway_link']
            );
        } else {
            delete_post_meta(
                $post_id,
                'popup_silicon_highway_link'
            );
        }

        if (isset($_POST['key_features']) && is_array($_POST['key_features'])) {
            $key_features = array_map( 'sanitize_text_field', $_POST['key_features'] );
            // Remove empty features
            $key_features = array_filter($key_features);
            // Re-index array
            $key_features = array_values($key_features);
            update_post_meta( $post_id, 'key_features', $key_features );
        } else {
            delete_post_meta(
                $post_id,
                'key_features'
            );
        }


        if (
            isset($_POST['key_specifications']) &&
            is_array($_POST['key_specifications'])
        ) {

            $key_specifications = [];

            foreach ($_POST['key_specifications'] as $specification) {

                $key = sanitize_text_field(
                    $specification['key'] ?? ''
                );

                $values = $specification['values'] ?? [];

                if (!is_array($values)) {
                    $values = [$values];
                }

                $clean_values = [];

                foreach ($values as $value) {

                    $value = sanitize_text_field($value);

                    if ($value !== '') {
                        $clean_values[] = $value;
                    }
                }

                // Skip completely empty specification
                if ($key === '' && empty($clean_values)) {
                    continue;
                }

                $key_specifications[] = [
                    'key'    => $key,
                    'values' => $clean_values,
                ];
            }

            update_post_meta(
                $post_id,
                'key_specifications',
                $key_specifications
            );

        } else {

            delete_post_meta(
                $post_id,
                'key_specifications'
            );
        }


    }
}

$products_extra_options = new Products_Extra_Options();
