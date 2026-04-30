<?php
/**
 * Tools Manager.
 *
 * Handles Tools actions (Import/Export/Utilities).
 * Current implementation covers users/assets CSV import and users/assets CSV export.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage Tools actions and their runtime hooks.
 */
class ALMGR_Tools_Manager {

	/**
	 * Register runtime hooks for Tools actions.
	 *
	 * @return void
	 */
	public function register() {
		// Users CSV import action (Tools > Import > Users).
		add_action(
			'admin_post_almgr_import_users_csv',
			array( $this, 'handle_import_users_csv' ),
		);
		// Assets CSV import action (Tools > Import > Assets).
		add_action(
			'admin_post_almgr_import_assets_csv',
			array( $this, 'handle_import_assets_csv' ),
		);
		// Assets CSV errors export for latest assets import report.
		add_action(
			'admin_post_almgr_download_assets_import_errors_csv',
			array( $this, 'handle_download_assets_import_errors_csv' ),
		);
		// Users CSV export action (Tools > Export > Users).
		add_action(
			'admin_post_almgr_export_users_csv',
			array( $this, 'handle_export_users_csv' ),
		);
		// Assets CSV export action (Tools > Export > Assets).
		add_action(
			'admin_post_almgr_export_assets_csv',
			array( $this, 'handle_export_assets_csv' ),
		);
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public function activate() {
		// No activation side effects required.
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public function deactivate() {
		// No deactivation side effects required.
	}

	/**
	 * Handle users CSV import submission from Tools > Import.
	 *
	 * @return void
	 */
	public function handle_import_users_csv() {
		// 1) Authorize action and validate nonce.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}
		check_admin_referer( 'almgr_import_users_csv_action', 'almgr_import_users_csv_nonce' );

		// 2) Normalize import mode and run mode.
		$allowed_import_modes = array( 'create_only', 'update_only', 'upsert' );
		$allowed_run_modes    = array( 'dry_run', 'execute' );
		$import_mode          = isset( $_POST['almgr_users_import_mode'] ) ? sanitize_key( wp_unslash( $_POST['almgr_users_import_mode'] ) ) : 'upsert';
		$run_mode             = isset( $_POST['almgr_users_run_mode'] ) ? sanitize_key( wp_unslash( $_POST['almgr_users_run_mode'] ) ) : 'dry_run';

		if ( ! in_array( $import_mode, $allowed_import_modes, true ) ) {
			$import_mode = 'upsert';
		}
		if ( ! in_array( $run_mode, $allowed_run_modes, true ) ) {
			$run_mode = 'dry_run';
		}

		// 3) Validate upload shape/type/size before reading the CSV.
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

		// 4) Process CSV rows, store report, and return to the import tab.
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
	 * Handle assets CSV import submission from Tools > Import.
	 *
	 * MVP scope:
	 * - create-only behavior (existing assets are skipped)
	 * - dry-run / execute modes
	 * - two-pass processing for kit component links
	 *
	 * @return void
	 */
	public function handle_import_assets_csv() {
		if ( ! $this->current_user_can_import_assets_csv() ) {
			wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}
		check_admin_referer( 'almgr_import_assets_csv_action', 'almgr_import_assets_csv_nonce' );

		$allowed_run_modes = array( 'dry_run', 'execute' );
		$run_mode          = isset( $_POST['almgr_assets_run_mode'] ) ? sanitize_key( wp_unslash( $_POST['almgr_assets_run_mode'] ) ) : 'dry_run';
		if ( ! in_array( $run_mode, $allowed_run_modes, true ) ) {
			$run_mode = 'dry_run';
		}

		$upload = $this->get_assets_csv_upload();
		if ( is_wp_error( $upload ) ) {
			$report = $this->new_assets_import_report( 'create_only', $run_mode, '' );
			$this->add_assets_import_error(
				$report,
				1,
				'',
				$upload->get_error_message()
			);
			$this->store_assets_import_report_for_current_user( $report );
			$this->redirect_to_tools_assets_import_tab();
		}

		$report = $this->process_assets_csv_import(
			$upload['tmp_name'],
			$upload['name'],
			'create_only',
			$run_mode
		);

		$this->store_assets_import_report_for_current_user( $report );
		$this->redirect_to_tools_assets_import_tab();
	}

	/**
	 * Download CSV containing row-level errors from the latest assets import report.
	 *
	 * @return void
	 */
	public function handle_download_assets_import_errors_csv() {
		if ( ! $this->current_user_can_import_assets_csv() ) {
			wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}

		check_admin_referer(
			'almgr_download_assets_import_errors_csv_action',
			'almgr_download_assets_import_errors_csv_nonce'
		);

		$report = $this->get_assets_import_report_for_current_user();
		if ( empty( $report ) || empty( $report['errors'] ) || ! is_array( $report['errors'] ) ) {
			wp_die( esc_html__( 'No import errors are available for download.', 'asset-lending-manager' ) );
		}

		if ( headers_sent() ) {
			wp_die( esc_html__( 'Cannot start CSV export because headers are already sent.', 'asset-lending-manager' ) );
		}

		$file_name = sprintf(
			'almgr-assets-import-errors-%s.csv',
			gmdate( 'Ymd-His' )
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file_name ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to generate CSV export output stream.', 'asset-lending-manager' ) );
		}

		echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility.

		fputcsv(
			$output,
			array( 'Line', 'Title', 'Message' ),
			';',
			'"',
			'\\'
		);

		foreach ( $report['errors'] as $error_row ) {
			fputcsv(
				$output,
				array(
					(int) ( $error_row['line'] ?? 0 ),
					$this->sanitize_users_export_csv_cell( (string) ( $error_row['title'] ?? '' ) ),
					$this->sanitize_users_export_csv_cell( (string) ( $error_row['message'] ?? '' ) ),
				),
				';',
				'"',
				'\\'
			);
		}

		exit;
	}

	/**
	 * Handle users CSV export submission from Tools > Export.
	 *
	 * @return void
	 */
	public function handle_export_users_csv() {
		if ( ! $this->current_user_can_export_users_csv() ) {
			wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}

		check_admin_referer( 'almgr_export_users_csv_action', 'almgr_export_users_csv_nonce' );
		$this->stream_users_csv_export();
	}

	/**
	 * Handle assets CSV export submission from Tools > Export.
	 *
	 * @return void
	 */
	public function handle_export_assets_csv() {
		if ( ! $this->current_user_can_export_assets_csv() ) {
			wp_die( esc_html__( 'Unauthorized action.', 'asset-lending-manager' ) );
		}

		check_admin_referer( 'almgr_export_assets_csv_action', 'almgr_export_assets_csv_nonce' );
		$this->stream_assets_csv_export();
	}

	/**
	 * Return whether current user can export users CSV.
	 *
	 * Allowed: administrators and operators.
	 *
	 * @return bool
	 */
	private function current_user_can_export_users_csv() {
		return current_user_can( 'manage_options' ) || current_user_can( ALMGR_EDIT_ASSET );
	}

	/**
	 * Return whether current user can export assets CSV.
	 *
	 * Allowed: administrators and operators.
	 *
	 * @return bool
	 */
	private function current_user_can_export_assets_csv() {
		return current_user_can( 'manage_options' ) || current_user_can( ALMGR_EDIT_ASSET );
	}

	/**
	 * Stream users CSV export response.
	 *
	 * The output is compatible with the Users CSV import format.
	 *
	 * @return void
	 */
	private function stream_users_csv_export() {
		if ( headers_sent() ) {
			wp_die( esc_html__( 'Cannot start CSV export because headers are already sent.', 'asset-lending-manager' ) );
		}

		$file_name = sprintf(
			'almgr-users-export-%s.csv',
			gmdate( 'Ymd-His' )
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file_name ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to generate CSV export output stream.', 'asset-lending-manager' ) );
		}

		// Add UTF-8 BOM to improve compatibility with spreadsheet applications.
		echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility.

		fputcsv(
			$output,
			array( 'Username', 'Email', 'First_Name', 'Last_Name', 'Role' ),
			';',
			'"',
			'\\'
		);

		$per_page = 200;
		$offset   = 0;
		$rows     = 0;

		do {
			$user_query = new WP_User_Query(
				array(
					'orderby'     => 'ID',
					'order'       => 'ASC',
					'number'      => $per_page,
					'offset'      => $offset,
					'count_total' => false,
					'role__in'    => array( ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE ),
					'fields'      => 'all_with_meta',
				)
			);

			$users = $user_query->get_results();
			if ( empty( $users ) ) {
				break;
			}

			foreach ( $users as $user ) {
				if ( ! $user instanceof WP_User ) {
					continue;
				}

				$export_role = $this->map_user_roles_to_export_role( (array) $user->roles );
				if ( '' === $export_role ) {
					continue;
				}

				$row = array(
					$this->sanitize_users_export_csv_cell( $user->user_login ),
					$this->sanitize_users_export_csv_cell( $user->user_email ),
					$this->sanitize_users_export_csv_cell( (string) get_user_meta( (int) $user->ID, 'first_name', true ) ),
					$this->sanitize_users_export_csv_cell( (string) get_user_meta( (int) $user->ID, 'last_name', true ) ),
					$this->sanitize_users_export_csv_cell( $export_role ),
				);

				fputcsv(
					$output,
					$row,
					';',
					'"',
					'\\'
				);
			}

			$rows    = count( $users );
			$offset += $per_page;
		} while ( $rows === $per_page );

		exit;
	}

	/**
	 * Stream assets CSV export response.
	 *
	 * The output is compatible with the Assets CSV import contract.
	 *
	 * @return void
	 */
	private function stream_assets_csv_export() {
		if ( headers_sent() ) {
			wp_die( esc_html__( 'Cannot start CSV export because headers are already sent.', 'asset-lending-manager' ) );
		}

		$file_name = sprintf(
			'almgr-assets-export-%s.csv',
			gmdate( 'Ymd-His' )
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file_name ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to generate CSV export output stream.', 'asset-lending-manager' ) );
		}

		// Add UTF-8 BOM to improve compatibility with spreadsheet applications.
		echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility.

		fputcsv(
			$output,
			array( 'Title', 'Structure', 'Type', 'State', 'Level', 'External_Code', 'Description', 'Manufacturer', 'Model', 'Wp_Status', 'Kit_Component_Titles' ),
			';',
			'"',
			'\\'
		);

		$per_page = 200;
		$offset   = 0;
		$rows     = 0;

		do {
			$query = new WP_Query(
				array(
					'post_type'              => ALMGR_ASSET_CPT_SLUG,
					'post_status'            => 'publish',
					'posts_per_page'         => $per_page,
					'offset'                 => $offset,
					'fields'                 => 'ids',
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			$asset_ids = is_array( $query->posts ) ? $query->posts : array();
			if ( empty( $asset_ids ) ) {
				break;
			}

			foreach ( $asset_ids as $asset_id ) {
				$asset_id     = (int) $asset_id;
				$asset_title  = (string) get_the_title( $asset_id );
				$structure    = $this->get_assets_export_term_slug( $asset_id, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG );
				$type         = $this->get_assets_export_term_slug( $asset_id, ALMGR_ASSET_TYPE_TAXONOMY_SLUG );
				$state        = $this->get_assets_export_term_slug( $asset_id, ALMGR_ASSET_STATE_TAXONOMY_SLUG );
				$level        = $this->get_assets_export_term_slug( $asset_id, ALMGR_ASSET_LEVEL_TAXONOMY_SLUG );
				$external     = (string) ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_external_code', $asset_id );
				$description  = (string) get_post_field( 'post_content', $asset_id );
				$manufacturer = (string) ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_manufacturer', $asset_id );
				$model        = (string) ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_model', $asset_id );

				$kit_titles = '';
				if ( ALMGR_ASSET_KIT_SLUG === $structure ) {
					$kit_titles = $this->get_assets_export_kit_component_titles( $asset_id );
				}

				$row = array(
					$this->sanitize_users_export_csv_cell( $asset_title ),
					$this->sanitize_users_export_csv_cell( $structure ),
					$this->sanitize_users_export_csv_cell( $type ),
					$this->sanitize_users_export_csv_cell( $state ),
					$this->sanitize_users_export_csv_cell( $level ),
					$this->sanitize_users_export_csv_cell( $external ),
					$this->sanitize_users_export_csv_cell( $description ),
					$this->sanitize_users_export_csv_cell( $manufacturer ),
					$this->sanitize_users_export_csv_cell( $model ),
					$this->sanitize_users_export_csv_cell( 'publish' ),
					$this->sanitize_users_export_csv_cell( $kit_titles ),
				);

				fputcsv(
					$output,
					$row,
					';',
					'"',
					'\\'
				);
			}

			$rows    = count( $asset_ids );
			$offset += $per_page;
		} while ( $rows === $per_page );

		exit;
	}

	/**
	 * Return first term slug for the given asset and taxonomy.
	 *
	 * @param int    $asset_id Asset post ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	private function get_assets_export_term_slug( $asset_id, $taxonomy ) {
		$term_slugs = wp_get_post_terms(
			(int) $asset_id,
			(string) $taxonomy,
			array(
				'fields' => 'slugs',
			)
		);

		if ( is_wp_error( $term_slugs ) || empty( $term_slugs ) || ! is_array( $term_slugs ) ) {
			return '';
		}

		return sanitize_title( (string) $term_slugs[0] );
	}

	/**
	 * Return pipe-separated component titles for a kit asset.
	 *
	 * @param int $kit_id Kit asset post ID.
	 * @return string
	 */
	private function get_assets_export_kit_component_titles( $kit_id ) {
		$raw_components = ALMGR_ACF_Asset_Adapter::get_custom_field( 'almgr_components', (int) $kit_id );

		$component_ids = $this->normalize_assets_export_component_ids( $raw_components );
		if ( empty( $component_ids ) ) {
			return '';
		}

		$component_titles = array();
		foreach ( $component_ids as $component_id ) {
			$component = get_post( (int) $component_id );
			if ( ! $component || ALMGR_ASSET_CPT_SLUG !== $component->post_type ) {
				continue;
			}

			$title = trim( (string) get_the_title( (int) $component_id ) );
			if ( '' === $title ) {
				continue;
			}

			$component_titles[] = $title;
		}

		if ( empty( $component_titles ) ) {
			return '';
		}

		return implode( '|', $component_titles );
	}

	/**
	 * Normalize raw kit components field value into an array of unique integer IDs.
	 *
	 * @param mixed $raw_components Raw field value from ACF or post meta.
	 * @return array
	 */
	private function normalize_assets_export_component_ids( $raw_components ) {
		$values = maybe_unserialize( $raw_components );
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		$ids = array();
		foreach ( $values as $value ) {
			$id = 0;
			if ( $value instanceof WP_Post ) {
				$id = (int) $value->ID;
			} elseif ( is_object( $value ) && isset( $value->ID ) ) {
				$id = (int) $value->ID;
			} elseif ( is_numeric( $value ) ) {
				$id = (int) $value;
			}

			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Map user roles to export role value used by CSV import/export contract.
	 *
	 * @param array $roles User role slugs.
	 * @return string
	 */
	private function map_user_roles_to_export_role( array $roles ) {
		if ( in_array( ALMGR_OPERATOR_ROLE, $roles, true ) ) {
			return 'operator';
		}

		if ( in_array( ALMGR_MEMBER_ROLE, $roles, true ) ) {
			return 'member';
		}

		return '';
	}

	/**
	 * Sanitize a CSV cell and protect against spreadsheet formula injection.
	 *
	 * @param string $value Cell value.
	 * @return string
	 */
	private function sanitize_users_export_csv_cell( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		$trimmed_left = ltrim( $value );
		if ( '' !== $trimmed_left && preg_match( '/^[=\+\-@]/', $trimmed_left ) ) {
			return "'" . $value;
		}

		if ( preg_match( '/^[\t\r\n]/', $value ) ) {
			return "'" . $value;
		}

		return $value;
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
			'logs'        => array(),
			'errors'      => array(),
		);
	}

	/**
	 * Read and validate the uploaded CSV file from the request.
	 *
	 * @return array|WP_Error
	 */
	private function get_users_csv_upload() {
		check_admin_referer( 'almgr_import_users_csv_action', 'almgr_import_users_csv_nonce' );

		if ( ! isset( $_FILES['almgr_users_csv_file'] ) || ! is_array( $_FILES['almgr_users_csv_file'] ) ) {
			return new WP_Error( 'missing_file', __( 'Select a CSV file to import.', 'asset-lending-manager' ) );
		}

		$error_code = isset( $_FILES['almgr_users_csv_file']['error'] ) ? absint( wp_unslash( $_FILES['almgr_users_csv_file']['error'] ) ) : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error_code ) {
			return new WP_Error( 'upload_error', __( 'File upload failed. Please try again.', 'asset-lending-manager' ) );
		}

		$file_name = isset( $_FILES['almgr_users_csv_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['almgr_users_csv_file']['name'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- tmp_name is generated by PHP; unslashing or sanitizing it can corrupt valid upload paths on Windows. It is validated with is_uploaded_file() before use.
		$tmp_name  = isset( $_FILES['almgr_users_csv_file']['tmp_name'] ) ? (string) $_FILES['almgr_users_csv_file']['tmp_name'] : '';
		$file_size = isset( $_FILES['almgr_users_csv_file']['size'] ) ? absint( wp_unslash( $_FILES['almgr_users_csv_file']['size'] ) ) : 0;

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

		// Header must match exactly (strict contract, no column auto-mapping).
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

		// One-pass CSV processing with per-row validation and isolated row failures.
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
				$this->add_users_import_log_entry(
					$report,
					$line_number,
					$username,
					$email,
					'skipped',
					__( 'Skipped duplicate email in the same CSV file.', 'asset-lending-manager' )
				);
				continue;
			}
			if ( isset( $seen_usernames[ $username_key ] ) ) {
				++$report['counts']['skipped'];
				$this->add_users_import_log_entry(
					$report,
					$line_number,
					$username,
					$email,
					'skipped',
					__( 'Skipped duplicate username in the same CSV file.', 'asset-lending-manager' )
				);
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

			// Email/username must always identify the same user record.
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
					$this->add_users_import_log_entry(
						$report,
						$line_number,
						$username,
						$email,
						'skipped',
						__( 'Skipped in update_only mode: no existing user found for this email.', 'asset-lending-manager' )
					);
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

			// Create branch.
			if ( ! $existing_by_email instanceof WP_User ) {
				if ( 'update_only' === $import_mode ) {
					++$report['counts']['skipped'];
					$this->add_users_import_log_entry(
						$report,
						$line_number,
						$username,
						$email,
						'skipped',
						__( 'Skipped in update_only mode: user not found.', 'asset-lending-manager' )
					);
					continue;
				}
				if ( 'dry_run' === $run_mode ) {
					++$report['counts']['created'];
					$this->add_users_import_log_entry(
						$report,
						$line_number,
						$username,
						$email,
						'ok',
						__( 'Would create user (dry-run).', 'asset-lending-manager' )
					);
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
				$this->add_users_import_log_entry(
					$report,
					$line_number,
					$username,
					$email,
					'ok',
					__( 'User created.', 'asset-lending-manager' )
				);
				continue;
			}

			// Update branch.
			$existing_user = $existing_by_email;
			if ( 'create_only' === $import_mode ) {
				++$report['counts']['skipped'];
				$this->add_users_import_log_entry(
					$report,
					$line_number,
					$username,
					$email,
					'skipped',
					__( 'Skipped in create_only mode: user already exists.', 'asset-lending-manager' )
				);
				continue;
			}

			if ( $this->is_users_import_protected_user( $existing_user ) ) {
				++$report['counts']['skipped'];
				$this->add_users_import_log_entry(
					$report,
					$line_number,
					$username,
					$email,
					'skipped',
					__( 'Skipped protected administrator account.', 'asset-lending-manager' )
				);
				continue;
			}

			$user_id         = (int) $existing_user->ID;
			$target_display  = trim( $first_name . ' ' . $last_name );
			$current_first   = (string) get_user_meta( $user_id, 'first_name', true );
			$current_last    = (string) get_user_meta( $user_id, 'last_name', true );
			$current_display = (string) $existing_user->display_name;
			$needs_profile   = (
				$current_first !== $first_name ||
				$current_last !== $last_name ||
				$current_display !== $target_display
			);
			$needs_role      = $this->is_users_import_role_change_needed( $existing_user, $target_role );

			if ( ! $needs_profile && ! $needs_role ) {
				++$report['counts']['skipped'];
				$this->add_users_import_log_entry(
					$report,
					$line_number,
					$username,
					$email,
					'skipped',
					__( 'No changes required.', 'asset-lending-manager' )
				);
				continue;
			}
			if ( 'dry_run' === $run_mode ) {
				++$report['counts']['updated'];
				$this->add_users_import_log_entry(
					$report,
					$line_number,
					$username,
					$email,
					'ok',
					__( 'Would update user (dry-run).', 'asset-lending-manager' )
				);
				continue;
			}

			if ( $needs_profile ) {
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

			if ( $needs_role ) {
				$this->apply_users_import_role( $existing_user, $target_role );
			}

			++$report['counts']['updated'];
			$this->add_users_import_log_entry(
				$report,
				$line_number,
				$username,
				$email,
				'ok',
				__( 'User updated.', 'asset-lending-manager' )
			);
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
		return 1 === count( $row ) && '' === (string) $row[0];
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
	 * Return true when the importer must not update an existing account.
	 *
	 * @param WP_User $user Existing user object.
	 * @return bool
	 */
	private function is_users_import_protected_user( WP_User $user ) {
		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return true;
		}

		if ( function_exists( 'is_super_admin' ) && is_super_admin( (int) $user->ID ) ) {
			return true;
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
	 * Add a single row entry to the import log.
	 *
	 * @param array  $report Report array passed by reference.
	 * @param int    $line CSV line number.
	 * @param string $username Username value.
	 * @param string $email Email value.
	 * @param string $status Row status (ok|skipped|error).
	 * @param string $message Human-readable message.
	 * @return void
	 */
	private function add_users_import_log_entry( array &$report, $line, $username, $email, $status, $message ) {
		$report['logs'][] = array(
			'line'     => (int) $line,
			'username' => (string) $username,
			'email'    => (string) $email,
			'status'   => (string) $status,
			'message'  => (string) $message,
		);
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
		$this->add_users_import_log_entry(
			$report,
			$line,
			$username,
			$email,
			'error',
			$message
		);

		$report['errors'][] = array(
			'line'     => (int) $line,
			'username' => (string) $username,
			'email'    => (string) $email,
			'message'  => (string) $message,
		);
		++$report['counts']['errors'];
	}

	/**
	 * Return whether current user can import assets CSV.
	 *
	 * Allowed: administrators and operators.
	 *
	 * @return bool
	 */
	private function current_user_can_import_assets_csv() {
		return current_user_can( 'manage_options' ) || current_user_can( ALMGR_EDIT_ASSET );
	}

	/**
	 * Create default assets import report structure.
	 *
	 * @param string $import_mode Import mode.
	 * @param string $run_mode Run mode.
	 * @param string $file_name Uploaded file name.
	 * @return array
	 */
	private function new_assets_import_report( $import_mode, $run_mode, $file_name ) {
		return array(
			'import_mode' => $import_mode,
			'run_mode'    => $run_mode,
			'file_name'   => $file_name,
			'header'      => 'Title;Structure;Type;State;Level;External_Code;Description;Manufacturer;Model;Wp_Status;Kit_Component_Titles',
			'counts'      => array(
				'processed' => 0,
				'created'   => 0,
				'updated'   => 0,
				'skipped'   => 0,
				'errors'    => 0,
			),
			'logs'        => array(),
			'errors'      => array(),
		);
	}

	/**
	 * Read and validate uploaded assets CSV file from request.
	 *
	 * @return array|WP_Error
	 */
	private function get_assets_csv_upload() {
		check_admin_referer( 'almgr_import_assets_csv_action', 'almgr_import_assets_csv_nonce' );

		if ( ! isset( $_FILES['almgr_assets_csv_file'] ) || ! is_array( $_FILES['almgr_assets_csv_file'] ) ) {
			return new WP_Error( 'missing_file', __( 'Select a CSV file to import.', 'asset-lending-manager' ) );
		}

		$error_code = isset( $_FILES['almgr_assets_csv_file']['error'] ) ? absint( wp_unslash( $_FILES['almgr_assets_csv_file']['error'] ) ) : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error_code ) {
			return new WP_Error( 'upload_error', __( 'File upload failed. Please try again.', 'asset-lending-manager' ) );
		}

		$file_name = isset( $_FILES['almgr_assets_csv_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['almgr_assets_csv_file']['name'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- tmp_name is generated by PHP; unslashing or sanitizing it can corrupt valid upload paths on Windows. It is validated with is_uploaded_file() before use.
		$tmp_name  = isset( $_FILES['almgr_assets_csv_file']['tmp_name'] ) ? (string) $_FILES['almgr_assets_csv_file']['tmp_name'] : '';
		$file_size = isset( $_FILES['almgr_assets_csv_file']['size'] ) ? absint( wp_unslash( $_FILES['almgr_assets_csv_file']['size'] ) ) : 0;

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
	 * Execute or simulate assets CSV import (create-only MVP).
	 *
	 * @param string $file_path CSV absolute path.
	 * @param string $file_name Original uploaded file name.
	 * @param string $import_mode Import mode.
	 * @param string $run_mode Run mode.
	 * @return array
	 */
	private function process_assets_csv_import( $file_path, $file_name, $import_mode, $run_mode ) {
		$report          = $this->new_assets_import_report( $import_mode, $run_mode, $file_name );
		$expected_header = array(
			'Title',
			'Structure',
			'Type',
			'State',
			'Level',
			'External_Code',
			'Description',
			'Manufacturer',
			'Model',
			'Wp_Status',
			'Kit_Component_Titles',
		);

		try {
			$file = new SplFileObject( $file_path );
			$file->setFlags( SplFileObject::READ_CSV );
			$file->setCsvControl( ';', '"', '\\' );
		} catch ( RuntimeException $exception ) {
			$this->add_assets_import_error(
				$report,
				1,
				'',
				__( 'Unable to read the uploaded CSV file.', 'asset-lending-manager' )
			);
			return $report;
		}

		$header_row = $file->fgetcsv( ';', '"', '\\' );
		$header_row = $this->normalize_assets_csv_row( $header_row );
		if ( $expected_header !== $header_row ) {
			$this->add_assets_import_error(
				$report,
				1,
				'',
				sprintf(
					/* translators: %s: expected CSV header with semicolon delimiter. */
					__( 'Invalid CSV header. Expected exactly: %s', 'asset-lending-manager' ),
					'Title;Structure;Type;State;Level;External_Code;Description;Manufacturer;Model;Wp_Status;Kit_Component_Titles'
				)
			);
			return $report;
		}

		$assets_indexes  = $this->get_assets_import_existing_indexes();
		$seen_title_keys = array();
		$queued_kits     = array();
		$line_number     = 1;

		while ( ! $file->eof() ) {
			++$line_number;
			$row = $file->fgetcsv( ';', '"', '\\' );
			if ( false === $row ) {
				continue;
			}

			$row = $this->normalize_assets_csv_row( $row );
			if ( $this->is_empty_assets_csv_row( $row ) ) {
				continue;
			}
			if ( 11 !== count( $row ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					'',
					__( 'Invalid number of columns. Use only the ; delimiter.', 'asset-lending-manager' )
				);
				continue;
			}

			$asset_title_raw    = $row[0];
			$structure_raw      = $row[1];
			$type_raw           = $row[2];
			$state_raw          = $row[3];
			$level_raw          = $row[4];
			$external_code_raw  = $row[5];
			$description_raw    = $row[6];
			$manufacturer_raw   = $row[7];
			$model_raw          = $row[8];
			$wp_status_raw      = $row[9];
			$kit_components_raw = $row[10];

			if ( '' === $asset_title_raw || '' === $structure_raw || '' === $type_raw || '' === $state_raw || '' === $level_raw || '' === $manufacturer_raw || '' === $model_raw ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title_raw,
					__( 'Title, Structure, Type, State, Level, Manufacturer and Model are required for every row.', 'asset-lending-manager' )
				);
				continue;
			}

			$asset_title = sanitize_text_field( $asset_title_raw );
			if ( '' === $asset_title ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title_raw,
					__( 'Invalid title.', 'asset-lending-manager' )
				);
				continue;
			}

			$asset_title_key  = $this->normalize_assets_import_title_key( $asset_title );
			$asset_title_slug = sanitize_title( $asset_title );
			if ( '' === $asset_title_key || '' === $asset_title_slug ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					__( 'Unable to derive a valid title key from Title.', 'asset-lending-manager' )
				);
				continue;
			}

			if ( isset( $seen_title_keys[ $asset_title_key ] ) ) {
				++$report['counts']['skipped'];
				$this->add_assets_import_log_entry(
					$report,
					$line_number,
					$asset_title,
					'skipped',
					__( 'Skipped duplicate title in the same CSV file.', 'asset-lending-manager' )
				);
				continue;
			}
			$seen_title_keys[ $asset_title_key ] = true;

			if ( ! empty( $assets_indexes['by_title'][ $asset_title_key ] ) ) {
				++$report['counts']['skipped'];
				$this->add_assets_import_log_entry(
					$report,
					$line_number,
					$asset_title,
					'skipped',
					__( 'Skipped in create-only mode: asset already exists.', 'asset-lending-manager' )
				);
				continue;
			}

			$structure = sanitize_key( $structure_raw );
			if ( ! in_array( $structure, array( ALMGR_ASSET_COMPONENT_SLUG, ALMGR_ASSET_KIT_SLUG ), true ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					__( 'Invalid Structure. Allowed values are: component, kit.', 'asset-lending-manager' )
				);
				continue;
			}

			$type = sanitize_title( $type_raw );
			if ( '' === $type || ! $this->assets_import_term_exists( ALMGR_ASSET_TYPE_TAXONOMY_SLUG, $type ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					__( 'Invalid Type. Use an existing type taxonomy slug.', 'asset-lending-manager' )
				);
				continue;
			}

			$state = sanitize_key( $state_raw );
			if ( ! in_array( $state, array( 'available', 'maintenance', 'retired' ), true ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					__( 'Invalid State. Allowed values are: available, maintenance, retired.', 'asset-lending-manager' )
				);
				continue;
			}
			if ( ! $this->assets_import_term_exists( ALMGR_ASSET_STATE_TAXONOMY_SLUG, $state ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					__( 'Invalid State taxonomy term.', 'asset-lending-manager' )
				);
				continue;
			}

			$level = sanitize_key( $level_raw );
			if ( '' === $level || ! $this->assets_import_term_exists( ALMGR_ASSET_LEVEL_TAXONOMY_SLUG, $level ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					__( 'Invalid Level. Use an existing level taxonomy slug.', 'asset-lending-manager' )
				);
				continue;
			}

			if ( ! $this->assets_import_term_exists( ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, $structure ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					__( 'Invalid Structure taxonomy term.', 'asset-lending-manager' )
				);
				continue;
			}

			$manufacturer = sanitize_text_field( $manufacturer_raw );
			$model        = sanitize_text_field( $model_raw );
			if ( '' === $manufacturer || '' === $model ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					__( 'Manufacturer and Model cannot be empty after sanitization.', 'asset-lending-manager' )
				);
				continue;
			}

			$wp_status = sanitize_key( $wp_status_raw );
			if ( '' === $wp_status ) {
				$wp_status = 'publish';
			}
			if ( ! in_array( $wp_status, array( 'publish', 'draft' ), true ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					__( 'Invalid Wp_Status. Allowed values are: publish, draft.', 'asset-lending-manager' )
				);
				continue;
			}

			$asset_row = array(
				'title'                    => $asset_title,
				'title_key'                => $asset_title_key,
				'title_slug'               => $asset_title_slug,
				'structure'                => $structure,
				'type'                     => $type,
				'state'                    => $state,
				'level'                    => $level,
				'external_code'            => sanitize_text_field( $external_code_raw ),
				'description'              => wp_kses_post( $description_raw ),
				'manufacturer'             => $manufacturer,
				'model'                    => $model,
				'wp_status'                => $wp_status,
				'kit_component_titles_raw' => (string) $kit_components_raw,
			);

			++$report['counts']['processed'];

			if ( ALMGR_ASSET_KIT_SLUG === $structure ) {
				$queued_kits[] = array(
					'line' => $line_number,
					'row'  => $asset_row,
				);
				continue;
			}

			$create_result = $this->create_assets_import_asset( $asset_row, $run_mode, $line_number, $report );
			if ( is_wp_error( $create_result ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					$create_result->get_error_message()
				);
				continue;
			}

			$assets_indexes['by_title'][ $asset_title_key ][] = $create_result;
			$this->append_assets_import_slug_reference( $assets_indexes['by_slug'], $asset_title_slug, $create_result );
		}

		foreach ( $queued_kits as $queued_kit ) {
			$line_number = (int) $queued_kit['line'];
			$asset_row   = (array) $queued_kit['row'];
			$asset_title = (string) $asset_row['title'];
			$asset_slug  = (string) $asset_row['title_slug'];

			$component_titles = $this->parse_assets_import_kit_component_titles( (string) $asset_row['kit_component_titles_raw'] );
			$component_ids    = array();
			$row_errors       = array();

			foreach ( $component_titles as $component_title ) {
				$component_slug = sanitize_title( $component_title );
				if ( '' === $component_slug ) {
					$row_errors[] = sprintf(
						/* translators: %s: invalid kit component title. */
						__( 'Component "%s" is not valid.', 'asset-lending-manager' ),
						$component_title
					);
					continue;
				}

				if ( $component_slug === $asset_slug ) {
					$row_errors[] = sprintf(
						/* translators: %s: kit title. */
						__( 'Kit "%s" cannot include itself.', 'asset-lending-manager' ),
						$asset_title
					);
					continue;
				}

				$matches = isset( $assets_indexes['by_slug'][ $component_slug ] ) && is_array( $assets_indexes['by_slug'][ $component_slug ] )
					? $assets_indexes['by_slug'][ $component_slug ]
					: array();

				if ( empty( $matches ) ) {
					$row_errors[] = sprintf(
						/* translators: %s: missing kit component title from CSV. */
						__( 'Component not found: "%s".', 'asset-lending-manager' ),
						$component_title
					);
					continue;
				}

				if ( count( $matches ) > 1 ) {
					$row_errors[] = sprintf(
						/* translators: %s: ambiguous kit component title from CSV. */
						__( 'Ambiguous component slug for "%s".', 'asset-lending-manager' ),
						$component_title
					);
					continue;
				}

				$component_ids[] = (int) ( $matches[0]['id'] ?? 0 );
			}

			if ( ! empty( $row_errors ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					implode( ' ', $row_errors )
				);
				continue;
			}

			$create_result = $this->create_assets_import_asset( $asset_row, $run_mode, $line_number, $report, $component_ids );
			if ( is_wp_error( $create_result ) ) {
				$this->add_assets_import_error(
					$report,
					$line_number,
					$asset_title,
					$create_result->get_error_message()
				);
				continue;
			}

			$assets_indexes['by_title'][ (string) $asset_row['title_key'] ][] = $create_result;
			$this->append_assets_import_slug_reference( $assets_indexes['by_slug'], $asset_slug, $create_result );
		}

		return $report;
	}

	/**
	 * Build existing assets indexes for title-based and slug-based lookups.
	 *
	 * @return array
	 */
	private function get_assets_import_existing_indexes() {
		$indexes = array(
			'by_title' => array(),
			'by_slug'  => array(),
		);

		$asset_ids = get_posts(
			array(
				'post_type'              => ALMGR_ASSET_CPT_SLUG,
				'post_status'            => array( 'draft', 'publish' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $asset_ids as $asset_id ) {
			$asset_id    = (int) $asset_id;
			$asset_title = (string) get_the_title( $asset_id );
			$title_key   = $this->normalize_assets_import_title_key( $asset_title );
			$title_slug  = sanitize_title( $asset_title );

			if ( '' === $title_key || '' === $title_slug ) {
				continue;
			}

			$asset_ref = array(
				'id'    => $asset_id,
				'title' => $asset_title,
				'slug'  => $title_slug,
			);

			$indexes['by_title'][ $title_key ][] = $asset_ref;
			$this->append_assets_import_slug_reference( $indexes['by_slug'], $title_slug, $asset_ref );
		}

		return $indexes;
	}

	/**
	 * Create an asset row (or simulate creation in dry-run mode).
	 *
	 * @param array  $asset_row Sanitized asset row.
	 * @param string $run_mode Run mode.
	 * @param int    $line_number CSV line number.
	 * @param array  $report Report array passed by reference.
	 * @param array  $component_ids Kit component IDs (only used when structure=kit).
	 * @return array|WP_Error
	 */
	private function create_assets_import_asset( array $asset_row, $run_mode, $line_number, array &$report, array $component_ids = array() ) {
		$asset_title = (string) $asset_row['title'];
		$title_slug  = (string) $asset_row['title_slug'];
		$structure   = (string) $asset_row['structure'];

		if ( 'dry_run' === $run_mode ) {
			++$report['counts']['created'];
			$this->add_assets_import_log_entry(
				$report,
				$line_number,
				$asset_title,
				'ok',
				__( 'Would create asset (dry-run).', 'asset-lending-manager' )
			);

			return array(
				'id'    => -1 * abs( (int) $line_number ),
				'title' => $asset_title,
				'slug'  => $title_slug,
			);
		}

		$asset_id = wp_insert_post(
			array(
				'post_type'    => ALMGR_ASSET_CPT_SLUG,
				'post_title'   => $asset_title,
				'post_content' => (string) $asset_row['description'],
				'post_status'  => (string) $asset_row['wp_status'],
			),
			true
		);

		if ( is_wp_error( $asset_id ) ) {
			return $asset_id;
		}

		$asset_id = (int) $asset_id;
		$terms    = array(
			ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG => (string) $asset_row['structure'],
			ALMGR_ASSET_TYPE_TAXONOMY_SLUG      => (string) $asset_row['type'],
			ALMGR_ASSET_STATE_TAXONOMY_SLUG     => (string) $asset_row['state'],
			ALMGR_ASSET_LEVEL_TAXONOMY_SLUG     => (string) $asset_row['level'],
		);

		foreach ( $terms as $taxonomy => $term_slug ) {
			$set_terms_result = wp_set_object_terms( $asset_id, $term_slug, $taxonomy, false );
			if ( is_wp_error( $set_terms_result ) ) {
				wp_delete_post( $asset_id, true );
				return new WP_Error(
					'assets_import_term_assignment_failed',
					sprintf(
						/* translators: %s: taxonomy slug. */
						__( 'Unable to assign taxonomy term for %s.', 'asset-lending-manager' ),
						$taxonomy
					)
				);
			}
		}

		ALMGR_ACF_Asset_Adapter::set_custom_field( 'almgr_manufacturer', (string) $asset_row['manufacturer'], $asset_id );
		ALMGR_ACF_Asset_Adapter::set_custom_field( 'almgr_model', (string) $asset_row['model'], $asset_id );
		update_post_meta( $asset_id, '_almgr_current_owner', 0 );

		if ( '' !== (string) $asset_row['external_code'] ) {
			ALMGR_ACF_Asset_Adapter::set_custom_field( 'almgr_external_code', (string) $asset_row['external_code'], $asset_id );
		} else {
			ALMGR_ACF_Asset_Adapter::delete_custom_field( 'almgr_external_code', $asset_id );
		}

		if ( ALMGR_ASSET_KIT_SLUG === $structure && ! empty( $component_ids ) ) {
			$save_components_result = $this->save_assets_import_kit_components( $asset_id, $component_ids );
			if ( is_wp_error( $save_components_result ) ) {
				wp_delete_post( $asset_id, true );
				return $save_components_result;
			}
		}

		++$report['counts']['created'];
		$this->add_assets_import_log_entry(
			$report,
			$line_number,
			$asset_title,
			'ok',
			__( 'Asset created.', 'asset-lending-manager' )
		);

		return array(
			'id'    => $asset_id,
			'title' => $asset_title,
			'slug'  => $title_slug,
		);
	}

	/**
	 * Save kit components relation for imported kit assets.
	 *
	 * @param int   $kit_id Kit post ID.
	 * @param array $component_ids Component post IDs.
	 * @return true|WP_Error
	 */
	private function save_assets_import_kit_components( $kit_id, array $component_ids ) {
		$component_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $component_ids ),
					static function ( $value ) {
						return $value > 0;
					}
				)
			)
		);

		if ( empty( $component_ids ) ) {
			return true;
		}

		$update_result = ALMGR_ACF_Asset_Adapter::set_custom_field( 'almgr_components', $component_ids, $kit_id );
		if ( ! $update_result ) {
			return new WP_Error(
				'assets_import_components_update_failed',
				__( 'Unable to link kit components using ACF.', 'asset-lending-manager' )
			);
		}

		return true;
	}

	/**
	 * Return true when a taxonomy term slug exists.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $term_slug Term slug.
	 * @return bool
	 */
	private function assets_import_term_exists( $taxonomy, $term_slug ) {
		$term = term_exists( $term_slug, $taxonomy );
		return ! empty( $term );
	}

	/**
	 * Normalize a CSV row for assets import.
	 *
	 * @param array|false $row Raw CSV row.
	 * @return array
	 */
	private function normalize_assets_csv_row( $row ) {
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
	 * Return true when an assets CSV row is empty.
	 *
	 * @param array $row CSV row.
	 * @return bool
	 */
	private function is_empty_assets_csv_row( array $row ) {
		if ( empty( $row ) ) {
			return true;
		}
		return 1 === count( $row ) && '' === (string) $row[0];
	}

	/**
	 * Normalize title lookup key for assets import matching.
	 *
	 * @param string $title Asset title.
	 * @return string
	 */
	private function normalize_assets_import_title_key( $title ) {
		$title = trim( wp_strip_all_tags( (string) $title ) );
		if ( '' === $title ) {
			return '';
		}
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $title, 'UTF-8' );
		}
		return strtolower( $title );
	}

	/**
	 * Parse kit component titles from CSV cell.
	 *
	 * @param string $raw_value Raw CSV value.
	 * @return array
	 */
	private function parse_assets_import_kit_component_titles( $raw_value ) {
		$raw_value = trim( (string) $raw_value );
		if ( '' === $raw_value ) {
			return array();
		}

		$parts  = explode( '|', $raw_value );
		$titles = array();
		foreach ( $parts as $part ) {
			$part = trim( (string) $part );
			if ( '' === $part ) {
				continue;
			}

			$key = $this->normalize_assets_import_title_key( $part );
			if ( isset( $titles[ $key ] ) ) {
				continue;
			}
			$titles[ $key ] = $part;
		}

		return array_values( $titles );
	}

	/**
	 * Append an asset reference to a slug index.
	 *
	 * @param array  $index Slug index passed by reference.
	 * @param string $slug Slug key.
	 * @param array  $asset_ref Asset reference.
	 * @return void
	 */
	private function append_assets_import_slug_reference( array &$index, $slug, array $asset_ref ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return;
		}
		if ( ! isset( $index[ $slug ] ) || ! is_array( $index[ $slug ] ) ) {
			$index[ $slug ] = array();
		}
		$index[ $slug ][] = $asset_ref;
	}

	/**
	 * Add a single row entry to assets import log.
	 *
	 * @param array  $report Report array passed by reference.
	 * @param int    $line CSV line number.
	 * @param string $title Asset title.
	 * @param string $status Row status (ok|skipped|error).
	 * @param string $message Human-readable message.
	 * @return void
	 */
	private function add_assets_import_log_entry( array &$report, $line, $title, $status, $message ) {
		$report['logs'][] = array(
			'line'    => (int) $line,
			'title'   => (string) $title,
			'status'  => (string) $status,
			'message' => (string) $message,
		);
	}

	/**
	 * Add an error row to assets import report.
	 *
	 * @param array  $report Report array passed by reference.
	 * @param int    $line CSV line number.
	 * @param string $title Asset title.
	 * @param string $message Error message.
	 * @return void
	 */
	private function add_assets_import_error( array &$report, $line, $title, $message ) {
		$this->add_assets_import_log_entry(
			$report,
			$line,
			$title,
			'error',
			$message
		);

		$report['errors'][] = array(
			'line'    => (int) $line,
			'title'   => (string) $title,
			'message' => (string) $message,
		);

		++$report['counts']['errors'];
	}

	/**
	 * Store assets import report for current user.
	 *
	 * @param array $report Assets import report.
	 * @return void
	 */
	private function store_assets_import_report_for_current_user( array $report ) {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		set_transient(
			'almgr_assets_import_report_' . $user_id,
			$report,
			15 * MINUTE_IN_SECONDS
		);
	}

	/**
	 * Read the latest assets import report for current user.
	 *
	 * @return array
	 */
	private function get_assets_import_report_for_current_user() {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return array();
		}

		$report = get_transient( 'almgr_assets_import_report_' . $user_id );
		return is_array( $report ) ? $report : array();
	}

	/**
	 * Redirect to the assets import section in Tools page.
	 *
	 * @return void
	 */
	private function redirect_to_tools_assets_import_tab() {
		wp_safe_redirect(
			add_query_arg(
				array(
					'tab'                        => 'import',
					'section'                    => 'assets',
					'almgr_assets_import_report' => '1',
				),
				admin_url( 'admin.php?page=almgr-tools' )
			)
		);
		exit;
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
					'section'                   => 'users',
					'almgr_users_import_report' => '1',
				),
				admin_url( 'admin.php?page=almgr-tools' )
			)
		);
		exit;
	}
}
