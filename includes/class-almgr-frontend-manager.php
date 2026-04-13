<?php
/**
 * Asset Lending Manager - Frontend Manager
 *
 * Handles frontend rendering for ALMGR assets using shortcodes.
 *
 * Responsibilities:
 * - Provide fallback templates for almgr_asset CPT.
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
class ALMGR_Frontend_Manager {

	/**
	 * Settings manager instance.
	 *
	 * @var ALMGR_Settings_Manager
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Settings_Manager $settings Plugin settings instance.
	 */
	public function __construct( ALMGR_Settings_Manager $settings ) {
		$this->settings = $settings;
	}

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
		add_shortcode( 'almgr_asset_list', array( $this, 'shortcode_asset_list' ) );
		add_shortcode( 'almgr_asset_view', array( $this, 'shortcode_asset_view' ) );
		// Enqueue frontend assets (CSS/JS).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		// Handle QR scan redirect (?almgr_scan=ALMGR-00000052).
		add_action( 'template_redirect', array( $this, 'handle_almgr_scan_redirect' ) );
		// Login and logout redirect for operators and members.
		add_filter( 'login_redirect', array( $this, 'redirect_login_by_role' ), 10, 3 );
		add_filter( 'logout_redirect', array( $this, 'redirect_logout_by_role' ), 10, 3 );
	}

	/**
	 * Load plugin templates for almgr_asset archive and single views
	 * if the active theme does not provide them.
	 *
	 * @param string $template The path to the template WordPress intends to use.
	 * @return string
	 */
	public function load_asset_template( $template ) {
		if ( is_post_type_archive( ALMGR_ASSET_CPT_SLUG ) ) {
			return $this->locate_template( 'archive-almgr-asset.php', $template );
		}
		if ( is_singular( ALMGR_ASSET_CPT_SLUG ) ) {
			return $this->locate_template( 'single-almgr-asset.php', $template );
		}
		return $template;
	}

	/**
	 * Redirect operators and members after login.
	 *
	 * @param string                 $redirect_to The redirect destination URL.
	 * @param string                 $requested   The requested redirect URL.
	 * @param WP_User|WP_Error|mixed $user        Authenticated user object or error.
	 * @return string
	 */
	public function redirect_login_by_role( $redirect_to, $requested, $user ) {
		if ( ! $user instanceof WP_User ) {
			return $redirect_to;
		}
		// Explicit role check.
		$roles = (array) $user->roles;
		if (
			in_array( ALMGR_MEMBER_ROLE, $roles, true ) ||
			in_array( ALMGR_OPERATOR_ROLE, $roles, true )
		) {
			$login_page_id = (int) $this->settings->get( 'frontend.login_redirect_page_id', 0 );
			if ( $login_page_id > 0 ) {
				$url = get_permalink( $login_page_id );
				if ( $url ) {
					return $url;
				}
			}
			$assets_page_id = (int) $this->settings->get( 'frontend.assets_page_id', 0 );
			if ( $assets_page_id > 0 ) {
				$url = get_permalink( $assets_page_id );
				if ( $url ) {
					return $url;
				}
			}
			return home_url( '/asset/' );
		}
		return $redirect_to;
	}

	/**
	 * Redirect operators and members after logout.
	 *
	 * @param string                 $redirect_to The redirect destination URL.
	 * @param string                 $requested   The requested redirect URL.
	 * @param WP_User|WP_Error|mixed $user        Current user object or error.
	 * @return string
	 */
	public function redirect_logout_by_role( $redirect_to, $requested, $user ) {
		if ( ! $user instanceof WP_User ) {
			return home_url( '/' );
		}
		// Explicit role check.
		$roles = (array) $user->roles;
		if (
			in_array( ALMGR_MEMBER_ROLE, $roles, true ) ||
			in_array( ALMGR_OPERATOR_ROLE, $roles, true )
		) {
			$logout_page_id = (int) $this->settings->get( 'frontend.logout_redirect_page_id', 0 );
			if ( $logout_page_id > 0 ) {
				$url = get_permalink( $logout_page_id );
				if ( $url ) {
					return $url;
				}
			}
			return home_url( '/' );
		}
		return $redirect_to;
	}

	/**
	 * Locate a template, allowing theme override with plugin fallback.
	 *
	 * @param string $template_name     Template file name.
	 * @param string $default_template  Default template resolved by WordPress.
	 * @return string
	 */
	protected function locate_template( $template_name, $default_template ) {
		// 1) Try to locate the template in the theme (child theme first, then parent).
		$theme_template = locate_template( $template_name );
		// 2) Allow override via filter (can replace or confirm the template path).
		$template = apply_filters(
			'almgr_locate_template',
			$theme_template,
			$template_name,
			$default_template
		);
		// 3) If the filter returned a valid template path, use it.
		if ( is_string( $template ) && '' !== $template && file_exists( $template ) ) {
			return $template;
		}
		// 4) If the theme template exists and the filter did not return a valid path, use it.
		if ( is_string( $theme_template ) && '' !== $theme_template && file_exists( $theme_template ) ) {
			return $theme_template;
		}
		// 5) Fallback to the plugin template.
		$plugin_template = trailingslashit( ALMGR_PLUGIN_DIR ) . 'templates/' . ltrim( $template_name, '/\\' );
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		// 6) Final fallback for safety
		return $default_template;
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
			'almgr-frontend-assets',
			ALMGR_PLUGIN_URL . 'assets/css/frontend-assets.css',
			array(),
			ALMGR_VERSION,
			'all'
		);
		wp_enqueue_style(
			'almgr-requests-table',
			ALMGR_PLUGIN_URL . 'assets/css/asset-requests-table.css',
			array(),
			ALMGR_VERSION
		);
		wp_enqueue_style(
			'almgr-history-table',
			ALMGR_PLUGIN_URL . 'assets/css/asset-history-table.css',
			array(),
			ALMGR_VERSION
		);
		// Enqueue JS.
			wp_enqueue_script(
				'almgr-frontend-assets',
				ALMGR_PLUGIN_URL . 'assets/js/frontend-assets.js',
				array( 'wp-i18n' ),
				ALMGR_VERSION,
				true
			);
			wp_set_script_translations(
				'almgr-frontend-assets',
				ALMGR_TEXT_DOMAIN,
				ALMGR_PLUGIN_DIR . 'languages'
			);
		// Pass data from PHP to JavaScript (useful for AJAX).
		wp_localize_script(
			'almgr-frontend-assets',
			'almgrFrontend',
			array(
				'ajaxUrl'                     => admin_url( 'admin-ajax.php' ),
				'nonce'                       => wp_create_nonce( 'almgr_frontend_nonce' ),
				'loanRequestNonce'            => wp_create_nonce( 'almgr_loan_request_nonce' ),
				'directAssignNonce'           => wp_create_nonce( 'almgr_direct_assign_nonce' ),
				'changeStateNonce'            => wp_create_nonce( 'almgr_change_state_nonce' ),
				'restoreStateNonce'           => wp_create_nonce( 'almgr_restore_state_nonce' ),
				'qrScanEnabled'               => (bool) $this->settings->get( 'autocomplete.qr_scan_enabled', true ),
				'requestMessageMaxLength'     => (int) $this->settings->get( 'loans.request_message_max_length', 500 ),
				'rejectionMessageMaxLength'   => (int) $this->settings->get( 'loans.rejection_message_max_length', 500 ),
				'directAssignReasonMaxLength' => (int) $this->settings->get( 'loans.direct_assign_reason_max_length', 500 ),
				'changeStateNotesMaxLength'   => (int) $this->settings->get( 'loans.change_state_notes_max_length', 500 ),
			)
		);

		// Enqueue QR code generator library only on asset detail pages.
		if ( $this->is_asset_view_page() ) {
			wp_enqueue_script(
				'almgr-qrcode-generator',
				ALMGR_PLUGIN_URL . 'assets/js/vendor/qrcode-generator.js',
				array(),
				'1.4.4',
				true
			);
		}

		// Enqueue jsQR library only on asset list pages (used by the QR scanner button).
		if ( $this->is_asset_list_page() ) {
			wp_enqueue_script(
				'almgr-jsqr',
				ALMGR_PLUGIN_URL . 'assets/js/vendor/jsqr.min.js',
				array(),
				'1.4.0',
				true
			);
		}

			// Enqueue user autocomplete assets (used by the direct assignment form for operators).
				wp_enqueue_script(
					'almgr-user-autocomplete',
					ALMGR_PLUGIN_URL . 'assets/js/almgr-user-autocomplete.js',
					array( 'wp-i18n' ),
					ALMGR_VERSION,
					true
				);
			wp_set_script_translations(
				'almgr-user-autocomplete',
				ALMGR_TEXT_DOMAIN,
				ALMGR_PLUGIN_DIR . 'languages'
			);
		wp_localize_script(
			'almgr-user-autocomplete',
			'almgrUserAutocomplete',
			array(
				'restUrl'   => esc_url( rest_url( 'almgr/v1/users/autocomplete' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'minChars'  => (int) $this->settings->get( 'autocomplete.min_chars', 3 ),
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
		if ( is_post_type_archive( ALMGR_ASSET_CPT_SLUG ) || is_singular( ALMGR_ASSET_CPT_SLUG ) ) {
			return true;
		}

		// Page with asset shortcodes.
		global $post;
		if ( $post && ( has_shortcode( $post->post_content, 'almgr_asset_list' ) || has_shortcode( $post->post_content, 'almgr_asset_view' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get a sanitized query-string value for read-only frontend filters.
	 *
	 * @param string $key Query-string key.
	 * @return string
	 */
	private function get_sanitized_query_text( $key ) {
		if ( ! isset( $_GET[ $key ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
	}

	/**
	 * Get a sanitized slug from query string for read-only frontend filters.
	 *
	 * @param string $key Query-string key.
	 * @return string
	 */
	private function get_sanitized_query_slug( $key ) {
		$query_value = $this->get_sanitized_query_text( $key );

		if ( '' === $query_value ) {
			return '';
		}

		return sanitize_title( $query_value );
	}

	/**
	 * Get a positive integer from query string for read-only frontend filters.
	 *
	 * @param string $key Query-string key.
	 * @return int
	 */
	private function get_sanitized_query_absint( $key ) {
		$query_value = $this->get_sanitized_query_text( $key );

		if ( '' === $query_value ) {
			return 0;
		}

		return absint( $query_value );
	}

	/**
	 * Shortcode handler for asset list.
	 *
	 * Usage: [almgr_asset_list]
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_asset_list( $attributes ) {
		// Parse shortcode attributes (for future extensions like filters).
		$attributes = shortcode_atts(
			array(
				'per_page' => (int) $this->settings->get( 'frontend.asset_list_per_page', ALMGR_ASSET_LIST_PER_PAGE ),
			),
			$attributes,
			'almgr_asset_list'
		);
		// Read and sanitize search parameter.
		$search_term = $this->get_sanitized_query_text( 's' );
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
	 * - [almgr_asset_view slug="binocolo"]
	 * - [almgr_asset_view] (uses query string ?asset=binocolo or current post)
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
			'almgr_asset_view'
		);

		// Determine asset ID.
		$asset_id = $this->get_asset_id_from_context( $attributes['slug'] );

		if ( ! $asset_id ) {
			return '<p class="almgr-error">' . esc_html__( 'Asset not found.', 'asset-lending-manager' ) . '</p>';
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
			$asset = get_page_by_path( $slug, OBJECT, ALMGR_ASSET_CPT_SLUG );
			if ( $asset ) {
				return $asset->ID;
			}
		}
		// Priority 2: Slug from query string.
		$query_slug = $this->get_sanitized_query_slug( 'asset' );
		if ( '' !== $query_slug ) {
			$asset = get_page_by_path( $query_slug, OBJECT, ALMGR_ASSET_CPT_SLUG );
			if ( $asset ) {
				return $asset->ID;
			}
		}
		// Priority 3: Current post ID (if in single asset context).
		if ( is_singular( ALMGR_ASSET_CPT_SLUG ) ) {
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
		$filter_structure = $this->get_sanitized_query_slug( 'almgr_structure' );
		$filter_type      = $this->get_sanitized_query_slug( 'almgr_type' );
		$filter_state     = $this->get_sanitized_query_slug( 'almgr_state' );
		$filter_level     = $this->get_sanitized_query_slug( 'almgr_level' );
		// Read owner filter (operator: by user ID; member: "my assets" checkbox).
		$filter_owner      = 0;
		$filter_owner_name = '';
		$filter_my_assets  = false;
		if ( current_user_can( ALMGR_EDIT_ASSET ) ) {
			$filter_owner = $this->get_sanitized_query_absint( 'almgr_owner' );
			if ( $filter_owner > 0 ) {
				$owner_data = get_userdata( $filter_owner );
				if ( $owner_data ) {
					$filter_owner_name = $owner_data->display_name;
				} else {
					$filter_owner = 0; // Invalid user ID — reset.
				}
			}
		} elseif ( is_user_logged_in() ) {
			if ( '1' === $this->get_sanitized_query_text( 'almgr_my_assets' ) ) {
				$filter_my_assets = true;
				$filter_owner     = get_current_user_id();
			}
		}
		// Pagination.
		$per_page     = max( 1, (int) $attributes['per_page'] );
		$current_page = max( 1, $this->get_sanitized_query_absint( 'almgr_paged' ) );

		// Build query args.
		$query_args = array(
			'post_type'      => ALMGR_ASSET_CPT_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
		);
		// Add search term if present.
		if ( ! empty( $search_term ) ) {
			$query_args['s'] = $search_term;
		}

		$tax_query = array(
			'relation' => 'AND',
		);
		if ( ! empty( $filter_structure ) ) {
			$tax_query[] = array(
				'taxonomy' => ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
				'field'    => 'slug',
				'terms'    => $filter_structure,
			);
		}
		if ( ! empty( $filter_type ) ) {
			$tax_query[] = array(
				'taxonomy' => ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
				'field'    => 'slug',
				'terms'    => $filter_type,
			);
		}
		if ( ! empty( $filter_state ) ) {
			$tax_query[] = array(
				'taxonomy' => ALMGR_ASSET_STATE_TAXONOMY_SLUG,
				'field'    => 'slug',
				'terms'    => $filter_state,
			);
		}
		if ( ! empty( $filter_level ) ) {
			$tax_query[] = array(
				'taxonomy' => ALMGR_ASSET_LEVEL_TAXONOMY_SLUG,
				'field'    => 'slug',
				'terms'    => $filter_level,
			);
		}
		// Add tax_query to query args if we have filters.
		if ( count( $tax_query ) > 1 ) {
			$query_args['tax_query'] = $tax_query;
		}
		// Add meta_query to filter by owner if set.
		if ( $filter_owner > 0 ) {
			$query_args['meta_query'] = array(
				array(
					'key'     => '_almgr_current_owner',
					'value'   => $filter_owner,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			);
		}
		// Build and execute query.
		$query        = new WP_Query( $query_args );
		$assets       = array();
		$assets_count = 0;
		$total_pages  = 0;
		if ( $query->have_posts() ) {
			$assets_count = (int) $query->found_posts;
			$total_pages  = (int) $query->max_num_pages;

			// Prime the user cache for all asset owners in a single query.
			// Post meta is already cached by WP_Query; collect owner IDs from cache
			// and bulk-load user records so get_userdata() inside the loop is a cache hit.
			$owner_ids = array_filter(
				array_map(
					static fn( $p ) => (int) get_post_meta( $p->ID, '_almgr_current_owner', true ),
					$query->posts
				)
			);
			if ( ! empty( $owner_ids ) ) {
				cache_users( array_unique( $owner_ids ) );
			}

			foreach ( $query->posts as $post ) {
				$wrapper = ALMGR_Asset_Manager::get_asset_wrapper( $post->ID );
				if ( $wrapper ) {
					$assets[] = $wrapper;
				}
			}
		}
		wp_reset_postdata();
		$almgr_current_search       = $search_term;
		$almgr_default_filters_open = (bool) $this->settings->get( 'frontend.default_filters_open', false );
		$almgr_qr_scan_enabled      = (bool) $this->settings->get( 'autocomplete.qr_scan_enabled', true );
		include ALMGR_PLUGIN_DIR . 'templates/shortcodes/asset-list.php';
	}

	/**
	 * Handle the ?almgr_scan=CODE redirect.
	 *
	 * Reads the almgr_scan query parameter, resolves the asset post ID from the
	 * code, and redirects to the asset permalink. Redirects to home on failure.
	 *
	 * @return void
	 */
	public function handle_almgr_scan_redirect() {
		if ( ! isset( $_GET['almgr_scan'] ) ) {
			return;
		}
		$code    = sanitize_text_field( wp_unslash( $_GET['almgr_scan'] ) );
		$post_id = ALMGR_Asset_Manager::get_asset_id_from_code( $code );
		if ( $post_id > 0 ) {
			wp_safe_redirect( get_permalink( $post_id ) );
			exit;
		}
		// Invalid or unresolvable code: do not redirect; let WordPress render the current page normally.
	}

	/**
	 * Check if the current page shows the asset list.
	 *
	 * @return bool
	 */
	private function is_asset_list_page() {
		if ( is_post_type_archive( ALMGR_ASSET_CPT_SLUG ) ) {
			return true;
		}
		global $post;
		if ( $post && has_shortcode( $post->post_content, 'almgr_asset_list' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the current page shows a single asset detail view.
	 *
	 * @return bool
	 */
	private function is_asset_view_page() {
		if ( is_singular( ALMGR_ASSET_CPT_SLUG ) ) {
			return true;
		}
		global $post;
		if ( $post && has_shortcode( $post->post_content, 'almgr_asset_view' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Render the asset view template.
	 *
	 * @param int $asset_id Asset post ID.
	 * @return void
	 */
	private function render_asset_view_template( $asset_id ) {
		// Get asset wrapper.
		$asset = ALMGR_Asset_Manager::get_asset_wrapper( $asset_id );

		if ( ! $asset ) {
			echo '<p class="almgr-error">' . esc_html__( 'Asset not found.', 'asset-lending-manager' ) . '</p>';
			return;
		}

		// Include template.
		$template_path = trailingslashit( ALMGR_PLUGIN_DIR ) . 'templates/shortcodes/asset-view.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
