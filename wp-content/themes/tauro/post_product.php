<?php
/*
Template Name: Product Item
Template Post Type: post
*/
?>
<?php get_header(); ?>
    <main class="site-main page-products-item">
        <?php get_template_part('breadcrumbs'); ?>
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php $id = get_the_ID();?>


                <!-- Hero -->
                <section class="hero-section">
                    <div class="container">
                        <div class="row align-items-center g-5">
                            <div class="col-lg-6">
                                <h1 class="hero-title"><?php the_title(); ?></h1>
                                <div class="hero-desc">
                                    <?php the_content(); ?>
                                </div>
                                <div class="hero-actions">
                                    <a href="#" class="btn-hero-outline">
                                        Data sheet <i class="bi bi-arrow-up-right"></i>
                                    </a>
                                    <a href="#" class="btn btn-primary-custom">
                                        User manual <i class="bi bi-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="col-lg-6 text-center">

                                <?php
                                $custom_gallery = get_post_meta($post->ID, 'custom_gallery', true);

                                $images = [];
                                if (has_post_thumbnail($post)) {
                                    $post_thumbnail_id = get_post_thumbnail_id($post);
                                    $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);
                                    $images[] = $post_thumbnail_url;
                                }
                                if (!empty($custom_gallery)) {
                                    foreach ($custom_gallery as $image) {
                                        if (!empty($image['url'])) {
                                            $images[] = $image['url'];
                                        }
                                    }
                                }
                                $images = array_values(array_unique($images));
                                ?>

                                <?php if (!empty($images)) : ?>

                                    <img
                                            id="product-main-image"
                                            class="hero-img"
                                            title="<?php echo esc_attr(get_the_title($post->ID)); ?>"
                                            alt="<?php echo esc_attr(get_the_title($post->ID)); ?>"
                                            src="<?php echo esc_url($images[0]); ?>"
                                    >

                                    <?php if (count($images) > 1) : ?>

                                        <div class="img-dots">

                                            <?php foreach ($images as $index => $image) : ?>

                                                <span
                                                        class="img-dot <?php echo $index === 0 ? 'active' : ''; ?>"
                                                        data-image="<?php echo esc_url($image); ?>"
                                                        data-index="<?php echo $index; ?>"
                                                ></span>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {

                                    const mainImage = document.getElementById('product-main-image');
                                    const dots = document.querySelectorAll('.img-dot');

                                    if (!mainImage || !dots.length) {
                                        return;
                                    }

                                    let currentIndex = 0;
                                    let sliderInterval;
                                    let isAnimating = false;

                                    function showSlide(index) {

                                        if (isAnimating) {
                                            return;
                                        }

                                        if (index >= dots.length) {
                                            index = 0;
                                        }

                                        if (index < 0) {
                                            index = dots.length - 1;
                                        }

                                        const dot = dots[index];
                                        const image = dot.dataset.image;

                                        if (!image || image === mainImage.src) {
                                            currentIndex = index;
                                            return;
                                        }

                                        isAnimating = true;

                                        // Fade out
                                        mainImage.classList.add('fade-out');

                                        setTimeout(function () {

                                            // Change image
                                            mainImage.src = image;

                                            // Update active dot
                                            dots.forEach(function (item) {
                                                item.classList.remove('active');
                                            });

                                            dot.classList.add('active');

                                            currentIndex = index;

                                            // Fade in
                                            mainImage.classList.remove('fade-out');

                                            setTimeout(function () {
                                                isAnimating = false;
                                            }, 500);

                                        }, 500);
                                    }

                                    function startSlider() {

                                        sliderInterval = setInterval(function () {
                                            showSlide(currentIndex + 1);
                                        }, 3000);

                                    }

                                    function resetSlider() {

                                        clearInterval(sliderInterval);
                                        startSlider();

                                    }

                                    // Dot click
                                    dots.forEach(function (dot, index) {

                                        dot.addEventListener('click', function () {

                                            showSlide(index);
                                            resetSlider();

                                        });

                                    });

                                    // Start automatic slider
                                    startSlider();

                                });
                            </script>


                        </div>
                    </div>
                </section>

                <!-- Description & Key Features -->
                <section class="pb-5">
                    <div class="container">
                        <div class="row g-5">
                            <div class="col-lg-6">
                                <h2 class="section-title">Description</h2>

                                    <?php
                                        $description = get_post_meta($post->ID, 'description', true);
                                        if ($description) {
                                            $description = wp_strip_all_tags($description);
                                            $words = preg_split('/\s+/', trim($description));
                                            $visible_words = array_slice($words, 0, 80);
                                            $hidden_words = array_slice($words, 80);
                                            echo '<div class="product-description desc-text">';
                                            echo '<span class="description-visible">';
                                            echo esc_html(implode(' ', $visible_words));
                                            echo '</span>';
                                            if (!empty($hidden_words)) {
                                                echo '<span class="description-hidden">';
                                                echo ' ' . esc_html(implode(' ', $hidden_words));
                                                echo '</span>';
                                                echo ' <span class="description-toggle view-more">View more</span>';
                                            }
                                            echo '</div>';
                                        } ?>


                                <script>
                                    document.addEventListener('DOMContentLoaded', function () {
                                        document.querySelectorAll('.description-toggle').forEach(function (button) {

                                            button.addEventListener('click', function () {
                                                const container = this.closest('.product-description');
                                                const hiddenText = container.querySelector('.description-hidden');

                                                const isHidden = window.getComputedStyle(hiddenText).display === 'none';

                                                if (isHidden) {
                                                    hiddenText.style.display = 'inline';
                                                    this.textContent = 'View less';
                                                } else {
                                                    hiddenText.style.display = 'none';
                                                    this.textContent = 'View more';
                                                }
                                            });

                                        });
                                    });
                                </script>

                            </div>
                            <div class="col-lg-6">
                                <h2 class="section-title">Key Features</h2>
                                <?php
                                $key_features = get_post_meta(get_the_ID(), 'key_features', true);
                                if (!empty($key_features) && is_array($key_features)) : ?>
                                    <ul class="feature-list">
                                        <?php foreach ($key_features as $feature) : ?>
                                            <li>
                                                <?php echo esc_html($feature); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Key Specifications -->
                <section class="pb-5">
                    <div class="container">
                        <h2 class="section-title">Key Specifications</h2>
                        <div class="table-responsive">
                            <table class="specs-table"><tbody>

                            <?php
                            $key_specifications = get_post_meta(get_the_ID(), 'key_specifications', true);
                            if (!empty($key_specifications) && is_array($key_specifications)) :
                                foreach ($key_specifications as $specification) : ?>
                                    <tr>
                                        <th><?php echo esc_html($specification['key']); ?></th>
                                        <td>
                                            <?php foreach ($specification['values'] as $value) : ?>
                                                    <?php echo esc_html($value); ?><br>
                                            <?php endforeach; ?>

                                        </td>
                                    </tr>
                                <?php
                                endforeach;
                            endif;
                            ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>


                <input type="hidden"
                       id="popup_arrow_link"
                       value="<?php echo esc_attr(get_post_meta($post->ID, 'popup_arrow_link', true)); ?>">

                <input type="hidden"
                       id="popup_silicon_highway_link"
                       value="<?php echo esc_attr(get_post_meta($post->ID, 'popup_silicon_highway_link', true)); ?>">

            <?php endwhile; ?>
        <?php endif; ?>
        <?php
        if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('product-post-sidebar')) : endif;
        ?>

    </main>
<?php get_footer(); ?>



