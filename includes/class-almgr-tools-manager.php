<?php
/**
 * Tools Manager.
 *
 * Handles Tools actions (Import/Export/Utilities).
 * Current implementation covers Users CSV import.
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
		// Users CSV export action (Tools > Export > Users).
		add_action(
			'admin_post_almgr_export_users_csv',
			array( $this, 'handle_export_users_csv' ),
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
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writing BOM to php://output stream.
		fwrite( $output, "\xEF\xBB\xBF" );

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

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing php://output stream handle after CSV stream.
		fclose( $output );
		exit;
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
