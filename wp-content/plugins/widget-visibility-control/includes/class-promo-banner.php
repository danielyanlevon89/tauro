<?php
/**
 * AyudaWP Promo Banner — cross-promotion strip for AyudaWP services.
 *
 * Single-file, plugin-scoped copy of the canonical AyudaWP promo banner pattern
 * (see ayudawp-promo-banner-catalog-servicios.md at the family root, the single
 * source of truth). This plugin is not part of the Native SEO Suite, so it does
 * NOT use the shared NSEO core — the banner lives entirely inside the plugin
 * with a unique class name to avoid collisions with sibling plugins that ship
 * their own banner.
 *
 * Since 1.4.0 the banner shows services only: three cards picked at random from
 * the services catalog, each one linking out to an AyudaWP landing page.
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin-scoped promo banner.
 *
 * @since 1.3.0
 */
class AyudaWP_WVC_Promo_Banner {

	/**
	 * CSS class prefix used on the rendered markup.
	 *
	 * @var string
	 */
	private $css_prefix;

	/**
	 * Constructor.
	 *
	 * @param string $css_prefix CSS class prefix used on rendered elements.
	 */
	public function __construct( $css_prefix ) {
		$this->css_prefix = $css_prefix;
	}

	/**
	 * AyudaWP services catalog.
	 *
	 * Mirror of ayudawp-promo-banner-catalog-servicios.md. Keep both in sync.
	 *
	 * @return array
	 */
	private function get_services_catalog() {
		return array(
			'maintenance' => array(
				'icon'        => 'dashicons-admin-tools',
				'title'       => __( 'Need help with your website?', 'widget-visibility-control' ),
				'description' => __( 'Professional WordPress maintenance: security monitoring, regular backups, performance optimization, and priority support.', 'widget-visibility-control' ),
				'button'      => __( 'Learn more', 'widget-visibility-control' ),
				/* translators: AyudaWP maintenance service URL. Change this URL in translations to use a localized landing page. */
				'url'         => __( 'https://mantenimiento.ayudawp.com/en/', 'widget-visibility-control' ),
			),
			'consultancy' => array(
				'icon'        => 'dashicons-businessman',
				'title'       => __( 'WordPress consultancy', 'widget-visibility-control' ),
				'description' => __( 'One-on-one online sessions to solve your WordPress doubts, get expert advice, and make better decisions for your project.', 'widget-visibility-control' ),
				'button'      => __( 'Book a session', 'widget-visibility-control' ),
				'url'         => 'https://servicios.ayudawp.com/producto/consultoria-online-wordpress/',
			),
			'hacked'      => array(
				'icon'        => 'dashicons-sos',
				'title'       => __( 'Hacked website?', 'widget-visibility-control' ),
				'description' => __( 'Fast recovery service for compromised WordPress sites. We clean malware, fix vulnerabilities, and restore your site security.', 'widget-visibility-control' ),
				'button'      => __( 'Get help now', 'widget-visibility-control' ),
				'url'         => 'https://servicios.ayudawp.com/producto/wordpress-hackeado/',
			),
			'development' => array(
				'icon'        => 'dashicons-editor-code',
				'title'       => __( 'Custom development', 'widget-visibility-control' ),
				'description' => __( 'Need a custom plugin, theme modifications, or specific functionality? We build tailored WordPress solutions for your needs.', 'widget-visibility-control' ),
				'button'      => __( 'Request a quote', 'widget-visibility-control' ),
				'url'         => 'https://servicios.ayudawp.com/producto/desarrollo-wordpress/',
			),
			'hosting'     => array(
				'icon'        => 'dashicons-cloud-saved',
				'title'       => __( 'Hosting built for WordPress', 'widget-visibility-control' ),
				'description' => __( 'Google Cloud servers, automatic geo-located daily backups, and 24/7 expert support. Speed, security, and migration tools included.', 'widget-visibility-control' ),
				'button'      => __( 'Learn more', 'widget-visibility-control' ),
				/* translators: SiteGround affiliate URL. Change this URL in translations to use a localized landing page. */
				'url'         => __( 'https://stgrnd.co/telladowpbox', 'widget-visibility-control' ),
			),
			'plugins'     => array(
				'icon'        => 'dashicons-admin-plugins',
				'title'       => __( 'Premium WordPress plugins', 'widget-visibility-control' ),
				'description' => __( 'Focused plugins for WordPress and WooCommerce, each solving one problem well. No bloated suites, no upsell nags, no telemetry.', 'widget-visibility-control' ),
				'button'      => __( 'Browse plugins', 'widget-visibility-control' ),
				/* translators: AyudaWP plugin store URL. Change this URL in translations to use a localized landing page. */
				'url'         => __( 'https://plugins.ayudawp.com/en/', 'widget-visibility-control' ),
			),
		);
	}

	/**
	 * Pick random services from the catalog.
	 *
	 * @since 1.4.0
	 *
	 * @param int $count Number of services to return.
	 * @return array
	 */
	private function get_random_services( $count = 3 ) {
		$services = $this->get_services_catalog();

		if ( empty( $services ) ) {
			return array();
		}

		$random_keys = array_rand( $services, min( $count, count( $services ) ) );

		if ( ! is_array( $random_keys ) ) {
			$random_keys = array( $random_keys );
		}

		$result = array();
		foreach ( $random_keys as $key ) {
			$result[ $key ] = $services[ $key ];
		}

		return $result;
	}

	/**
	 * Render the promo banner as a sidebar widget (3 stacked boxes).
	 *
	 * Used by the settings page, where the banner sits in a column of its own
	 * so it stays visible regardless of how long the main content is.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function render_vertical() {
		$services = $this->get_random_services( 3 );
		$prefix   = $this->css_prefix;
		?>
		<div class="<?php echo esc_attr( $prefix ); ?>-promo-widget">
			<?php foreach ( $services as $service ) : ?>
				<div class="<?php echo esc_attr( $prefix ); ?>-promo-box">
					<span class="dashicons <?php echo esc_attr( $service['icon'] ); ?>"></span>
					<h3><?php echo esc_html( $service['title'] ); ?></h3>
					<p><?php echo esc_html( $service['description'] ); ?></p>
					<a href="<?php echo esc_url( $service['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
						<?php echo esc_html( $service['button'] ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render the promo banner (horizontal, 3 columns).
	 *
	 * @return void
	 */
	public function render() {
		$services = $this->get_random_services( 3 );
		$prefix   = $this->css_prefix;
		?>
		<div class="<?php echo esc_attr( $prefix ); ?>-promo-notice">
			<div class="<?php echo esc_attr( $prefix ); ?>-promo-columns">

				<?php foreach ( $services as $service ) : ?>
					<div class="<?php echo esc_attr( $prefix ); ?>-promo-column">
						<span class="dashicons <?php echo esc_attr( $service['icon'] ); ?>"></span>
						<h5><?php echo esc_html( $service['title'] ); ?></h5>
						<p><?php echo esc_html( $service['description'] ); ?></p>
						<a href="<?php echo esc_url( $service['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
							<?php echo esc_html( $service['button'] ); ?>
						</a>
					</div>
				<?php endforeach; ?>

			</div>
		</div>
		<?php
	}
}
