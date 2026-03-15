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
		// Configure logger with user settings (runs on plugins_loaded, safe because
		// get_defaults() defers __() calls until after the 'init' action).
		ALM_Logger::configure( $this->modules['settings'] );
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
		// Save settings page form.
		add_action(
			'admin_post_alm_save_settings',
			array( $this, 'handle_settings_save' ),
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
				function () {
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
		add_action(
			'init',
			static function () {
				load_plugin_textdomain(
					ALM_TEXT_DOMAIN,
					false,
					dirname( plugin_basename( ALM_PLUGIN_FILE ) ) . '/languages/'
				);
			}
		);
	}

	/**
	 * Instantiate plugin modules.
	 *
	 * @return void
	 */
	private function init_modules() {
		if ( empty( $this->modules ) ) {
			$settings      = new ALM_Settings_Manager();
			$this->modules = array(
				'settings'     => $settings,
				'role'         => new ALM_Role_Manager(),
				'asset'        => new ALM_Asset_Manager(),
				'loan'         => new ALM_Loan_Manager( $settings ),
				'notification' => new ALM_Notification_Manager( $settings ),
				'frontend'     => new ALM_Frontend_Manager( $settings ),
				'admin'        => new ALM_Admin_Manager(),
				'autocomplete' => new ALM_Autocomplete_Manager( $settings ),
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
	 * This menu acts as a container for all ALM-related CPTs and pages.
	 *
	 * @return void
	 */
	public function register_alm_custom_menu() {

		$slug_main_menu = ALM_SLUG_MAIN_MENU;

		add_menu_page(
			__( 'Asset Lending Manager', 'asset-lending-manager' ),  // Page title.
			__( 'ALM', 'asset-lending-manager' ),                    // Menu title.
			ALM_EDIT_ASSET,                                         // Capability.
			$slug_main_menu,                                         // Menu slug.
			array( $this, 'get_plugin_presentation' ),               // Callback (handled by CPT).
			ALM_MAIN_MENU_ICON,                                      // Icon.
			30                                                       // Position.
		);

		// List of the assets.
		add_submenu_page(
			$slug_main_menu,                            // parent slug.
			__( 'Assets', 'asset-lending-manager' ),   // page title.
			__( 'Assets', 'asset-lending-manager' ),   // sub-menu title.
			ALM_VIEW_ASSETS,                           // capability.
			'edit.php?post_type=' . ALM_ASSET_CPT_SLUG // link.
		);

		// Add a book.
		add_submenu_page(
			$slug_main_menu,
			__( 'Add a asset', 'asset-lending-manager' ),
			__( 'Add a asset', 'asset-lending-manager' ),
			ALM_EDIT_ASSET,
			'post-new.php?post_type=' . ALM_ASSET_CPT_SLUG
		);

		// Taxonomy: asset structure.
		add_submenu_page(
			$slug_main_menu,
			__( 'Asset Structure', 'asset-lending-manager' ),
			__( 'Asset Structure', 'asset-lending-manager' ),
			ALM_EDIT_ASSET,
			'edit-tags.php?taxonomy=' . ALM_ASSET_STRUCTURE_TAXONOMY_SLUG,
		);

		// Taxonomy: asset type.
		add_submenu_page(
			$slug_main_menu,
			__( 'Asset Type', 'asset-lending-manager' ),
			__( 'Asset Type', 'asset-lending-manager' ),
			ALM_EDIT_ASSET,
			'edit-tags.php?taxonomy=' . ALM_ASSET_TYPE_TAXONOMY_SLUG,
		);

		// Taxonomy: asset state.
		add_submenu_page(
			$slug_main_menu,
			__( 'Asset State', 'asset-lending-manager' ),
			__( 'Asset State', 'asset-lending-manager' ),
			ALM_EDIT_ASSET,
			'edit-tags.php?taxonomy=' . ALM_ASSET_STATE_TAXONOMY_SLUG,
		);

		// Taxonomy: asset levels.
		add_submenu_page(
			$slug_main_menu,
			__( 'Asset Level', 'asset-lending-manager' ),
			__( 'Asset Level', 'asset-lending-manager' ),
			ALM_EDIT_ASSET,
			'edit-tags.php?taxonomy=' . ALM_ASSET_LEVEL_TAXONOMY_SLUG,
		);

		// Settings page.
		add_submenu_page(
			$slug_main_menu,
			__( 'ALM Settings', 'asset-lending-manager' ),
			__( 'Settings', 'asset-lending-manager' ),
			ALM_EDIT_ASSET,
			'alm-settings',
			array( $this, 'render_settings_page' )
		);

		// Link to the page to reload default data.
		add_submenu_page(
			$slug_main_menu,
			__( 'ALM Tools', 'asset-lending-manager' ),
			__( 'Tools', 'asset-lending-manager' ),
			ALM_EDIT_ASSET,
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
		if ( ! current_user_can( ALM_EDIT_ASSET ) ) {
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
		if ( ! current_user_can( ALM_EDIT_ASSET ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'asset-lending-manager' ) );
		}
		require_once ALM_PLUGIN_DIR . 'admin/alm-tools-page.php';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( ALM_EDIT_ASSET ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'asset-lending-manager' ) );
		}
		require_once ALM_PLUGIN_DIR . 'admin/alm-settings-page.php';
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
		if ( ! current_user_can( ALM_EDIT_ASSET ) ) {
			wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}

		check_admin_referer( 'alm_save_settings', 'alm_settings_nonce' );

		$is_admin   = current_user_can( 'manage_options' );
		$active_tab = isset( $_POST['alm_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['alm_active_tab'] ) ) : 'email';
		$changes    = array();

		if ( 'email' === $active_tab ) {
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['email.from_name']       = sanitize_text_field( wp_unslash( $_POST['alm_email_from_name'] ?? '' ) );
				$changes['email.from_address']    = sanitize_email( wp_unslash( $_POST['alm_email_from_address'] ?? '' ) );
				$changes['email.system_email']    = sanitize_email( wp_unslash( $_POST['alm_email_system_email'] ?? '' ) );
				$changes['notifications.enabled'] = isset( $_POST['alm_notifications_enabled'] );
			}
			// [A/O] fields.
			$changes['notifications.loan_request']      = isset( $_POST['alm_notifications_loan_request'] );
			$changes['notifications.loan_decision']     = isset( $_POST['alm_notifications_loan_decision'] );
			$changes['notifications.loan_confirmation'] = isset( $_POST['alm_notifications_loan_confirmation'] );
		}

		if ( 'loans' === $active_tab ) {
			// [A/O] fields.
			$changes['loans.loan_requests_enabled']   = isset( $_POST['alm_loans_loan_requests_enabled'] );
			$changes['loans.max_active_per_user']     = max( 0, absint( wp_unslash( $_POST['alm_loans_max_active_per_user'] ?? 0 ) ) );
			$changes['loans.allow_multiple_requests'] = isset( $_POST['alm_loans_allow_multiple_requests'] );
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['loans.request_message_max_length']         = max( 0, absint( wp_unslash( $_POST['alm_loans_request_message_max_length'] ?? 500 ) ) );
				$changes['loans.rejection_message_max_length']       = max( 0, absint( wp_unslash( $_POST['alm_loans_rejection_message_max_length'] ?? 500 ) ) );
				$changes['loans.direct_assign_reason_max_length']    = max( 0, absint( wp_unslash( $_POST['alm_loans_direct_assign_reason_max_length'] ?? 500 ) ) );
			}
		}

		if ( 'direct_assign' === $active_tab ) {
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['direct_assign.enabled']              = isset( $_POST['alm_direct_assign_enabled'] );
				$valid_roles                                   = array( ALM_MEMBER_ROLE, ALM_OPERATOR_ROLE );
				$posted_roles                                  = filter_input( INPUT_POST, 'alm_direct_assign_roles', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
				$raw_roles                                     = is_array( $posted_roles ) ? array_map( 'sanitize_key', $posted_roles ) : array();
				$changes['direct_assign.allowed_target_roles'] = array_values(
					array_intersect( $raw_roles, $valid_roles )
				);
			}
		}

		if ( 'workflow' === $active_tab ) {
			// [A/O] fields.
			$changes['workflow.cancel_concurrent_requests_on_assign']        = isset( $_POST['alm_workflow_cancel_concurrent'] );
			$changes['workflow.cancel_component_requests_when_kit_assigned'] = isset( $_POST['alm_workflow_cancel_component_requests'] );
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['workflow.automatic_operations_actor_user_id'] = max( 1, absint( wp_unslash( $_POST['alm_workflow_actor_user_id'] ?? 1 ) ) );
			}
		}

		if ( 'frontend' === $active_tab ) {
			// [A/O] fields.
			$changes['frontend.asset_list_per_page']  = min( 100, max( 1, absint( wp_unslash( $_POST['alm_frontend_asset_list_per_page'] ?? ALM_ASSET_LIST_PER_PAGE ) ) ) );
			$changes['frontend.default_filters_open'] = isset( $_POST['alm_frontend_default_filters_open'] );
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['frontend.assets_page_id']          = max( 0, absint( wp_unslash( $_POST['alm_frontend_assets_page_id'] ?? 0 ) ) );
				$changes['frontend.login_redirect_page_id']  = max( 0, absint( wp_unslash( $_POST['alm_frontend_login_redirect_page_id'] ?? 0 ) ) );
				$changes['frontend.logout_redirect_page_id'] = max( 0, absint( wp_unslash( $_POST['alm_frontend_logout_redirect_page_id'] ?? 0 ) ) );
			}
		}

		if ( 'autocomplete' === $active_tab ) {
			// [A/O] fields.
			$changes['autocomplete.min_chars']          = min( 10, max( 1, absint( wp_unslash( $_POST['alm_autocomplete_min_chars'] ?? 3 ) ) ) );
			$changes['autocomplete.max_results']        = min( 20, max( 1, absint( wp_unslash( $_POST['alm_autocomplete_max_results'] ?? ALM_AUTOCOMPLETE_MAX_RESULTS ) ) ) );
			$changes['autocomplete.description_length'] = min( 200, max( 0, absint( wp_unslash( $_POST['alm_autocomplete_description_length'] ?? ALM_AUTOCOMPLETE_DESC_LENGTH ) ) ) );
			$changes['autocomplete.qr_scan_enabled']    = isset( $_POST['alm_autocomplete_qr_scan_enabled'] );
			// [A]-only fields.
			if ( $is_admin ) {
				$changes['autocomplete.public_assets_endpoint_enabled'] = isset( $_POST['alm_autocomplete_public_endpoint'] );
			}
		}

		if ( 'logging' === $active_tab && $is_admin ) {
			$level_whitelist                       = array( 'debug', 'info', 'warning', 'error' );
			$raw_level                             = sanitize_key( wp_unslash( $_POST['alm_logging_level'] ?? 'error' ) );
			$changes['logging.enabled']            = isset( $_POST['alm_logging_enabled'] );
			$changes['logging.level']              = in_array( $raw_level, $level_whitelist, true ) ? $raw_level : 'error';
			$changes['logging.mask_personal_data'] = isset( $_POST['alm_logging_mask_personal_data'] );
			$changes['logging.log_email_events']   = isset( $_POST['alm_logging_log_email_events'] );
		}

		if ( 'asset' === $active_tab && $is_admin ) {
			$raw_prefix                   = sanitize_text_field( wp_unslash( $_POST['alm_asset_code_prefix'] ?? ALM_ASSET_CODE_PREFIX ) );
			$clean_prefix                 = substr( preg_replace( '/[^A-Za-z0-9]/', '', $raw_prefix ), 0, 10 );
			$changes['asset.code_prefix'] = '' !== $clean_prefix ? $clean_prefix : ALM_ASSET_CODE_PREFIX;
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
				$changes[ 'template.subject.' . $type ] = sanitize_text_field( wp_unslash( $_POST[ 'alm_tpl_subject_' . $type ] ?? '' ) );
				$changes[ 'template.body.' . $type ]    = sanitize_textarea_field( wp_unslash( $_POST[ 'alm_tpl_body_' . $type ] ?? '' ) );
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
				admin_url( 'admin.php?page=alm-settings' )
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
		ALM_Logger::info(
			'Reload default terms action triggered.',
			array( 'user_id' => $user_id )
		);

		// Ensure the current user has the required capability.
		// This check is mandatory even if the menu item is already protected.
		if ( ! current_user_can( ALM_EDIT_ASSET ) ) {
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
