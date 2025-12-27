<?php
/**
 * Plugin Manager.
 *
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main module of the plugin to bootstrap and to coordinate all the other modules.
 */
class ALM_Plugin_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var ALM_Plugin_Manager|null
	 */
	private static $instance = null;

	/**
	 * Registered plugin modules.
	 *
	 * @var array
	 */
	private $modules = array();

	/**
	 * Get singleton instance.
	 *
	 * @return ALM_Plugin_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Private to enforce singleton.
	 */
	private function __construct() {
		$this->init_modules();
	}

	/**
	 * Plugin bootstrap.
	 *
	 * Called once from the main plugin file.
	 */
	public function init() {
		$this->check_dependencies();
		$this->init_i18n();
		// Register all the other modules of the plugin.
		$this->register_modules();
		// Register the main menu of the plugin.
		add_action(
			'admin_menu',
			array( $this, 'register_alm_custom_menu' ),
		);
		// Fix the layout of the main menu for taxonomies entries.
		add_action(
			'parent_file',
			array( $this, 'keep_alm_taxonomy_menu_open' ),
		);
		// Reload default taxonomy terms.
		add_action(
			'admin_post_alm_reload_default_terms',
			array( $this, 'handle_reload_default_terms' ),
		);

	}

	/**
	 * Check if all dependencies are satisfied, if not show an admin notice.
	 *
	 * @return void
	 */
	private function check_dependencies() {
		if ( ! class_exists( 'ACF' ) ) {
			add_action(
				'admin_notices',
				function() {
					$msg = __( 'The ALM plugin requires the plugin ACF installed and enabled.', 'asset-lending-manager' );
					echo '<div class="notice notice-error"><p>';
					echo esc_html( $msg );
					echo '</p></div>';
				}
			);
		}
	}

	/**
	 * Load plugin translations.
	 *
	 * @return void
	 */
	private function init_i18n() {
		if ( function_exists( 'load_plugin_textdomain' ) ) {
			load_plugin_textdomain(
				ALM_TEXT_DOMAIN,
				false,
				dirname( plugin_basename( ALM_PLUGIN_FILE ) ) . '/languages/'
			);
		}
	}

	/**
	 * Instantiate plugin modules.
	 *
	 * @return void
	 */
	private function init_modules() {
		if ( empty( $this->modules ) ) {
			$this->modules = array(
				new ALM_Settings_Manager(),
				new ALM_Role_Manager(),
				new ALM_Device_Manager(),
				new ALM_Loan_Manager(),
				new ALM_Notification_Manager(),
				new ALM_Frontend_Manager(),
				new ALM_Admin_Manager(),
			);
		}
	}

	/**
	 * Register all the plugin modules.
	 *
	 * @return void
	 */
	private function register_modules() {
		// Register the modules.
		if ( function_exists( 'add_action' ) ) {
			foreach ( $this->modules as $module ) {
				$module->register();
			}
		}
	}

	/**
	 * Return the modules used by the plugin.
	 */
	public function get_modules() {
		return $this->modules;
	}

	// /**
	//  * Return a specific module.
	//  */
	// public function get_module( $name ) {
	// 	return $this->modules[ $name ] ?? null;
	// }

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws Exception If unserialization is attempted.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Plugin activation handler.
	 *
	 * Called by WordPress on plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		// Activate all the other modules of the plugin.
		foreach ( $this->modules as $module ) {
			if ( method_exists( $module, 'activate' ) ) {
				$module->activate();
			}
		}
	}

	/**
	 * Plugin deactivation handler.
	 *
	 * Called by WordPress on plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate() {
		// $this->init_modules();
		// Deactivate all the other modules of the plugin.
		foreach ( $this->modules as $module ) {
			if ( method_exists( $module, 'deactivate' ) ) {
				$module->deactivate();
			}
		}
	}

	/**
	 * Register the main admin menu for the plugin.
	 *
	 * This menu acts as a container for all ALM-related CPTs and pages.
	 *
	 * @return void
	 */
	public function register_alm_custom_menu() {

		$slug_main_menu = ALM_SLUG_MAIN_MENU;

		add_menu_page(
			__( 'Asset Lending Manager', 'asset-lending-manager' ),  // Page title.
			__( 'ALM', 'asset-lending-manager' ),                    // Menu title.
			ALM_EDIT_DEVICE,                                         // Capability.
			$slug_main_menu,                                         // Menu slug.
			array( $this, 'get_plugin_presentation' ),               // Callback (handled by CPT).
			ALM_MAIN_MENU_ICON,                                      // Icon.
			30                                                       // Position.
		);

		// List of the devices.
		add_submenu_page(
			$slug_main_menu,                            // parent slug.
			__( 'Devices', 'asset-lending-manager' ),   // page title.
			__( 'Devices', 'asset-lending-manager' ),   // sub-menu title.
			ALM_VIEW_DEVICES,                           // capability.
			'edit.php?post_type=' . ALM_DEVICE_CPT_SLUG // link.
		);

		// Add a book.
		add_submenu_page(
			$slug_main_menu,
			__( 'Add a device', 'asset-lending-manager' ),
			__( 'Add a device', 'asset-lending-manager' ),
			ALM_EDIT_DEVICE,
			'post-new.php?post_type=' . ALM_DEVICE_CPT_SLUG
		);

		// Taxonomy: device structure.
		add_submenu_page(
			$slug_main_menu,
			__( 'Device Structure', 'asset-lending-manager' ),
			__( 'Device Structure', 'asset-lending-manager' ),
			ALM_EDIT_DEVICE,
			'edit-tags.php?taxonomy=' . ALM_DEVICE_STRUCTURE_TAXONOMY_SLUG,
		);

		// Taxonomy: device type.
		add_submenu_page(
			$slug_main_menu,
			__( 'Device Type', 'asset-lending-manager' ),
			__( 'Device Type', 'asset-lending-manager' ),
			ALM_EDIT_DEVICE,
			'edit-tags.php?taxonomy=' . ALM_DEVICE_TYPE_TAXONOMY_SLUG,
		);

		// Taxonomy: device state.
		add_submenu_page(
			$slug_main_menu,
			__( 'Device State', 'asset-lending-manager' ),
			__( 'Device State', 'asset-lending-manager' ),
			ALM_EDIT_DEVICE,
			'edit-tags.php?taxonomy=' . ALM_DEVICE_STATE_TAXONOMY_SLUG,
		);

		// Taxonomy: device levels.
		add_submenu_page(
			$slug_main_menu,
			__( 'Device Level', 'asset-lending-manager' ),
			__( 'Device Level', 'asset-lending-manager' ),
			ALM_EDIT_DEVICE,
			'edit-tags.php?taxonomy=' . ALM_DEVICE_LEVEL_TAXONOMY_SLUG,
		);

		// Link to the page to reload default data.
		add_submenu_page(
			$slug_main_menu,
			__( 'ALM Tools', 'asset-lending-manager' ),
			__( 'Tools', 'asset-lending-manager' ),
			ALM_EDIT_DEVICE,
			'alm-tools',
			array( $this, 'render_tools_page' )
		);
	}

	/**
	 * Return the name of the parent of a taxonomy in the menu.
	 *
	 * @param [type] $parent_file
	 * @return void
	 */
	public function keep_alm_taxonomy_menu_open( $parent_file ) {
		global $current_screen;
		$taxonomy = $current_screen->taxonomy;
		if ( in_array( $taxonomy, ALM_CUSTOM_TAXONOMIES ) ) {
			$parent_file = ALM_SLUG_MAIN_MENU;
		}
		return $parent_file;
	}

	/**
	 * Render the presentation page of the plugin.
	 *
	 * @return void
	 */
	public function get_plugin_presentation() {
		if ( ! current_user_can( ALM_EDIT_DEVICE ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'asset-lending-manager' ) );
		}
		require_once ALM_PLUGIN_DIR . 'admin/plugin-main-page.php';
	}

	/**
	 * Rendere the tools page.
	 *
	 * @return void
	 */
	public function render_tools_page() {
		if ( ! current_user_can( ALM_EDIT_DEVICE ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'asset-lending-manager' ) );
		}
		require_once ALM_PLUGIN_DIR . 'admin/alm-tools-page.php';
	}

	/**
	 * Handles the admin action to reload default taxonomy terms.
	 *
	 * This method is triggered via admin-post.php and is responsible for:
	 * - validating user capabilities
	 * - verifying the security nonce
	 * - executing an idempotent setup operation
	 * - redirecting back to the tools page with a status flag
	 *
	 * @return void
	 */
	public function handle_reload_default_terms() {
		$user_id = get_current_user_id();
		ALM_Logger::info(
			'Reload default terms action triggered.',
			array( 'user_id' => $user_id )
		);

		// Ensure the current user has the required capability.
		// This check is mandatory even if the menu item is already protected.
		if ( ! current_user_can( ALM_EDIT_DEVICE ) ) {
				wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}

		// Verify the nonce to protect against CSRF attacks.
		check_admin_referer( 'alm_reload_terms_action', 'alm_reload_terms_nonce' );

		try {
			ALM_Logger::debug(
				'Starting default terms setup.',
				array( 'user_id' => $user_id )
			);

			require_once plugin_dir_path( __FILE__ ) . 'class-alm-installer.php';
			// Execute the idempotent setup routine.
			// Safe to call multiple times.
			ALM_Installer::create_default_terms();
			ALM_Logger::info(
				'Default terms successfully reloaded.',
				array( 'user_id' => $user_id )
			);
			$status = 'success';
		} catch ( Exception $e ) {
			ALM_Logger::error(
				'Error while reloading default terms.',
				array(
					'user_id'   => $user_id,
					'exception' => $e->getMessage(),
				)
			);
			$status = 'error';
		}

		// Redirect back to the tools page with a status indicator.
		// No output must be sent before this redirect.
		wp_safe_redirect(
			add_query_arg(
				'alm_status',
				$status,
				admin_url( 'admin.php?page=alm-tools' )
			)
		);
		exit;
	}

}
