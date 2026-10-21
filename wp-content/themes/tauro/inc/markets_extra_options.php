<?php

class Markets_Extra_Options
{
    public function __construct()
    {
        add_action(
                'add_meta_boxes_post',
                array($this, 'add_markets_extra_options_meta_boxes'), 10, 2
        );

        add_action(
                'save_post',
                array($this, 'save_markets_extra_options_meta_data')
        );
    }

    public function add_markets_extra_options_meta_boxes($post)
    {
        if (!$post instanceof WP_Post || $post->post_type !== 'post') {
            return;
        }

        $markets_category = get_category_by_slug('markets');

        if (!$markets_category) {
            return;
        }

        $categories = get_the_category($post->ID);

        foreach ($categories as $category) {
            if (
                    $category->term_id === $markets_category->term_id ||
                    cat_is_ancestor_of($markets_category->term_id, $category->term_id)
            ) {
                add_meta_box(
                        'markets_extra_options',
                        __('Markets Extra Options', 'tauro'),
                        [$this, 'markets_extra_options_callback'],
                        'post',
                        'normal',
                        'default'
                );

                break;
            }
        }
    }

    public function markets_extra_options_callback($post)
    {
        wp_nonce_field(
                'save_markets_extra_options_meta_data',
                'save_markets_extra_options_meta_data_nonce'
        );


        $quote_description = get_post_meta(get_the_ID(), 'quote_description', true);

        $production_shift_title = get_post_meta(get_the_ID(), 'production_shift_title', true);
        $production_shift_description = get_post_meta(get_the_ID(), 'production_shift_description', true);
        $production_shift_items = get_post_meta(get_the_ID(), 'production_shift_items', true);
        if (!is_array($production_shift_items)) {
            $production_shift_items = [];
        }

        $what_we_build_title = get_post_meta(get_the_ID(), 'what_we_build_title', true);
        $what_we_build_description = get_post_meta(get_the_ID(), 'what_we_build_description', true);
        $what_we_build_items = get_post_meta(get_the_ID(), 'what_we_build_items', true);
        if (!is_array($what_we_build_items)) {
            $what_we_build_items = [];
        }

        $compute_makes_it_work_title = get_post_meta(get_the_ID(), 'compute_makes_it_work_title', true);
        $compute_makes_it_work_description = get_post_meta(get_the_ID(), 'compute_makes_it_work_description', true);
        $compute_makes_it_work_items = get_post_meta(get_the_ID(), 'compute_makes_it_work_items', true);
        if (!is_array($compute_makes_it_work_items)) {
            $compute_makes_it_work_items = [];
        }


        $frequently_asked_questions_description = get_post_meta(get_the_ID(), 'frequently_asked_questions_description', true);
        $frequently_asked_questions = get_post_meta(get_the_ID(), 'frequently_asked_questions', true);
        if (!is_array($frequently_asked_questions)) {
            $frequently_asked_questions = [];
        }


        ?>

        <div class="tauro-field">
            <label for="quote_description"
                   class="tauro-field-label"> <?php esc_html_e('quote description', 'tauro'); ?> </label>
            <textarea
                    id="quote_description"
                    name="quote_description"
                    class="large-text"
                    rows="6"
                    placeholder="<?php esc_attr_e('Enter quote description...', 'tauro'); ?>"><?= $quote_description ?: 'Tauro Technologies has worked on high-data-rate robotic joints and PCIe compute architecture with many of the most recognizable names in advanced robotics – validating our position as an embedded compute partner for serious robotics OEMs.'; ?></textarea>
        </div>

        <div class="tauro-field">
            <label for="production_shift_title"
                   class="tauro-field-label"> <?php esc_html_e('production shift title', 'tauro'); ?> </label>
            <input type="text" name="production_shift_title"
                   value="<?= $production_shift_title ?: 'The production shift'; ?>"
                   class="regular-text"
                   placeholder="<?php esc_attr_e('Enter production shift title...', 'tauro'); ?>">

            <label for="production_shift_description"
                   class="tauro-field-label"> <?php esc_html_e('production shift description', 'tauro'); ?> </label>
            <textarea
                    id="production_shift_description"
                    name="production_shift_description"
                    class="large-text"
                    rows="6"
                    placeholder="<?php esc_attr_e('Enter production shift description...', 'tauro'); ?>"><?= $production_shift_description ?: 'Production-grade compute built for the demands of modern robotics.'; ?></textarea>

            <label class="tauro-field-label"> <?php esc_html_e('production shift items', 'tauro'); ?> </label>
            <div id="production_shift_items-list">
                <?php foreach ($production_shift_items as $index => $production_shift_item) : ?>

                    <div class="production_shift_item-row">
                        <span class="production_shift_item-icon"> ✓ </span>

                        <input type="text" name="production_shift_items[<?php echo $index; ?>][title]"
                               value="<?php echo esc_attr($production_shift_item['title'] ?? ''); ?>"
                               class="regular-text"
                               placeholder="<?php esc_attr_e('Enter production shift item title...', 'tauro'); ?>">
                        <textarea rows="6" name="production_shift_items[<?php echo $index; ?>][description]"
                                  class="regular-text"
                                  placeholder="<?php esc_attr_e('Enter production shift item description...', 'tauro'); ?>"><?php echo esc_attr($production_shift_item['description'] ?? ''); ?></textarea>

                        <button type="button"
                                class="button button-link-delete remove-production_shift_item "> <?php esc_html_e('Remove', 'tauro'); ?> </button>

                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-production_shift_item" class="button button-primary">
                + <?php esc_html_e('Add production shift item', 'tauro'); ?> </button>


            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    /*
                     * =========================
                     * Key production shift items
                     * =========================
                     */

                    const productionShiftItemList =
                        document.getElementById('production_shift_items-list');

                    const addProductionShiftItemButton =
                        document.getElementById('add-production_shift_item');


                    function getProductionShiftItemIndex() {

                        return productionShiftItemList.querySelectorAll(
                            '.production_shift_item-row'
                        ).length;
                    }

                    if (
                        productionShiftItemList &&
                        addProductionShiftItemButton
                    ) {

                        /*
                         * =========================
                         * Add production shift item
                         * =========================
                         */

                        addProductionShiftItemButton.addEventListener(
                            'click',
                            function () {

                                const index = getProductionShiftItemIndex();

                                const row = document.createElement('div');

                                row.className = 'production_shift_item-row';

                                row.innerHTML = `
                    <span class="production_shift_item-icon">✓</span>

                    <input
                        type="text"
                        name="production_shift_items[${index}][title]"
                        class="regular-text"
                        placeholder="Enter production shift item title..."
                    >

                    <textarea
                        rows="6"
                        name="production_shift_items[${index}][description]"
                        class="regular-text"
                        placeholder="Enter production shift item description..."
                    ></textarea>

                    <button
                        type="button"
                        class="button button-link-delete remove-production_shift_item"
                    >
                        Remove
                    </button>
                `;


                                productionShiftItemList.appendChild(row);

                                row.querySelector('input').focus();
                            }
                        );

                        /*
                                * Remove production shift item
                                */

                        productionShiftItemList.addEventListener(
                            'click',
                            function () {

                                const removeProductionShiftItemButton =
                                    event.target.closest(
                                        '.remove-production_shift_item'
                                    );

                                if (!removeProductionShiftItemButton) {
                                    return;
                                }

                                const row =
                                    removeProductionShiftItemButton.closest(
                                        '.production_shift_item-row'
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
            <label for="what_we_build_title"
                   class="tauro-field-label"> <?php esc_html_e('what we build title', 'tauro'); ?> </label>
            <input type="text" name="what_we_build_title"
                   value="<?= $what_we_build_title ?: 'What we build for robotics platforms'; ?>"
                   class="regular-text"
                   placeholder="<?php esc_attr_e('Enter what we build title...', 'tauro'); ?>">

            <label for="what_we_build_description"
                   class="tauro-field-label"> <?php esc_html_e('what we build description', 'tauro'); ?> </label>
            <textarea
                    id="what_we_build_description"
                    name="what_we_build_description"
                    class="large-text"
                    rows="6"
                    placeholder="<?php esc_attr_e('Enter what we build description...', 'tauro'); ?>"><?= $what_we_build_description ?: 'Embedded hardware for AI, sensor processing, and real-time compute.'; ?></textarea>

            <label class="tauro-field-label"> <?php esc_html_e('what we build items', 'tauro'); ?> </label>
            <div id="what_we_build_items-list">
                <?php foreach ($what_we_build_items as $index => $what_we_build_item) : ?>

                    <div class="what_we_build_item-row">
                        <span class="what_we_build_item-icon"> ✓ </span>

                        <input type="text" name="what_we_build_items[<?php echo $index; ?>][title]"
                               value="<?php echo esc_attr($what_we_build_item['title'] ?? ''); ?>"
                               class="regular-text"
                               placeholder="<?php esc_attr_e('Enter what we build item title...', 'tauro'); ?>">
                        <textarea rows="6" name="what_we_build_items[<?php echo $index; ?>][description]"
                                  class="regular-text"
                                  placeholder="<?php esc_attr_e('Enter what we build item description...', 'tauro'); ?>"><?php echo esc_attr($what_we_build_item['description'] ?? ''); ?></textarea>

                        <button type="button"
                                class="button button-link-delete remove-what_we_build_item "> <?php esc_html_e('Remove', 'tauro'); ?> </button>

                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-what_we_build_item" class="button button-primary">
                + <?php esc_html_e('Add what we build item', 'tauro'); ?> </button>


            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    /*
                     * =========================
                     * what we build items
                     * =========================
                     */

                    const whatWeBuildItemList =
                        document.getElementById('what_we_build_items-list');

                    const addWhatWeBuildItemButton =
                        document.getElementById('add-what_we_build_item');


                    function getWhatWeBuildItemIndex() {

                        return whatWeBuildItemList.querySelectorAll(
                            '.what_we_build_item-row'
                        ).length;
                    }

                    if (
                        whatWeBuildItemList &&
                        addWhatWeBuildItemButton
                    ) {

                        /*
                         * =========================
                         * Add what we build item
                         * =========================
                         */

                        addWhatWeBuildItemButton.addEventListener(
                            'click',
                            function () {

                                const index = getWhatWeBuildItemIndex();

                                const row = document.createElement('div');

                                row.className = 'what_we_build_item-row';

                                row.innerHTML = `
                    <span class="what_we_build_item-icon">✓</span>

                    <input
                        type="text"
                        name="what_we_build_items[${index}][title]"
                        class="regular-text"
                        placeholder="Enter what we build item title..."
                    >

                    <textarea
                        rows="6"
                        name="what_we_build_items[${index}][description]"
                        class="regular-text"
                        placeholder="Enter what we build item description..."
                    ></textarea>

                    <button
                        type="button"
                        class="button button-link-delete remove-what_we_build_item"
                    >
                        Remove
                    </button>
                `;


                                whatWeBuildItemList.appendChild(row);

                                row.querySelector('input').focus();
                            }
                        );

                        /*
                                * Remove what we build item
                                */

                        whatWeBuildItemList.addEventListener(
                            'click',
                            function () {

                                const removeWhatWeBuildItemButton =
                                    event.target.closest(
                                        '.remove-what_we_build_item'
                                    );

                                if (!removeWhatWeBuildItemButton) {
                                    return;
                                }

                                const row =
                                    removeWhatWeBuildItemButton.closest(
                                        '.what_we_build_item-row'
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
            <label for="compute_makes_it_work_title"
                   class="tauro-field-label"> <?php esc_html_e('compute makes it work title', 'tauro'); ?> </label>
            <input type="text" name="compute_makes_it_work_title"
                   value="<?= $compute_makes_it_work_title ?: "How Tauro's compute makes it work"; ?>"
                   class="regular-text"
                   placeholder="<?php esc_attr_e('Enter compute makes it work title...', 'tauro'); ?>">

            <label for="compute_makes_it_work_description"
                   class="tauro-field-label"> <?php esc_html_e('compute makes it work description', 'tauro'); ?> </label>
            <textarea
                    id="compute_makes_it_work_description"
                    name="compute_makes_it_work_description"
                    class="large-text"
                    rows="6"
                    placeholder="<?php esc_attr_e('Enter compute makes it work description...', 'tauro'); ?>"><?= $compute_makes_it_work_description ?: 'Real-time compute for perception, sensor fusion, and control.'; ?></textarea>

            <label class="tauro-field-label"> <?php esc_html_e('compute makes it work items', 'tauro'); ?> </label>
            <div id="compute_makes_it_work_items-list">
                <?php foreach ($compute_makes_it_work_items as $index => $compute_makes_it_work_item) : ?>

                    <div class="compute_makes_it_work_item-row">
                        <span class="compute_makes_it_work_item-icon"> ✓ </span>
                        <img src="<?php echo esc_attr($compute_makes_it_work_item['featured_image'] ?? ''); ?>"
                             style="width:48px">
                        <input type="text" name="compute_makes_it_work_items[<?php echo $index; ?>][title]"
                               value="<?php echo esc_attr($compute_makes_it_work_item['title'] ?? ''); ?>"
                               class="regular-text"
                               placeholder="<?php esc_attr_e('Enter compute makes it work item title...', 'tauro'); ?>">
                        <textarea rows="6" name="compute_makes_it_work_items[<?php echo $index; ?>][description]"
                                  class="regular-text"
                                  placeholder="<?php esc_attr_e('Enter compute makes it work item description...', 'tauro'); ?>"><?php echo esc_attr($compute_makes_it_work_item['description'] ?? ''); ?></textarea>

                        <button type="button"
                                class="button button-link-delete remove-compute_makes_it_work_item "> <?php esc_html_e('Remove', 'tauro'); ?> </button>

                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-compute_makes_it_work_item" class="button button-primary">
                + <?php esc_html_e('Add compute makes it work item', 'tauro'); ?> </button>


            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    /*
                     * =========================
                     * compute makes it work items
                     * =========================
                     */

                    const computeMakesItWorkItemList =
                        document.getElementById('compute_makes_it_work_items-list');

                    const addComputeMakesItWorkItemButton =
                        document.getElementById('add-compute_makes_it_work_item');


                    function getComputeMakesItWorkItemIndex() {

                        return computeMakesItWorkItemList.querySelectorAll(
                            '.compute_makes_it_work_item-row'
                        ).length;
                    }

                    if (
                        computeMakesItWorkItemList &&
                        addComputeMakesItWorkItemButton
                    ) {

                        /*
                         * =========================
                         * Add  compute makes it work item
                         * =========================
                         */

                        addComputeMakesItWorkItemButton.addEventListener(
                            'click',
                            function () {

                                const index = getComputeMakesItWorkItemIndex();

                                const row = document.createElement('div');

                                row.className = 'compute_makes_it_work_item-row';

                                row.innerHTML = `
                    <span class="compute_makes_it_work_item-icon">✓</span>

                    <input
                        type="text"
                        name="compute_makes_it_work_items[${index}][title]"
                        class="regular-text"
                        placeholder="Enter compute makes it work item title..."
                    >

                    <textarea
                        rows="6"
                        name="compute_makes_it_work_items[${index}][description]"
                        class="regular-text"
                        placeholder="Enter compute makes it work item description..."
                    ></textarea>

                    <input
                        name="compute_makes_it_work_items[${index}][featured_image]"
                        size="25"
                        type="file"
                    >

                    <button
                        type="button"
                        class="button button-link-delete remove-compute_makes_it_work_item"
                    >
                        Remove
                    </button>
                `;


                                computeMakesItWorkItemList.appendChild(row);

                                row.querySelector('input').focus();
                            }
                        );

                        /*
                                * Remove compute makes it work item
                                */

                        computeMakesItWorkItemList.addEventListener(
                            'click',
                            function () {

                                const removeComputeMakesItWorkItemButton =
                                    event.target.closest(
                                        '.remove-compute_makes_it_work_item'
                                    );

                                if (!removeComputeMakesItWorkItemButton) {
                                    return;
                                }

                                const row =
                                    removeComputeMakesItWorkItemButton.closest(
                                        '.compute_makes_it_work_item-row'
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


            .what_we_build_item-row,
            .production_shift_item-row,
            .compute_makes_it_work_item-row,
            .frequently_asked_question-row
            {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 8px;
            }

            .what_we_build_item-icon
            {
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #2271b1;
                font-weight: 600;
            }

            .what_we_build_item-input {
                flex: 1;
                max-width: 500px;
            }

            .remove-what_we_build_item
            {
                white-space: nowrap;
            }

            #add-what_we_build_item
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


        </style>

        <?php
    }

    public function save_markets_extra_options_meta_data($post_id)
    {
        // Nonce
        if (
                !isset($_POST['save_markets_extra_options_meta_data_nonce']) ||
                !wp_verify_nonce(
                        $_POST['save_markets_extra_options_meta_data_nonce'],
                        'save_markets_extra_options_meta_data'
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

        // Save quote description
        if (isset($_POST['quote_description'])) {
            update_post_meta(
                    $post_id,
                    'quote_description',
                    $_POST['quote_description']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'quote_description'
            );
        }


        // Save production shift description
        if (isset($_POST['production_shift_description'])) {
            update_post_meta(
                    $post_id,
                    'production_shift_description',
                    $_POST['production_shift_description']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'production_shift_description'
            );
        }
        // Save production shift title
        if (isset($_POST['production_shift_title'])) {
            update_post_meta(
                    $post_id,
                    'production_shift_title',
                    $_POST['production_shift_title']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'production_shift_title'
            );
        }



        /*
        * Save production shift items
        */
        if (
                isset($_POST['production_shift_items']) &&
                is_array($_POST['production_shift_items'])
        ) {

            $production_shift_items = [];

            foreach ($_POST['production_shift_items'] as $index => $production_shift_item) {

                if (!is_array($production_shift_item)) {
                    continue;
                }

                $title = isset($production_shift_item['title'])
                        ? sanitize_text_field($production_shift_item['title'])
                        : '';

                $description = isset($production_shift_item['description'])
                        ? sanitize_textarea_field($production_shift_item['description'])
                        : '';


                // Skip empty rows
                if ($title === '' && $description === '') {
                    continue;
                }

                $production_shift_items[] = [ 'title' => $title, 'description' => $description ];
            }
            // Re-index array
            $production_shift_items = array_values($production_shift_items);

            update_post_meta(
                    $post_id,
                    'production_shift_items',
                    $production_shift_items
            );

        } else {

            delete_post_meta(
                    $post_id,
                    'production_shift_items'
            );
        }


        // Save what we build description
        if (isset($_POST['what_we_build_description'])) {
            update_post_meta(
                    $post_id,
                    'what_we_build_description',
                    $_POST['what_we_build_description']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'what_we_build_description'
            );
        }
        // Save what we build title
        if (isset($_POST['what_we_build_title'])) {
            update_post_meta(
                    $post_id,
                    'what_we_build_title',
                    $_POST['what_we_build_title']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'what_we_build_title'
            );
        }



        /*
        * Save what we build items
        */
        if (
                isset($_POST['what_we_build_items']) &&
                is_array($_POST['what_we_build_items'])
        ) {

            $what_we_build_items = [];

            foreach ($_POST['what_we_build_items'] as $index => $what_we_build_item) {

                if (!is_array($what_we_build_item)) {
                    continue;
                }

                $title = isset($what_we_build_item['title'])
                        ? sanitize_text_field($what_we_build_item['title'])
                        : '';

                $description = isset($what_we_build_item['description'])
                        ? sanitize_textarea_field($what_we_build_item['description'])
                        : '';


                // Skip empty rows
                if ($title === '' && $description === '') {
                    continue;
                }

                $what_we_build_items[] = [ 'title' => $title, 'description' => $description ];
            }
            // Re-index array
            $what_we_build_items = array_values($what_we_build_items);

            update_post_meta(
                    $post_id,
                    'what_we_build_items',
                    $what_we_build_items
            );

        } else {

            delete_post_meta(
                    $post_id,
                    'what_we_build_items'
            );
        }



        // Save compute makes it work description
        if (isset($_POST['compute_makes_it_work_description'])) {
            update_post_meta(
                    $post_id,
                    'compute_makes_it_work_description',
                    $_POST['compute_makes_it_work_description']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'compute_makes_it_work_description'
            );
        }
        // Save compute makes it work title
        if (isset($_POST['compute_makes_it_work_title'])) {
            update_post_meta(
                    $post_id,
                    'compute_makes_it_work_title',
                    $_POST['compute_makes_it_work_title']
            );
        } else {
            delete_post_meta(
                    $post_id,
                    'compute_makes_it_work_title'
            );
        }



        /*
        * Save compute makes it work  items
        */
        if (
                isset($_POST['compute_makes_it_work_items']) &&
                is_array($_POST['compute_makes_it_work_items'])
        ) {

            $compute_makes_it_work_items = [];

            foreach ($_POST['compute_makes_it_work_items'] as $index => $compute_makes_it_work_item) {

                if (!is_array($compute_makes_it_work_item)) {
                    continue;
                }

                $title = isset($compute_makes_it_work_item['title'])
                        ? sanitize_text_field($compute_makes_it_work_item['title'])
                        : '';

                $description = isset($compute_makes_it_work_item['description'])
                        ? sanitize_textarea_field($compute_makes_it_work_item['description'])
                        : '';

                $featured_image = isset($compute_makes_it_work_item['featured_image'])
                        ? esc_url_raw($compute_makes_it_work_item['featured_image'])
                        : '';

                if (
                        isset($_FILES['compute_makes_it_work_items']['name'][$index]['featured_image']) &&
                        $_FILES['compute_makes_it_work_items']['error'][$index]['featured_image'] === UPLOAD_ERR_OK
                ) {

                    $file = [
                            'name' => $_FILES['compute_makes_it_work_items']['name'][$index]['featured_image'],
                            'type' => $_FILES['compute_makes_it_work_items']['type'][$index]['featured_image'],
                            'tmp_name' => $_FILES['compute_makes_it_work_items']['tmp_name'][$index]['featured_image'],
                            'error' => $_FILES['compute_makes_it_work_items']['error'][$index]['featured_image'],
                            'size' => $_FILES['compute_makes_it_work_items']['size'][$index]['featured_image'],
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


                // Skip empty rows
                if ($title === '' && $description === '') {
                    continue;
                }

                $compute_makes_it_work_items[] = [ 'title' => $title, 'description' => $description,'featured_image' => $featured_image ];
            }
            // Re-index array
            $compute_makes_it_work_items = array_values($compute_makes_it_work_items);

            update_post_meta(
                    $post_id,
                    'compute_makes_it_work_items',
                    $compute_makes_it_work_items
            );

        } else {

            delete_post_meta(
                    $post_id,
                    'compute_makes_it_work_items'
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

$markets_extra_options = new Markets_Extra_Options();
