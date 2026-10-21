<?php
/*
Template Name: Blog Item
Template Post Type: post
*/
?>
<?php get_header(); ?>
    <main class="site-main page-blog-item">
        <?php get_template_part('breadcrumbs'); ?>
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php $id = get_the_ID();?>



                <div class="container pb-5 ">
                    <div class="row g-5">
                        <!-- Article -->
                        <div class="col-lg-8">
                            <article class="article-meta">
                                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                    <div class="article-meta-badge">
                                          <span class="meta-badge">
                                            <?= get_the_date('F j, Y'); ?>
                                          </span>
                                            <?= reading_time(); ?> min read
                                    </div>
                                </div>
                                <h1 class="article-title"><?php the_title(); ?></h1>
                                <div>
                                    <?php
                                    $post = get_post();
                                    $tags = get_the_tags($post->ID);
                                    if ($tags) :
                                        $tags = array_slice($tags, 0, 3);
                                        foreach ($tags as $tag) : ?>
                                            <a href="<?= esc_url(get_tag_link($tag->term_id)); ?>" class="article-tag"><?= esc_html($tag->name); ?></a>
                                        <?php endforeach;
                                    endif;

                                    $author_id   = get_post_field( 'post_author', get_the_ID() );
                                    $author_bio  = get_the_author_meta( 'description', $author_id );
                                    $author_name = get_the_author_meta( 'display_name', $author_id );
                                    ?>
                                </div>
                                <div class="author-line">
                                    <div>
                                        <strong>By <?= $author_name ; ?></strong>
                                        <div class="u-fs-08"><?= $author_bio ; ?></div>
                                    </div>
                                </div>
                            </article>
                            <div class="article-content">
                                <?php the_content(); ?>
                            </div>

                        </div>

                        <!-- Sidebar -->
                        <aside class="col-lg-4">
                            <?php
                            if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('right-sidebar')) : endif;
                            ?>
                        </aside>

                    </div>
                </div>



            <?php endwhile; ?>
        <?php endif; ?>
        <?php
        if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('blog-post-sidebar')) : endif;
        ?>

    </main>
<?php get_footer(); ?>



