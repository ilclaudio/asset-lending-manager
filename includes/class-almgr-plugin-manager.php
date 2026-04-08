<?php
/**
 * Plugin Manager.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main module of the plugin to bootstrap and to coordinate all the other modules.
 */
class ALMGR_Plugin_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var ALMGR_Plugin_Manager|null
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
	 * @return ALMGR_Plugin_Manager
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
		if ( ! $this->check_dependencies() ) {
			return;
		}
		// Configure logger with user settings (runs on plugins_loaded, safe because
		// get_defaults() defers __() calls until after the 'init' action).
		ALMGR_Logger::configure( $this->modules['settings'] );
		// Register all the other modules of the plugin.
		$this->register_modules();
		// Register the main menu of the plugin.
		add_action(
			'admin_menu',
			array( $this, 'register_almgr_custom_menu' ),
		);
		// Fix the layout of the main menu for taxonomies entries.
		add_action(
			'parent_file',
			array( $this, 'keep_almgr_taxonomy_menu_open' ),
		);
		// Reload default taxonomy terms.
		add_action(
			'admin_post_almgr_reload_default_terms',
			array( $this, 'handle_reload_default_terms' ),
		);
		// Save settings page form.
		add_action(
			'admin_post_almgr_save_settings',
			array( $this, 'handle_settings_save' ),
		);
	}

	/**
	 * Check if all dependencies are satisfied.
	 *
	 * Registers an admin notice and returns false when a required dependency
	 * is missing so that init() can abort module registration early.
	 *
	 * @return bool True if all dependencies are present, false otherwise.
	 */
	private function check_dependencies(): bool {
		if ( ! class_exists( 'ACF' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_acf_missing' ) );
			return false;
		}
		return true;
	}

	/**
	 * Admin notice shown when Advanced Custom Fields is not active.
	 *
	 * Visible to administrators only.
	 *
	 * @return void
	 */
	public function notice_acf_missing() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$install_url = admin_url( 'plugin-install.php?s=advanced+custom+fields&tab=search&type=term' );
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: %s: URL to the WordPress plugin installer search page for ACF. */
					__( '<strong>Asset Lending Manager</strong> requires <strong>Advanced Custom Fields</strong> to be installed and active. <a href="%s">Install it now</a>.', 'asset-lending-manager' ),
					esc_url( $install_url )
				),
				array(
					'strong' => array(),
					'a'      => array( 'href' => array() ),
				)
			)
		);
	}

	/**
	 * Instantiate plugin modules.
	 *
	 * @return void
	 */
	private function init_modules() {
		if ( empty( $this->modules ) ) {
			$settings      = new ALMGR_Settings_Manager();
			$loan          = new ALMGR_Loan_Manager( $settings );
			$this->modules = array(
				'settings'     => $settings,
				'role'         => new ALMGR_Role_Manager(),
				'asset'        => new ALMGR_Asset_Manager(),
				'loan'         => $loan,
				'notification' => new ALMGR_Notification_Manager( $settings ),
				'frontend'     => new ALMGR_Frontend_Manager( $settings ),
				'admin'        => new ALMGR_Admin_Manager(),
				'autocomplete' => new ALMGR_Autocomplete_Manager( $settings ),
				'rest'         => new ALMGR_REST_Manager( $settings, $loan ),
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
		return array_values( $this->modules );
	}

	/**
	 * Return a specific module.
	 *
	 * @param string $name Module key.
	 */
	public function get_module( $name ) {
		return $this->modules[ $name ] ?? null;
	}

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
	 * This menu acts as a container for all ALMGR-related CPTs and pages.
	 *
	 * @return void
	 */
	public function register_almgr_custom_menu() {

		$slug_main_menu = ALMGR_SLUG_MAIN_MENU;

		add_menu_page(
			__( 'Asset Lending Manager', 'asset-lending-manager' ),  // Page title.
			__( 'ALM', 'asset-lending-manager' ),                    // Menu title.
			ALMGR_EDIT_ASSET,                                         // Capability.
			$slug_main_menu,                                         // Menu slug.
			array( $this, 'get_plugin_presentation' ),               // Callback (handled by CPT).
			ALMGR_MAIN_MENU_ICON,                                      // Icon.
			30                                                       // Position.
		);

		// List of the assets.
		add_submenu_page(
			$slug_main_menu,                            // parent slug.
			__( 'Assets', 'asset-lending-manager' ),   // page title.
			__( 'Assets', 'asset-lending-manager' ),   // sub-menu title.
			ALMGR_VIEW_ASSETS,                           // capability.
			'edit.php?post_type=' . ALMGR_ASSET_CPT_SLUG // link.
		);

		// Add a book.
		add_submenu_page(
			$slug_main_menu,
			__( 'Add a asset', 'asset-lending-manager' ),
			__( 'Add a asset', 'asset-lending-manager' ),
			ALMGR_EDIT_ASSET,
			'post-new.php?post_type=' . ALMGR_ASSET_CPT_SLUG
		);

		// Taxonomy: asset structure.
		add_submenu_page(
			$slug_main_menu,
			__( 'Asset Structure', 'asset-lending-manager' ),
			__( 'Asset Structure', 'asset-lending-manager' ),
			ALMGR_EDIT_ASSET,
			'edit-tags.php?taxonomy=' . ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
		);

		// Taxonomy: asset type.
		add_submenu_page(
			$slug_main_menu,
			__( 'Asset Type', 'asset-lending-manager' ),
			__( 'Asset Type', 'asset-lending-manager' ),
			ALMGR_EDIT_ASSET,
			'edit-tags.php?taxonomy=' . ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
		);

		// Taxonomy: asset state.
		add_submenu_page(
			$slug_main_menu,
			__( 'Asset State', 'asset-lending-manager' ),
			__( 'Asset State', 'asset-lending-manager' ),
			ALMGR_EDIT_ASSET,
			'edit-tags.php?taxonomy=' . ALMGR_ASSET_STATE_TAXONOMY_SLUG,
		);

		// Taxonomy: asset levels.
		add_submenu_page(
			$slug_main_menu,
			__( 'Asset Level', 'asset-lending-manager' ),
			__( 'Asset Level', 'asset-lending-manager' ),
			ALMGR_EDIT_ASSET,
			'edit-tags.php?taxonomy=' . ALMGR_ASSET_LEVEL_TAXONOMY_SLUG,
		);

		// Settings page.
		add_submenu_page(
			$slug_main_menu,
			__( 'ALM Settings', 'asset-lending-manager' ),
			__( 'Settings', 'asset-lending-manager' ),
			ALMGR_EDIT_ASSET,
			'almgr-settings',
			array( $this, 'render_settings_page' )
		);

		// Link to the page to reload default data.
		add_submenu_page(
			$slug_main_menu,
			__( 'ALM Tools', 'asset-lending-manager' ),
			__( 'Tools', 'asset-lending-manager' ),
			ALMGR_EDIT_ASSET,
			'almgr-tools',
			array( $this, 'render_tools_page' )
		);
	}

	/**
	 * Return the name of the parent of a taxonomy in the menu.
	 *
	 * @param string $parent_file Current parent file slug.
	 * @return string
	 */
	public function keep_almgr_taxonomy_menu_open( $parent_file ) {
		global $current_screen;
		$taxonomy = $current_screen->taxonomy;
		if ( in_array( $taxonomy, ALMGR_CUSTOM_TAXONOMIES, true ) ) {
			$parent_file = ALMGR_SLUG_MAIN_MENU;
		}
		return $parent_file;
	}

	/**
	 * Render the presentation page of the plugin.
	 *
	 * @return void
	 */
	public function get_plugin_presentation() {
		if ( ! current_user_can( ALMGR_EDIT_ASSET ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'asset-lending-manager' ) );
		}
		require_once ALMGR_PLUGIN_DIR . 'admin/plugin-main-page.php';
	}

	/**
	 * Rendere the tools page.
	 *
	 * @return void
	 */
	public function render_tools_page() {
		if ( ! current_user_can( ALMGR_EDIT_ASSET ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'asset-lending-manager' ) );
		}
		require_once ALMGR_PLUGIN_DIR . 'admin/almgr-tools-page.php';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( ALMGR_EDIT_ASSET ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'asset-lending-manager' ) );
		}
		require_once ALMGR_PLUGIN_DIR . 'admin/almgr-settings-page.php';
	}

	/**
	 * Handle the settings form submission.
	 *
	 * Validates capability and nonce, sanitizes input per field type,
	 * respects [A] vs [A/O] access levels, then saves via set_batch().
	 *
	 * @return void
	 */
	public function handle_settings_save() {
		if ( ! current_user_can( ALMGR_EDIT_ASSET ) ) {
			wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}

		check_admin_referer( 'almgr_save_settings', 'almgr_settings_nonce' );

		$is_admin   = current_user_can( 'manage_options' );
		$active_tab = isset( $_POST['almgr_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['almgr_active_tab'] ) ) : 'email';
		$changes    = array();

		if ( 'email' === $active_tab ) {
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['email.from_name']       = sanitize_text_field( wp_unslash( $_POST['almgr_email_from_name'] ?? '' ) );
				$changes['email.from_address']    = sanitize_email( wp_unslash( $_POST['almgr_email_from_address'] ?? '' ) );
				$changes['email.system_email']    = sanitize_email( wp_unslash( $_POST['almgr_email_system_email'] ?? '' ) );
				$changes['notifications.enabled'] = isset( $_POST['almgr_notifications_enabled'] );
			}
			// [A/O] fields.
			$changes['notifications.loan_request']      = isset( $_POST['almgr_notifications_loan_request'] );
			$changes['notifications.loan_decision']     = isset( $_POST['almgr_notifications_loan_decision'] );
			$changes['notifications.loan_confirmation'] = isset( $_POST['almgr_notifications_loan_confirmation'] );
		}

		if ( 'loans' === $active_tab ) {
			// [A/O] fields.
			$changes['loans.loan_requests_enabled']   = isset( $_POST['almgr_loans_loan_requests_enabled'] );
			$changes['loans.max_active_per_user']     = max( 0, absint( wp_unslash( $_POST['almgr_loans_max_active_per_user'] ?? 0 ) ) );
			$changes['loans.allow_multiple_requests'] = isset( $_POST['almgr_loans_allow_multiple_requests'] );
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['loans.request_message_max_length']      = max( 0, absint( wp_unslash( $_POST['almgr_loans_request_message_max_length'] ?? 500 ) ) );
				$changes['loans.rejection_message_max_length']    = max( 0, absint( wp_unslash( $_POST['almgr_loans_rejection_message_max_length'] ?? 500 ) ) );
				$changes['loans.direct_assign_reason_max_length'] = max( 0, absint( wp_unslash( $_POST['almgr_loans_direct_assign_reason_max_length'] ?? 500 ) ) );
			}
		}

		if ( 'direct_assign' === $active_tab ) {
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['direct_assign.enabled']              = isset( $_POST['almgr_direct_assign_enabled'] );
				$valid_roles                                   = array( ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE );
				$posted_roles                                  = filter_input( INPUT_POST, 'almgr_direct_assign_roles', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
				$raw_roles                                     = is_array( $posted_roles ) ? array_map( 'sanitize_key', $posted_roles ) : array();
				$changes['direct_assign.allowed_target_roles'] = array_values(
					array_intersect( $raw_roles, $valid_roles )
				);
			}
		}

		if ( 'workflow' === $active_tab ) {
			// [A/O] fields.
			$changes['workflow.cancel_concurrent_requests_on_assign']        = isset( $_POST['almgr_workflow_cancel_concurrent'] );
			$changes['workflow.cancel_component_requests_when_kit_assigned'] = isset( $_POST['almgr_workflow_cancel_component_requests'] );
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['workflow.automatic_operations_actor_user_id'] = max( 1, absint( wp_unslash( $_POST['almgr_workflow_actor_user_id'] ?? 1 ) ) );
			}
		}

		if ( 'frontend' === $active_tab ) {
			// [A/O] fields.
			$changes['frontend.asset_list_per_page']  = min( 100, max( 1, absint( wp_unslash( $_POST['almgr_frontend_asset_list_per_page'] ?? ALMGR_ASSET_LIST_PER_PAGE ) ) ) );
			$changes['frontend.default_filters_open'] = isset( $_POST['almgr_frontend_default_filters_open'] );
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['frontend.assets_page_id']          = max( 0, absint( wp_unslash( $_POST['almgr_frontend_assets_page_id'] ?? 0 ) ) );
				$changes['frontend.login_redirect_page_id']  = max( 0, absint( wp_unslash( $_POST['almgr_frontend_login_redirect_page_id'] ?? 0 ) ) );
				$changes['frontend.logout_redirect_page_id'] = max( 0, absint( wp_unslash( $_POST['almgr_frontend_logout_redirect_page_id'] ?? 0 ) ) );
			}
		}

		if ( 'autocomplete' === $active_tab ) {
			// [A/O] fields.
			$changes['autocomplete.min_chars']          = min( 10, max( 1, absint( wp_unslash( $_POST['almgr_autocomplete_min_chars'] ?? 3 ) ) ) );
			$changes['autocomplete.max_results']        = min( 20, max( 1, absint( wp_unslash( $_POST['almgr_autocomplete_max_results'] ?? ALMGR_AUTOCOMPLETE_MAX_RESULTS ) ) ) );
			$changes['autocomplete.description_length'] = min( 200, max( 0, absint( wp_unslash( $_POST['almgr_autocomplete_description_length'] ?? ALMGR_AUTOCOMPLETE_DESC_LENGTH ) ) ) );
			$changes['autocomplete.qr_scan_enabled']    = isset( $_POST['almgr_autocomplete_qr_scan_enabled'] );
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['autocomplete.public_assets_endpoint_enabled'] = isset( $_POST['almgr_autocomplete_public_endpoint'] );
			}
		}

		if ( 'logging' === $active_tab && $is_admin ) {
			$level_whitelist                       = array( 'debug', 'info', 'warning', 'error' );
			$raw_level                             = sanitize_key( wp_unslash( $_POST['almgr_logging_level'] ?? 'error' ) );
			$changes['logging.enabled']            = isset( $_POST['almgr_logging_enabled'] );
			$changes['logging.level']              = in_array( $raw_level, $level_whitelist, true ) ? $raw_level : 'error';
			$changes['logging.mask_personal_data'] = isset( $_POST['almgr_logging_mask_personal_data'] );
			$changes['logging.log_email_events']   = isset( $_POST['almgr_logging_log_email_events'] );
		}

		if ( 'asset' === $active_tab && $is_admin ) {
			$raw_prefix                   = sanitize_text_field( wp_unslash( $_POST['almgr_asset_code_prefix'] ?? ALMGR_ASSET_CODE_PREFIX ) );
			$clean_prefix                 = substr( preg_replace( '/[^A-Za-z0-9]/', '', $raw_prefix ), 0, 10 );
			$changes['asset.code_prefix'] = '' !== $clean_prefix ? $clean_prefix : ALMGR_ASSET_CODE_PREFIX;
		}

		if ( 'rest_api' === $active_tab && $is_admin ) {
			$changes['rest_api.enabled'] = isset( $_POST['almgr_rest_api_enabled'] );
		}

		if ( 'templates' === $active_tab && $is_admin ) {
			$types = array(
				'request_to_requester',
				'request_to_owner',
				'approved',
				'rejected',
				'canceled',
				'direct_assign',
				'direct_assign_to_prev_owner',
			);
			foreach ( $types as $type ) {
				$changes[ 'template.subject.' . $type ] = sanitize_text_field( wp_unslash( $_POST[ 'almgr_tpl_subject_' . $type ] ?? '' ) );
				$changes[ 'template.body.' . $type ]    = sanitize_textarea_field( wp_unslash( $_POST[ 'almgr_tpl_body_' . $type ] ?? '' ) );
			}
		}

		if ( ! empty( $changes ) ) {
			$this->modules['settings']->set_batch( $changes );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'tab'   => $active_tab,
					'saved' => '1',
				),
				admin_url( 'admin.php?page=almgr-settings' )
			)
		);
		exit;
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
		ALMGR_Logger::info(
			'Reload default terms action triggered.',
			array( 'user_id' => $user_id )
		);

		// Ensure the current user has the required capability.
		// This check is mandatory even if the menu item is already protected.
		if ( ! current_user_can( ALMGR_EDIT_ASSET ) ) {
				wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}

		// Verify the nonce to protect against CSRF attacks.
		check_admin_referer( 'almgr_reload_terms_action', 'almgr_reload_terms_nonce' );

		try {
			ALMGR_Logger::debug(
				'Starting default terms setup.',
				array( 'user_id' => $user_id )
			);

			require_once plugin_dir_path( __FILE__ ) . 'class-almgr-installer.php';
			// Execute the idempotent setup routine.
			// Safe to call multiple times.
			ALMGR_Installer::create_default_terms();
			ALMGR_Logger::info(
				'Default terms successfully reloaded.',
				array( 'user_id' => $user_id )
			);
			$status = 'success';
		} catch ( Exception $e ) {
			ALMGR_Logger::error(
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
				'almgr_status',
				$status,
				admin_url( 'admin.php?page=almgr-tools' )
			)
		);
		exit;
	}
}
