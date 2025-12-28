<?php
/**
 * ALM Admin Manager.
 *
 * Handles admin UI customizations and access restrictions for specific roles.
 *
 * Responsibilities:
 * - Manage admin menu visibility and redirects for restricted users.
 * - Enqueue admin CSS and JS for ALM pages.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manager of the plugin backoffice.
 */
class ALM_Admin_Manager {

	/**
	 * Roles affected by admin restrictions.
	 *
	 * @var array
	 */
	private $restricted_roles = array( ALM_MEMBER_ROLE );

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public function activate() {
		// No activation tasks needed for frontend.
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Admin UI restrictions.
		add_action( 'admin_init', array( $this, 'redirect_restricted_users' ) );
		add_action( 'admin_menu', array( $this, 'remove_menus' ), 999 );
		// Enqueue admin assets (CSS/JS).
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Redirect restricted users away from the dashboard or profile pages.
	 *
	 * @return void
	 */
	public function redirect_restricted_users() {
		if ( $this->is_restricted_user() && ! defined( 'DOING_AJAX' ) ) {
			global $pagenow;
			$restricted_pages = array( 'index.php', 'profile.php', 'tools.php' );
			if ( in_array( $pagenow, $restricted_pages, true ) ) {
				wp_redirect( home_url() );
				exit;
			}
		}
	}

	/**
	 * Remove menus for restricted users.
	 *
	 * @return void
	*/
	public function remove_menus() {
		if ( $this->is_restricted_user() ) {
			remove_menu_page( 'index.php' ); // Dashboard.
			remove_menu_page( 'profile.php' ); // Profile.
			// remove_menu_page( 'edit.php' ); // Posts.
			// remove_menu_page( 'upload.php' ); // Media.
			// remove_menu_page( 'edit.php?post_type=page' ); // Pages.
			// remove_menu_page( 'tools.php' ); // Tools.
		}
	}

	/**
	 * Enqueue admin CSS and JS for ALM pages.
	 *
	 * Loads assets only on plugin-related admin pages:
	 * - Device post type pages (list, edit, add)
	 * - ALM custom admin pages
	 * - ALM taxonomy pages
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Load only on ALM admin pages.
		if ( ! $this->is_alm_admin_page( $hook ) ) {
			return;
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'alm-admin-devices',
			ALM_PLUGIN_URL . 'assets/css/admin-devices.css',
			array(),
			ALM_VERSION,
			'all'
		);

		// Enqueue JS.
		wp_enqueue_script(
			'alm-admin-devices',
			ALM_PLUGIN_URL . 'assets/js/admin-devices.js',
			array( 'jquery' ),
			ALM_VERSION,
			true
		);

		// Pass data from PHP to JavaScript (useful for AJAX).
		wp_localize_script(
			'alm-admin-devices',
			'almAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'alm_admin_nonce' ),
			)
		);
	}

	/**
	 * Check if current admin page is ALM-related.
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool True if on ALM admin page.
	 */
	private function is_alm_admin_page( $hook ) {
		global $post_type;
		// Device CPT pages (edit, list, add new).
		if ( ALM_DEVICE_CPT_SLUG === $post_type ) {
			return true;
		}
		// ALM custom admin pages (main menu, tools, etc).
		if ( strpos( $hook, 'alm' ) !== false ) {
			return true;
		}
		// ALM taxonomies pages.
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->taxonomy, ALM_CUSTOM_TAXONOMIES, true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Helper to check if the current user is in a restricted role.
	 *
	 * @return bool
	 */
	private function is_restricted_user() {
		$user = wp_get_current_user();
		if ( ( ! $user ) || empty( $user->ID ) ) {
			return false;
		}
		foreach ( $this->restricted_roles as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return true;
			}
		}
		return false;
	}

}
