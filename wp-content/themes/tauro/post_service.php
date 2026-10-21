<?php
/*
Template Name: Service Item
Template Post Type: post
*/
?>
<?php get_header(); ?>
    <main class="site-main page-services-item">
        <?php get_template_part('breadcrumbs'); ?>
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php $id = get_the_ID();?>


                <!-- Hero -->
                <section class="hero-section">
                    <div class="container">
                        <div class="row align-items-center g-5">
                            <div class="col-lg-8">

                                <div class="hero-desc">

                                    <?php the_content(); ?>
                                </div>
                                <a href="#" class="btn btn-primary-custom">Start project</a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Key engineering capabilities -->
                <section class="py-5">
                    <div class="container">
                                <h2 class="section-title">Key engineering capabilities</h2>
                                <p class="section-subtitle"><?= get_post_meta($post->ID, 'key_engineering_capabilities_description', true); ?></p>

                        <div class="row g-4">
                            <?php
                            $key_engineering_capabilities = get_post_meta(get_the_ID(), 'key_engineering_capabilities', true);
                            if (!empty($key_engineering_capabilities) && is_array($key_engineering_capabilities)) :
                                foreach ($key_engineering_capabilities as $key_engineering_capability) : ?>

                                    <div class="col-md-6">
                                        <div class="capability-card">
                                            <div class="capability-icon"><img src="<?=esc_attr($key_engineering_capability['featured_image']??''); ?>" style="width:48px"></div>
                                            <div class="capability-body">
                                                <div class="capability-title"><?= esc_html($key_engineering_capability['title']); ?></div>
                                                <p class="capability-desc"><?= esc_html($key_engineering_capability['description']); ?></p>
                                                <ul class="capability-list">
                                                    <?php if (!empty($key_engineering_capability['description_list']) && is_array($key_engineering_capability['description_list'])) : ?>
                                                        <?php foreach ($key_engineering_capability['description_list'] as $description_list_item) : ?>
                                                            <li>
                                                                <?php echo esc_html($description_list_item); ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                endforeach;
                            endif;
                            ?>


                        </div>

                    </div>
                </section>



                <!-- Key design considerations -->
                <section class="pb-5">
                    <div class="container">
                        <h2 class="section-title">Key design considerations</h2>
                        <p class="section-subtitle"><?= get_post_meta($post->ID, 'key_design_considerations_description', true); ?></p>
                        <div class="row g-4">

                                                        <?php
                            $key_design_considerations = get_post_meta(get_the_ID(), 'key_design_considerations', true);
                            if (!empty($key_design_considerations) && is_array($key_design_considerations)) :
                                foreach ($key_design_considerations as $key_design_consideration) : ?>

                            <div class="col-md-6 col-lg-4">
                                <div class="design-card">
                                    <div class="design-header">
                                        <div class="design-title"><?= esc_html($key_design_consideration['title']); ?></div>
                                        <div class="design-dot"></div>
                                    </div>
                                    <p class="design-desc"><?= esc_html($key_design_consideration['description']); ?></p>
                                </div>
                            </div>

                                <?php
                                endforeach;
                            endif;
                              ?>

                        </div>
                    </div>
                </section>

                <!-- Tools and platforms -->
                <section class="pb-5">
                    <div class="container">
                        <h2 class="section-title">Tools and platforms</h2>
                        <p class="section-subtitle"><?= get_post_meta($post->ID, 'tools_and_platforms_description', true); ?></p>
                        <div class="d-flex flex-wrap">
                                                                              <?php
                            $tools_and_platforms = get_post_meta(get_the_ID(), 'tools_and_platforms', true);
                            if (!empty($tools_and_platforms) && is_array($tools_and_platforms)) :
                                foreach ($tools_and_platforms as $tools_and_platform) : ?>
                                        <span class="tool-tag"><?= esc_html($tools_and_platform['title']); ?></span>
                                <?php
                                endforeach;
                            endif;
                                                                              ?>
                        </div>
                    </div>
                </section>

                <?php
                if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('service-post-sidebar')) : endif;
                ?>

                <!-- FAQ -->
                <section class="faq-section">
                    <div class="container">
                        <h2 class="section-title">Frequently asked questions</h2>
                        <p class="section-subtitle"><?= get_post_meta($post->ID, 'frequently_asked_questions_description', true); ?></p>
                        <div class="accordion" id="faqAccordion">
                            <?php
                            $frequently_asked_questions = get_post_meta(get_the_ID(), 'frequently_asked_questions', true);
                            if (!empty($frequently_asked_questions) && is_array($frequently_asked_questions)) :
                                foreach ($frequently_asked_questions as $k=>$frequently_asked_question) : ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button <?php echo $k!=0 ? 'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?=$k?>">
                                                <?= esc_html($frequently_asked_question['question']); ?>
                                            </button>
                                        </h2>
                                        <div id="faq<?=$k?>" class="accordion-collapse collapse <?php echo $k==0 ? 'show':'' ?>" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                <?= esc_html($frequently_asked_question['answer']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                </section>




            <?php endwhile; ?>
        <?php endif; ?>


    </main>
<?php get_footer(); ?>



