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
	 * Maximum length accepted for frontend search query text.
	 *
	 * @var int
	 */
	const SEARCH_QUERY_MAX_LENGTH = 200;

	/**
	 * Maximum length accepted for QR scan query text.
	 *
	 * @var int
	 */
	const SCAN_QUERY_MAX_LENGTH = 200;

	/**
	 * Settings manager instance.
	 *
	 * @var ALMGR_Settings_Manager
	 */
	private $settings;

	/**
	 * Shared asset catalog query service.
	 *
	 * @var ALMGR_Asset_Read_Service
	 */
	private $asset_reads;

	/**
	 * Shared asset detail service.
	 *
	 * @var ALMGR_Asset_Detail_Service
	 */
	private $asset_details;

	/**
	 * Shared asset history service.
	 *
	 * @var ALMGR_Asset_History_Service
	 */
	private $asset_history;

	/**
	 * Shared member-held asset service.
	 *
	 * @var ALMGR_Member_Assets_Service
	 */
	private $member_assets;

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Settings_Manager      $settings      Plugin settings instance.
	 * @param ALMGR_Asset_Read_Service    $asset_reads   Shared asset read service.
	 * @param ALMGR_Asset_Detail_Service  $asset_details Shared asset detail service.
	 * @param ALMGR_Asset_History_Service $asset_history Shared asset history service.
	 * @param ALMGR_Member_Assets_Service $member_assets Shared member-held asset service.
	 */
	public function __construct( ALMGR_Settings_Manager $settings, ALMGR_Asset_Read_Service $asset_reads, ALMGR_Asset_Detail_Service $asset_details, ALMGR_Asset_History_Service $asset_history, ALMGR_Member_Assets_Service $member_assets ) {
		$this->settings      = $settings;
		$this->asset_reads   = $asset_reads;
		$this->asset_details = $asset_details;
		$this->asset_history = $asset_history;
		$this->member_assets = $member_assets;
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
		add_filter( 'query_vars', array( $this, 'register_frontend_query_vars' ) );
		// Register shortcodes.
		add_shortcode( 'almgr_asset_list', array( $this, 'shortcode_asset_list' ) );
		add_shortcode( 'almgr_asset_view', array( $this, 'shortcode_asset_view' ) );
		add_shortcode( 'almgr_asset_history', array( $this, 'shortcode_asset_history' ) );
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
		// Block themes render the full page via their own template pipeline; injecting
		// a custom PHP template breaks header/footer styling. Users should place the
		// [almgr_asset_list] / [almgr_asset_view] shortcode on a dedicated page instead.
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return $template;
		}
		if ( is_post_type_archive( ALMGR_ASSET_CPT_SLUG ) ) {
			return $this->locate_template( 'archive-almgr-asset.php', $template );
		}
		if ( is_singular( ALMGR_ASSET_CPT_SLUG ) ) {
			return $this->locate_template( 'single-almgr-asset.php', $template );
		}
		$history_page_id = (int) $this->settings->get( 'frontend.asset_history_page_id' );
		if ( $history_page_id > 0 && is_page( $history_page_id ) ) {
			return $this->locate_template( 'page-asset-history.php', $template );
		}
		return $template;
	}

	/**
	 * Register ALMGR frontend query vars used by shortcode-based pages.
	 *
	 * @param array $query_vars Public query vars.
	 * @return array
	 */
	public function register_frontend_query_vars( $query_vars ) {
		$query_vars[] = 'almgr_asset_id';
		$query_vars[] = 'almgr_per_page';
		$query_vars[] = 'almgr_paged';

		return $query_vars;
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
				'qrLabelTitleMaxLength'       => ALMGR_QR_LABEL_TITLE_MAX_LENGTH,
				'requestMessageMaxLength'     => (int) $this->settings->get( 'loans.request_message_max_length', 500 ),
				'rejectionMessageMaxLength'   => (int) $this->settings->get( 'loans.rejection_message_max_length', 500 ),
				'directAssignReasonMaxLength' => (int) $this->settings->get( 'loans.direct_assign_reason_max_length', 500 ),
				'changeStateNotesMaxLength'   => (int) $this->settings->get( 'loans.change_state_notes_max_length', 500 ),
			)
		);

		// Enqueue contact form JS only on asset detail pages.
		if ( $this->is_asset_view_page() ) {
			global $post;
			$almgr_contact_asset_id = is_singular( ALMGR_ASSET_CPT_SLUG )
				? get_queried_object_id()
				: ( $post ? $post->ID : 0 );
			wp_enqueue_script(
				'almgr-contact-form',
				ALMGR_PLUGIN_URL . 'assets/js/alm-contact-form.js',
				array(),
				ALMGR_VERSION,
				true
			);
			wp_localize_script(
				'almgr-contact-form',
				'almgrContact',
				array(
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'nonce'        => wp_create_nonce( 'almgr_contact_nonce' ),
					'assetId'      => $almgr_contact_asset_id,
					'errorMessage' => __( 'Error while sending. Please try again.', 'asset-lending-manager' ),
				)
			);
		}

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

		// Enqueue jsQR library on pages that show the QR scanner button.
		if ( $this->is_asset_list_page() || $this->is_asset_history_page() ) {
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
		if ( $post && ( has_shortcode( $post->post_content, 'almgr_asset_list' ) || has_shortcode( $post->post_content, 'almgr_asset_view' ) || has_shortcode( $post->post_content, 'almgr_asset_history' ) ) ) {
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL-based filter parameter; no state change occurs.
		if ( ! isset( $_GET[ $key ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL-based filter parameter; no state change occurs.
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
	 * Get a sanitized and taxonomy-validated slug from query string.
	 *
	 * @param string $key      Query-string key.
	 * @param string $taxonomy Taxonomy slug used to validate the term.
	 * @return string
	 */
	private function get_validated_query_term_slug( $key, $taxonomy ) {
		$term_slug = $this->get_sanitized_query_slug( $key );

		if ( '' === $term_slug ) {
			return '';
		}

		$term = term_exists( $term_slug, $taxonomy );
		if ( 0 === $term || null === $term || is_wp_error( $term ) ) {
			return '';
		}

		return $term_slug;
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
		// Read and sanitize search parameter. Plugin-scoped 'almgr_search' avoids colliding
		// with the WordPress-reserved 's' query var; 's' is kept as a fallback for
		// previously bookmarked/shared links.
		$search_term = $this->get_sanitized_query_text( 'almgr_search' );
		if ( '' === $search_term ) {
			$search_term = $this->get_sanitized_query_text( 's' );
		}
		if ( mb_strlen( $search_term ) > self::SEARCH_QUERY_MAX_LENGTH ) {
			$search_term = mb_substr( $search_term, 0, self::SEARCH_QUERY_MAX_LENGTH );
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
	 * Shortcode handler for the full asset history page.
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_asset_history( $attributes ) {
		if ( ! current_user_can( ALMGR_EDIT_ASSET ) ) {
			return '<p class="almgr-error">' . esc_html__( 'You do not have permission to view asset history.', 'asset-lending-manager' ) . '</p>';
		}

		$attributes = shortcode_atts(
			array(),
			$attributes,
			'almgr_asset_history'
		);

		$almgr_asset_id     = $this->get_sanitized_query_absint( 'almgr_asset_id' );
		$almgr_per_page     = $this->get_sanitized_query_absint( 'almgr_per_page' );
		$almgr_current_page = max( 1, $this->get_sanitized_query_absint( 'almgr_paged' ) );
		$almgr_per_page     = in_array( $almgr_per_page, array( 10, 20, 50, 100, 200 ), true ) ? $almgr_per_page : 20;
		$almgr_asset_title  = '';
		$almgr_history      = array();
		$almgr_total        = 0;
		$almgr_total_pages  = 0;

		if ( $almgr_asset_id > 0 ) {
			$almgr_asset_post = get_post( $almgr_asset_id );
			if ( $almgr_asset_post instanceof WP_Post && ALMGR_ASSET_CPT_SLUG === $almgr_asset_post->post_type ) {
				$almgr_asset_title  = get_the_title( $almgr_asset_post );
				$almgr_caller_id    = get_current_user_id();
				$almgr_result       = $this->asset_history->get_history( $almgr_asset_id, $almgr_caller_id, $almgr_per_page, $almgr_current_page );
				$almgr_history      = $almgr_result->get_items();
				$almgr_total        = $almgr_result->get_total();
				$almgr_total_pages  = $almgr_result->get_pages();
				$almgr_current_page = $almgr_result->get_page();
			} else {
				$almgr_asset_id = 0;
			}
		}

		ob_start();
		$this->render_asset_history_template(
			array(
				'almgr_asset_id'        => $almgr_asset_id,
				'almgr_asset_title'     => $almgr_asset_title,
				'almgr_asset_url'       => $almgr_asset_id > 0 ? $this->build_asset_link( $almgr_asset_id ) : '',
				'almgr_per_page'        => $almgr_per_page,
				'almgr_current_page'    => $almgr_current_page,
				'almgr_history'         => $almgr_history,
				'almgr_total'           => $almgr_total,
				'almgr_total_pages'     => $almgr_total_pages,
				'almgr_qr_scan_enabled' => (bool) $this->settings->get( 'autocomplete.qr_scan_enabled', true ),
			)
		);

		return ob_get_clean();
	}

	/**
	 * Return shared history data for the asset detail template.
	 *
	 * @param int $asset_id Asset ID.
	 * @param int $user_id  Current actor ID.
	 * @param int $per_page Rows per page.
	 * @param int $page     Current page.
	 * @return ALMGR_Asset_History_Result
	 */
	public function get_asset_history_for_template( $asset_id, $user_id = 0, $per_page = 20, $page = 1 ) {
		return $this->asset_history->get_history( $asset_id, $user_id, $per_page, $page );
	}

	/**
	 * Build the frontend URL for a single asset detail page.
	 *
	 * When frontend.asset_view_page_id is configured, links point to that page
	 * with ?asset=slug so [almgr_asset_view] can resolve the asset from the query
	 * string. Falls back to the standard CPT permalink when the setting is not set.
	 *
	 * @param int $post_id Asset post ID.
	 * @return string Absolute URL.
	 */
	private function build_asset_link( $post_id ) {
		$view_page_id = (int) $this->settings->get( 'frontend.asset_view_page_id', 0 );
		if ( $view_page_id > 0 ) {
			$post = get_post( $post_id );
			if ( $post && ! empty( $post->post_name ) ) {
				$page_url = get_permalink( $view_page_id );
				if ( $page_url ) {
					return add_query_arg( 'asset', rawurlencode( $post->post_name ), $page_url );
				}
			}
		}
		return (string) get_permalink( $post_id );
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
		$filter_structure = $this->get_validated_query_term_slug( 'almgr_structure', ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG );

		// Apply default kit filter when enabled and the user has not explicitly set a structure filter.
		$almgr_structure_is_default = false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query param for display filtering.
		if ( '' === $filter_structure && ! isset( $_GET['almgr_structure'] ) ) {
			if ( (bool) $this->settings->get( 'frontend.default_kit_filter', false ) ) {
				if ( term_exists( 'kit', ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG ) ) {
					$filter_structure           = 'kit';
					$almgr_structure_is_default = true;
				}
			}
		}

		$filter_type  = $this->get_validated_query_term_slug( 'almgr_type', ALMGR_ASSET_TYPE_TAXONOMY_SLUG );
		$filter_state = $this->get_validated_query_term_slug( 'almgr_state', ALMGR_ASSET_STATE_TAXONOMY_SLUG );
		$filter_level = $this->get_validated_query_term_slug( 'almgr_level', ALMGR_ASSET_LEVEL_TAXONOMY_SLUG );
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

		$query_filters = array(
			'page'      => $current_page,
			'per_page'  => $per_page,
			'search'    => $search_term,
			'structure' => $filter_structure,
			'type'      => $filter_type,
			'state'     => $filter_state,
			'level'     => $filter_level,
		);
		$result        = $filter_owner > 0
			? $this->member_assets->get_assets_for_member( get_current_user_id(), $filter_owner, $query_filters )
			: $this->asset_reads->get_assets( $query_filters );
		$assets        = array();
		$assets_count  = 0;
		$total_pages   = 0;
		$asset_records = is_wp_error( $result ) ? array() : $result->get_items();
		if ( ! is_wp_error( $result ) && ! empty( $asset_records ) ) {
			$assets_count = $result->get_total();
			$total_pages  = $result->get_pages();

			foreach ( $result->get_items() as $asset ) {
				$asset->permalink = $this->build_asset_link( $asset->id );
				foreach ( $asset->parent_kits as &$almgr_kit ) {
					$almgr_kit['permalink'] = $this->build_asset_link( $almgr_kit['id'] );
				}
				unset( $almgr_kit );
				$assets[] = $asset;
			}
		}
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- QR code URL is printed on physical labels; nonce cannot be embedded in a static QR code.
		if ( ! isset( $_GET['almgr_scan'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- QR code URL is printed on physical labels; nonce cannot be embedded in a static QR code.
		$code    = sanitize_text_field( wp_unslash( $_GET['almgr_scan'] ) );
		$code    = substr( $code, 0, self::SCAN_QUERY_MAX_LENGTH );
		$post_id = ALMGR_Asset_Manager::get_asset_id_from_code( $code );
		if ( $post_id > 0 ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- almgr_dest is appended by the JS scanner when on the history page; no user-submitted form involved.
			$dest = isset( $_GET['almgr_dest'] ) ? sanitize_key( wp_unslash( $_GET['almgr_dest'] ) ) : '';
			if ( 'history' === $dest ) {
				$history_page_id = (int) $this->settings->get( 'frontend.asset_history_page_id', 0 );
				if ( $history_page_id > 0 ) {
					$history_url = get_permalink( $history_page_id );
					if ( $history_url ) {
						wp_safe_redirect( add_query_arg( 'almgr_asset_id', $post_id, $history_url ) );
						exit;
					}
				}
			}
			wp_safe_redirect( $this->build_asset_link( $post_id ) );
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
	 * Check if the current page shows the asset history shortcode.
	 *
	 * @return bool
	 */
	private function is_asset_history_page() {
		global $post;
		return $post && has_shortcode( $post->post_content, 'almgr_asset_history' );
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
		$asset = $this->asset_details->get_asset_detail( $asset_id );

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

	/**
	 * Render the asset history template.
	 *
	 * @param array $template_args Variables passed to the template.
	 * @return void
	 */
	private function render_asset_history_template( array $template_args ) {
		$almgr_template_args = $template_args;
		$template_path       = trailingslashit( ALMGR_PLUGIN_DIR ) . 'templates/shortcodes/asset-history.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
