<section class=" cta-section bg-gray"><div class="container">
        <div class="cta-box d-flex flex-wrap align-items-center justify-content-between gap-4">
            <div>
                <h2 class="cta-title">Partner with us on your next platform</h2>
                <p class="cta-subtitle">From architecture to deployment, we deliver mission-critical platforms</p>
            </div>
            <a href="#" class="btn-cta">
                Get in touch <i class="bi bi-arrow-up-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Footer -->

<footer class="footer-section  cta-section bg-gray">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="logo-section footer-logo">
                        <?= the_custom_logo(); ?>
                    <div>
                        <?= strtoupper(get_bloginfo('name')); ?>
                        <div><?= strtoupper(get_bloginfo('description')); ?></div>
                    </div>
                </div>
                <div class="footer-contact">
                    <a href="tel:<?= get_theme_mod('phone_number'); ?>"> <?= get_theme_mod('phone_number'); ?> </a> &nbsp;|&nbsp;  <a href="mailto:<?= get_theme_mod('email_address'); ?>"> <?= get_theme_mod('email_address'); ?></a>
                </div>
                <div class="footer-socials">
                    <a href="<?= get_theme_mod('linkedin_url'); ?>" target="_blank" aria-label="LinkedIn">
                        <svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"></path></svg>
                    </a>
                    <a href="<?= get_theme_mod('youtube_url'); ?>" target="_blank" aria-label="YouTube">
                        <svg viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"></path></svg>
                    </a>
                </div>
            </div>
            <div class="col-lg-2"></div>

            <?php
            wp_nav_menu([
                    'theme_location' => 'footer-menu',
                    'menu_id' => 'footer-menu',
                    'container' => false,
                    'items_wrap'     => '%3$s',
                    'walker' => new Footer_Menu_Walker()
            ]);
            ?>

        </div>
        <div class="footer-bottom">
            <div class="footer-copy"><?= get_theme_mod('footer_rights_text'); ?></div>
            <div class="footer-legal">
                <?php
                wp_nav_menu([
                        'theme_location' => 'footer-bottom-menu',
                        'menu_id' => 'footer-bottom-menu',
                        'container' => false,
                        'items_wrap'     => '%3$s',
                        'walker' => new Footer_Menu_Bottom_Walker()
                ]);
                ?>
            </div>

        </div>
    </div>
</footer>

</body>
<?php wp_footer(); ?>
</html>

