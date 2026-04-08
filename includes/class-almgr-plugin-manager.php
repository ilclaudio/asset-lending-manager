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
		// Import users from CSV (Tools > Import).
		add_action(
			'admin_post_almgr_import_users_csv',
			array( $this, 'handle_import_users_csv' ),
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
	 * Handle users CSV import submission from Tools > Import.
	 *
	 * @return void
	 */
	public function handle_import_users_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}

		check_admin_referer( 'almgr_import_users_csv_action', 'almgr_import_users_csv_nonce' );

		$allowed_import_modes = array( 'create_only', 'update_only', 'upsert' );
		$allowed_run_modes    = array( 'dry_run', 'execute' );

		$import_mode = isset( $_POST['almgr_users_import_mode'] ) ? sanitize_key( wp_unslash( $_POST['almgr_users_import_mode'] ) ) : 'upsert';
		$run_mode    = isset( $_POST['almgr_users_run_mode'] ) ? sanitize_key( wp_unslash( $_POST['almgr_users_run_mode'] ) ) : 'dry_run';

		if ( ! in_array( $import_mode, $allowed_import_modes, true ) ) {
			$import_mode = 'upsert';
		}
		if ( ! in_array( $run_mode, $allowed_run_modes, true ) ) {
			$run_mode = 'dry_run';
		}

		$upload = $this->get_users_csv_upload();
		if ( is_wp_error( $upload ) ) {
			$report = $this->new_users_import_report( $import_mode, $run_mode, '' );
			$this->add_users_import_error(
				$report,
				1,
				'',
				$upload->get_error_message()
			);
			$this->store_users_import_report_for_current_user( $report );
			$this->redirect_to_tools_import_tab();
		}

		$report = $this->process_users_csv_import(
			$upload['tmp_name'],
			$upload['name'],
			$import_mode,
			$run_mode
		);

		$this->store_users_import_report_for_current_user( $report );
		$this->redirect_to_tools_import_tab();
	}

	/**
	 * Create the default users import report structure.
	 *
	 * @param string $import_mode Import mode.
	 * @param string $run_mode Run mode.
	 * @param string $file_name Uploaded file name.
	 * @return array
	 */
	private function new_users_import_report( $import_mode, $run_mode, $file_name ) {
		return array(
			'import_mode' => $import_mode,
			'run_mode'    => $run_mode,
			'file_name'   => $file_name,
			'header'      => 'Username;Email;First_Name;Last_Name;Role',
			'counts'      => array(
				'processed' => 0,
				'created'   => 0,
				'updated'   => 0,
				'skipped'   => 0,
				'errors'    => 0,
			),
			'errors'      => array(),
		);
	}

	/**
	 * Read and validate the uploaded CSV file from the request.
	 *
	 * @return array|WP_Error
	 */
	private function get_users_csv_upload() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in handle_import_users_csv().
		if ( ! isset( $_FILES['almgr_users_csv_file'] ) || ! is_array( $_FILES['almgr_users_csv_file'] ) ) {
			return new WP_Error( 'missing_file', __( 'Select a CSV file to import.', 'asset-lending-manager' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload array is validated/sanitized field-by-field below.
		$file = $_FILES['almgr_users_csv_file'];

		$error_code = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error_code ) {
			return new WP_Error( 'upload_error', __( 'File upload failed. Please try again.', 'asset-lending-manager' ) );
		}

		$file_name = sanitize_file_name( wp_unslash( (string) ( $file['name'] ?? '' ) ) );
		$tmp_name  = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		$file_size = isset( $file['size'] ) ? (int) $file['size'] : 0;

		if ( '' === $file_name || '' === $tmp_name ) {
			return new WP_Error( 'invalid_upload', __( 'Invalid uploaded file.', 'asset-lending-manager' ) );
		}
		if ( ! is_uploaded_file( $tmp_name ) ) {
			return new WP_Error( 'invalid_upload_origin', __( 'Invalid uploaded file source.', 'asset-lending-manager' ) );
		}
		if ( $file_size <= 0 ) {
			return new WP_Error( 'empty_file', __( 'The uploaded CSV file is empty.', 'asset-lending-manager' ) );
		}
		if ( $file_size > 1024 * 1024 ) {
			return new WP_Error( 'file_too_large', __( 'The CSV file exceeds the 1MB limit.', 'asset-lending-manager' ) );
		}

		$extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		if ( 'csv' !== $extension ) {
			return new WP_Error( 'invalid_extension', __( 'Only .csv files are allowed.', 'asset-lending-manager' ) );
		}

		$type_check = wp_check_filetype_and_ext(
			$tmp_name,
			$file_name,
			array(
				'csv' => 'text/csv',
			)
		);

		if ( empty( $type_check['ext'] ) || 'csv' !== $type_check['ext'] ) {
			return new WP_Error( 'invalid_file_type', __( 'The uploaded file is not a valid CSV.', 'asset-lending-manager' ) );
		}

		return array(
			'name'     => $file_name,
			'tmp_name' => $tmp_name,
			'size'     => $file_size,
		);
	}

	/**
	 * Execute or simulate CSV users import.
	 *
	 * @param string $file_path CSV absolute path.
	 * @param string $file_name Original uploaded file name.
	 * @param string $import_mode Import mode.
	 * @param string $run_mode Run mode.
	 * @return array
	 */
	private function process_users_csv_import( $file_path, $file_name, $import_mode, $run_mode ) {
		$report          = $this->new_users_import_report( $import_mode, $run_mode, $file_name );
		$expected_header = array( 'Username', 'Email', 'First_Name', 'Last_Name', 'Role' );

		try {
			$file = new SplFileObject( $file_path );
			$file->setFlags( SplFileObject::READ_CSV );
			$file->setCsvControl( ';', '"', '\\' );
		} catch ( RuntimeException $exception ) {
			$this->add_users_import_error(
				$report,
				1,
				'',
				__( 'Unable to read the uploaded CSV file.', 'asset-lending-manager' )
			);
			return $report;
		}

		$header_row = $file->fgetcsv( ';', '"', '\\' );
		$header_row = $this->normalize_users_csv_row( $header_row );

		if ( $expected_header !== $header_row ) {
			$this->add_users_import_error(
				$report,
				1,
				'',
				sprintf(
					/* translators: %s: expected CSV header with semicolon delimiter. */
					__( 'Invalid CSV header. Expected exactly: %s', 'asset-lending-manager' ),
					'Username;Email;First_Name;Last_Name;Role'
				)
			);
			return $report;
		}

		$seen_emails    = array();
		$seen_usernames = array();
		$line_number    = 1;
		while ( ! $file->eof() ) {
			++$line_number;
			$row = $file->fgetcsv( ';', '"', '\\' );
			if ( false === $row ) {
				continue;
			}
			$row = $this->normalize_users_csv_row( $row );
			if ( $this->is_empty_users_csv_row( $row ) ) {
				continue;
			}

			if ( 5 !== count( $row ) ) {
				$this->add_users_import_error(
					$report,
					$line_number,
					'',
					__( 'Invalid number of columns. Use only the ; delimiter.', 'asset-lending-manager' )
				);
				continue;
			}

			$username_raw   = $row[0];
			$email_raw      = $row[1];
			$first_name_raw = $row[2];
			$last_name_raw  = $row[3];
			$role_raw       = $row[4];

			if ( '' === $username_raw || '' === $email_raw || '' === $first_name_raw || '' === $last_name_raw || '' === $role_raw ) {
				$this->add_users_import_error(
					$report,
					$line_number,
					$email_raw,
					__( 'Username, Email, First_Name, Last_Name and Role are required for every row.', 'asset-lending-manager' ),
					$username_raw
				);
				continue;
			}

			$username = sanitize_user( $username_raw, true );
			if ( '' === $username || ! validate_username( $username ) ) {
				$this->add_users_import_error(
					$report,
					$line_number,
					$email_raw,
					__( 'Invalid username format.', 'asset-lending-manager' ),
					$username_raw
				);
				continue;
			}

			$email = sanitize_email( $email_raw );
			if ( '' === $email || ! is_email( $email ) ) {
				$this->add_users_import_error(
					$report,
					$line_number,
					$email_raw,
					__( 'Invalid email format.', 'asset-lending-manager' ),
					$username
				);
				continue;
			}

			$email_key    = strtolower( $email );
			$username_key = strtolower( $username );
			if ( isset( $seen_emails[ $email_key ] ) ) {
				++$report['counts']['skipped'];
				continue;
			}
			if ( isset( $seen_usernames[ $username_key ] ) ) {
				++$report['counts']['skipped'];
				continue;
			}
			$seen_emails[ $email_key ]       = true;
			$seen_usernames[ $username_key ] = true;

			$first_name = sanitize_text_field( $first_name_raw );
			$last_name  = sanitize_text_field( $last_name_raw );
			if ( '' === $first_name || '' === $last_name ) {
				$this->add_users_import_error(
					$report,
					$line_number,
					$email,
					__( 'First_Name and Last_Name cannot be empty after sanitization.', 'asset-lending-manager' ),
					$username
				);
				continue;
			}

			$target_role = $this->map_import_role_to_wp_role( $role_raw );
			if ( is_wp_error( $target_role ) ) {
				$this->add_users_import_error(
					$report,
					$line_number,
					$email,
					$target_role->get_error_message(),
					$username
				);
				continue;
			}

			$existing_by_email = get_user_by( 'email', $email );
			$existing_by_login = get_user_by( 'login', $username );

			++$report['counts']['processed'];

			if ( $existing_by_email instanceof WP_User && $existing_by_login instanceof WP_User && (int) $existing_by_email->ID !== (int) $existing_by_login->ID ) {
				$this->add_users_import_error(
					$report,
					$line_number,
					$email,
					__( 'Username and email point to different existing users.', 'asset-lending-manager' ),
					$username
				);
				continue;
			}

			if ( $existing_by_email instanceof WP_User && ! $existing_by_login instanceof WP_User ) {
				$this->add_users_import_error(
					$report,
					$line_number,
					$email,
					__( 'Username does not match the existing user for this email.', 'asset-lending-manager' ),
					$username
				);
				continue;
			}

			if ( ! $existing_by_email instanceof WP_User && $existing_by_login instanceof WP_User ) {
				if ( 'update_only' === $import_mode ) {
					++$report['counts']['skipped'];
					continue;
				}

				$this->add_users_import_error(
					$report,
					$line_number,
					$email,
					__( 'Username already exists for another user.', 'asset-lending-manager' ),
					$username
				);
				continue;
			}

			if ( ! $existing_by_email instanceof WP_User ) {
				if ( 'update_only' === $import_mode ) {
					++$report['counts']['skipped'];
					continue;
				}

				if ( 'dry_run' === $run_mode ) {
					++$report['counts']['created'];
					continue;
				}

				$result = wp_insert_user(
					array(
						'user_login'   => $username,
						'user_email'   => $email,
						'first_name'   => $first_name,
						'last_name'    => $last_name,
						'display_name' => trim( $first_name . ' ' . $last_name ),
						'user_pass'    => wp_generate_password( 24, true, true ),
						'role'         => $target_role,
					)
				);

				if ( is_wp_error( $result ) ) {
					$this->add_users_import_error(
						$report,
						$line_number,
						$email,
						$result->get_error_message(),
						$username
					);
					continue;
				}

				++$report['counts']['created'];
				continue;
			}

			$existing_user = $existing_by_email;
			if ( 'create_only' === $import_mode ) {
				++$report['counts']['skipped'];
				continue;
			}

			$user_id         = (int) $existing_user->ID;
			$target_display  = trim( $first_name . ' ' . $last_name );
			$current_first   = (string) get_user_meta( $user_id, 'first_name', true );
			$current_last    = (string) get_user_meta( $user_id, 'last_name', true );
			$current_display = (string) $existing_user->display_name;

			$needs_profile_update = (
				$current_first !== $first_name ||
				$current_last !== $last_name ||
				$current_display !== $target_display
			);
			$needs_role_update    = $this->is_users_import_role_change_needed( $existing_user, $target_role );

			if ( ! $needs_profile_update && ! $needs_role_update ) {
				++$report['counts']['skipped'];
				continue;
			}

			if ( 'dry_run' === $run_mode ) {
				++$report['counts']['updated'];
				continue;
			}

			if ( $needs_profile_update ) {
				$update_result = wp_update_user(
					array(
						'ID'           => $user_id,
						'first_name'   => $first_name,
						'last_name'    => $last_name,
						'display_name' => $target_display,
					)
				);

				if ( is_wp_error( $update_result ) ) {
					$this->add_users_import_error(
						$report,
						$line_number,
						$email,
						$update_result->get_error_message(),
						$username
					);
					continue;
				}
			}

			if ( $needs_role_update ) {
				$this->apply_users_import_role( $existing_user, $target_role );
			}

			++$report['counts']['updated'];
		}

		return $report;
	}

	/**
	 * Normalize a CSV row by trimming values and removing UTF-8 BOM.
	 *
	 * @param array|false $row Raw CSV row.
	 * @return array
	 */
	private function normalize_users_csv_row( $row ) {
		if ( ! is_array( $row ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $row as $value ) {
			$normalized[] = trim( (string) $value );
		}

		if ( isset( $normalized[0] ) ) {
			$normalized[0] = (string) preg_replace( '/^\xEF\xBB\xBF/', '', $normalized[0] );
		}

		return $normalized;
	}

	/**
	 * Return true when a CSV row should be considered empty.
	 *
	 * @param array $row CSV row.
	 * @return bool
	 */
	private function is_empty_users_csv_row( array $row ) {
		if ( empty( $row ) ) {
			return true;
		}
		if ( 1 === count( $row ) && '' === (string) $row[0] ) {
			return true;
		}
		return false;
	}

	/**
	 * Map CSV role value to the corresponding WordPress role slug.
	 *
	 * Accepted CSV values: member, operator.
	 *
	 * @param string $role_raw CSV role value.
	 * @return string|WP_Error
	 */
	private function map_import_role_to_wp_role( $role_raw ) {
		$role = strtolower( sanitize_key( $role_raw ) );
		if ( 'member' === $role ) {
			return ALMGR_MEMBER_ROLE;
		}
		if ( 'operator' === $role ) {
			return ALMGR_OPERATOR_ROLE;
		}
		return new WP_Error(
			'invalid_role',
			__( 'Invalid role. Allowed values are: member, operator.', 'asset-lending-manager' )
		);
	}

	/**
	 * Return true when user roles require changes for the imported role.
	 *
	 * Operator always overrides member.
	 *
	 * @param WP_User $user Existing user object.
	 * @param string  $target_role Target role slug.
	 * @return bool
	 */
	private function is_users_import_role_change_needed( WP_User $user, $target_role ) {
		$roles = (array) $user->roles;

		if ( ALMGR_OPERATOR_ROLE === $target_role ) {
			return ! in_array( ALMGR_OPERATOR_ROLE, $roles, true ) || in_array( ALMGR_MEMBER_ROLE, $roles, true );
		}

		if ( ALMGR_MEMBER_ROLE === $target_role ) {
			if ( in_array( ALMGR_OPERATOR_ROLE, $roles, true ) ) {
				return false;
			}
			return ! in_array( ALMGR_MEMBER_ROLE, $roles, true );
		}

		return false;
	}

	/**
	 * Apply imported ALMGR role to an existing user.
	 *
	 * Operator takes precedence over member.
	 *
	 * @param WP_User $user Existing user object.
	 * @param string  $target_role Target role slug.
	 * @return void
	 */
	private function apply_users_import_role( WP_User $user, $target_role ) {
		$roles = (array) $user->roles;

		if ( ALMGR_OPERATOR_ROLE === $target_role ) {
			if ( ! in_array( ALMGR_OPERATOR_ROLE, $roles, true ) ) {
				$user->add_role( ALMGR_OPERATOR_ROLE );
			}
			if ( in_array( ALMGR_MEMBER_ROLE, $roles, true ) ) {
				$user->remove_role( ALMGR_MEMBER_ROLE );
			}
			return;
		}

		if ( ALMGR_MEMBER_ROLE === $target_role ) {
			if ( in_array( ALMGR_OPERATOR_ROLE, $roles, true ) ) {
				return;
			}
			if ( ! in_array( ALMGR_MEMBER_ROLE, $roles, true ) ) {
				$user->add_role( ALMGR_MEMBER_ROLE );
			}
		}
	}

	/**
	 * Add a single error row to the import report.
	 *
	 * @param array  $report Report array passed by reference.
	 * @param int    $line Line number.
	 * @param string $email Email value from CSV row.
	 * @param string $message Error message.
	 * @param string $username Username value from CSV row.
	 * @return void
	 */
	private function add_users_import_error( array &$report, $line, $email, $message, $username = '' ) {
		$report['errors'][] = array(
			'line'     => (int) $line,
			'username' => (string) $username,
			'email'    => (string) $email,
			'message'  => (string) $message,
		);
		++$report['counts']['errors'];
	}

	/**
	 * Store users import report for the current user.
	 *
	 * @param array $report Users import report.
	 * @return void
	 */
	private function store_users_import_report_for_current_user( array $report ) {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}
		set_transient(
			'almgr_users_import_report_' . $user_id,
			$report,
			15 * MINUTE_IN_SECONDS
		);
	}

	/**
	 * Redirect to the import tab in Tools page.
	 *
	 * @return void
	 */
	private function redirect_to_tools_import_tab() {
		wp_safe_redirect(
			add_query_arg(
				array(
					'tab'                       => 'import',
					'almgr_users_import_report' => '1',
				),
				admin_url( 'admin.php?page=almgr-tools' )
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
				array(
					'tab'          => 'utilities',
					'almgr_status' => $status,
				),
				admin_url( 'admin.php?page=almgr-tools' )
			)
		);
		exit;
	}
}
