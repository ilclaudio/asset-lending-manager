<?php
/**
 * Asset Lending Manager - Frontend Manager
 *
 * Handles frontend rendering for ALM devices using shortcodes.
 *
 * Responsibilities:
 * - Provide fallback templates for alm_device CPT.
 * - Register shortcodes for device list and device view.
 * - Enqueue frontend CSS and JS for device pages.
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
		add_filter( 'template_include', array( $this, 'load_device_template' ) );
		// Register shortcodes.
		add_shortcode( 'alm_device_list', array( $this, 'shortcode_device_list' ) );
		add_shortcode( 'alm_device_view', array( $this, 'shortcode_device_view' ) );
		// Enqueue frontend assets (CSS/JS).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Load plugin templates for alm_device archive and single views
	 * if the active theme does not provide them.
	 *
	 * @param string $template The path to the template WordPress intends to use.
	 * @return string
	 */
	public function load_device_template( $template ) {
		if ( is_post_type_archive( ALM_DEVICE_CPT_SLUG ) ) {
			return $this->locate_template( 'archive-alm_device.php', $template );
		}
		if ( is_singular( ALM_DEVICE_CPT_SLUG ) ) {
			return $this->locate_template( 'single-alm_device.php', $template );
		}
		return $template;
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
	 * Enqueue frontend CSS and JS for device pages.
	 *
	 * Loads assets only on pages where devices are displayed:
	 * - Archive page (device list)
	 * - Single device page
	 * - Pages with device shortcodes
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Load only on device-related pages.
		if ( ! $this->is_device_page() ) {
			return;
		}
		// Enqueue CSS.
		wp_enqueue_style(
			'alm-frontend-devices',
			ALM_PLUGIN_URL . 'assets/css/frontend-devices.css',
			array(),
			ALM_VERSION,
			'all'
		);
		// Enqueue JS.
		wp_enqueue_script(
			'alm-frontend-devices',
			ALM_PLUGIN_URL . 'assets/js/frontend-devices.js',
			array( 'jquery' ),
			ALM_VERSION,
			true
		);
		// Pass data from PHP to JavaScript (useful for AJAX).
		wp_localize_script(
			'alm-frontend-devices',
			'almFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'alm_frontend_nonce' ),
			)
		);
	}

	/**
	 * Check if current page is device-related.
	 *
	 * @return bool True if on device archive, single, or page with device shortcodes.
	 */
	private function is_device_page() {
		// Archive or single device page.
		if ( is_post_type_archive( ALM_DEVICE_CPT_SLUG ) || is_singular( ALM_DEVICE_CPT_SLUG ) ) {
			return true;
		}

		// Page with device shortcodes.
		global $post;
		if ( $post && ( has_shortcode( $post->post_content, 'alm_device_list' ) || has_shortcode( $post->post_content, 'alm_device_view' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Shortcode handler for device list.
	 *
	 * Usage: [alm_device_list]
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_device_list( $attributes ) {
		// Parse shortcode attributes (for future extensions like filters).
		$attributes = shortcode_atts(
			array(
				'posts_per_page' => -1,
			),
			$attributes,
			'alm_device_list'
		);
		// Read and sanitize search parameter.
		$search_term = '';
		if ( isset( $_GET['s'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}
		// Start output buffering.
		ob_start();
		// Render the device list template.
		$this->render_device_list_template( $attributes, $search_term );
		return ob_get_clean(); // End output buffering.
	}

	/**
	 * Shortcode handler for single device view.
	 *
	 * Usage:
	 * - [alm_device_view slug="binocolo"]
	 * - [alm_device_view] (uses query string ?device=binocolo or current post)
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_device_view( $attributes ) {
		// Parse shortcode attributes.
		$attributes = shortcode_atts(
			array(
				'slug' => '',
			),
			$attributes,
			'alm_device_view'
		);

		// Determine device ID.
		$device_id = $this->get_device_id_from_context( $attributes['slug'] );

		if ( ! $device_id ) {
			return '<p class="alm-error">' . esc_html__( 'Device not found.', 'asset-lending-manager' ) . '</p>';
		}

		// Start output buffering.
		ob_start();

		// Render the device view template.
		$this->render_device_view_template( $device_id );

		return ob_get_clean();
	}

	/**
	 * Get device ID from slug, query string, or current post context.
	 *
	 * Priority:
	 * 1. Slug from shortcode attribute
	 * 2. Slug from query string (?device=binocolo)
	 * 3. Current post ID (if in single context)
	 *
	 * @param string $slug Device slug from shortcode attribute.
	 * @return int|null Device post ID or null if not found.
	 */
	private function get_device_id_from_context( $slug ) {
		// Priority 1: Slug from shortcode attribute.
		if ( ! empty( $slug ) ) {
			$device = get_page_by_path( $slug, OBJECT, ALM_DEVICE_CPT_SLUG );
			if ( $device ) {
				return $device->ID;
			}
		}
		// Priority 2: Slug from query string.
		if ( isset( $_GET['device'] ) && ! empty( $_GET['device'] ) ) {
			$query_slug = sanitize_title( $_GET['device'] );
			$device     = get_page_by_path( $query_slug, OBJECT, ALM_DEVICE_CPT_SLUG );
			if ( $device ) {
				return $device->ID;
			}
		}
		// Priority 3: Current post ID (if in single device context).
		if ( is_singular( ALM_DEVICE_CPT_SLUG ) ) {
			return get_the_ID();
		}

		return null;
	}

	/**
	 * Render device list template.
	 *
	 * @param array  $attributes  Shortcode attributes.
	 * @param string $search_term Optional search term.
	 * @return void
	 */
	protected function render_device_list_template( $attributes, $search_term = '' ) {
		if ( ! empty( $search_term ) ) {
			$query_args = array(
				'post_type'      => ALM_DEVICE_CPT_SLUG,
				'post_status'    => 'publish',
				's'              => $search_term,
				'posts_per_page' => $attributes['posts_per_page'],
			);
		} else {
			$query_args = array(
				'post_type'      => ALM_DEVICE_CPT_SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => $attributes['posts_per_page'],
			);
		}
		$query = new WP_Query( $query_args );
		$devices       = array();
		$devices_count = 0;
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$devices_count = (int) $query->found_posts;
				$wrapper = ALM_Device_Manager::get_device_wrapper( $post->ID );
				if ( $wrapper ) {
					$devices[] = $wrapper;
				}
			}
		}
		wp_reset_postdata();
		include ALM_PLUGIN_DIR . 'templates/shortcodes/device_list.php';
	}

	/**
	 * Render the device view template.
	 *
	 * @param int $device_id Device post ID.
	 * @return void
	 */
	private function render_device_view_template( $device_id ) {
		// Get device wrapper.
		$device = ALM_Device_Manager::get_device_wrapper( $device_id );

		if ( ! $device ) {
			echo '<p class="alm-error">' . esc_html__( 'Device not found.', 'asset-lending-manager' ) . '</p>';
			return;
		}

		// Include template.
		$template_path = trailingslashit( ALM_PLUGIN_DIR ) . 'templates/shortcodes/device_view.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
