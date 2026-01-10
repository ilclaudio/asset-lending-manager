<?php
/**
 * Asset Lending Manager - Frontend Manager
 *
 * Handles frontend rendering for ALM assets using shortcodes.
 *
 * Responsibilities:
 * - Provide fallback templates for alm_asset CPT.
 * - Register shortcodes for asset list and asset view.
 * - Enqueue frontend CSS and JS for asset pages.
 * - Keep rendering logic inside plugin templates.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definition of the public layout of the plugin.
 */
class ALM_Frontend_Manager {

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public function activate() {
		// No activation tasks needed for frontend.
	}

	/**
	 * Register frontend hooks and shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		// Register template loading filter.
		add_filter( 'template_include', array( $this, 'load_asset_template' ) );
		// Register shortcodes.
		add_shortcode( 'alm_asset_list', array( $this, 'shortcode_asset_list' ) );
		add_shortcode( 'alm_asset_view', array( $this, 'shortcode_asset_view' ) );
		// Enqueue frontend assets (CSS/JS).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		// Login and logout redirect for operators and members.
		add_filter( 'login_redirect', array( $this, 'redirect_login_by_role' ), 10, 3 );
		add_filter( 'logout_redirect', array( $this, 'redirect_logout_by_role' ), 10, 3 );
	}

	/**
	 * Load plugin templates for alm_asset archive and single views
	 * if the active theme does not provide them.
	 *
	 * @param string $template The path to the template WordPress intends to use.
	 * @return string
	 */
	public function load_asset_template( $template ) {
		if ( is_post_type_archive( ALM_ASSET_CPT_SLUG ) ) {
			return $this->locate_template( 'archive-alm_asset.php', $template );
		}
		if ( is_singular( ALM_ASSET_CPT_SLUG ) ) {
			return $this->locate_template( 'single-alm_asset.php', $template );
		}
		return $template;
	}

	/**
	 * Redirect operators and members after login.
	 *
	 * @param [type] $redirect_to
	 * @param [type] $requested
	 * @param [type] $user
	 * @return void
	 */
	public function redirect_login_by_role( $redirect_to, $requested, $user ) {
		if ( ! $user instanceof WP_User ) {
			return $redirect_to;
		}
		// Explicit role check.
		$roles = (array) $user->roles;
		if (
			in_array( ALM_MEMBER_ROLE, $roles, true ) ||
			in_array( ALM_OPERATOR_ROLE, $roles, true )
		) {
			return home_url( '/asset/' );
		}
		return $redirect_to;
	}

	/**
	 * Redirect operators and members after logout.
	 *
	 * @param [type] $redirect_to
	 * @param [type] $requested
	 * @param [type] $user
	 * @return void
	 */
	public function redirect_logout_by_role( $redirect_to, $requested, $user ) {
		if ( ! $user instanceof WP_User ) {
			return home_url( '/' );
		}
		// Explicit role check.
		$roles = (array) $user->roles;
		if (
			in_array( ALM_MEMBER_ROLE, $roles, true ) ||
			in_array( ALM_OPERATOR_ROLE, $roles, true )
		) {
			return home_url( '/' );
		}
		return $redirect_to;
	}

	/**
	 * Locate a template, allowing theme override with plugin fallback.
	 *
	 * @param string $template_name Template file name.
	 * @param string $default       Default template resolved by WordPress.
	 * @return string
	 */
	protected function locate_template( $template_name, $default ) {
		// 1) Try to locate the template in the theme (child theme first, then parent).
		$theme_template = locate_template( $template_name );
		// 2) Allow override via filter (can replace or confirm the template path).
		$template = apply_filters(
			'alm_locate_template',
			$theme_template,
			$template_name,
			$default
		);
		// 3) If the filter returned a valid template path, use it.
		if ( is_string( $template ) && $template !== '' && file_exists( $template ) ) {
			return $template;
		}
		// 4) If the theme template exists and the filter did not return a valid path, use it.
		if ( is_string( $theme_template ) && $theme_template !== '' && file_exists( $theme_template ) ) {
			return $theme_template;
		}
		// 5) Fallback to the plugin template.
		$plugin_template = trailingslashit( ALM_PLUGIN_DIR ) . 'templates/' . ltrim( $template_name, '/\\' );
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		// 6) Final fallback for safety
		return $default;
	}

	/**
	 * Enqueue frontend CSS and JS for asset pages.
	 *
	 * Loads assets only on pages where assets are displayed:
	 * - Archive page (asset list)
	 * - Single asset page
	 * - Pages with asset shortcodes
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Load only on asset-related pages.
		if ( ! $this->is_asset_page() ) {
			return;
		}
		// Enqueue CSS.
		wp_enqueue_style(
			'alm-frontend-assets',
			ALM_PLUGIN_URL . 'assets/css/frontend-assets.css',
			array(),
			ALM_VERSION,
			'all'
		);
		// Enqueue JS.
		wp_enqueue_script(
			'alm-frontend-assets',
			ALM_PLUGIN_URL . 'assets/js/frontend-assets.js',
			array( 'jquery' ),
			ALM_VERSION,
			true
		);
		// Pass data from PHP to JavaScript (useful for AJAX).
		wp_localize_script(
			'alm-frontend-assets',
			'almFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'alm_frontend_nonce' ),
			)
		);
	}

	/**
	 * Check if current page is asset-related.
	 *
	 * @return bool True if on asset archive, single, or page with asset shortcodes.
	 */
	private function is_asset_page() {
		// Archive or single asset page.
		if ( is_post_type_archive( ALM_ASSET_CPT_SLUG ) || is_singular( ALM_ASSET_CPT_SLUG ) ) {
			return true;
		}

		// Page with asset shortcodes.
		global $post;
		if ( $post && ( has_shortcode( $post->post_content, 'alm_asset_list' ) || has_shortcode( $post->post_content, 'alm_asset_view' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Shortcode handler for asset list.
	 *
	 * Usage: [alm_asset_list]
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_asset_list( $attributes ) {
		// Parse shortcode attributes (for future extensions like filters).
		$attributes = shortcode_atts(
			array(
				'posts_per_page' => -1,
			),
			$attributes,
			'alm_asset_list'
		);
		// Read and sanitize search parameter.
		$search_term = '';
		if ( isset( $_GET['s'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}
		// Start output buffering.
		ob_start();
		// Render the asset list template.
		$this->render_asset_list_template( $attributes, $search_term );
		return ob_get_clean(); // End output buffering.
	}

	/**
	 * Shortcode handler for single asset view.
	 *
	 * Usage:
	 * - [alm_asset_view slug="binocolo"]
	 * - [alm_asset_view] (uses query string ?asset=binocolo or current post)
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_asset_view( $attributes ) {
		// Parse shortcode attributes.
		$attributes = shortcode_atts(
			array(
				'slug' => '',
			),
			$attributes,
			'alm_asset_view'
		);

		// Determine asset ID.
		$asset_id = $this->get_asset_id_from_context( $attributes['slug'] );

		if ( ! $asset_id ) {
			return '<p class="alm-error">' . esc_html__( 'Asset not found.', 'asset-lending-manager' ) . '</p>';
		}

		// Start output buffering.
		ob_start();

		// Render the asset view template.
		$this->render_asset_view_template( $asset_id );

		return ob_get_clean();
	}

	/**
	 * Get asset ID from slug, query string, or current post context.
	 *
	 * Priority:
	 * 1. Slug from shortcode attribute
	 * 2. Slug from query string (?asset=binocolo)
	 * 3. Current post ID (if in single context)
	 *
	 * @param string $slug Asset slug from shortcode attribute.
	 * @return int|null Asset post ID or null if not found.
	 */
	private function get_asset_id_from_context( $slug ) {
		// Priority 1: Slug from shortcode attribute.
		if ( ! empty( $slug ) ) {
			$asset = get_page_by_path( $slug, OBJECT, ALM_ASSET_CPT_SLUG );
			if ( $asset ) {
				return $asset->ID;
			}
		}
		// Priority 2: Slug from query string.
		if ( isset( $_GET['asset'] ) && ! empty( $_GET['asset'] ) ) {
			$query_slug = sanitize_title( $_GET['asset'] );
			$asset     = get_page_by_path( $query_slug, OBJECT, ALM_ASSET_CPT_SLUG );
			if ( $asset ) {
				return $asset->ID;
			}
		}
		// Priority 3: Current post ID (if in single asset context).
		if ( is_singular( ALM_ASSET_CPT_SLUG ) ) {
			return get_the_ID();
		}

		return null;
	}

	/**
	 * Render asset list template.
	 *
	 * @param array  $attributes  Shortcode attributes.
	 * @param string $search_term Optional search term.
	 * @return void
	 */
	protected function render_asset_list_template( $attributes, $search_term = '' ) {
		$filter_structure = '';
		$filter_type      = '';
		$filter_state     = '';
		$filter_level     = '';
		if ( isset( $_GET['alm_structure'] ) ) {
			$filter_structure = sanitize_text_field( wp_unslash( $_GET['alm_structure'] ) );
		}
		if ( isset( $_GET['alm_type'] ) ) {
			$filter_type = sanitize_text_field( wp_unslash( $_GET['alm_type'] ) );
		}
		if ( isset( $_GET['alm_state'] ) ) {
			$filter_state = sanitize_text_field( wp_unslash( $_GET['alm_state'] ) );
		}
		if ( isset( $_GET['alm_level'] ) ) {
			$filter_level = sanitize_text_field( wp_unslash( $_GET['alm_level'] ) );
		}
		// Build query args.
		$query_args = array(
			'post_type'      => ALM_ASSET_CPT_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $attributes['posts_per_page'],
		);
		// Add search term if present.
		if ( ! empty( $search_term ) ) {
			$query_args['s'] = $search_term;
		}

		$tax_query = array();
		if ( ! empty( $filter_structure ) ) {
			$tax_query[] = array(
				'taxonomy' => ALM_ASSET_STRUCTURE_TAXONOMY_SLUG,
				'field'    => 'slug',
				'terms'    => $filter_structure,
			);
		}
		if ( ! empty( $filter_type ) ) {
			$tax_query[] = array(
				'taxonomy' => ALM_ASSET_TYPE_TAXONOMY_SLUG,
				'field'    => 'slug',
				'terms'    => $filter_type,
			);
		}
		if ( ! empty( $filter_state ) ) {
			$tax_query[] = array(
				'taxonomy' => ALM_ASSET_STATE_TAXONOMY_SLUG,
				'field'    => 'slug',
				'terms'    => $filter_state,
			);
		}
		if ( ! empty( $filter_level ) ) {
			$tax_query[] = array(
				'taxonomy' => ALM_ASSET_LEVEL_TAXONOMY_SLUG,
				'field'    => 'slug',
				'terms'    => $filter_level,
			);
		}
		// Add tax_query to query args if we have filters.
		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query;
		}
		// Build and execute query.
		$query = new WP_Query( $query_args );
		$assets       = array();
		$assets_count = 0;
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$assets_count = (int) $query->found_posts;
				$wrapper = ALM_Asset_Manager::get_asset_wrapper( $post->ID );
				if ( $wrapper ) {
					$assets[] = $wrapper;
				}
			}
		}
		wp_reset_postdata();
		include ALM_PLUGIN_DIR . 'templates/shortcodes/asset-list.php';
	}

	/**
	 * Render the asset view template.
	 *
	 * @param int $asset_id Asset post ID.
	 * @return void
	 */
	private function render_asset_view_template( $asset_id ) {
		// Get asset wrapper.
		$asset = ALM_Asset_Manager::get_asset_wrapper( $asset_id );

		if ( ! $asset ) {
			echo '<p class="alm-error">' . esc_html__( 'Asset not found.', 'asset-lending-manager' ) . '</p>';
			return;
		}

		// Include template.
		$template_path = trailingslashit( ALM_PLUGIN_DIR ) . 'templates/shortcodes/asset-view.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
