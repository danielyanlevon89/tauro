<?php
/**
 * Widget Conditions - Core functionality.
 *
 * @package ayudawp/widget-visibility-control
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for widget visibility conditions.
 *
 * @since 1.0.0
 */
class AyudaWP_Widget_Conditions {

	/**
	 * Tracks if template_redirect has been called.
	 *
	 * @var bool
	 */
	public static $passed_template_redirect = false;

	/**
	 * Key that carries a widget's rules inside its instance while they are
	 * being edited in the Customizer.
	 *
	 * The Customizer only lets a widget update write the widget's own option,
	 * and nothing reaches the database until the changes are published. Rules
	 * therefore travel with the instance, under this key, and are moved to the
	 * plugin's own option on publish. See absorb_pending_conditions().
	 *
	 * @since 1.5.0
	 */
	const PENDING_KEY = 'ayudawp_wvc_conditions';

	/**
	 * Initialize the widget conditions.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		global $pagenow;

		// Rules edited in the Customizer are stored when its changes are published,
		// whatever request does the publishing (the Customizer itself, or a
		// scheduled set of changes going live later).
		add_action( 'customize_save_after', array( __CLASS__, 'absorb_pending_conditions' ) );

		// Front-end filtering is registered before anything else, and depends on
		// nothing but is_admin(): no value a visitor can send may switch it off.
		// Up to 1.4.0 it was registered at the end of this method, below early
		// returns that look at $_GET, $_POST and the request URI, so a request
		// carrying any of those showed every widget whatever its rules said.
		if ( ! is_admin() ) {
			add_filter( 'widget_display_callback', array( __CLASS__, 'filter_widget' ), 10, 3 );
			add_filter( 'sidebars_widgets', array( __CLASS__, 'sidebars_widgets' ) );
			add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
		}

		// Everything below only decides which editing hooks to add.

		// Check if Jetpack Widget Visibility is active - if so, skip admin UI to avoid conflict.
		// We still process frontend filtering for rules stored in our format.
		$jetpack_wv_active = false;

		if ( class_exists( 'Jetpack' ) && method_exists( 'Jetpack', 'is_module_active' ) ) {
			// Jetpack is installed - check if module is actually active.
			$jetpack_wv_active = Jetpack::is_module_active( 'widget-visibility' );
		} elseif ( class_exists( 'Jetpack_Widget_Conditions' ) && ! class_exists( 'Jetpack' ) ) {
			// Jetpack_Widget_Conditions exists without main Jetpack class - assume standalone use.
			$jetpack_wv_active = true;
		}

		// Skip legacy widget preview in block editor.
		if ( isset( $_GET['legacy-widget-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- presence check only: nothing is read from it or saved.
			return;
		}

		// Check if this is a relevant widget action.
		if ( isset( $_POST['action'] ) && ! isset( $_POST['customize_changeset_uuid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- presence check that only decides which hooks to add.
			$action = sanitize_text_field( wp_unslash( $_POST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- only compared with literals to decide which hooks to add; core checks the nonce of the save itself in wp_ajax_save_widget(), wp-admin/includes/ajax-actions.php:2398 (WordPress 7.1).
			if ( ! in_array( $action, array( 'save-widget', 'update-widget' ), true ) ) {
				return;
			}
		}

		// Skip widget-types list API calls.
		if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/widget-types?' ) !== false ) {
			return;
		}

		// Only add admin UI if Jetpack Widget Visibility is NOT active.
		if ( ! $jetpack_wv_active ) {
			$add_html_to_form      = false;
			$handle_widget_updates = false;

			// Customizer ALWAYS uses classic widget interface, regardless of block editor setting.
			$is_customizer            = 'customize.php' === $pagenow || is_customize_preview();
			$using_classic_experience = ! wp_use_widgets_block_editor();

			// Classic AJAX saves: `save-widget` comes from the classic widgets screen
			// and `update-widget` from the Customizer. The Customizer cannot be told
			// apart here with is_customize_preview(): it only turns true on
			// `setup_theme`, and this runs earlier, on `plugins_loaded`.
			$ajax_action  = ( 'admin-ajax.php' === $pagenow && isset( $_POST['action'] ) ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- only compared with literals to decide which hooks to add; core checks the nonce in wp_ajax_save_widget() and in WP_Customize_Widgets::wp_ajax_update_widget(), wp-includes/class-wp-customize-widgets.php:1709 (WordPress 7.1).
			$is_ajax_save = in_array( $ajax_action, array( 'save-widget', 'update-widget' ), true );

			// Customizer ALWAYS uses classic interface (fix for block editor active).
			if ( $is_customizer || $is_ajax_save ) {
				$add_html_to_form      = true;
				$handle_widget_updates = true;
			} elseif ( $using_classic_experience && 'widgets.php' === $pagenow ) {
				// Classic Widgets plugin or theme without block editor support.
				$add_html_to_form      = true;
				$handle_widget_updates = true;
			} else {
				$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

				if ( 1 === preg_match( '|/widget-types/.*/encode|', $current_url ) ) {
					$add_html_to_form      = true;
					$handle_widget_updates = true;
				}

				if ( strpos( $current_url, '/wp-json/batch/v1' ) !== false || 1 === preg_match( '/^\/wp\/v2\/sites\/\d+\/batch\/v1/', $current_url ) ) {
					$handle_widget_updates = true;
					$add_html_to_form      = true;
				}

				if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== $_SERVER['REQUEST_METHOD'] && strpos( $current_url, '/wp/v2/widgets' ) !== false ) {
					$handle_widget_updates = true;
					$add_html_to_form      = true;
				}
			}

			if ( $add_html_to_form ) {
				add_action( 'in_widget_form', array( __CLASS__, 'widget_conditions_admin' ), 10, 3 );
			}

			if ( $handle_widget_updates ) {
				add_filter( 'widget_update_callback', array( __CLASS__, 'widget_update' ), 10, 3 );
			}

			// Load assets on widgets screen and customizer.
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_action( 'sidebar_admin_setup', array( __CLASS__, 'widget_admin_setup' ) );
		}
	}

	/**
	 * Mark that template_redirect has passed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function template_redirect() {
		self::$passed_template_redirect = true;
	}

	/**
	 * Enqueue scripts and styles on admin screens.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public static function admin_enqueue_scripts( $hook_suffix ) {
		// Only load on widgets screen.
		if ( 'widgets.php' !== $hook_suffix ) {
			return;
		}

		// Skip if using block-based widget editor (handled by class-block-editor.php).
		if ( wp_use_widgets_block_editor() ) {
			return;
		}

		// Enqueue dashicons.
		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'ayudawp-widget-conditions',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/css/widget-conditions.css',
			array( 'dashicons' ),
			AYUDAWP_WVC_VERSION
		);

		wp_enqueue_script(
			'ayudawp-widget-conditions',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/js/widget-conditions.js',
			array( 'jquery' ),
			AYUDAWP_WVC_VERSION,
			true
		);

		$conditions_data = self::get_conditions_data();
		wp_localize_script( 'ayudawp-widget-conditions', 'ayudawp_wvc_conditions_data', $conditions_data );

		$all_parents = self::get_parent_pages();
		wp_localize_script( 'ayudawp-widget-conditions', 'ayudawp_wvc_parent_pages', $all_parents );

		// Parent terms data for "include children" checkbox on taxonomy terms.
		if ( ! empty( $conditions_data['taxonomy_parent_terms'] ) ) {
			wp_localize_script( 'ayudawp-widget-conditions', 'ayudawp_wvc_parent_terms', $conditions_data['taxonomy_parent_terms'] );
		}
	}

	/**
	 * Enqueue admin scripts and styles for classic widgets (Customizer).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function widget_admin_setup() {
		// Enqueue dashicons.
		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'ayudawp-widget-conditions',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/css/widget-conditions.css',
			array( 'widgets', 'dashicons' ),
			AYUDAWP_WVC_VERSION
		);

		wp_enqueue_script(
			'ayudawp-widget-conditions',
			AYUDAWP_WVC_PLUGIN_URL . 'assets/js/widget-conditions.js',
			array( 'jquery' ),
			AYUDAWP_WVC_VERSION,
			true
		);

		$conditions_data = self::get_conditions_data();
		wp_localize_script( 'ayudawp-widget-conditions', 'ayudawp_wvc_conditions_data', $conditions_data );

		$all_parents = self::get_parent_pages();
		wp_localize_script( 'ayudawp-widget-conditions', 'ayudawp_wvc_parent_pages', $all_parents );

		// Parent terms data for "include children" checkbox on taxonomy terms.
		if ( ! empty( $conditions_data['taxonomy_parent_terms'] ) ) {
			wp_localize_script( 'ayudawp-widget-conditions', 'ayudawp_wvc_parent_terms', $conditions_data['taxonomy_parent_terms'] );
		}
	}

	/**
	 * Get all condition options data.
	 *
	 * @since 1.0.0
	 * @return array Conditions data for JavaScript.
	 */
	public static function get_conditions_data() {
		$data = array();

		// Categories.
		$data['category']   = array();
		$data['category'][] = array( '', __( 'All category pages', 'widget-visibility-control' ) );

		$max_categories = (int) apply_filters( 'ayudawp_wvc_max_categories', 1000 );
		$categories     = get_categories(
			array(
				'number'  => $max_categories,
				'orderby' => 'count',
				'order'   => 'DESC',
			)
		);
		usort( $categories, array( __CLASS__, 'strcasecmp_name' ) );

		foreach ( $categories as $category ) {
			$data['category'][] = array( (string) $category->term_id, $category->name );
		}

		// Logged in/out.
		$data['loggedin']   = array();
		$data['loggedin'][] = array( 'loggedin', __( 'Logged In', 'widget-visibility-control' ) );
		$data['loggedin'][] = array( 'loggedout', __( 'Logged Out', 'widget-visibility-control' ) );

		// Authors.
		$data['author']   = array();
		$data['author'][] = array( '', __( 'All author pages', 'widget-visibility-control' ) );

		$authors = get_users(
			array(
				'orderby'    => 'name',
				'capability' => array( 'edit_posts' ),
				'fields'     => array( 'ID', 'display_name' ),
			)
		);

		foreach ( $authors as $author ) {
			$data['author'][] = array( (string) $author->ID, $author->display_name );
		}

		// Roles.
		$data['role'] = array();
		global $wp_roles;

		foreach ( $wp_roles->roles as $role_key => $role ) {
			$data['role'][] = array( (string) $role_key, $role['name'] );
		}

		// Capabilities, gathered from every registered role.
		$data['capability'] = array();
		$capabilities       = array();

		foreach ( $wp_roles->roles as $role ) {
			if ( empty( $role['capabilities'] ) || ! is_array( $role['capabilities'] ) ) {
				continue;
			}
			foreach ( $role['capabilities'] as $cap => $granted ) {
				// Skip the legacy user levels: they are noise in the dropdown.
				if ( ! $granted || preg_match( '/^level_\d+$/', $cap ) ) {
					continue;
				}
				$capabilities[ $cap ] = true;
			}
		}

		$capabilities = array_keys( $capabilities );
		sort( $capabilities );

		/**
		 * Filters the list of capabilities offered by the Capability rule.
		 *
		 * Useful to trim the list down to the handful of capabilities a site
		 * actually cares about, or to add one that a plugin checks but never
		 * assigns to a role.
		 *
		 * @since 1.4.0
		 *
		 * @param array $capabilities Capability names, alphabetically sorted.
		 */
		$capabilities = apply_filters( 'ayudawp_wvc_capabilities', $capabilities );

		foreach ( (array) $capabilities as $capability ) {
			$capability = (string) $capability;
			if ( '' === $capability ) {
				continue;
			}
			$data['capability'][] = array( $capability, $capability );
		}

		// Tags.
		$data['tag']   = array();
		$data['tag'][] = array( '', __( 'All tag pages', 'widget-visibility-control' ) );

		$max_tags = (int) apply_filters( 'ayudawp_wvc_max_tags', 1000 );
		$tags     = get_tags(
			array(
				'number'  => $max_tags,
				'orderby' => 'count',
				'order'   => 'DESC',
			)
		);

		if ( is_array( $tags ) ) {
			usort( $tags, array( __CLASS__, 'strcasecmp_name' ) );
			foreach ( $tags as $tag ) {
				$data['tag'][] = array( (string) $tag->term_id, $tag->name );
			}
		}

		// Date archives.
		$data['date']   = array();
		$data['date'][] = array( '', __( 'All date archives', 'widget-visibility-control' ) );
		$data['date'][] = array( 'day', __( 'Daily archives', 'widget-visibility-control' ) );
		$data['date'][] = array( 'month', __( 'Monthly archives', 'widget-visibility-control' ) );
		$data['date'][] = array( 'year', __( 'Yearly archives', 'widget-visibility-control' ) );

		// Pages and post types.
		$data['page']   = array();
		$data['page'][] = array( 'front', __( 'Front page', 'widget-visibility-control' ) );
		$data['page'][] = array( 'posts', __( 'Posts page', 'widget-visibility-control' ) );
		$data['page'][] = array( 'archive', __( 'Archive page', 'widget-visibility-control' ) );
		$data['page'][] = array( '404', __( '404 error page', 'widget-visibility-control' ) );
		$data['page'][] = array( 'search', __( 'Search results', 'widget-visibility-control' ) );
		$data['page'][] = array( 'singular', __( 'Any single content', 'widget-visibility-control' ) );
		$data['page'][] = array( 'sticky', __( 'Sticky post', 'widget-visibility-control' ) );
		$data['page'][] = array( 'paged', __( 'Paginated archive (page 2+)', 'widget-visibility-control' ) );

		$post_types                  = get_post_types( array( 'public' => true ), 'objects' );
		$post_types_options          = array();
		$post_type_archives_options  = array();

		foreach ( $post_types as $post_type ) {
			$post_types_options[]         = array( 'post_type-' . $post_type->name, $post_type->labels->singular_name );
			$post_type_archives_options[] = array( 'post_type_archive-' . $post_type->name, $post_type->labels->name );
		}

		$data['page'][] = array( __( 'Post type:', 'widget-visibility-control' ), $post_types_options );
		$data['page'][] = array( __( 'Post type Archives:', 'widget-visibility-control' ), $post_type_archives_options );

		// Static pages.
		$pages              = self::get_pages();
		$dropdown_tree_args = array(
			'depth'                 => 0,
			'child_of'              => 0,
			'selected'              => 0,
			'echo'                  => false,
			'name'                  => 'page_id',
			'id'                    => '',
			'class'                 => '',
			'show_option_none'      => '',
			'show_option_no_change' => '',
			'option_none_value'     => '',
			'value_field'           => 'ID',
		);
		$pages_dropdown     = walk_page_dropdown_tree( $pages, 0, $dropdown_tree_args );
		preg_match_all( '/value=.([0-9]+).[^>]*>([^<]+)</', $pages_dropdown, $page_ids_and_titles, PREG_SET_ORDER );
		$static_pages = array();

		foreach ( $page_ids_and_titles as $page_id_and_title ) {
			$static_pages[] = array( (string) $page_id_and_title[1], $page_id_and_title[2] );
		}

		$data['page'][] = array( __( 'Static page:', 'widget-visibility-control' ), $static_pages );

		// Custom taxonomies.
		$data['taxonomy']   = array();
		$data['taxonomy'][] = array( '', __( 'All taxonomy pages', 'widget-visibility-control' ) );

		// Taxonomy list for the cascading picker (slug => label).
		$data['taxonomy_list'] = array();

		// Terms indexed by taxonomy slug for cascading loading.
		$data['taxonomy_terms'] = array();

		// Terms that have children (for "include children" checkbox).
		$data['taxonomy_parent_terms'] = array();

		$taxonomy_args = apply_filters( 'ayudawp_wvc_taxonomy_args', array( '_builtin' => false ) );
		$taxonomies    = get_taxonomies( $taxonomy_args, 'objects' );
		usort( $taxonomies, array( __CLASS__, 'strcasecmp_name' ) );

		$max_terms = (int) apply_filters( 'ayudawp_wvc_max_taxonomy_terms', 250 );

		foreach ( $taxonomies as $taxonomy ) {
			// Only check hierarchy for hierarchical taxonomies.
			$is_hierarchical = $taxonomy->hierarchical;

			$taxonomy_terms = get_terms(
				array(
					'taxonomy'   => $taxonomy->name,
					'number'     => $max_terms,
					'hide_empty' => false,
				)
			);

			if ( is_wp_error( $taxonomy_terms ) ) {
				continue;
			}

			$terms_options   = array();
			$terms_options[] = array( $taxonomy->name, $taxonomy->labels->all_items );

			foreach ( $taxonomy_terms as $term ) {
				$minor_value     = $taxonomy->name . '_tax_' . $term->term_id;
				$terms_options[] = array( $minor_value, $term->name );

				// Track terms that have children (hierarchical taxonomies only).
				if ( $is_hierarchical && $term->parent > 0 ) {
					$parent_minor = $taxonomy->name . '_tax_' . $term->parent;
					$data['taxonomy_parent_terms'][ $parent_minor ] = true;
				}
			}

			// Flat list for backwards compatibility.
			$data['taxonomy'][] = array( $taxonomy->labels->name . ':', $terms_options );

			// Indexed data for cascading UI.
			$data['taxonomy_list'][]                    = array( $taxonomy->name, $taxonomy->labels->name );
			$data['taxonomy_terms'][ $taxonomy->name ]  = $terms_options;
		}

		// Include parent pages data for "include children" in block editor.
		$data['parent_pages'] = self::get_parent_pages();

		return $data;
	}

	/**
	 * Get pages that have children.
	 *
	 * @since 1.0.0
	 * @return array Array of page IDs that have children.
	 */
	public static function get_parent_pages() {
		$all_pages   = self::get_pages();
		$all_parents = array();

		foreach ( $all_pages as $page ) {
			if ( $page->post_parent ) {
				$all_parents[ (string) $page->post_parent ] = true;
			}
		}

		$front_page_id = get_option( 'page_on_front' );
		if ( isset( $all_parents[ $front_page_id ] ) ) {
			$all_parents['front'] = true;
		}

		return $all_parents;
	}

	/**
	 * Get all published pages (optimized query).
	 *
	 * @since 1.0.0
	 * @return array Array of page objects.
	 */
	public static function get_pages() {
		global $wpdb;

		$last_changed = wp_cache_get_last_changed( 'posts' );
		$cache_key    = "ayudawp_wvc_pages:$last_changed";
		$pages        = wp_cache_get( $cache_key, 'ayudawp_wvc' );

		if ( false === $pages ) {
			// Direct query for performance with caching - faster than get_posts for large sites.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$pages = $wpdb->get_results(
				"SELECT ID, post_parent, post_title, post_status 
				FROM {$wpdb->posts} 
				WHERE post_type = 'page' 
				AND post_status = 'publish' 
				ORDER BY post_title ASC"
			);
			wp_cache_set( $cache_key, $pages, 'ayudawp_wvc' );
		}

		return apply_filters( 'ayudawp_wvc_get_pages', $pages );
	}

	/**
	 * Render the visibility conditions UI in widget form.
	 *
	 * @since 1.0.0
	 * @param WP_Widget $widget   Widget instance.
	 * @param null      $return   Unused.
	 * @param array     $instance Widget settings.
	 * @return void
	 */
	public static function widget_conditions_admin( $widget, $return, $instance ) {
		// Get widget ID (e.g., 'categories-2').
		$widget_id = $widget->id;

		// Rules changed in the Customizer and not published yet come first: they
		// are what the user is editing.
		$conditions = self::get_pending_conditions( $instance );

		// Then our own storage.
		if ( null === $conditions && class_exists( 'AyudaWP_WVC_Data_Manager' ) ) {
			$conditions = AyudaWP_WVC_Data_Manager::get_conditions( $widget_id );
		}

		// Fall back to instance conditions (legacy/Jetpack format).
		if ( null === $conditions && isset( $instance['conditions'] ) ) {
			$conditions = $instance['conditions'];
		}

		// Default empty conditions.
		if ( ! is_array( $conditions ) ) {
			$conditions = array();
		}

		if ( ! isset( $conditions['action'] ) ) {
			$conditions['action'] = 'show';
		}

		if ( empty( $conditions['rules'] ) ) {
			$conditions['rules'][] = array(
				'major'        => '',
				'minor'        => '',
				'has_children' => '',
			);
		}

		if ( empty( $conditions['match_all'] ) ) {
			$conditions['match_all'] = false;
		}

		$visible_value = '0';
		if ( isset( $_POST['widget-conditions-visible'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- interface state (was the panel open), only echoed back escaped; this form is rendered inside the widget save that core has already verified.
			$visible_value = sanitize_text_field( wp_unslash( $_POST['widget-conditions-visible'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- interface state, see the line above.
		}

		$hide_class        = ( empty( $visible_value ) || '0' === $visible_value ) ? 'widget-conditional-hide' : '';
		$conjunction_class = ( ! empty( $conditions['match_all'] ) && $conditions['match_all'] ) ? 'intersection' : 'conjunction';

		?>
		<div class="widget-conditional <?php echo esc_attr( $hide_class . ' ' . $conjunction_class ); ?>">
			<input type="hidden" name="widget-conditions-visible" value="<?php echo esc_attr( $visible_value ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $widget->get_field_name( 'conditions[widget_id]' ) ); ?>" value="<?php echo esc_attr( $widget_id ); ?>" />
			
			<?php if ( ! isset( $_POST['widget-conditions-visible'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Missing -- interface state: decides whether to print the Visibility button. ?>
				<a href="#" class="button display-options"><?php esc_html_e( 'Visibility', 'widget-visibility-control' ); ?></a>
			<?php endif; ?>
			
			<div class="widget-conditional-inner">
				<div class="condition-top">
					<?php
					$action_select = sprintf(
						'<select name="%s"><option value="show" %s>%s</option><option value="hide" %s>%s</option></select>',
						esc_attr( $widget->get_field_name( 'conditions[action]' ) ),
						selected( $conditions['action'], 'show', false ),
						esc_html_x( 'Show', 'Widget visibility action', 'widget-visibility-control' ),
						selected( $conditions['action'], 'hide', false ),
						esc_html_x( 'Hide', 'Widget visibility action', 'widget-visibility-control' )
					);

					$allowed_select = array(
						'select' => array( 'name' => array() ),
						'option' => array(
							'value'    => array(),
							'selected' => array(),
						),
					);

					printf(
						/* translators: %s: dropdown select for show/hide action */
						esc_html_x( '%s if:', 'Widget visibility: show/hide dropdown', 'widget-visibility-control' ),
						wp_kses( $action_select, $allowed_select )
					);
					?>
				</div>

				<div class="conditions">
					<?php foreach ( $conditions['rules'] as $rule_index => $rule ) : ?>
						<?php
						$rule = wp_parse_args(
							$rule,
							array(
								'major'        => '',
								'minor'        => '',
								'has_children' => '',
							)
						);

						// Every rule posts exactly one value, whatever its type: the
						// list, the URL field or an empty placeholder. Rule types and
						// values are paired by position, so a row that posted nothing
						// would shift the values of the rows below it.
						$is_url_rule  = ( 'url' === $rule['major'] );
						$has_no_value = ( '' === $rule['major'] || 'always' === $rule['major'] );
						$minor_name   = $widget->get_field_name( 'conditions[rules_minor][]' );
						?>
						<div class="condition" data-rule-major="<?php echo esc_attr( $rule['major'] ); ?>" data-rule-minor="<?php echo esc_attr( $rule['minor'] ); ?>" data-rule-has-children="<?php echo esc_attr( $rule['has_children'] ); ?>">
							<div class="selection alignleft">
								<select class="conditions-rule-major" name="<?php echo esc_attr( $widget->get_field_name( 'conditions[rules_major][]' ) ); ?>">
									<option value="" <?php selected( '', $rule['major'] ); ?>><?php echo esc_html_x( '-- Select --', 'Dropdown default option', 'widget-visibility-control' ); ?></option>
									<option value="category" <?php selected( 'category', $rule['major'] ); ?>><?php esc_html_e( 'Category', 'widget-visibility-control' ); ?></option>
									<option value="author" <?php selected( 'author', $rule['major'] ); ?>><?php esc_html_e( 'Author', 'widget-visibility-control' ); ?></option>
									<option value="loggedin" <?php selected( 'loggedin', $rule['major'] ); ?>><?php esc_html_e( 'User', 'widget-visibility-control' ); ?></option>
									<option value="role" <?php selected( 'role', $rule['major'] ); ?>><?php esc_html_e( 'Role', 'widget-visibility-control' ); ?></option>
									<option value="capability" <?php selected( 'capability', $rule['major'] ); ?>><?php esc_html_e( 'Capability', 'widget-visibility-control' ); ?></option>
									<option value="tag" <?php selected( 'tag', $rule['major'] ); ?>><?php esc_html_e( 'Tag', 'widget-visibility-control' ); ?></option>
									<option value="date" <?php selected( 'date', $rule['major'] ); ?>><?php esc_html_e( 'Date', 'widget-visibility-control' ); ?></option>
									<option value="page" <?php selected( 'page', $rule['major'] ); ?>><?php esc_html_e( 'Page', 'widget-visibility-control' ); ?></option>
									<option value="url" <?php selected( 'url', $rule['major'] ); ?>><?php esc_html_e( 'URL', 'widget-visibility-control' ); ?></option>
									<?php if ( get_taxonomies( array( '_builtin' => false ) ) ) : ?>
										<option value="taxonomy" <?php selected( 'taxonomy', $rule['major'] ); ?>><?php esc_html_e( 'Taxonomy', 'widget-visibility-control' ); ?></option>
									<?php endif; ?>
									<option value="always" <?php selected( 'always', $rule['major'] ); ?>><?php esc_html_e( 'Always', 'widget-visibility-control' ); ?></option>
								</select>

								<?php echo esc_html_x( 'is', 'Condition: Page is Front page', 'widget-visibility-control' ); ?>

								<select class="conditions-rule-taxonomy-picker" style="display: none;" disabled>
									<option value=""><?php echo esc_html_x( '-- Select taxonomy --', 'Taxonomy picker default', 'widget-visibility-control' ); ?></option>
								</select>

								<select class="conditions-rule-minor" name="<?php echo esc_attr( $minor_name ); ?>" <?php disabled( $has_no_value || $is_url_rule ); ?><?php echo $is_url_rule ? ' style="display: none;"' : ''; ?>>
									<option value="<?php echo esc_attr( $is_url_rule ? '' : $rule['minor'] ); ?>" selected="selected"></option>
								</select>

								<input type="text" class="conditions-rule-url" name="<?php echo esc_attr( $minor_name ); ?>" value="<?php echo esc_attr( $is_url_rule ? $rule['minor'] : '' ); ?>" placeholder="shop/*" aria-label="<?php esc_attr_e( 'URL or path', 'widget-visibility-control' ); ?>" dir="ltr" autocomplete="off" spellcheck="false" <?php disabled( ! $is_url_rule ); ?><?php echo $is_url_rule ? '' : ' style="display: none;"'; ?> />
								<span class="conditions-rule-url-help description"<?php echo $is_url_rule ? '' : ' style="display: none;"'; ?>><?php esc_html_e( 'Path or full URL. Use * as a wildcard: shop/* matches /shop/ and everything below it.', 'widget-visibility-control' ); ?></span>

								<input type="hidden" class="conditions-rule-empty" name="<?php echo esc_attr( $minor_name ); ?>" value="" <?php disabled( ! $has_no_value ); ?> />

								<span class="conditions-rule-has-children"<?php echo empty( $rule['has_children'] ) ? ' style="display: none;"' : ''; ?>>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( $widget->get_field_name( 'conditions[page_children][' . $rule_index . ']' ) ); ?>" value="has" <?php checked( $rule['has_children'], true ); ?> />
										<?php esc_html_e( 'Include children', 'widget-visibility-control' ); ?>
									</label>
								</span>
							</div>

							<div class="condition-control">
								<span class="condition-conjunction"><?php echo esc_html_x( 'or', 'Condition separator', 'widget-visibility-control' ); ?></span>
								<span class="condition-intersection"><?php echo esc_html_x( 'and', 'Condition separator', 'widget-visibility-control' ); ?></span>
								<div class="actions alignright">
									<a href="#" class="delete-condition dashicons dashicons-no"><span class="screen-reader-text"><?php esc_html_e( 'Delete', 'widget-visibility-control' ); ?></span></a>
									<a href="#" class="add-condition dashicons dashicons-plus"><span class="screen-reader-text"><?php esc_html_e( 'Add', 'widget-visibility-control' ); ?></span></a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="conditions">
					<div class="condition-top">
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $widget->get_field_name( 'conditions[match_all]' ) ); ?>" value="1" class="conditions-match-all" <?php checked( $conditions['match_all'], '1' ); ?> />
							<?php esc_html_e( 'Match all conditions', 'widget-visibility-control' ); ?>
						</label>
					</div>
				</div>

				<?php
				// Schedule defaults.
				$schedule = isset( $conditions['schedule'] ) ? $conditions['schedule'] : array();
				$schedule = wp_parse_args(
					$schedule,
					array(
						'enabled'     => false,
						'action'      => '',
						'from'        => '',
						'to'          => '',
						'no_end_date' => false,
					)
				);

				// Parse existing dates using WordPress timezone.
				$from_parts = self::parse_schedule_date( $schedule['from'] );
				$to_parts   = self::parse_schedule_date( $schedule['to'] );

				// Get WordPress timezone.
				$timezone_string = wp_timezone_string();
				?>
				<div class="conditions-schedule">
					<div class="schedule-header">
						<?php esc_html_e( 'Time Schedule', 'widget-visibility-control' ); ?>
						<span class="schedule-timezone">(<?php echo esc_html( $timezone_string ); ?>)</span>
					</div>

					<div class="schedule-action">
						<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][action]' ) ); ?>" class="schedule-action-select">
							<option value="" <?php selected( $schedule['action'], '' ); ?>><?php esc_html_e( '-- No schedule --', 'widget-visibility-control' ); ?></option>
							<option value="show" <?php selected( $schedule['action'], 'show' ); ?>><?php esc_html_e( 'Show only during period', 'widget-visibility-control' ); ?></option>
							<option value="hide" <?php selected( $schedule['action'], 'hide' ); ?>><?php esc_html_e( 'Hide during period', 'widget-visibility-control' ); ?></option>
						</select>
					</div>

					<div class="schedule-dates"<?php echo empty( $schedule['action'] ) ? ' style="display: none;"' : ''; ?>>
						<div class="schedule-row">
							<span class="schedule-label"><?php esc_html_e( 'From:', 'widget-visibility-control' ); ?></span>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][from_day]' ) ); ?>" class="schedule-day">
								<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
									<option value="<?php echo esc_attr( $d ); ?>" <?php selected( $from_parts['day'], $d ); ?>><?php echo esc_html( $d ); ?></option>
								<?php endfor; ?>
							</select>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][from_month]' ) ); ?>" class="schedule-month">
								<?php
								global $wp_locale;
								for ( $m = 1; $m <= 12; $m++ ) :
									?>
									<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $from_parts['month'], $m ); ?>><?php echo esc_html( $wp_locale->get_month_abbrev( $wp_locale->get_month( $m ) ) ); ?></option>
								<?php endfor; ?>
							</select>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][from_year]' ) ); ?>" class="schedule-year">
								<?php
								$current_year = (int) wp_date( 'Y' );
								for ( $y = $current_year; $y <= $current_year + 5; $y++ ) :
									?>
									<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $from_parts['year'], $y ); ?>><?php echo esc_html( $y ); ?></option>
								<?php endfor; ?>
							</select>
							<span class="schedule-at">@</span>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][from_hour]' ) ); ?>" class="schedule-hour">
								<?php for ( $h = 0; $h <= 23; $h++ ) : ?>
									<option value="<?php echo esc_attr( $h ); ?>" <?php selected( $from_parts['hour'], $h ); ?>><?php echo esc_html( str_pad( $h, 2, '0', STR_PAD_LEFT ) ); ?></option>
								<?php endfor; ?>
							</select>
							<span class="schedule-colon">:</span>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][from_minute]' ) ); ?>" class="schedule-minute">
								<?php for ( $min = 0; $min <= 59; $min += 5 ) : ?>
									<option value="<?php echo esc_attr( $min ); ?>" <?php selected( $from_parts['minute'], $min ); ?>><?php echo esc_html( str_pad( $min, 2, '0', STR_PAD_LEFT ) ); ?></option>
								<?php endfor; ?>
							</select>
						</div>

						<?php
						$no_end_date = ! empty( $schedule['no_end_date'] );
						$to_disabled = $no_end_date ? ' disabled' : '';
						$to_class    = $no_end_date ? ' schedule-row-disabled' : '';
						?>
						<div class="schedule-row schedule-row-to<?php echo esc_attr( $to_class ); ?>">
							<span class="schedule-label"><?php esc_html_e( 'To:', 'widget-visibility-control' ); ?></span>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][to_day]' ) ); ?>" class="schedule-day"<?php echo esc_attr( $to_disabled ); ?>>
								<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
									<option value="<?php echo esc_attr( $d ); ?>" <?php selected( $to_parts['day'], $d ); ?>><?php echo esc_html( $d ); ?></option>
								<?php endfor; ?>
							</select>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][to_month]' ) ); ?>" class="schedule-month"<?php echo esc_attr( $to_disabled ); ?>>
								<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
									<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $to_parts['month'], $m ); ?>><?php echo esc_html( $wp_locale->get_month_abbrev( $wp_locale->get_month( $m ) ) ); ?></option>
								<?php endfor; ?>
							</select>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][to_year]' ) ); ?>" class="schedule-year"<?php echo esc_attr( $to_disabled ); ?>>
								<?php for ( $y = $current_year; $y <= $current_year + 5; $y++ ) : ?>
									<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $to_parts['year'], $y ); ?>><?php echo esc_html( $y ); ?></option>
								<?php endfor; ?>
							</select>
							<span class="schedule-at">@</span>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][to_hour]' ) ); ?>" class="schedule-hour"<?php echo esc_attr( $to_disabled ); ?>>
								<?php for ( $h = 0; $h <= 23; $h++ ) : ?>
									<option value="<?php echo esc_attr( $h ); ?>" <?php selected( $to_parts['hour'], $h ); ?>><?php echo esc_html( str_pad( $h, 2, '0', STR_PAD_LEFT ) ); ?></option>
								<?php endfor; ?>
							</select>
							<span class="schedule-colon">:</span>
							<select name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][to_minute]' ) ); ?>" class="schedule-minute"<?php echo esc_attr( $to_disabled ); ?>>
								<?php for ( $min = 0; $min <= 59; $min += 5 ) : ?>
									<option value="<?php echo esc_attr( $min ); ?>" <?php selected( $to_parts['minute'], $min ); ?>><?php echo esc_html( str_pad( $min, 2, '0', STR_PAD_LEFT ) ); ?></option>
								<?php endfor; ?>
							</select>
						</div>

						<div class="schedule-no-end-date">
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $widget->get_field_name( 'conditions[schedule][no_end_date]' ) ); ?>" value="1" class="schedule-no-end-checkbox" <?php checked( $no_end_date ); ?> />
								<?php esc_html_e( 'No end date', 'widget-visibility-control' ); ?>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Parse a schedule date string into components.
	 * Uses WordPress timezone for consistent date handling.
	 *
	 * @since 1.1.0
	 * @param string $date_string Date in 'Y-m-d H:i' format.
	 * @return array Date components with defaults.
	 */
	public static function parse_schedule_date( $date_string ) {
		// Get current time in WordPress timezone for defaults.
		$defaults = array(
			'year'   => (int) wp_date( 'Y' ),
			'month'  => (int) wp_date( 'n' ),
			'day'    => (int) wp_date( 'j' ),
			'hour'   => 0,
			'minute' => 0,
		);

		if ( empty( $date_string ) ) {
			return $defaults;
		}

		// Parse the date string using WordPress timezone.
		try {
			$wp_timezone = wp_timezone();
			$date        = new DateTime( $date_string, $wp_timezone );

			return array(
				'year'   => (int) $date->format( 'Y' ),
				'month'  => (int) $date->format( 'n' ),
				'day'    => (int) $date->format( 'j' ),
				'hour'   => (int) $date->format( 'G' ),
				'minute' => (int) $date->format( 'i' ),
			);
		} catch ( Exception $e ) {
			return $defaults;
		}
	}

	/**
	 * Process widget conditions on save.
	 *
	 * @since 1.0.0
	 * @param array $instance     Current widget settings.
	 * @param array $new_instance New settings from form.
	 * @param array $old_instance Previous settings.
	 * @return array Modified settings.
	 */
	public static function widget_update( $instance, $new_instance, $old_instance ) {
		$conditions              = array();
		$conditions['action']    = isset( $new_instance['conditions']['action'] ) ? sanitize_text_field( $new_instance['conditions']['action'] ) : 'show';
		$conditions['match_all'] = ! empty( $new_instance['conditions']['match_all'] ) ? '1' : '0';
		$conditions['rules']     = array();

		// Get widget ID from the form.
		$widget_id = isset( $new_instance['conditions']['widget_id'] ) ? sanitize_text_field( $new_instance['conditions']['widget_id'] ) : '';

		if ( isset( $new_instance['conditions']['rules_major'] ) && is_array( $new_instance['conditions']['rules_major'] ) ) {
			foreach ( $new_instance['conditions']['rules_major'] as $index => $major_rule ) {
				$major_rule = sanitize_text_field( $major_rule );
				if ( empty( $major_rule ) ) {
					continue;
				}

				$minor_raw = isset( $new_instance['conditions']['rules_minor'][ $index ] ) ? $new_instance['conditions']['rules_minor'][ $index ] : '';

				if ( 'url' === $major_rule ) {
					// A URL pattern has its own cleaner: sanitize_text_field() deletes
					// the percent-encoded octets of an address pasted from the browser.
					$minor_rule = self::sanitize_url_pattern( $minor_raw );

					// A URL rule without a pattern says nothing, so it is not stored.
					// In the Customizer it is kept while it is being typed: every
					// keystroke goes through here, and dropping the rule would make
					// the form come back without it. It is dropped on publish.
					if ( '' === $minor_rule && ! is_customize_preview() ) {
						continue;
					}
				} else {
					$minor_rule = sanitize_text_field( $minor_raw );
				}

				$conditions['rules'][] = array(
					'major'        => $major_rule,
					'minor'        => $minor_rule,
					'has_children' => isset( $new_instance['conditions']['page_children'][ $index ] ),
				);
			}
		}

		// Process schedule data.
		$conditions['schedule'] = self::process_schedule_data( $new_instance );

		// Determine what we have.
		$has_rules    = ! empty( $conditions['rules'] );
		$has_schedule = ! empty( $conditions['schedule']['action'] );

		// In the Customizer nothing is saved until the changes are published, and
		// a widget update may only write the widget's own option: core answers
		// `widget_setting_too_many_options` otherwise
		// (wp-includes/class-wp-customize-widgets.php:1647). The rules stay in the
		// instance and absorb_pending_conditions() stores them on publish.
		if ( is_customize_preview() ) {
			// A widget cancels its own update by returning false, and that stands:
			// writing a key into it would turn it into an instance with nothing
			// but the rules, and core would save that.
			if ( ! is_array( $instance ) ) {
				return $instance;
			}

			// No form came with this update. The Customizer also redraws a widget
			// from the value of its setting alone (`sanitized_widget_setting`,
			// wp-includes/class-wp-customize-widgets.php:1601), and then the
			// instance itself arrives where the fields would be. "No fields" is
			// not "no rules": the rules that value carried stay as they were.
			if ( '' === $widget_id ) {
				$carried = self::get_pending_conditions( $new_instance );

				if ( null === $carried ) {
					unset( $instance[ self::PENDING_KEY ] );
				} else {
					$instance[ self::PENDING_KEY ] = $carried;
				}

				return $instance;
			}

			unset( $instance['conditions'] );

			$has_stored  = class_exists( 'AyudaWP_WVC_Data_Manager' ) && null !== AyudaWP_WVC_Data_Manager::get_conditions( $widget_id );
			$had_pending = is_array( $old_instance ) && array_key_exists( self::PENDING_KEY, $old_instance );

			if ( $has_rules || $has_schedule ) {
				$instance[ self::PENDING_KEY ] = $conditions;
			} elseif ( $has_stored || $had_pending ) {
				// An empty set is kept on purpose: it is what says "remove the rules".
				$instance[ self::PENDING_KEY ] = array();
			} else {
				unset( $instance[ self::PENDING_KEY ] );
			}

			return $instance;
		}

		// Save to our data manager.
		if ( ! empty( $widget_id ) && class_exists( 'AyudaWP_WVC_Data_Manager' ) ) {
			// Clear cache before saving to ensure fresh data.
			AyudaWP_WVC_Data_Manager::clear_cache();

			if ( $has_rules || $has_schedule ) {
				AyudaWP_WVC_Data_Manager::save_conditions( $widget_id, $conditions );
			} else {
				AyudaWP_WVC_Data_Manager::delete_conditions( $widget_id );
			}
		}

		// Make sure the instance carries no stale `conditions` from Jetpack-format
		// data. Our own storage in ayudawp_wvc_conditions is authoritative; leaving
		// a copy inside the instance creates a false positive in the Jetpack data
		// detector and a divergent source of truth.
		$had_legacy_conditions = isset( $old_instance['conditions'] );
		unset( $instance['conditions'], $instance[ self::PENDING_KEY ] );

		// Fire actions for extensions based on the actual save outcome.
		if ( $has_rules || $has_schedule ) {
			$changed = ! $had_legacy_conditions
				|| wp_json_encode( $conditions ) !== wp_json_encode( $old_instance['conditions'] );
			if ( $changed ) {
				do_action( 'ayudawp_wvc_conditions_save', $widget_id, $conditions );
			}
		} elseif ( $had_legacy_conditions ) {
			do_action( 'ayudawp_wvc_conditions_delete', $widget_id );
		}

		return $instance;
	}

	/**
	 * Process schedule data from form submission.
	 *
	 * @since 1.1.0
	 * @param array $new_instance New settings from form.
	 * @return array Processed schedule data.
	 */
	private static function process_schedule_data( $new_instance ) {
		$schedule = array(
			'enabled'     => false,
			'action'      => '',
			'from'        => '',
			'to'          => '',
			'no_end_date' => false,
		);

		if ( ! isset( $new_instance['conditions']['schedule'] ) ) {
			return $schedule;
		}

		$sched_data = $new_instance['conditions']['schedule'];
		$action     = isset( $sched_data['action'] ) ? sanitize_text_field( $sched_data['action'] ) : '';

		if ( empty( $action ) ) {
			return $schedule;
		}

		$schedule['enabled']     = true;
		$schedule['action']      = $action;
		$schedule['no_end_date'] = ! empty( $sched_data['no_end_date'] );

		// Build from date using WordPress timezone.
		$current_year = (int) wp_date( 'Y' );
		$from_year    = isset( $sched_data['from_year'] ) ? absint( $sched_data['from_year'] ) : $current_year;
		$from_month   = isset( $sched_data['from_month'] ) ? absint( $sched_data['from_month'] ) : 1;
		$from_day     = isset( $sched_data['from_day'] ) ? absint( $sched_data['from_day'] ) : 1;
		$from_hour    = isset( $sched_data['from_hour'] ) ? absint( $sched_data['from_hour'] ) : 0;
		$from_minute  = isset( $sched_data['from_minute'] ) ? absint( $sched_data['from_minute'] ) : 0;

		$schedule['from'] = sprintf(
			'%04d-%02d-%02d %02d:%02d',
			$from_year,
			$from_month,
			$from_day,
			$from_hour,
			$from_minute
		);

		// Build to date (even if no_end_date, store the values for UI restoration).
		$to_year   = isset( $sched_data['to_year'] ) ? absint( $sched_data['to_year'] ) : $current_year;
		$to_month  = isset( $sched_data['to_month'] ) ? absint( $sched_data['to_month'] ) : 1;
		$to_day    = isset( $sched_data['to_day'] ) ? absint( $sched_data['to_day'] ) : 1;
		$to_hour   = isset( $sched_data['to_hour'] ) ? absint( $sched_data['to_hour'] ) : 23;
		$to_minute = isset( $sched_data['to_minute'] ) ? absint( $sched_data['to_minute'] ) : 59;

		$schedule['to'] = sprintf(
			'%04d-%02d-%02d %02d:%02d',
			$to_year,
			$to_month,
			$to_day,
			$to_hour,
			$to_minute
		);

		return $schedule;
	}

	/**
	 * Read the rules a widget instance carries from the Customizer, if any.
	 *
	 * @since 1.5.0
	 * @param mixed $instance Widget instance.
	 * @return array|null The rules (an empty array means "no rules"), or null when the instance carries none.
	 */
	private static function get_pending_conditions( $instance ) {
		if ( ! is_array( $instance ) || ! array_key_exists( self::PENDING_KEY, $instance ) ) {
			return null;
		}

		return is_array( $instance[ self::PENDING_KEY ] ) ? $instance[ self::PENDING_KEY ] : array();
	}

	/**
	 * Rebuild the rules an instance carried from the Customizer before storing them.
	 *
	 * They normally come from widget_update(), already clean. But the Customizer
	 * also accepts a whole instance as sent by the browser (`raw_instance`, in
	 * WP_Customize_Widgets::sanitize_widget_instance()), and that one never went
	 * through the form. So nothing is copied: every key is built again, and what
	 * is not a usable rule is left out, unfinished URL rules included.
	 *
	 * @since 1.5.0
	 * @param array $conditions Rules as found in the instance.
	 * @return array Clean rules, in the shape widget_update() stores.
	 */
	private static function clean_pending_conditions( $conditions ) {
		$clean = array(
			'action'    => ( isset( $conditions['action'] ) && 'hide' === $conditions['action'] ) ? 'hide' : 'show',
			'match_all' => ( isset( $conditions['match_all'] ) && '1' === $conditions['match_all'] ) ? '1' : '0',
			'rules'     => array(),
		);

		if ( isset( $conditions['rules'] ) && is_array( $conditions['rules'] ) ) {
			foreach ( $conditions['rules'] as $rule ) {
				$rule = self::normalize_rule( $rule );
				if ( null === $rule ) {
					continue;
				}

				$major = sanitize_key( $rule['major'] );
				$minor = ( 'url' === $major ) ? self::sanitize_url_pattern( $rule['minor'] ) : sanitize_text_field( $rule['minor'] );

				if ( '' === $major || ( 'url' === $major && '' === $minor ) ) {
					continue;
				}

				$clean['rules'][] = array(
					'major'        => $major,
					'minor'        => $minor,
					'has_children' => ! empty( $rule['has_children'] ),
				);
			}
		}

		// Same shape process_schedule_data() gives when nothing is scheduled.
		$clean['schedule'] = array(
			'enabled'     => false,
			'action'      => '',
			'from'        => '',
			'to'          => '',
			'no_end_date' => false,
		);

		if ( isset( $conditions['schedule'] ) && is_array( $conditions['schedule'] ) ) {
			$schedule = $conditions['schedule'];
			$action   = ( isset( $schedule['action'] ) && is_string( $schedule['action'] ) ) ? $schedule['action'] : '';

			if ( in_array( $action, array( 'show', 'hide' ), true ) ) {
				$clean['schedule'] = array(
					'enabled'     => true,
					'action'      => $action,
					'from'        => ( isset( $schedule['from'] ) && is_string( $schedule['from'] ) ) ? sanitize_text_field( $schedule['from'] ) : '',
					'to'          => ( isset( $schedule['to'] ) && is_string( $schedule['to'] ) ) ? sanitize_text_field( $schedule['to'] ) : '',
					'no_end_date' => ! empty( $schedule['no_end_date'] ),
				);
			}
		}

		return $clean;
	}

	/**
	 * Store the rules edited in the Customizer once its changes are published.
	 *
	 * By then every widget instance has been written to the database with its
	 * rules inside. They are moved to the plugin's own option, which is the one
	 * source of truth, and taken out of the instance.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function absorb_pending_conditions() {
		global $wp_widget_factory;

		if ( empty( $wp_widget_factory->widgets ) || ! class_exists( 'AyudaWP_WVC_Data_Manager' ) ) {
			return;
		}

		$seen = array();

		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( empty( $widget->id_base ) || empty( $widget->option_name ) || isset( $seen[ $widget->option_name ] ) ) {
				continue;
			}
			$seen[ $widget->option_name ] = true;

			$instances = get_option( $widget->option_name );
			if ( ! is_array( $instances ) ) {
				continue;
			}

			$changed = false;

			foreach ( $instances as $number => $instance ) {
				$conditions = is_numeric( $number ) ? self::get_pending_conditions( $instance ) : null;
				if ( null === $conditions ) {
					continue;
				}

				$widget_id = $widget->id_base . '-' . $number;

				// Rebuilt key by key: what was left unfinished in the Customizer,
				// or did not come from the form at all, is not stored.
				$conditions = self::clean_pending_conditions( $conditions );

				if ( ! empty( $conditions['rules'] ) || ! empty( $conditions['schedule']['action'] ) ) {
					AyudaWP_WVC_Data_Manager::save_conditions( $widget_id, $conditions );
					do_action( 'ayudawp_wvc_conditions_save', $widget_id, $conditions );
				} elseif ( null !== AyudaWP_WVC_Data_Manager::get_conditions( $widget_id ) ) {
					AyudaWP_WVC_Data_Manager::delete_conditions( $widget_id );
					do_action( 'ayudawp_wvc_conditions_delete', $widget_id );
				}

				unset( $instances[ $number ][ self::PENDING_KEY ] );
				$changed = true;
			}

			if ( $changed ) {
				update_option( $widget->option_name, $instances );
			}
		}
	}

	/**
	 * Filter widgets in sidebars.
	 *
	 * @since 1.0.0
	 * @param array $widget_areas Widget areas and their widgets.
	 * @return array Modified widget areas.
	 */
	public static function sidebars_widgets( $widget_areas ) {
		if ( ! is_array( $widget_areas ) ) {
			return $widget_areas;
		}

		// Rules describe a front-end page, and there is no page to compare with
		// until the main query is ready (the `wp` action). REST requests, WP-CLI
		// and cron never get there. Filtering this list for them hid widgets from
		// whoever was reading it, and core writes back what it reads: saving one
		// widget area from the block-based widgets screen dropped the widgets
		// whose rules were not met from every other area. See
		// WP_REST_Sidebars_Controller::update_item() and
		// wp_assign_widget_to_sidebar(). Whether a widget is printed is still
		// decided by filter_widget(), which runs everywhere.
		if ( ! did_action( 'wp' ) ) {
			return $widget_areas;
		}

		foreach ( $widget_areas as $widget_area => $widgets ) {
			if ( empty( $widgets ) || ! is_array( $widgets ) ) {
				continue;
			}

			if ( 'wp_inactive_widgets' === $widget_area ) {
				continue;
			}

			foreach ( $widgets as $position => $widget_id ) {
				if ( ! preg_match( '/^(.+?)-(\d+)$/', $widget_id, $matches ) ) {
					continue;
				}

				$id_base       = $matches[1];
				$widget_number = (int) $matches[2];
				$conditions    = null;

				// In the Customizer preview, rules not published yet win.
				if ( is_customize_preview() ) {
					$settings = get_option( 'widget_' . $id_base );
					if ( is_array( $settings ) && isset( $settings[ $widget_number ] ) ) {
						$conditions = self::get_pending_conditions( $settings[ $widget_number ] );
					}
				}

				// Then our data manager.
				if ( null === $conditions && class_exists( 'AyudaWP_WVC_Data_Manager' ) ) {
					$conditions = AyudaWP_WVC_Data_Manager::get_conditions( $widget_id );
				}

				// Fall back to legacy widget instance if no conditions in our storage.
				if ( null === $conditions ) {
					$settings = get_option( 'widget_' . $id_base );

					if ( isset( $settings[ $widget_number ]['conditions'] ) ) {
						$conditions = $settings[ $widget_number ]['conditions'];
					}
				}

				// Check conditions.
				$has_rules    = ! empty( $conditions['rules'] );
				$has_schedule = ! empty( $conditions['schedule']['action'] );

				if ( ! empty( $conditions ) && ( $has_rules || $has_schedule ) ) {
					if ( ! self::filter_widget_check_conditions( $conditions ) ) {
						unset( $widget_areas[ $widget_area ][ $position ] );
					}
				}
			}
		}

		return $widget_areas;
	}

	/**
	 * Determine if a widget should be displayed.
	 *
	 * This is called by widget_display_callback filter.
	 * We check our storage first, then fall back to instance conditions.
	 * Also handles block widgets by parsing block content for conditions.
	 *
	 * @since 1.0.0
	 * @param array       $instance  Widget instance settings.
	 * @param WP_Widget   $widget    Widget object (optional, passed by filter).
	 * @param array       $args      Widget area args (optional).
	 * @return array|false Widget instance or false to hide.
	 */
	public static function filter_widget( $instance, $widget = null, $args = array() ) {
		$conditions = null;

		// Handle block widgets (WP_Widget_Block).
		if ( $widget && 'block' === $widget->id_base && isset( $instance['content'] ) ) {
			// Parse the block content to extract conditions.
			$blocks = parse_blocks( $instance['content'] );
			if ( ! empty( $blocks[0]['attrs']['conditions'] ) ) {
				$conditions = $blocks[0]['attrs']['conditions'];
			}
		}

		// Rules changed in the Customizer and not published yet travel inside the
		// instance, and win over the stored ones: that is the preview.
		if ( null === $conditions ) {
			$conditions = self::get_pending_conditions( $instance );
		}

		// Try to get widget ID and check our storage (for classic widgets).
		if ( null === $conditions && $widget && isset( $widget->id ) && class_exists( 'AyudaWP_WVC_Data_Manager' ) ) {
			$conditions = AyudaWP_WVC_Data_Manager::get_conditions( $widget->id );
		}

		// Fall back to instance conditions (legacy format).
		if ( null === $conditions && isset( $instance['conditions'] ) ) {
			$conditions = $instance['conditions'];
		}

		// Check if there's anything to evaluate.
		$has_rules    = ! empty( $conditions['rules'] );
		$has_schedule = ! empty( $conditions['schedule']['action'] );

		// No conditions, show the widget.
		if ( empty( $conditions ) || ( ! $has_rules && ! $has_schedule ) ) {
			return $instance;
		}

		if ( self::filter_widget_check_conditions( $conditions ) ) {
			return $instance;
		}

		return false;
	}

	/**
	 * Check if widget conditions are met.
	 *
	 * @since 1.0.0
	 * @param array $conditions Widget visibility conditions.
	 * @return bool True if widget should be shown.
	 */
	public static function filter_widget_check_conditions( $conditions ) {
		// First check schedule - if schedule says hide, hide regardless of other rules.
		$schedule_result = self::evaluate_schedule( $conditions );
		if ( false === $schedule_result ) {
			return false;
		}

		// If no visibility rules, only schedule matters (which already passed).
		if ( empty( $conditions['rules'] ) || ! is_array( $conditions['rules'] ) ) {
			return true;
		}

		// Rules can come from block markup, which nothing validated on the way
		// in. Whatever is not a usable rule is left out, as if it was not there.
		$rules = array();
		foreach ( $conditions['rules'] as $rule ) {
			$rule = self::normalize_rule( $rule );
			if ( null !== $rule ) {
				$rules[] = $rule;
			}
		}

		if ( empty( $rules ) ) {
			return true;
		}

		// Cache for visibility rules only (not schedule - schedule is time-based and shouldn't be cached).
		static $condition_result_cache = array();

		$condition_result = false;

		foreach ( $rules as $rule ) {
			$condition_key = md5( wp_json_encode( $rule ) );

			if ( isset( $condition_result_cache[ $condition_key ] ) ) {
				$condition_result = $condition_result_cache[ $condition_key ];
			} else {
				$condition_result = self::evaluate_rule( $rule );

				if ( $condition_result || self::$passed_template_redirect ) {
					$condition_result_cache[ $condition_key ] = $condition_result;
				}
			}

			if ( isset( $conditions['match_all'] ) && '1' === $conditions['match_all'] && ! $condition_result ) {
				break;
			} elseif ( ( empty( $conditions['match_all'] ) || '1' !== $conditions['match_all'] ) && $condition_result ) {
				break;
			}
		}

		$action = isset( $conditions['action'] ) ? $conditions['action'] : '';

		if (
			( 'show' === $action && ! $condition_result ) ||
			( 'hide' === $action && $condition_result )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Check the shape of a stored rule before it is evaluated.
	 *
	 * @since 1.5.0
	 * @param mixed $rule Rule as stored.
	 * @return array|null The rule with a string value, or null when it cannot be used.
	 */
	private static function normalize_rule( $rule ) {
		if ( ! is_array( $rule ) || ! isset( $rule['major'] ) || ! is_string( $rule['major'] ) ) {
			return null;
		}

		$minor = isset( $rule['minor'] ) ? $rule['minor'] : '';
		if ( ! is_scalar( $minor ) ) {
			return null;
		}

		$rule['minor'] = (string) $minor;

		// A URL rule whose pattern is still empty is unfinished, not a rule that
		// never matches: left in, it would hide a widget set to "show if".
		if ( 'url' === $rule['major'] && '' === trim( $rule['minor'] ) ) {
			return null;
		}

		return $rule;
	}

	/**
	 * Evaluate schedule conditions.
	 * Always evaluates fresh (no caching) since time changes constantly.
	 *
	 * @since 1.1.0
	 * @param array $conditions Widget visibility conditions.
	 * @return bool True if widget should be shown based on schedule, false to hide.
	 */
	private static function evaluate_schedule( $conditions ) {
		// No schedule configured.
		if ( empty( $conditions['schedule'] ) || empty( $conditions['schedule']['action'] ) ) {
			return true;
		}

		$schedule = $conditions['schedule'];
		if ( ! is_array( $schedule ) ) {
			return true;
		}

		// The dates go straight into DateTime, which only takes strings.
		$action      = $schedule['action'];
		$from        = ( isset( $schedule['from'] ) && is_string( $schedule['from'] ) ) ? $schedule['from'] : '';
		$to          = ( isset( $schedule['to'] ) && is_string( $schedule['to'] ) ) ? $schedule['to'] : '';
		$no_end_date = ! empty( $schedule['no_end_date'] );

		// Need at least from date.
		if ( empty( $from ) ) {
			return true;
		}

		// Need to date unless no_end_date is set.
		if ( ! $no_end_date && empty( $to ) ) {
			return true;
		}

		// Get current time in WordPress timezone.
		$wp_timezone  = wp_timezone();
		$current_time = new DateTime( 'now', $wp_timezone );

		// Parse from date.
		try {
			$from_time = new DateTime( $from, $wp_timezone );
		} catch ( Exception $e ) {
			// Invalid date, don't restrict.
			return true;
		}

		// Determine if within period.
		if ( $no_end_date ) {
			// No end date: only check if current time is after from.
			$is_within_period = ( $current_time >= $from_time );
		} else {
			// With end date: check if current time is between from and to.
			try {
				$to_time = new DateTime( $to, $wp_timezone );
			} catch ( Exception $e ) {
				return true;
			}
			$is_within_period = ( $current_time >= $from_time && $current_time <= $to_time );
		}

		// Apply schedule action.
		if ( 'show' === $action ) {
			// Show only during period - if not within period, hide.
			return $is_within_period;
		} elseif ( 'hide' === $action ) {
			// Hide during period - if within period, hide.
			return ! $is_within_period;
		}

		return true;
	}

	/**
	 * Evaluate a single visibility rule.
	 *
	 * @since 1.0.0
	 * @param array $rule Visibility rule to evaluate.
	 * @return bool True if rule matches.
	 */
	private static function evaluate_rule( $rule ) {
		$result = false;

		switch ( $rule['major'] ) {
			case 'category':
				$result = self::evaluate_category_rule( $rule );
				break;

			case 'loggedin':
				$result = is_user_logged_in();
				if ( 'loggedin' !== $rule['minor'] ) {
					$result = ! $result;
				}
				break;

			case 'author':
				$result = self::evaluate_author_rule( $rule );
				break;

			case 'role':
				if ( is_user_logged_in() ) {
					$current_user = wp_get_current_user();
					$result       = in_array( $rule['minor'], $current_user->roles, true );
				}
				break;

			case 'capability':
				$result = ! empty( $rule['minor'] ) && current_user_can( $rule['minor'] );
				break;

			case 'tag':
				$result = self::evaluate_tag_rule( $rule );
				break;

			case 'date':
				$result = self::evaluate_date_rule( $rule );
				break;

			case 'page':
				$result = self::evaluate_page_rule( $rule );
				break;

			case 'url':
				$result = self::evaluate_url_rule( $rule );
				break;

			case 'taxonomy':
				$result = self::evaluate_taxonomy_rule( $rule );
				break;

			case 'always':
				$result = true;
				break;
		}

		return $result;
	}

	/**
	 * Evaluate category rule.
	 *
	 * @since 1.0.0
	 * @param array $rule Rule to evaluate.
	 * @return bool True if rule matches.
	 */
	private static function evaluate_category_rule( $rule ) {
		if ( empty( $rule['minor'] ) ) {
			return is_category();
		}

		$term_id = self::maybe_get_split_term( $rule['minor'], 'category' );

		if ( is_category( $term_id ) ) {
			return true;
		}

		if ( is_singular() && has_category( $term_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Evaluate author rule.
	 *
	 * @since 1.0.0
	 * @param array $rule Rule to evaluate.
	 * @return bool True if rule matches.
	 */
	private static function evaluate_author_rule( $rule ) {
		if ( empty( $rule['minor'] ) ) {
			return is_author();
		}

		if ( is_author( $rule['minor'] ) ) {
			return true;
		}

		if ( is_singular() && (int) get_the_author_meta( 'ID' ) === (int) $rule['minor'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Evaluate tag rule.
	 *
	 * @since 1.0.0
	 * @param array $rule Rule to evaluate.
	 * @return bool True if rule matches.
	 */
	private static function evaluate_tag_rule( $rule ) {
		if ( empty( $rule['minor'] ) ) {
			return is_tag();
		}

		$term_id = self::maybe_get_split_term( $rule['minor'], 'post_tag' );

		if ( is_tag( $term_id ) ) {
			return true;
		}

		if ( is_singular() && has_tag( $term_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Evaluate date rule.
	 *
	 * @since 1.0.0
	 * @param array $rule Rule to evaluate.
	 * @return bool True if rule matches.
	 */
	private static function evaluate_date_rule( $rule ) {
		if ( empty( $rule['minor'] ) ) {
			return is_date();
		}

		switch ( $rule['minor'] ) {
			case 'day':
				return is_day();
			case 'month':
				return is_month();
			case 'year':
				return is_year();
		}

		return false;
	}

	/**
	 * Evaluate page rule.
	 *
	 * @since 1.0.0
	 * @param array $rule Rule to evaluate.
	 * @return bool True if rule matches.
	 */
	private static function evaluate_page_rule( $rule ) {
		switch ( $rule['minor'] ) {
			case 'front':
				if ( is_front_page() ) {
					return true;
				}
				if ( ! empty( $rule['has_children'] ) && is_page() ) {
					return self::is_child_of( get_the_ID(), get_option( 'page_on_front' ) );
				}
				return false;

			case 'posts':
				return is_home();

			case 'archive':
				return is_archive();

			case '404':
				return is_404();

			case 'search':
				return is_search();

			case 'singular':
				return is_singular();

			case 'sticky':
				return is_singular() && is_sticky( get_queried_object_id() );

			case 'paged':
				return is_paged();

			default:
				if ( strpos( $rule['minor'], 'post_type-' ) === 0 ) {
					return is_singular( substr( $rule['minor'], 10 ) );
				}

				if ( strpos( $rule['minor'], 'post_type_archive-' ) === 0 ) {
					return is_post_type_archive( substr( $rule['minor'], 18 ) );
				}

				if ( is_numeric( $rule['minor'] ) ) {
					if ( is_page( $rule['minor'] ) ) {
						return true;
					}
					if ( ! empty( $rule['has_children'] ) && is_page() ) {
						return self::is_child_of( get_the_ID(), $rule['minor'] );
					}
				}

				return false;
		}
	}

	/**
	 * Maximum length of a stored URL pattern, in characters.
	 *
	 * @since 1.5.0
	 */
	const MAX_URL_PATTERN_LENGTH = 500;

	/**
	 * Evaluate URL rule.
	 *
	 * @since 1.5.0
	 * @param array $rule Rule to evaluate.
	 * @return bool True if rule matches.
	 */
	private static function evaluate_url_rule( $rule ) {
		// The address is read as the browser sent it: sanitize_text_field() would
		// delete its percent-encoded octets (accents, other alphabets).
		$request_uri = ( isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) )
			? wp_strip_all_tags( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		return self::url_rule_matches( $rule['minor'], $request_uri, self::get_home_path() );
	}

	/**
	 * Path of the site home URL, for sites installed in a subdirectory.
	 *
	 * @since 1.5.0
	 * @return string Path without slashes at the ends, empty for a site at the domain root.
	 */
	public static function get_home_path() {
		return trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	}

	/**
	 * Clean a URL pattern before it is stored.
	 *
	 * The pattern is kept as typed. Decoding, lower-casing and slashes are dealt
	 * with when the rule is evaluated, by normalize_url_pattern().
	 *
	 * @since 1.5.0
	 * @param mixed $pattern Pattern as typed or imported.
	 * @return string Clean pattern, empty when there is nothing usable.
	 */
	public static function sanitize_url_pattern( $pattern ) {
		if ( ! is_string( $pattern ) ) {
			return '';
		}

		$pattern = wp_check_invalid_utf8( $pattern, true );
		$pattern = wp_strip_all_tags( $pattern, true );
		$pattern = trim( (string) preg_replace( '/[\x00-\x1F\x7F]+/', '', $pattern ) );

		if ( mb_strlen( $pattern ) > self::MAX_URL_PATTERN_LENGTH ) {
			$pattern = mb_substr( $pattern, 0, self::MAX_URL_PATTERN_LENGTH );
		}

		return $pattern;
	}

	/**
	 * Tell whether a URL pattern matches a request.
	 *
	 * Depends on nothing but its arguments, so it can be checked outside
	 * WordPress: see recursos/release-checks/permanentes/.
	 *
	 * @since 1.5.0
	 * @param mixed  $pattern     Pattern as stored.
	 * @param string $request_uri Request URI, as in $_SERVER['REQUEST_URI'].
	 * @param string $home_path   Path of the site home URL, see get_home_path().
	 * @return bool True if the pattern matches.
	 */
	public static function url_rule_matches( $pattern, $request_uri, $home_path = '' ) {
		$pattern = self::normalize_url_pattern( $pattern, $home_path );
		if ( null === $pattern ) {
			return false;
		}

		$subject = self::url_subject( $request_uri, $home_path );

		// A pattern with a `?` needs both halves to match. Nothing after the `?`
		// asks for no query string at all (`/?` is the home page with nothing
		// added); anything after it asks for a query string, and one that matches.
		if ( null !== $pattern['query'] ) {
			if ( ! self::url_glob( $pattern['path'], $subject['path'] ) ) {
				return false;
			}

			if ( '' === $pattern['query'] ) {
				return '' === $subject['query'];
			}

			return '' !== $subject['query'] && self::url_glob( $pattern['query'], $subject['query'] );
		}

		if ( self::url_glob( $pattern['path'], $subject['path'] ) ) {
			return true;
		}

		if ( '' === $subject['query'] ) {
			return false;
		}

		// Without a `?`, the pattern is also tried on the whole address, so that
		// *utm_source=news* finds its text wherever it is. The slashes are
		// collapsed here as they were in the pattern.
		$address = $subject['path'] . '?' . preg_replace( '~/{2,}~', '/', $subject['query'] );

		return self::url_glob( $pattern['whole'], $address );
	}

	/**
	 * Reduce a URL pattern to the form it is compared in.
	 *
	 * The path always starts and ends with a slash unless a wildcard sits at
	 * that end, which is what makes `shop`, `/shop` and `/shop/` the same rule
	 * and lets `shop/*` cover /shop/ and everything below it.
	 *
	 * @since 1.5.0
	 * @param mixed  $pattern   Pattern as stored.
	 * @param string $home_path Path of the site home URL, see get_home_path().
	 * @return array|null Array with `path`, `whole` (the same pattern without the closing slash, for the whole address) and `query` (null when the pattern has no `?`), or null for an empty pattern.
	 */
	public static function normalize_url_pattern( $pattern, $home_path = '' ) {
		if ( ! is_string( $pattern ) ) {
			return null;
		}

		$pattern = trim( $pattern );
		if ( '' === $pattern ) {
			return null;
		}

		// A full address pasted from the browser: keep what follows the host.
		$pattern = (string) preg_replace( '~^[a-z][a-z0-9+.\-]*://[^/?#]*~i', '', $pattern );

		// The fragment never reaches the server.
		$pattern = substr( $pattern, 0, strcspn( $pattern, '#' ) );

		$query = null;
		$cut   = strpos( $pattern, '?' );
		if ( false !== $cut ) {
			$query   = self::url_fold( (string) substr( $pattern, $cut + 1 ) );
			$pattern = (string) substr( $pattern, 0, $cut );
		}

		$path      = self::url_fold( $pattern );
		$home_path = trim( self::url_fold( (string) $home_path ), '/' );

		if ( '' === $path ) {
			// `?utm_source=*` on its own means any page carrying that query string.
			$path = ( null !== $query ) ? '*' : '/';
		} elseif ( '' !== $home_path ) {
			// Subdirectory installs: accept the path as the address bar shows it.
			$bare = ltrim( $path, '/' );
			if ( $bare === $home_path || 0 === strpos( $bare, $home_path . '/' ) ) {
				$path = '/' . ltrim( (string) substr( $bare, strlen( $home_path ) ), '/' );
			}
		}

		if ( '*' !== $path[0] && '/' !== $path[0] ) {
			$path = '/' . $path;
		}

		$path  = (string) preg_replace( '~/{2,}~', '/', $path );
		$path  = (string) preg_replace( '/\*{2,}/', '*', $path );
		$whole = $path;

		$last = substr( $path, -1 );
		if ( '*' !== $last && '/' !== $last ) {
			$path .= '/';
		}

		return array(
			'path'  => $path,
			'whole' => $whole,
			'query' => $query,
		);
	}

	/**
	 * Reduce a request URI to the form URL patterns are compared with.
	 *
	 * @since 1.5.0
	 * @param mixed  $request_uri Request URI, as in $_SERVER['REQUEST_URI'].
	 * @param string $home_path   Path of the site home URL, see get_home_path().
	 * @return array Array with `path` (always inside slashes) and `query` (empty when there is none).
	 */
	public static function url_subject( $request_uri, $home_path = '' ) {
		$request_uri = is_string( $request_uri ) ? $request_uri : '';

		// Some proxies forward the absolute URL.
		$request_uri = (string) preg_replace( '~^[a-z][a-z0-9+.\-]*://[^/?#]*~i', '', $request_uri );
		$request_uri = substr( $request_uri, 0, strcspn( $request_uri, '#' ) );

		$query = '';
		$cut   = strpos( $request_uri, '?' );
		if ( false !== $cut ) {
			$query       = self::url_fold( substr( $request_uri, $cut + 1 ) );
			$request_uri = substr( $request_uri, 0, $cut );
		}

		$path      = trim( self::url_fold( $request_uri ), '/' );
		$home_path = trim( self::url_fold( (string) $home_path ), '/' );

		if ( '' !== $home_path ) {
			if ( $path === $home_path ) {
				$path = '';
			} elseif ( 0 === strpos( $path, $home_path . '/' ) ) {
				$path = (string) substr( $path, strlen( $home_path ) + 1 );
			}
		}

		$path = trim( (string) preg_replace( '~/{2,}~', '/', $path ), '/' );

		return array(
			'path'  => ( '' === $path ) ? '/' : '/' . $path . '/',
			'query' => $query,
		);
	}

	/**
	 * Decode a piece of URL and fold its case so both sides compare alike.
	 *
	 * Case folding rather than lower-casing: since PHP 8.3 mb_strtolower()
	 * picks the final form of the Greek sigma by context, and the same word
	 * would come out differently in a pattern and in an address.
	 *
	 * @since 1.5.0
	 * @param string $value Piece of URL.
	 * @return string
	 */
	private static function url_fold( $value ) {
		$value = rawurldecode( $value );

		if ( function_exists( 'mb_convert_case' ) && defined( 'MB_CASE_FOLD' ) ) {
			return mb_convert_case( $value, MB_CASE_FOLD, 'UTF-8' );
		}

		return strtolower( $value );
	}

	/**
	 * Match a string against a pattern where `*` stands for any run of characters.
	 *
	 * Done by hand and not with a regular expression: the subject is whatever
	 * address a visitor requests, and this way a pattern with many wildcards
	 * costs a few string searches instead of backtracking.
	 *
	 * @since 1.5.0
	 * @param string $pattern Pattern, already normalized.
	 * @param string $subject String to test.
	 * @return bool
	 */
	private static function url_glob( $pattern, $subject ) {
		if ( false === strpos( $pattern, '*' ) ) {
			return $pattern === $subject;
		}

		$parts = explode( '*', $pattern );
		$first = array_shift( $parts );
		$last  = array_pop( $parts );

		// What comes before the first wildcard has to open the subject...
		$offset = strlen( $first );
		if ( 0 !== strncmp( $subject, $first, $offset ) ) {
			return false;
		}

		// ...what comes after the last one has to close it, without overlapping...
		$end = strlen( $subject ) - strlen( $last );
		if ( $end < $offset || ( '' !== $last && substr( $subject, $end ) !== $last ) ) {
			return false;
		}

		// ...and the pieces in between have to appear in order in what is left.
		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}

			$found = strpos( $subject, $part, $offset );
			if ( false === $found || $found + strlen( $part ) > $end ) {
				return false;
			}

			$offset = $found + strlen( $part );
		}

		return true;
	}

	/**
	 * Evaluate taxonomy rule.
	 *
	 * @since 1.0.0
	 * @param array $rule Rule to evaluate.
	 * @return bool True if rule matches.
	 */
	private static function evaluate_taxonomy_rule( $rule ) {
		if ( empty( $rule['minor'] ) ) {
			if ( is_archive() && ( is_tag() || is_category() || is_tax() ) ) {
				return true;
			}
			if ( is_singular() ) {
				return ! empty( get_post_taxonomies() );
			}
			return false;
		}

		$term = explode( '_tax_', $rule['minor'] );

		if ( ! isset( $term[1] ) || empty( $term[1] ) ) {
			if ( is_tax( $term[0] ) ) {
				return true;
			}
			if ( is_singular() && in_array( $term[0], get_post_taxonomies(), true ) ) {
				return true;
			}
			return false;
		}

		$taxonomy_name = $term[0];
		$term_id       = self::maybe_get_split_term( $term[1], $taxonomy_name );

		if ( is_tax( $taxonomy_name, $term_id ) ) {
			return true;
		}

		if ( is_singular() && has_term( $term_id, $taxonomy_name ) ) {
			return true;
		}

		// Include child terms when "include children" is checked.
		if ( ! empty( $rule['has_children'] ) && taxonomy_exists( $taxonomy_name ) && is_taxonomy_hierarchical( $taxonomy_name ) ) {
			$child_term_ids = get_term_children( $term_id, $taxonomy_name );

			if ( ! is_wp_error( $child_term_ids ) && ! empty( $child_term_ids ) ) {
				// Check if we're on a taxonomy archive of a child term.
				if ( is_tax( $taxonomy_name ) ) {
					$queried = get_queried_object();
					if ( $queried && isset( $queried->term_id ) && in_array( (int) $queried->term_id, $child_term_ids, true ) ) {
						return true;
					}
				}

				// Check if a singular post has any child term.
				if ( is_singular() ) {
					foreach ( $child_term_ids as $child_id ) {
						if ( has_term( $child_id, $taxonomy_name ) ) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Check if a page is a child of another page.
	 *
	 * @since 1.0.0
	 * @param int $page_id   Page ID to check.
	 * @param int $parent_id Potential parent ID.
	 * @return bool True if page_id is child of parent_id.
	 */
	private static function is_child_of( $page_id, $parent_id ) {
		$ancestors = get_post_ancestors( $page_id );
		return in_array( (int) $parent_id, $ancestors, true );
	}

	/**
	 * Handle split terms (WordPress 4.2+).
	 *
	 * @since 1.0.0
	 * @param int    $old_term_id Old term ID.
	 * @param string $taxonomy    Taxonomy name.
	 * @return int Current term ID.
	 */
	public static function maybe_get_split_term( $old_term_id, $taxonomy ) {
		if ( 'tag' === $taxonomy ) {
			$taxonomy = 'post_tag';
		}

		$new_term_id = wp_get_split_term( $old_term_id, $taxonomy );

		return $new_term_id ? $new_term_id : $old_term_id;
	}

	/**
	 * Compare term names for sorting.
	 *
	 * @since 1.0.0
	 * @param object $a Term object.
	 * @param object $b Term object.
	 * @return int Comparison result.
	 */
	public static function strcasecmp_name( $a, $b ) {
		return strcasecmp( $a->name, $b->name );
	}
}
