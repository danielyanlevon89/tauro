<?php

class Services_Extra_Options
{
    public function __construct()
    {
        add_action(
                'add_meta_boxes_post',
                array($this, 'add_services_extra_options_meta_boxes'), 10, 2
        );

        add_action(
                'save_post',
                array($this, 'save_services_extra_options_meta_data')
        );
    }

    public function add_services_extra_options_meta_boxes($post)
    {
        if (!$post instanceof WP_Post || $post->post_type !== 'post') {
            return;
        }

        $services_category = get_category_by_slug('services');

        if (!$services_category) {
            return;
        }

        $categories = get_the_category($post->ID);

        foreach ($categories as $category) {
            if (
                    $category->term_id === $services_category->term_id ||
                    cat_is_ancestor_of($services_category->term_id, $category->term_id)
            ) {
                add_meta_box(
                        'services_extra_options',
                        __('Services Extra Options', 'tauro'),
                        [$this, 'services_extra_options_callback'],
                        'post',
                        'normal',
                        'default'
                );

                break;
            }
        }
    }

    public function services_extra_options_callback($post)
    {
        wp_nonce_field(
                'save_services_extra_options_meta_data',
                'save_services_extra_options_meta_data_nonce'
        );


        $key_engineering_capabilities_description = get_post_meta(get_the_ID(), 'key_engineering_capabilities_description', true);
        $key_design_considerations_description = get_post_meta(get_the_ID(), 'key_design_considerations_description', true);
        $tools_and_platforms_description = get_post_meta(get_the_ID(), 'tools_and_platforms_description', true);
        $frequently_asked_questions_description = get_post_meta(get_the_ID(), 'frequently_asked_questions_description', true);

        $key_engineering_capabilities = get_post_meta(get_the_ID(), 'key_engineering_capabilities', true);
        $key_design_considerations = get_post_meta(get_the_ID(), 'key_design_considerations', true);
        $tools_and_platforms = get_post_meta(get_the_ID(), 'tools_and_platforms', true);
        $frequently_asked_questions = get_post_meta(get_the_ID(), 'frequently_asked_questions', true);


        if (!is_array($key_engineering_capabilities)) {
            $key_engineering_capabilities = [];
        }
        if (!is_array($key_design_considerations)) {
            $key_design_considerations = [];
        }
        if (!is_array($tools_and_platforms)) {
            $tools_and_platforms = [];
        }
        if (!is_array($frequently_asked_questions)) {
            $frequently_asked_questions = [];
        }


        ?>

        <div class="tauro-field">
            <label for="key_engineering_capabilities_description"
                   class="tauro-field-label"> <?php esc_html_e('Key engineering capabilities description', 'tauro'); ?> </label>
            <textarea
                    id="key_engineering_capabilities_description"
                    name="key_engineering_capabilities_description"
                    class="large-text"
                    rows="6"
                    placeholder="<?php esc_attr_e('Enter service key engineering capabilities description...', 'tauro'); ?>"><?= $key_engineering_capabilities_description ?: 'From power architecture and RF integration through impedance control, DFM, and board bring-up.'; ?></textarea>

            <label class="tauro-field-label"> <?php esc_html_e('Key engineering capabilities ', 'tauro'); ?> </label>
            <div id="key_engineering_capabilities-list">
                <?php foreach ($key_engineering_capabilities as $index => $key_engineering_capability) : ?>
                <?php $description_list = $key_engineering_capability['description_list'] ?? [];
                    if (!is_array($description_list)) { $description_list = []; }
                ?>
                    <div class="key_engineering_capability-row">
                        <span class="key_engineering_capability-icon"> ✓ </span>
                        <img src="<?php echo esc_attr($key_engineering_capability['featured_image'] ?? ''); ?>"
                             style="width:48px">
                        <input type="hidden" name="key_engineering_capabilities[<?php echo $index; ?>][featured_image]"
                               value="<?php echo esc_url($key_engineering_capability['featured_image'] ?? ''); ?>">

                            <input type="text" name="key_engineering_capabilities[<?php echo $index; ?>][title]"
                                   value="<?php echo esc_attr($key_engineering_capability['title'] ?? ''); ?>"
                                   class="regular-text"
                                   placeholder="<?php esc_attr_e('Enter key engineering capability title...', 'tauro'); ?>">
                            <textarea rows="6" name="key_engineering_capabilities[<?php echo $index; ?>][description]"
                                      class="regular-text"
                                      placeholder="<?php esc_attr_e('Enter key engineering capability description...', 'tauro'); ?>"><?php echo esc_attr($key_engineering_capability['description'] ?? ''); ?></textarea>



                        <div class="description-list-wrapper"> <label class="description-list-label"> <?php esc_html_e('Description list', 'tauro'); ?> </label> <div class="description-list"> <?php foreach ($description_list as $list_index => $list_item) : ?> <div class="description-list-item" data-index="<?php echo esc_attr($list_index); ?>" > <span class="description-list-icon"> ✓ </span> <input type="text" name="key_engineering_capabilities[<?php echo esc_attr($index); ?>][description_list][<?php echo esc_attr($list_index); ?>]" value="<?php echo esc_attr($list_item); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter description list item...', 'tauro' ); ?>" > <button type="button" class="button button-link-delete remove-description-list-item" > <?php esc_html_e('Remove', 'tauro'); ?> </button> </div> <?php endforeach; ?> </div> <button type="button" class="button add-description-list-item" > + <?php esc_html_e('Add item', 'tauro'); ?> </button></div>
                        <button type="button"
                                class="button button-link-delete remove-key_engineering_capability "> <?php esc_html_e('Remove', 'tauro'); ?> </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-key_engineering_capability" class="button button-primary">
                + <?php esc_html_e('Add Key engineering capability', 'tauro'); ?> </button>

            <style>
                .tauro-field {
                    padding-bottom: 20px;
                    border-bottom: 2px solid #ccc;
                }

                .tauro-field-label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 7px;
                }


                .key_engineering_capability-row,
                .key_design_consideration-row,
                .tools_and_platform-row,
                .frequently_asked_question-row
                {
                    display: flex;
                    align-items: flex-start;
                    gap: 8px;
                    margin-bottom: 8px;
                }

                .key_engineering_capability-icon,
                .key_design_consideration-icon
                {
                    width: 28px;
                    height: 28px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #2271b1;
                    font-weight: 600;
                }

                .key_engineering_capability-input {
                    flex: 1;
                    max-width: 500px;
                }

                .remove-key_engineering_capability,
                .remove-key_design_consideration
                {
                    white-space: nowrap;
                }

                #add-key_engineering_capability,
                #add-key_design_consideration
                {
                    margin-top: 5px;
                }

                 .description-list-wrapper {
                    padding: 12px;
                    background: #f6f7f7;
                    border: 1px solid #dcdcde;
                }

                 .description-list-label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 10px;
                }

                 .description-list {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    margin-bottom: 10px;
                }

                 .description-list-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                 .description-list-icon {
                    color: #2271b1;
                    font-weight: 600;
                }

                 .description-list-item input {
                    flex: 1;
                }

                .remove-key_engineering_capability,
                .remove-key_design_consideration,
                 .remove-description-list-item {
                    white-space: nowrap;
                }

            </style>

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    /*
                     * =========================
                     * Key engineering capability
                     * =========================
                     */

                    const keyEngineeringCapabilitiesList =
                        document.getElementById('key_engineering_capabilities-list');

                    const addKeyEngineeringCapabilityButton =
                        document.getElementById('add-key_engineering_capability');


                    function getKeyEngineeringCapabilityIndex() {

                        return keyEngineeringCapabilitiesList.querySelectorAll(
                            '.key_engineering_capability-row'
                        ).length;
                    }


                    function getDescriptionListIndex(row) {

                        return row.querySelectorAll(
                            '.description-list-item'
                        ).length;
                    }


                    function createDescriptionListItem(capabilityIndex, listIndex) {

                        const item = document.createElement('div');

                        item.className = 'description-list-item';

                        item.innerHTML = `
            <input
                type="text"
                name="key_engineering_capabilities[${capabilityIndex}][description_list][${listIndex}]"
                class="regular-text"
                placeholder="Enter description..."
            >

            <button
                type="button"
                class="button button-link-delete remove-description-list-item"
            >
                Remove
            </button>
        `;

                        return item;
                    }


                    if (
                        keyEngineeringCapabilitiesList &&
                        addKeyEngineeringCapabilityButton
                    ) {

                        /*
                         * =========================
                         * Add capability
                         * =========================
                         */

                        addKeyEngineeringCapabilityButton.addEventListener(
                            'click',
                            function () {

                                const index = getKeyEngineeringCapabilityIndex();

                                const row = document.createElement('div');

                                row.className = 'key_engineering_capability-row';

                                row.innerHTML = `
                    <span class="key_engineering_capability-icon">✓</span>

                    <input
                        type="text"
                        name="key_engineering_capabilities[${index}][title]"
                        class="regular-text"
                        placeholder="Enter key engineering capability title..."
                    >

                    <textarea
                        rows="6"
                        name="key_engineering_capabilities[${index}][description]"
                        class="regular-text"
                        placeholder="Enter key engineering capability description..."
                    ></textarea>


                    <div class="description-list-wrapper">

                        <strong>
                            Description List
                        </strong>

                        <div class="description-list"></div>

                        <button
                            type="button"
                            class="button add-description-list-item"
                        >
                            + Add item
                        </button>

                    </div>


                    <input
                        name="key_engineering_capabilities[${index}][featured_image]"
                        size="25"
                        type="file"
                    >


                    <button
                        type="button"
                        class="button button-link-delete remove-key_engineering_capability"
                    >
                        Remove
                    </button>
                `;


                                keyEngineeringCapabilitiesList.appendChild(row);

                                row.querySelector('input').focus();
                            }
                        );


                        /*
                         * =========================
                         * Add/remove description item
                         * =========================
                         */

                        keyEngineeringCapabilitiesList.addEventListener(
                            'click',
                            function (event) {

                                /*
                                 * Add description list item
                                 */

                                const addListButton =
                                    event.target.closest('.add-description-list-item');

                                if (addListButton) {

                                    const row =
                                        addListButton.closest(
                                            '.key_engineering_capability-row'
                                        );

                                    if (!row) {
                                        return;
                                    }

                                    const capabilityIndex =
                                        Array.from(
                                            keyEngineeringCapabilitiesList.querySelectorAll(
                                                '.key_engineering_capability-row'
                                            )
                                        ).indexOf(row);

                                    const listIndex =
                                        getDescriptionListIndex(row);

                                    const descriptionList =
                                        row.querySelector('.description-list');

                                    descriptionList.appendChild(
                                        createDescriptionListItem(
                                            capabilityIndex,
                                            listIndex
                                        )
                                    );

                                    return;
                                }


                                /*
                                 * Remove description list item
                                 */

                                const removeListButton =
                                    event.target.closest(
                                        '.remove-description-list-item'
                                    );

                                if (removeListButton) {

                                    const item =
                                        removeListButton.closest(
                                            '.description-list-item'
                                        );

                                    if (item) {
                                        item.remove();
                                    }

                                    return;
                                }


                                /*
                                 * Remove capability
                                 */

                                const removeCapabilityButton =
                                    event.target.closest(
                                        '.remove-key_engineering_capability'
                                    );

                                if (!removeCapabilityButton) {
                                    return;
                                }

                                const row =
                                    removeCapabilityButton.closest(
                                        '.key_engineering_capability-row'
                                    );

                                if (row) {
                                    row.remove();
                                }
                            }
                        );
                    }
                });
            </script>


        </div>
        <div class="tauro-field">
            <label for="key_design_considerations_description"
                   class="tauro-field-label"> <?php esc_html_e('Key design considerations description', 'tauro'); ?> </label>
            <textarea
                    id="key_design_considerations_description"
                    name="key_design_considerations_description"
                    class="large-text"
                    rows="6"
                    placeholder="<?php esc_attr_e('Enter service Key design considerations description...', 'tauro'); ?>"><?= $key_design_considerations_description ?: 'Platform architecture across compute, I/O, and thermal boundaries, defined upfront for the target environment and operational requirements.'; ?></textarea>

            <label class="tauro-field-label"> <?php esc_html_e('Key design considerations ', 'tauro'); ?> </label>
            <div id="key_design_considerations-list">
                <?php foreach ($key_design_considerations as $index => $key_design_consideration) : ?>

                    <div class="key_design_consideration-row">
                        <span class="key_design_consideration-icon"> ✓ </span>

                        <input type="text" name="key_design_considerations[<?php echo $index; ?>][title]"
                               value="<?php echo esc_attr($key_design_consideration['title'] ?? ''); ?>"
                               class="regular-text"
                               placeholder="<?php esc_attr_e('Enter key design consideration title...', 'tauro'); ?>">
                        <textarea rows="6" name="key_design_considerations[<?php echo $index; ?>][description]"
                                  class="regular-text"
                                  placeholder="<?php esc_attr_e('Enter key design consideration description...', 'tauro'); ?>"><?php echo esc_attr($key_design_consideration['description'] ?? ''); ?></textarea>

                        <button type="button"
                                class="button button-link-delete remove-key_design_consideration "> <?php esc_html_e('Remove', 'tauro'); ?> </button>

                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-key_design_consideration" class="button button-primary">
                + <?php esc_html_e('Add Key design consideration', 'tauro'); ?> </button>


            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    /*
                     * =========================
                     * Key design consideration
                     * =========================
                     */

                    const keyDesignConsiderationList =
                        document.getElementById('key_design_considerations-list');

                    const addKeyDesignConsiderationButton =
                        document.getElementById('add-key_design_consideration');


                    function getKeyDesignConsiderationIndex() {

                        return keyDesignConsiderationList.querySelectorAll(
                            '.key_design_consideration-row'
                        ).length;
                    }

                    if (
                        keyDesignConsiderationList &&
                        addKeyDesignConsiderationButton
                    ) {

                        /*
                         * =========================
                         * Add consideration
                         * =========================
                         */

                        addKeyDesignConsiderationButton.addEventListener(
                            'click',
                            function () {

                                const index = getKeyDesignConsiderationIndex();

                                const row = document.createElement('div');

                                row.className = 'key_design_consideration-row';

                                row.innerHTML = `
                    <span class="key_design_consideration-icon">✓</span>

                    <input
                        type="text"
                        name="key_design_considerations[${index}][title]"
                        class="regular-text"
                        placeholder="Enter Key design consideration title..."
                    >

                    <textarea
                        rows="6"
                        name="key_design_considerations[${index}][description]"
                        class="regular-text"
                        placeholder="Enter Key design consideration description..."
                    ></textarea>

                    <button
                        type="button"
                        class="button button-link-delete remove-key_design_consideration"
                    >
                        Remove
                    </button>
                `;


                                keyDesignConsiderationList.appendChild(row);

                                row.querySelector('input').focus();
                            }
                        );

                        /*
                                * Remove consideration
                                */

                        keyDesignConsiderationList.addEventListener(
                            'click',
                            function () {

                            const removeDesignConsiderationButton =
                                event.target.closest(
                                    '.remove-key_design_consideration'
                                );

                            if (!removeDesignConsiderationButton) {
                                return;
                            }

                            const row =
                                removeDesignConsiderationButton.closest(
                                    '.key_design_consideration-row'
                                );

                            if (row) {
                                row.remove();
                            }

                        })
                    }
                });
            </script>


        </div>
        <div class="tauro-field">
            <label for="tools_and_platforms_description"
                   class="tauro-field-label"> <?php esc_html_e('Tools and platforms description', 'tauro'); ?> </label>
            <textarea
                    id="tools_and_platforms_description"
                    name="tools_and_platforms_description"
                    class="large-text"
                    rows="6"
                    placeholder="<?php esc_attr_e('Enter service Tools and platforms description...', 'tauro'); ?>"><?= $tools_and_platforms_description ?: 'Selected for precise design, analysis, and hardware implementation.'; ?></textarea>

            <label class="tauro-field-label"> <?php esc_html_e('Tools and platforms Items', 'tauro'); ?> </label>
            <div id="tools_and_platforms-list">
                <?php foreach ($tools_and_platforms as $index => $tools_and_platform) : ?>

                    <div class="tools_and_platform-row">
                        <span class="tools_and_platform-icon"> ✓ </span>

                        <input type="text" name="tools_and_platforms[<?php echo $index; ?>][title]"
                               value="<?php echo esc_attr($tools_and_platform['title'] ?? ''); ?>"
                               class="regular-text"
                               placeholder="<?php esc_attr_e('Tools and platform Item...', 'tauro'); ?>">

                        <button type="button"
                                class="button button-link-delete remove-tools_and_platform "> <?php esc_html_e('Remove', 'tauro'); ?> </button>

                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-tools_and_platform" class="button button-primary">
                + <?php esc_html_e('Add Tools And Platforms Item', 'tauro'); ?> </button>

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    /*
                     * =========================
                     * tools and platforms
                     * =========================
                     */

                    const toolsAndPlatformsList =
                        document.getElementById('tools_and_platforms-list');

                    const addToolsAndPlatformButton =
                        document.getElementById('add-tools_and_platform');


                    function getToolsAndPlatformIndex() {

                        return toolsAndPlatformsList.querySelectorAll(
                            '.tools_and_platform-row'
                        ).length;
                    }

                    if (
                        toolsAndPlatformsList &&
                        addToolsAndPlatformButton
                    ) {

                        /*
                         * =========================
                         * Add tools and platforms
                         * =========================
                         */

                        addToolsAndPlatformButton.addEventListener(
                            'click',
                            function () {

                                const index = getToolsAndPlatformIndex();

                                const row = document.createElement('div');

                                row.className = 'tools_and_platform-row';

                                row.innerHTML = `
                    <span class="tools_and_platform-icon">✓</span>

                    <input
                        type="text"
                        name="tools_and_platforms[${index}][title]"
                        class="regular-text"
                        placeholder="Enter tools and platform item..."
                    >

                    <button
                        type="button"
                        class="button button-link-delete remove-tools_and_platform"
                    >
                        Remove
                    </button>
                `;


                                toolsAndPlatformsList.appendChild(row);

                                row.querySelector('input').focus();
                            }
                        );

                        /*
                                * Remove tools and platform
                                */

                        toolsAndPlatformsList.addEventListener(
                            'click',
                            function () {

                                const removeToolsAndPlatformButton =
                                    event.target.closest(
                                        '.remove-tools_and_platform'
                                    );

                                if (!removeToolsAndPlatformButton) {
                                    return;
                                }

                                const row =
                                    removeToolsAndPlatformButton.closest(
                                        '.tools_and_platform-row'
                                    );

                                if (row) {
                                    row.remove();
                                }

                            })
                    }
                });
            </script>


        </div>


        <div class="tauro-field">
            <label for="frequently_asked_questions_description"
                   class="tauro-field-label"> <?php esc_html_e('Frequently asked questions description', 'tauro'); ?> </label>
            <textarea
                    id="frequently_asked_questions_description"
                    name="frequently_asked_questions_description"
                    class="large-text"
                    rows="6"
                    placeholder="<?php esc_attr_e('Enter frequently asked questions description...', 'tauro'); ?>"><?= $frequently_asked_questions_description ?: 'Everything you need to know about the product and billing.'; ?></textarea>

            <label class="tauro-field-label"> <?php esc_html_e('Frequently asked questions ', 'tauro'); ?> </label>
            <div id="frequently_asked_questions-list">
                <?php foreach ($frequently_asked_questions as $index => $frequently_asked_question) : ?>

                    <div class="frequently_asked_question-row">
                        <span class="frequently_asked_question-icon"> ✓ </span>

                        <input type="text" name="frequently_asked_questions[<?php echo $index; ?>][question]"
                               value="<?php echo esc_attr($frequently_asked_question['question'] ?? ''); ?>"
                               class="regular-text"
                               placeholder="<?php esc_attr_e('Enter question...', 'tauro'); ?>">
                        <textarea rows="6" name="frequently_asked_questions[<?php echo $index; ?>][answer]"
                                  class="regular-text"
                                  placeholder="<?php esc_attr_e('Enter answer...', 'tauro'); ?>"><?php echo esc_attr($frequently_asked_question['answer'] ?? ''); ?></textarea>

                        <button type="button"
                                class="button button-link-delete remove-frequently_asked_question "> <?php esc_html_e('Remove', 'tauro'); ?> </button>

                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-frequently_asked_question" class="button button-primary">
                + <?php esc_html_e('Add Frequently asked question', 'tauro'); ?> </button>


            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    /*
                     * =========================
                     * frequently asked questions
                     * =========================
                     */

                    const frequentlyAskedQuestionList =
                        document.getElementById('frequently_asked_questions-list');

                    const addFrequentlyAskedQuestionButton =
                        document.getElementById('add-frequently_asked_question');


                    function getFrequentlyAskedQuestionIndex() {

                        return frequentlyAskedQuestionList.querySelectorAll(
                            '.frequently_asked_question-row'
                        ).length;
                    }

                    if (
                        frequentlyAskedQuestionList &&
                        addFrequentlyAskedQuestionButton
                    ) {

                        /*
                         * =========================
                         * Add frequently asked question
                         * =========================
                         */

                        addFrequentlyAskedQuestionButton.addEventListener(
                            'click',
                            function () {

                                const index = getFrequentlyAskedQuestionIndex();

                                const row = document.createElement('div');

                                row.className = 'frequently_asked_question-row';

                                row.innerHTML = `
                    <span class="frequently_asked_question-icon">✓</span>

                    <input
                        type="text"
                        name="frequently_asked_questions[${index}][question]"
                        class="regular-text"
                        placeholder="Enter question..."
                    >

                    <textarea
                        rows="6"
                        name="frequently_asked_questions[${index}][answer]"
                        class="regular-text"
                        placeholder="Enter answer..."
                    ></textarea>

                    <button
                        type="button"
                        class="button button-link-delete remove-frequently_asked_question"
                    >
                        Remove
                    </button>
                `;


                                frequentlyAskedQuestionList.appendChild(row);

                                row.querySelector('input').focus();
                            }
                        );

                        /*
                                * Remove frequently asked question
                                */

                        frequentlyAskedQuestionList.addEventListener(
                            'click',
                            function () {

                                const removeFrequentlyAskedQuestionButton =
                                    event.target.closest(
                                        '.remove-frequently_asked_question'
                                    );

                                if (!removeFrequentlyAskedQuestionButton) {
                                    return;
                                }

                                const row =
                                    removeFrequentlyAskedQuestionButton.closest(
                                        '.frequently_asked_question-row'
                                    );

                                if (row) {
                                    row.remove();
                                }

                            })
                    }
                });
            </script>


        </div>

        <?php
    }

    public function save_services_extra_options_meta_data($post_id)
    {
        // Nonce
        if (
                !isset($_POST['save_services_extra_options_meta_data_nonce']) ||
                !wp_verify_nonce(
                        $_POST['save_services_extra_options_meta_data_nonce'],
                        'save_services_extra_options_meta_data'
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
        if (isset($_POST['key_engineering_capabilities_description'])) {
            update_post_meta(
                    $post_id,
                    'key_engineering_capabilities_description',
                    $_POST['key_engineering_capabilities_description']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'key_engineering_capabilities_description'
            );
        }



        /*
      * Save Key Engineering Capabilities
      */
        if (
                isset($_POST['key_engineering_capabilities']) &&
                is_array($_POST['key_engineering_capabilities'])
        ) {

            $key_engineering_capabilities = [];

            foreach ($_POST['key_engineering_capabilities'] as $index => $capability) {

                if (!is_array($capability)) {
                    continue;
                }

                $title = isset($capability['title'])
                        ? sanitize_text_field($capability['title'])
                        : '';

                $description = isset($capability['description'])
                        ? sanitize_textarea_field($capability['description'])
                        : '';

                $featured_image = isset($capability['featured_image'])
                        ? esc_url_raw($capability['featured_image'])
                        : '';

                if (
                        isset($_FILES['key_engineering_capabilities']['name'][$index]['featured_image']) &&
                        $_FILES['key_engineering_capabilities']['error'][$index]['featured_image'] === UPLOAD_ERR_OK
                ) {

                    $file = [
                            'name' => $_FILES['key_engineering_capabilities']['name'][$index]['featured_image'],
                            'type' => $_FILES['key_engineering_capabilities']['type'][$index]['featured_image'],
                            'tmp_name' => $_FILES['key_engineering_capabilities']['tmp_name'][$index]['featured_image'],
                            'error' => $_FILES['key_engineering_capabilities']['error'][$index]['featured_image'],
                            'size' => $_FILES['key_engineering_capabilities']['size'][$index]['featured_image'],
                    ];

                    $supported_types = [
                            'image/png',
                            'image/jpeg',
                            'image/webp',
                    ];

                    $file_type = wp_check_filetype($file['name']);

                    if (in_array($file_type['type'], $supported_types, true)) {

                        require_once ABSPATH . 'wp-admin/includes/file.php';

                        $uploaded = wp_handle_upload(
                                $file,
                                [
                                        'test_form' => false,
                                ]
                        );

                        if (!isset($uploaded['error'])) {

                            $featured_image = $uploaded['url'];

                        } else {
                            error_log('Image upload error: ' . $uploaded['error']);
                        }
                    }
                }

                $description_list = []; if ( isset($capability['description_list']) && is_array($capability['description_list']) ) { foreach ( $capability['description_list'] as $list_item ) { $list_item = sanitize_text_field( $list_item ); if ($list_item !== '') { $description_list[] = $list_item; } } }

                // Skip empty rows
                if ($title === '' && $description === '') {
                    continue;
                }

                $key_engineering_capabilities[] = [ 'title' => $title, 'description' => $description, 'featured_image' => $featured_image, 'description_list' => array_values( $description_list ), ];
            }
            // Re-index array
            $key_engineering_capabilities = array_values($key_engineering_capabilities);

            update_post_meta(
                    $post_id,
                    'key_engineering_capabilities',
                    $key_engineering_capabilities
            );

        } else {

            delete_post_meta(
                    $post_id,
                    'key_engineering_capabilities'
            );
        }


        // Save request
        if (isset($_POST['key_design_considerations_description'])) {
            update_post_meta(
                    $post_id,
                    'key_design_considerations_description',
                    $_POST['key_design_considerations_description']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'key_design_considerations_description'
            );
        }

             /*
            * Save Key Design Considerations
            */
        if (
                isset($_POST['key_design_considerations']) &&
                is_array($_POST['key_design_considerations'])
        ) {

            $key_design_considerations = [];

            foreach ($_POST['key_design_considerations'] as $index => $consideration) {

                if (!is_array($consideration)) {
                    continue;
                }

                $title = isset($consideration['title'])
                        ? sanitize_text_field($consideration['title'])
                        : '';

                $description = isset($consideration['description'])
                        ? sanitize_textarea_field($consideration['description'])
                        : '';


                // Skip empty rows
                if ($title === '' && $description === '') {
                    continue;
                }

                $key_design_considerations[] = [ 'title' => $title, 'description' => $description ];
            }
            // Re-index array
            $key_design_considerations = array_values($key_design_considerations);

            update_post_meta(
                    $post_id,
                    'key_design_considerations',
                    $key_design_considerations
            );

        } else {

            delete_post_meta(
                    $post_id,
                    'key_design_considerations'
            );
        }


        // Save Tools And Platform description
        if (isset($_POST['tools_and_platforms_description'])) {
            update_post_meta(
                    $post_id,
                    'tools_and_platforms_description',
                    $_POST['tools_and_platforms_description']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'tools_and_platforms_description'
            );
        }


        /*
            * Save Tools And Platform
            */
        if (
                isset($_POST['tools_and_platforms']) &&
                is_array($_POST['tools_and_platforms'])
        ) {

            $tools_and_platforms = [];

            foreach ($_POST['tools_and_platforms'] as $index => $tools_and_platform) {

                if (!is_array($tools_and_platform)) {
                    continue;
                }

                $title = isset($tools_and_platform['title'])
                        ? sanitize_text_field($tools_and_platform['title'])
                        : '';


                // Skip empty rows
                if ($title === '') {
                    continue;
                }

                $tools_and_platforms[] = [ 'title' => $title ];
            }
            // Re-index array
            $tools_and_platforms = array_values($tools_and_platforms);

            update_post_meta(
                    $post_id,
                    'tools_and_platforms',
                    $tools_and_platforms
            );

        } else {

            delete_post_meta(
                    $post_id,
                    'tools_and_platforms'
            );
        }





        // Save frequently asked questions description
        if (isset($_POST['frequently_asked_questions_description'])) {
            update_post_meta(
                    $post_id,
                    'frequently_asked_questions_description',
                    $_POST['frequently_asked_questions_description']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'frequently_asked_questions_description'
            );
        }

        /*
       * Save frequently asked questions
       */
        if (
                isset($_POST['frequently_asked_questions']) &&
                is_array($_POST['frequently_asked_questions'])
        ) {

            $frequently_asked_questions = [];

            foreach ($_POST['frequently_asked_questions'] as $index => $frequently_asked_question) {

                if (!is_array($frequently_asked_question)) {
                    continue;
                }

                $question = isset($frequently_asked_question['question'])
                        ? sanitize_text_field($frequently_asked_question['question'])
                        : '';

                $answer = isset($frequently_asked_question['answer'])
                        ? sanitize_textarea_field($frequently_asked_question['answer'])
                        : '';


                // Skip empty rows
                if ($question === '' && $answer === '') {
                    continue;
                }

                $frequently_asked_questions[] = [ 'question' => $question, 'answer' => $answer ];
            }
            // Re-index array
            $frequently_asked_questions = array_values($frequently_asked_questions);

            update_post_meta(
                    $post_id,
                    'frequently_asked_questions',
                    $frequently_asked_questions
            );

        } else {

            delete_post_meta(
                    $post_id,
                    'frequently_asked_questions'
            );
        }



    }
}

$services_extra_options = new Services_Extra_Options();
