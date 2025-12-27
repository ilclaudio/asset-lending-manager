<?php
/**
 * ALM Admin Manager.
 *
 * Handles admin UI customizations and access restrictions for specific roles.
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
	 * Register hooks.
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'redirect_restricted_users' ) );
		add_action( 'admin_menu', array( $this, 'remove_menus' ), 999 );
		// add_action( 'after_setup_theme', array( $this, 'disable_admin_bar' ) );
	}

	/**
	 * Redirect restricted users away from the dashboard or profile pages.
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
	 */
	public function remove_menus() {
		if ( $this->is_restricted_user() ) {
			remove_menu_page( 'index.php' ); // Dashboard.
			remove_menu_page( 'profile.php' ); // Profile.
			// remove_menu_page( 'edit.php' ); // Posts
			// remove_menu_page( 'upload.php' ); // Media.
			// remove_menu_page( 'edit.php?post_type=page' ); // Pages.
			// remove_menu_page( 'tools.php' ); // Tools.
		}
	}

	// /**
	//  * Disable the admin bar for restricted users.
	//  */
	// public function disable_admin_bar() {
	// 	if ( $this->is_restricted_user() ) {
	// 		show_admin_bar( false );
	// 	}
	// }

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
