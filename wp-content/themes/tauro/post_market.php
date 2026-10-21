<?php
/*
Template Name: Market Item
Template Post Type: post
*/
?>

<?php get_header(); ?>
    <main class="site-main page-markets-item">
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
                                <div class="hero-actions">
                                    <a href="#" class="btn btn-primary-custom">Contact us</a>
                                    <a href="#" class="btn btn-outline-light-custom">
                                        Explore products <i class="bi bi-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </section>

                <!-- Quote -->
                <div class="container">
                    <div class="quote-block">
                        <?= get_post_meta($post->ID, 'quote_description', true); ?>
                    </div>
                </div>

                <!-- The production shift -->
                <section class="pb-5">
                    <div class="container">
                        <h2 class="section-title"><?= get_post_meta($post->ID, 'production_shift_title', true); ?></h2>
                        <p class="section-subtitle"><?= get_post_meta($post->ID, 'production_shift_description', true); ?></p>
                        <div class="row g-4">

                            <?php
                            $production_shift_items = get_post_meta(get_the_ID(), 'production_shift_items', true);
                            if (!empty($production_shift_items) && is_array($production_shift_items)) :
                                foreach ($production_shift_items as $production_shift_item) : ?>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <span class="stat-label"><?= esc_html($production_shift_item['title']); ?></span>
                                            <p class="stat-desc"><?= esc_html($production_shift_item['description']); ?></p>
                                        </div>
                                    </div>
                                <?php
                                endforeach;
                            endif;
                            ?>

                        </div>
                    </div>
                </section>

                <!-- What we build -->
                <section class="pb-5">
                    <div class="container">
                        <h2 class="section-title"><?= get_post_meta($post->ID, 'what_we_build_title', true); ?></h2>
                        <p class="section-subtitle"><?= get_post_meta($post->ID, 'what_we_build_description', true); ?></p>
                        <div class="row g-4">
                            <?php
                            $what_we_build_items = get_post_meta(get_the_ID(), 'what_we_build_items', true);
                            if (!empty($what_we_build_items) && is_array($what_we_build_items)) :
                                foreach ($what_we_build_items as $m=>$what_we_build_item) : ?>
                                    <div class="col-md-6 col-lg-3">
                                        <div class="feature-card">
                                            <span class="feature-number"><?= str_pad($m+1, 2, '0', STR_PAD_LEFT) ?></span>
                                            <div class="feature-title"><?= esc_html($what_we_build_item['title']); ?></div>
                                            <p class="feature-desc"><?= esc_html($what_we_build_item['description']); ?></p>
                                        </div>
                                    </div>
                                <?php
                                endforeach;
                            endif;
                            ?>

                        </div>
                    </div>
                </section>


                <!-- How compute makes it work -->
                <section class="process-section">
                    <div class="container">
                        <h2 class="section-title"><?= get_post_meta($post->ID, 'compute_makes_it_work_title', true); ?></h2>
                        <p class="section-subtitle"><?= get_post_meta($post->ID, 'compute_makes_it_work_description', true); ?></p>
                        <div class="row g-4 position-relative">
                                                        <?php
                            $compute_makes_it_work_items = get_post_meta(get_the_ID(), 'compute_makes_it_work_items', true);
                            if (!empty($compute_makes_it_work_items) && is_array($compute_makes_it_work_items)) :
                                foreach ($compute_makes_it_work_items as $key => $compute_makes_it_work_item) : ?>

                            <div class="col-md-4 position-relative">
                                <div class="process-card">
                                    <div class="process-icon"><img src="<?=esc_attr($compute_makes_it_work_item['featured_image']??''); ?>" style="width:48px"></div>
                                    <div class="process-body">
                                        <div class="process-title"><?= esc_html($compute_makes_it_work_item['title']); ?></div>
                                        <p class="process-desc"><?= esc_html($compute_makes_it_work_item['description']); ?></p>
                                    </div>
                                </div>
                                <?php if ($key != array_key_last($compute_makes_it_work_items)) { ?>
                                <div class="process-arrow d-none d-md-block">
                                    <svg class="arrow-icon" width="30" height="15" viewBox="0 0 30 24" fill="none">
                                        <rect x="0" y="11" width="10" height="3" rx="1" fill="#5b8cff"></rect>
                                        <path d="M8 4l9 8-9 8" stroke="#5b8cff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
                                        <path d="M18 4l9 8-9 8" stroke="#5b8cff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
                                        <rect x="29" y="11" width="10" height="3" rx="1" fill="#5b8cff"></rect>
                                    </svg>
                                </div>
                                <?php } ?>
                            </div>

                                <?php
                                endforeach;
                            endif;
                            ?>

                        </div>
                    </div>
                </section>

                <?php
                if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('market-post-sidebar')) : endif;
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



