<?php
/**
 * Block Editor Support.
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block editor support for widget visibility.
 *
 * @since 1.0.0
 */
class AyudaWP_Block_Editor {

	/**
	 * Initialize block editor support.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
		
		// Use pre_render_block to completely skip rendering hidden blocks.
		// This prevents the empty wrapper issue.
		add_filter( 'pre_render_block', array( __CLASS__, 'pre_render_block' ), 10, 2 );

		// Register block attributes early.
		self::maybe_add_block_attributes();
	}

	/**
	 * Enqueue block editor scripts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function enqueue_editor_assets() {
		$screen = get_current_screen();
		if ( ! $screen || ( 'widgets' !== $screen->id && 'customize' !== $screen->id ) ) {
			return;
		}

		if ( ! wp_use_widgets_block_editor() ) {
			return;
		}

		wp_enqueue_script(
			'ayudawp-wvc-editor',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/js/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-compose', 'wp-data', 'wp-hooks' ),
			AYUDAWP_WVC_VERSION,
			true
		);

		$conditions_data = AyudaWP_Widget_Conditions::get_conditions_data();
		wp_localize_script( 'ayudawp-wvc-editor', 'ayudawp_wvc_conditions_data', $conditions_data );

		// All translatable labels for the block editor JS.
		$labels = array(
			'select'              => _x( '-- Select --', 'Dropdown default option', 'widget-visibility-control' ),
			'category'            => __( 'Category', 'widget-visibility-control' ),
			'author'              => __( 'Author', 'widget-visibility-control' ),
			'user'                => __( 'User', 'widget-visibility-control' ),
			'role'                => __( 'Role', 'widget-visibility-control' ),
			'capability'          => __( 'Capability', 'widget-visibility-control' ),
			'tag'                 => __( 'Tag', 'widget-visibility-control' ),
			'date'                => __( 'Date', 'widget-visibility-control' ),
			'page'                => __( 'Page', 'widget-visibility-control' ),
			'url'                 => __( 'URL', 'widget-visibility-control' ),
			'url_help'            => __( 'Path or full URL. Use * as a wildcard: shop/* matches /shop/ and everything below it.', 'widget-visibility-control' ),
			'taxonomy'            => __( 'Taxonomy', 'widget-visibility-control' ),
			'always'              => __( 'Always', 'widget-visibility-control' ),
			'select_taxonomy'     => _x( '-- Select taxonomy --', 'Taxonomy picker default', 'widget-visibility-control' ),
			'if'                  => _x( 'If', 'Condition: If [Page] is [Front page]', 'widget-visibility-control' ),
			'is'                  => _x( 'is', 'Condition: If [Page] is [Front page]', 'widget-visibility-control' ),
			'remove'              => _x( 'Remove', 'Remove visibility rule', 'widget-visibility-control' ),
			'include_children'    => __( 'Include children', 'widget-visibility-control' ),
			'visibility'          => __( 'Visibility', 'widget-visibility-control' ),
			'no_rules_help'       => __( 'No visibility rules yet. Add a rule to control when this block is shown.', 'widget-visibility-control' ),
			'add_new_rule'        => __( 'Add new rule', 'widget-visibility-control' ),
			'show_block'          => __( 'Show this block', 'widget-visibility-control' ),
			'hide_block'          => __( 'Hide this block', 'widget-visibility-control' ),
			'match_all'           => __( 'Match all rules', 'widget-visibility-control' ),
			'top_level_help'      => __( 'Select the top level block to apply visibility rules.', 'widget-visibility-control' ),
			'schedule'            => __( 'Time Schedule', 'widget-visibility-control' ),
			'no_schedule'         => __( '-- No schedule --', 'widget-visibility-control' ),
			'show_during'         => __( 'Show only during period', 'widget-visibility-control' ),
			'hide_during'         => __( 'Hide during period', 'widget-visibility-control' ),
			'from'                => __( 'From:', 'widget-visibility-control' ),
			'to'                  => __( 'To:', 'widget-visibility-control' ),
			'no_end_date'         => __( 'No end date', 'widget-visibility-control' ),
		);

		// Month names from WordPress locale.
		global $wp_locale;
		$months = array();
		for ( $m = 1; $m <= 12; $m++ ) {
			$months[] = $wp_locale->get_month_abbrev( $wp_locale->get_month( $m ) );
		}
		$labels['months'] = $months;

		wp_localize_script( 'ayudawp-wvc-editor', 'ayudawp_wvc_labels', $labels );

		wp_enqueue_style(
			'ayudawp-wvc-editor',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/css/editor.css',
			array(),
			AYUDAWP_WVC_VERSION
		);
	}

	/**
	 * Maybe add block attributes filter for SSR blocks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function maybe_add_block_attributes() {
		global $pagenow;

		$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$should_add = is_customize_preview() ||
			'widgets.php' === $pagenow ||
			strpos( $current_url, '/wp-json/wp/v2/block-renderer' ) !== false ||
			1 === preg_match( '~^/wp/v2/sites/\d+/block-renderer~', $current_url );

		if ( $should_add ) {
			add_filter( 'register_block_type_args', array( __CLASS__, 'add_block_attributes' ), 10, 2 );
		}
	}

	/**
	 * Add conditions attribute to SSR blocks.
	 *
	 * @since 1.0.0
	 * @param array  $settings Block settings.
	 * @param string $name     Block name.
	 * @return array Modified settings.
	 */
	public static function add_block_attributes( $settings, $name ) {
		$blocks = array(
			'core/calendar',
			'core/latest-comments',
			'core/rss',
			'core/archives',
			'core/tag-cloud',
			'core/page-list',
			'core/latest-posts',
			'woocommerce/product-categories',
		);

		/**
		 * Filter the list of blocks that need server-side visibility support.
		 *
		 * @since 1.0.0
		 * @param array $blocks Array of block names.
		 */
		$blocks = apply_filters( 'ayudawp_wvc_ssr_blocks', $blocks );

		if ( in_array( $name, $blocks, true ) && ! empty( $settings['attributes'] ) ) {
			$settings['attributes']['conditions'] = array(
				'type' => 'object',
			);
		}

		return $settings;
	}

	/**
	 * Filter block rendering before it happens.
	 *
	 * Using pre_render_block instead of render_block to completely
	 * prevent hidden blocks from rendering (avoiding empty wrappers).
	 *
	 * @since 1.1.1
	 * @param string|null $pre_render   The pre-rendered content (null to continue normal rendering).
	 * @param array       $parsed_block The block being rendered.
	 * @return string|null Null to continue, empty string to hide completely.
	 */
	public static function pre_render_block( $pre_render, $parsed_block ) {
		// Don't filter in admin.
		if ( is_admin() ) {
			return $pre_render;
		}

		// Check if block has conditions.
		if ( empty( $parsed_block['attrs']['conditions'] ) ) {
			return $pre_render;
		}

		$conditions   = $parsed_block['attrs']['conditions'];
		$has_rules    = ! empty( $conditions['rules'] );
		$has_schedule = ! empty( $conditions['schedule']['action'] );

		// No conditions to evaluate.
		if ( ! $has_rules && ! $has_schedule ) {
			return $pre_render;
		}

		// Evaluate conditions.
		if ( ! AyudaWP_Widget_Conditions::filter_widget_check_conditions( $conditions ) ) {
			// Return empty string to completely prevent rendering.
			// This is better than returning '' in render_block because
			// pre_render_block happens BEFORE WordPress adds any wrapper.
			return '';
		}

		// Return null to continue normal rendering.
		return $pre_render;
	}
}

// Initialize block editor support.
AyudaWP_Block_Editor::init();