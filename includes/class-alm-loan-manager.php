<?php
/**
 * Asset Lending Manager - Loan Manager
 *
 * Handles loan request logic and database operations.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ALM_Loan_Manager
 */
class ALM_Loan_Manager {

	/**
	 * Maximum length for rejection message.
	 */
	const REJECTION_MESSAGE_MAX_LENGTH = 255;

	/**
	 * Maximum length for loan request message.
	 */
	const SEND_REQUEST_MESSAGE_MAX_LENGTH = 500;

	/**
	 * Maximum length for direct assignment reason.
	 */
	const DIRECT_ASSIGN_REASON_MAX_LENGTH = 500;

	/**
	 * User ID for automatic system operations.
	 *
	 * Used as fallback when the setting is not configured.
	 */
	const AUTOMATIC_OPERATIONS_OPERATOR_ID = 1;

	/**
	 * Settings manager instance.
	 *
	 * @var ALM_Settings_Manager
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param ALM_Settings_Manager $settings Plugin settings instance.
	 */
	public function __construct( ALM_Settings_Manager $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public function activate() {
		require_once plugin_dir_path( __FILE__ ) . 'class-alm-installer.php';
		ALM_Installer::create_tables();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		// AJAX handler for authenticated users.
		add_action( 'wp_ajax_alm_submit_loan_request', array( $this, 'ajax_submit_loan_request' ) );
		add_action( 'wp_ajax_alm_reject_loan_request', array( $this, 'ajax_reject_loan_request' ) );
		add_action( 'wp_ajax_alm_approve_loan_request', array( $this, 'ajax_approve_loan_request' ) );
		add_action( 'wp_ajax_alm_direct_assign_asset', array( $this, 'ajax_direct_assign_asset' ) );
	}

	/**
	 * AJAX handler for loan request submission.
	 *
	 * @return void
	 */
	public function ajax_submit_loan_request() {
		// Verify nonce.
		check_ajax_referer( 'alm_loan_request_nonce', 'nonce' );
		// Check user capabilities.
		if ( ! current_user_can( ALM_VIEW_ASSET ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to request loans.', 'asset-lending-manager' ),
				)
			);
		}
		// Get and validate input.
		$asset_id = isset( $_POST['asset_id'] ) ? absint( $_POST['asset_id'] ) : 0;
		$message  = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		// Validate message length.
		if ( empty( $message ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Request message is required.', 'asset-lending-manager' ),
				)
			);
		}
		$request_max = (int) $this->settings->get( 'loans.request_message_max_length', self::SEND_REQUEST_MESSAGE_MAX_LENGTH );
		if ( $request_max > 0 && mb_strlen( $message ) > $request_max ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: maximum number of characters allowed in request message */
						__( 'Request message must not exceed %d characters.', 'asset-lending-manager' ),
						$request_max
					),
				)
			);
		}
		if ( $asset_id <= 0 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid asset ID.', 'asset-lending-manager' ),
				)
			);
		}
		// Verify asset exists.
		$asset = get_post( $asset_id );
		if ( ! $asset || ALM_ASSET_CPT_SLUG !== $asset->post_type ) {
			wp_send_json_error(
				array(
					'message' => __( 'Asset not found.', 'asset-lending-manager' ),
				)
			);
		}
		$requester_id = get_current_user_id();
		// Get current owner (if any).
		$owner_id = $this->get_current_owner( $asset_id );
		// Current owner cannot request a loan for the same asset.
		if ( $owner_id > 0 && $requester_id === $owner_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'You already own this asset and cannot request it.', 'asset-lending-manager' ),
				)
			);
		}
		// Check if user already has a pending request for this asset.
		if ( $this->has_pending_request( $asset_id, $requester_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You already have a pending request for this asset.', 'asset-lending-manager' ),
				)
			);
		}
		// Create the loan request.
		$request_id = $this->create_loan_request( $asset_id, $requester_id, $owner_id, $message );
		if ( ! $request_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to create loan request.', 'asset-lending-manager' ),
				)
			);
		}
		// Log the request.
		ALM_Logger::info(
			'Loan request created',
			array(
				'request_id'   => $request_id,
				'asset_id'     => $asset_id,
				'requester_id' => $requester_id,
				'owner_id'     => $owner_id,
			)
		);
		// Fire loan request submitted action so ALM_Notification_Manager can send emails.
		do_action( 'alm_loan_request_submitted', $requester_id, $owner_id, $asset_id, $message );
		wp_send_json_success(
			array(
				'message'    => __( 'Loan request sent successfully!', 'asset-lending-manager' ),
				'request_id' => $request_id,
			)
		);
	}

	/**
	 * AJAX handler for loan request rejection.
	 *
	 * @return void
	 */
	public function ajax_reject_loan_request() {
		// Verify nonce.
		check_ajax_referer( 'alm_loan_request_nonce', 'nonce' );
		// Fail-fast capability check.
		if ( ! current_user_can( ALM_VIEW_ASSET ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to reject loan requests.', 'asset-lending-manager' ),
				)
			);
		}

		// Get and validate input.
		$request_id        = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
		$asset_id          = isset( $_POST['asset_id'] ) ? absint( $_POST['asset_id'] ) : 0;
		$rejection_message = isset( $_POST['rejection_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['rejection_message'] ) ) : '';

		// Validate inputs.
		if ( $request_id <= 0 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid request ID.', 'asset-lending-manager' ),
				)
			);
		}

		if ( $asset_id <= 0 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid asset ID.', 'asset-lending-manager' ),
				)
			);
		}

		// Validate rejection message.
		if ( empty( $rejection_message ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Rejection message is required.', 'asset-lending-manager' ),
				)
			);
		}

		$rejection_max = (int) $this->settings->get( 'loans.rejection_message_max_length', self::REJECTION_MESSAGE_MAX_LENGTH );
		if ( $rejection_max > 0 && mb_strlen( $rejection_message ) > $rejection_max ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: maximum number of characters allowed in rejection message */
						__( 'Rejection message must not exceed %d characters.', 'asset-lending-manager' ),
						$rejection_max
					),
				)
			);
		}

		// Get loan request from database.
		global $wpdb;
		$table_name       = $wpdb->prefix . 'alm_loan_requests';
			$loan_request = $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
					"SELECT * FROM $table_name WHERE id = %d",
					$request_id
				)
			);

		if ( ! $loan_request ) {
			wp_send_json_error(
				array(
					'message' => __( 'Loan request not found.', 'asset-lending-manager' ),
				)
			);
		}

		// Verify asset_id matches.
		if ( (int) $loan_request->asset_id !== $asset_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Asset ID mismatch.', 'asset-lending-manager' ),
				)
			);
		}

		// Check user permissions.
		$current_user_id = get_current_user_id();
		$can_reject      = $this->can_user_reject_request( $loan_request, $current_user_id );

		if ( ! $can_reject ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to reject this request.', 'asset-lending-manager' ),
				)
			);
		}

		// Reject the loan request (atomic operation).
		$result = $this->reject_loan_request( $loan_request, $rejection_message, $current_user_id );

		if ( ! $result ) {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to reject loan request.', 'asset-lending-manager' ),
				)
			);
		}

		// Log the rejection.
		ALM_Logger::info(
			'Loan request rejected',
			array(
				'request_id'   => $request_id,
				'asset_id'     => $asset_id,
				'requester_id' => $loan_request->requester_id,
				'rejected_by'  => $current_user_id,
			)
		);

		// Fire loan request rejected action so ALM_Notification_Manager can send emails.
		do_action( 'alm_loan_request_rejected', $loan_request, $rejection_message );

		wp_send_json_success(
			array(
				'message' => __( 'Loan request rejected successfully.', 'asset-lending-manager' ),
			)
		);
	}

	/**
	 * Check if user can reject a loan request.
	 *
	 * @param object $loan_request Loan request object from database.
	 * @param int    $user_id      User ID.
	 * @return bool True if user can reject.
	 */
	private function can_user_reject_request( $loan_request, $user_id ) {
		// Operators can reject any request, including unowned assets.
		if ( user_can( $user_id, ALM_EDIT_ASSET ) ) {
			return true;
		}

		// Current owner can reject requests for their assets.
		if ( (int) $loan_request->owner_id === $user_id && (int) $loan_request->owner_id > 0 ) {
			return true;
		}

		// Check if user is the current assignee of the asset.
		$current_owner = $this->get_current_owner( $loan_request->asset_id );
		if ( $current_owner === $user_id && $current_owner > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Reject a loan request (atomic operation).
	 *
	 * This method performs the following operations atomically:
	 * 1. Insert record into history table
	 * 2. Delete record from requests table
	 *
	 * @param object $loan_request      Loan request object from database.
	 * @param string $rejection_message Rejection message.
	 * @param int    $rejected_by       User ID who rejected the request.
	 * @throws Exception When a transactional operation fails (caught internally).
	 * @return bool True on success, false on failure.
	 */
	private function reject_loan_request( $loan_request, $rejection_message, $rejected_by ) {
		global $wpdb;

		// Start transaction.
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Insert into history table.
			$history_inserted = $this->log_history_entry(
				$loan_request->id,
				$loan_request->asset_id,
				$loan_request->requester_id,
				$loan_request->owner_id,
				'rejected',
				$rejection_message,
				$rejected_by
			);

			if ( ! $history_inserted ) {
				throw new Exception( 'Failed to insert history record' );
			}

			// Delete from requests table.
			$table_name = $wpdb->prefix . 'alm_loan_requests';
			$deleted    = $wpdb->delete(
				$table_name,
				array( 'id' => $loan_request->id ),
				array( '%d' )
			);

			if ( false === $deleted ) {
				throw new Exception( 'Failed to delete loan request' );
			}

			// Commit transaction.
			$wpdb->query( 'COMMIT' );

			ALM_Logger::debug(
				'Loan request rejected successfully',
				array(
					'request_id' => $loan_request->id,
					'asset_id'   => $loan_request->asset_id,
				)
			);

			return true;

		} catch ( Exception $e ) {
			// Rollback transaction on error.
			$wpdb->query( 'ROLLBACK' );

			ALM_Logger::error(
				'Failed to reject loan request',
				array(
					'request_id' => $loan_request->id,
					'error'      => $e->getMessage(),
					'db_error'   => $wpdb->last_error,
				)
			);

			return false;
		}
	}

	/**
	 * Log an entry in the loan requests history table.
	 *
	 * @param int    $loan_request_id Original loan request ID.
	 * @param int    $asset_id        Asset ID.
	 * @param int    $requester_id    Requester user ID.
	 * @param int    $owner_id        Owner user ID.
	 * @param string $status          Status (approved, rejected, canceled).
	 * @param string $message         Status message.
	 * @param int    $changed_by      User ID who made the change.
	 * @return bool True on success, false on failure.
	 */
	private function log_history_entry( $loan_request_id, $asset_id, $requester_id, $owner_id, $status, $message, $changed_by ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alm_loan_requests_history';

		$result = $wpdb->insert(
			$table_name,
			array(
				'loan_request_id' => $loan_request_id,
				'asset_id'        => $asset_id,
				'requester_id'    => $requester_id,
				'owner_id'        => $owner_id,
				'status'          => $status,
				'message'         => $message,
				'changed_at'      => current_time( 'mysql' ),
				'changed_by'      => $changed_by,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d' )
		);

		if ( false === $result ) {
			ALM_Logger::error(
				'Failed to insert history entry',
				array(
					'loan_request_id' => $loan_request_id,
					'db_error'        => $wpdb->last_error,
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * AJAX handler for loan request approval.
	 *
	 * @return void
	 */
	public function ajax_approve_loan_request() {
		// Verify nonce.
		check_ajax_referer( 'alm_loan_request_nonce', 'nonce' );
		// Fail-fast capability check.
		if ( ! current_user_can( ALM_VIEW_ASSET ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to approve loan requests.', 'asset-lending-manager' ),
				)
			);
		}

		// Get and validate input.
		$request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
		$asset_id   = isset( $_POST['asset_id'] ) ? absint( $_POST['asset_id'] ) : 0;

		// Validate inputs.
		if ( $request_id <= 0 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid request ID.', 'asset-lending-manager' ),
				)
			);
		}

		if ( $asset_id <= 0 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid asset ID.', 'asset-lending-manager' ),
				)
			);
		}

		// Get loan request from database.
		global $wpdb;
		$table_name       = $wpdb->prefix . 'alm_loan_requests';
			$loan_request = $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
					"SELECT * FROM $table_name WHERE id = %d",
					$request_id
				)
			);

		if ( ! $loan_request ) {
			wp_send_json_error(
				array(
					'message' => __( 'Loan request not found.', 'asset-lending-manager' ),
				)
			);
		}

		// Verify asset_id matches.
		if ( (int) $loan_request->asset_id !== $asset_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Asset ID mismatch.', 'asset-lending-manager' ),
				)
			);
		}

		// Check user permissions.
		$current_user_id = get_current_user_id();
		$can_approve     = $this->can_user_approve_request( $loan_request, $current_user_id );

		if ( ! $can_approve ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to approve this request.', 'asset-lending-manager' ),
				)
			);
		}

		// Approve the loan request (atomic operation).
		$result = $this->approve_loan_request( $loan_request, $current_user_id );

		if ( ! $result['success'] ) {
			wp_send_json_error(
				array(
					'message' => $result['message'],
				)
			);
		}

		// Log the approval.
		ALM_Logger::info(
			'Loan request approved',
			array(
				'request_id'   => $request_id,
				'asset_id'     => $asset_id,
				'requester_id' => $loan_request->requester_id,
				'approved_by'  => $current_user_id,
			)
		);

		// Fire loan request approved action so ALM_Notification_Manager can send emails.
		do_action( 'alm_loan_request_approved', $loan_request );

		wp_send_json_success(
			array(
				'message' => __( 'Loan request approved successfully. The page will reload.', 'asset-lending-manager' ),
			)
		);
	}

	/**
	 * Create a loan request in the database (atomic operation).
	 *
	 * @param int    $asset_id     Asset ID.
	 * @param int    $requester_id Requester user ID.
	 * @param int    $owner_id     Current owner user ID (0 if none).
	 * @param string $message      Request message.
	 * @throws Exception When a transactional operation fails (caught internally).
	 * @return int|false Request ID on success, false on failure.
	 */
	private function create_loan_request( $asset_id, $requester_id, $owner_id, $message ) {
		global $wpdb;
		// Start transaction.
		$wpdb->query( 'START TRANSACTION' );
		try {
			$table_name = $wpdb->prefix . 'alm_loan_requests';
			$result     = $wpdb->insert(
				$table_name,
				array(
					'asset_id'        => $asset_id,
					'requester_id'    => $requester_id,
					'owner_id'        => $owner_id,
					'request_date'    => current_time( 'mysql' ),
					'request_message' => $message,
					'status'          => 'pending',
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s' )
			);
			if ( false === $result ) {
				throw new Exception( 'Failed to insert loan request' );
			}
			$request_id = $wpdb->insert_id;
			// Commit transaction.
			$wpdb->query( 'COMMIT' );

			ALM_Logger::debug(
				'Loan request created successfully',
				array(
					'request_id' => $request_id,
					'asset_id'   => $asset_id,
				)
			);
			return $request_id;
		} catch ( Exception $e ) {
			// Rollback transaction on error.
			$wpdb->query( 'ROLLBACK' );

			ALM_Logger::error(
				'Failed to create loan request',
				array(
					'asset_id' => $asset_id,
					'error'    => $e->getMessage(),
					'db_error' => $wpdb->last_error,
				)
			);

			return false;
		}
	}

	/**
	 * Get the current owner of an asset.
	 *
	 * @param int $asset_id Asset ID.
	 * @return int Owner user ID or 0 if none.
	 */
	public function get_current_owner( $asset_id ) {
		$owner_id = get_post_meta( $asset_id, '_alm_current_owner', true );
		return $owner_id ? (int) $owner_id : 0;
	}

	/**
	 * Check if user has a pending request for an asset.
	 *
	 * @param int $asset_id Asset ID.
	 * @param int $user_id  User ID.
	 * @return bool True if pending request exists.
	 */
	public function has_pending_request( $asset_id, $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alm_loan_requests';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
				"SELECT COUNT(*) FROM $table_name 
				WHERE asset_id = %d 
				AND requester_id = %d 
				AND status = 'pending'",
				$asset_id,
				$user_id
			)
		);
		return $count > 0;
	}

	/**
	 * Get loan requests for a specific asset.
	 *
	 * @param int    $asset_id Asset ID.
	 * @param string $status   Request status (default: 'pending').
	 * @return array Array of request objects.
	 */
	public function get_asset_requests( $asset_id, $status = 'pending' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alm_loan_requests';

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
				"SELECT * FROM $table_name 
				WHERE asset_id = %d 
				AND status = %s 
				ORDER BY request_date DESC",
				$asset_id,
				$status
			)
		);
	}

	/**
	 * Get loan requests made by a specific user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Request status (default: all).
	 * @return array Array of request objects.
	 */
	public function get_user_requests( $user_id, $status = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alm_loan_requests';

		if ( empty( $status ) ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
					"SELECT * FROM $table_name 
					WHERE requester_id = %d 
					ORDER BY request_date DESC",
					$user_id
				)
			);
		}
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
				"SELECT * FROM $table_name 
				WHERE requester_id = %d 
				AND status = %s 
				ORDER BY request_date DESC",
				$user_id,
				$status
			)
		);
	}

	/**
	 * Get loan history for a specific asset (last 10 entries).
	 *
	 * Operators see all entries, members see only entries where they are involved
	 * (as requester, owner, or changed_by).
	 *
	 * @param int $asset_id Asset ID.
	 * @param int $user_id  User ID for permission filtering (0 = no filter).
	 * @return array Array of history objects.
	 */
	public function get_asset_history( $asset_id, $user_id = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alm_loan_requests_history';

		// Check if user is operator (can see all).
		$is_operator = current_user_can( ALM_EDIT_ASSET );

		if ( $is_operator || $user_id <= 0 ) {
			// Operators see all entries for this asset.
				return $wpdb->get_results(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
						"SELECT * FROM $table_name 
						WHERE asset_id = %d 
						ORDER BY changed_at DESC 
					LIMIT 10",
						$asset_id
					)
				);
		}

		// Members see only entries where they are involved.
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
				"SELECT * FROM $table_name 
				WHERE asset_id = %d 
				AND (
					requester_id = %d 
					OR owner_id = %d 
					OR changed_by = %d
				)
				ORDER BY changed_at DESC 
				LIMIT 10",
				$asset_id,
				$user_id,
				$user_id,
				$user_id
			)
		);
	}

	/**
	 * Check if user can approve a loan request.
	 *
	 * @param object $loan_request Loan request object from database.
	 * @param int    $user_id      User ID.
	 * @return bool True if user can approve.
	 */
	private function can_user_approve_request( $loan_request, $user_id ) {
		// Operators can approve any request, including unowned assets.
		if ( user_can( $user_id, ALM_EDIT_ASSET ) ) {
			return true;
		}

		// Current owner can approve requests for their assets.
		if ( (int) $loan_request->owner_id === $user_id && (int) $loan_request->owner_id > 0 ) {
			return true;
		}

		// Check if user is the current assignee of the asset.
		$current_owner = $this->get_current_owner( $loan_request->asset_id );
		if ( $current_owner === $user_id && $current_owner > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Execute ownership transfer for an asset.
	 *
	 * Shared core used by approve_loan_request() and direct_assign_asset().
	 * Performs in order:
	 * 1. Assign new owner to the main asset.
	 * 2. Set state to "on-loan" for the main asset.
	 * 3. Detect if asset is a kit and load component IDs.
	 * 4. Optionally check that components are not already on-loan (conflict guard).
	 * 5. Propagate owner and state to all kit components.
	 * 6. Cancel concurrent requests for the main asset.
	 * 7. Cancel concurrent requests for each kit component.
	 *
	 * Must be called inside an open database transaction.
	 *
	 * @param int    $asset_id                  Asset ID.
	 * @param int    $new_owner_id              New owner user ID.
	 * @param int    $exclude_request_id        Request ID to exclude from cancellation (0 = cancel all).
	 * @param string $cancel_reason             Cancellation message for concurrent requests on the main asset.
	 * @param bool   $check_component_conflicts Whether to throw if a kit component is already on-loan.
	 * @param array  $canceled_notification_events Collected notification events to dispatch post-commit.
	 * @throws Exception When any operation fails.
	 * @return int[] Component IDs processed (empty array if asset is not a kit).
	 */
	private function execute_ownership_transfer(
		$asset_id,
		$new_owner_id,
		$exclude_request_id,
		$cancel_reason,
		$check_component_conflicts = true,
		&$canceled_notification_events = array()
	) {
		// Capture the original kit owner before any DB write so the conflict
		// guard (step 4) can compare against the pre-transfer state.
		$original_kit_owner = $check_component_conflicts ? $this->get_current_owner( $asset_id ) : 0;

		// 1. Assign new owner.
		$this->set_asset_owner( $asset_id, $new_owner_id );

		// 2. Set state to on-loan.
		$this->set_asset_state( $asset_id, 'on-loan' );

		// 3. Detect kit and load components.
		$is_kit        = $this->is_asset_kit( $asset_id );
		$component_ids = array();

		if ( $is_kit ) {
			$component_ids = $this->get_kit_components( $asset_id );

			if ( ! empty( $component_ids ) ) {
				// 4. Optional conflict guard: block if a component is on-loan to a different owner.
				// Allows hand-off when the kit (and its components) is already on-loan to the approver.
				// Uses $original_kit_owner (captured before step 1) to avoid a false mismatch
				// caused by the kit owner already being updated in the DB at this point.
				if ( $check_component_conflicts ) {
					foreach ( $component_ids as $component_id ) {
						$component_state = $this->get_asset_state_slug( $component_id );
						if ( 'on-loan' === $component_state ) {
							$component_owner = $this->get_current_owner( $component_id );
							if ( $component_owner !== $original_kit_owner ) {
								$component_title = get_the_title( $component_id );
								throw new Exception(
									sprintf(
										/* translators: %s: kit component title */
										esc_html__( 'Component "%s" is already on loan and cannot be assigned as part of this kit.', 'asset-lending-manager' ),
										esc_html( (string) $component_title )
									)
								);
							}
						}
					}
				}

				// 5. Propagate owner and state to all components.
				foreach ( $component_ids as $component_id ) {
					$this->set_asset_owner( $component_id, $new_owner_id );
					$this->set_asset_state( $component_id, 'on-loan' );
				}
			}
		}

		// 6. Cancel concurrent requests for the main asset (if enabled in workflow settings).
		if ( (bool) $this->settings->get( 'workflow.cancel_concurrent_requests_on_assign', true ) ) {
			$this->cancel_concurrent_requests(
				$asset_id,
				$exclude_request_id,
				$cancel_reason,
				$canceled_notification_events
			);
		}

		// 7. Cancel concurrent requests for kit components (if enabled in workflow settings).
		$cancel_kit_requests = (bool) $this->settings->get( 'workflow.cancel_component_requests_when_kit_assigned', true );
		if ( $is_kit && ! empty( $component_ids ) && $cancel_kit_requests ) {
			$kit_title = get_the_title( $asset_id );
			foreach ( $component_ids as $component_id ) {
				$this->cancel_concurrent_requests(
					$component_id,
					0,
					sprintf(
						/* translators: %s: kit asset title */
						__( 'Request canceled: component assigned as part of kit "%s".', 'asset-lending-manager' ),
						$kit_title
					),
					$canceled_notification_events
				);
			}
		}

		return $component_ids;
	}

	/**
	 * Approve a loan request (atomic operation with full kit support).
	 *
	 * This method performs the following operations atomically:
	 * 1. Validate request is still pending (with row-level lock).
	 * 2-6. Delegate ownership transfer to execute_ownership_transfer().
	 * 7. Delete approved request from requests table.
	 * 8. Insert record into history table.
	 *
	 * @param object $loan_request Loan request object from database.
	 * @param int    $approved_by  User ID who approved the request.
	 * @throws Exception When a transactional operation fails (caught internally).
	 * @return array ['success' => bool, 'message' => string]
	 */
	private function approve_loan_request( $loan_request, $approved_by ) {
		global $wpdb;

		// Start transaction.
		$wpdb->query( 'START TRANSACTION' );

		try {
			$table_name = $wpdb->prefix . 'alm_loan_requests';
			$asset_id   = (int) $loan_request->asset_id;

			// Lock all pending requests for this asset to serialize concurrent approvals.
				$wpdb->get_results(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
						"SELECT id FROM $table_name
						WHERE asset_id = %d
						FOR UPDATE",
						$asset_id
					)
				);

			// Re-read and lock the target request row inside the transaction.
				$loan_request = $wpdb->get_row(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
						"SELECT * FROM $table_name
						WHERE id = %d
						FOR UPDATE",
						$loan_request->id
					)
				);

			if ( ! $loan_request ) {
				throw new Exception( __( 'Loan request not found.', 'asset-lending-manager' ) );
			}

			// 1. Validate request status.
			if ( 'pending' !== $loan_request->status ) {
				throw new Exception( __( 'Request is not pending.', 'asset-lending-manager' ) );
			}

			$asset_id          = (int) $loan_request->asset_id;
			$requester_id      = (int) $loan_request->requester_id;
			$previous_owner_id = (int) $loan_request->owner_id;

			// Verify asset exists.
			$asset = get_post( $asset_id );
			if ( ! $asset || ALM_ASSET_CPT_SLUG !== $asset->post_type ) {
				throw new Exception( __( 'Asset not found.', 'asset-lending-manager' ) );
			}

			// 2–6. Execute ownership transfer (set owner, set state, propagate to kit, cancel concurrent requests).
			$canceled_notification_events = array();
			$component_ids                = $this->execute_ownership_transfer(
				$asset_id,
				$requester_id,
				$loan_request->id,
				__( 'Request automatically canceled: asset approved for another user.', 'asset-lending-manager' ),
				true,
				$canceled_notification_events
			);

			// 8. Update request status to approved and delete from requests table.
			$deleted = $wpdb->delete(
				$table_name,
				array( 'id' => $loan_request->id ),
				array( '%d' )
			);

			if ( false === $deleted ) {
				throw new Exception( __( 'Failed to update request status.', 'asset-lending-manager' ) );
			}

			// 9. Insert record into history table.
			$history_logged = $this->log_history_entry(
				$loan_request->id,
				$asset_id,
				$requester_id,
				$previous_owner_id,
				'approved',
				$loan_request->request_message,
				$approved_by
			);

			if ( ! $history_logged ) {
				throw new Exception( __( 'Failed to log history entry.', 'asset-lending-manager' ) );
			}

			// Commit transaction.
			$wpdb->query( 'COMMIT' );

			$this->dispatch_cancellation_notifications( $canceled_notification_events );

			ALM_Logger::info(
				'Loan request approved successfully',
				array(
					'request_id'   => $loan_request->id,
					'asset_id'     => $asset_id,
					'requester_id' => $requester_id,
					'is_kit'       => ! empty( $component_ids ),
					'components'   => $component_ids,
				)
			);

			return array(
				'success' => true,
				'message' => __( 'Loan request approved successfully.', 'asset-lending-manager' ),
			);

		} catch ( Exception $e ) {
			// Rollback transaction on error.
			$wpdb->query( 'ROLLBACK' );

			ALM_Logger::error(
				'Failed to approve loan request',
				array(
					'request_id' => $loan_request->id,
					'error'      => $e->getMessage(),
					'db_error'   => $wpdb->last_error,
				)
			);

			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Set the owner of an asset.
	 *
	 * @param int $asset_id Asset ID.
	 * @param int $user_id  User ID (0 = no owner).
	 * @throws Exception When owner persistence fails.
	 * @return bool True on success, false on failure.
	 */
	private function set_asset_owner( $asset_id, $user_id ) {
		$result = update_post_meta( $asset_id, '_alm_current_owner', $user_id );

		// update_post_meta() returns false both on DB failure and when the stored
		// value is already equal to $user_id (no-op update). Only treat it as an
		// error when the value was not actually saved.
		if ( false === $result && (int) get_post_meta( $asset_id, '_alm_current_owner', true ) !== (int) $user_id ) {
			throw new Exception(
				sprintf(
					/* translators: %d: asset post ID */
					esc_html__( 'Failed to set owner for asset ID %d.', 'asset-lending-manager' ),
					(int) $asset_id
				)
			);
		}

		return true;
	}

	/**
	 * Set the state of an asset.
	 *
	 * @param int    $asset_id   Asset ID.
	 * @param string $state_slug State slug (e.g., 'available', 'on-loan', 'maintenance').
	 * @throws Exception When state slug is invalid or taxonomy update fails.
	 * @return bool True on success, false on failure.
	 */
	private function set_asset_state( $asset_id, $state_slug ) {
		$term = get_term_by( 'slug', $state_slug, ALM_ASSET_STATE_TAXONOMY_SLUG );

		if ( ! $term ) {
			throw new Exception(
				sprintf(
					/* translators: %s: invalid asset state slug */
					esc_html__( 'Invalid state slug: %s', 'asset-lending-manager' ),
					esc_html( (string) $state_slug )
				)
			);
		}

		$result = wp_set_object_terms( $asset_id, $term->term_id, ALM_ASSET_STATE_TAXONOMY_SLUG );

		if ( is_wp_error( $result ) ) {
			throw new Exception(
				sprintf(
					/* translators: 1: asset post ID, 2: taxonomy error message */
					esc_html__( 'Failed to set state for asset ID %1$d: %2$s', 'asset-lending-manager' ),
					(int) $asset_id,
					esc_html( $result->get_error_message() )
				)
			);
		}

		return true;
	}

	/**
	 * Get the state slug of an asset.
	 *
	 * @param int $asset_id Asset ID.
	 * @return string State slug or empty string if not set.
	 */
	private function get_asset_state_slug( $asset_id ) {
		$terms = get_the_terms( $asset_id, ALM_ASSET_STATE_TAXONOMY_SLUG );

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			return $terms[0]->slug;
		}

		return '';
	}

	/**
	 * Check if an asset is a kit.
	 *
	 * @param int $asset_id Asset ID.
	 * @return bool True if asset is a kit, false otherwise.
	 */
	private function is_asset_kit( $asset_id ) {
		return has_term( ALM_ASSET_KIT_SLUG, ALM_ASSET_STRUCTURE_TAXONOMY_SLUG, $asset_id );
	}

	/**
	 * Get component IDs of a kit.
	 *
	 * @param int $asset_id Kit asset ID.
	 * @return array Array of component post IDs.
	 */
	private function get_kit_components( $asset_id ) {
		$components = ALM_ACF_Asset_Adapter::get_custom_field( 'components', $asset_id );

		if ( ! is_array( $components ) || empty( $components ) ) {
			return array();
		}

		// Extract IDs from post objects.
		$component_ids = array();
		foreach ( $components as $component ) {
			if ( is_object( $component ) && isset( $component->ID ) ) {
				$component_ids[] = (int) $component->ID;
			} elseif ( is_numeric( $component ) ) {
				$component_ids[] = (int) $component;
			}
		}

		return $component_ids;
	}

	/**
	 * Cancel concurrent requests for an asset.
	 *
	 * @param int    $asset_id           Asset ID.
	 * @param int    $exclude_request_id Request ID to exclude (0 = cancel all).
	 * @param string $cancel_message     Cancellation message.
	 * @param array  $canceled_notification_events Collected notification events to dispatch post-commit.
	 * @throws Exception When cancellation logging or deletion fails.
	 * @return int Number of requests canceled.
	 */
	private function cancel_concurrent_requests( $asset_id, $exclude_request_id, $cancel_message, &$canceled_notification_events ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alm_loan_requests';

		// Get all pending requests for this asset (excluding the approved one).
		if ( $exclude_request_id > 0 ) {
			$requests = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
					"SELECT * FROM $table_name 
						WHERE asset_id = %d 
						AND id != %d 
					AND status = 'pending'",
					$asset_id,
					$exclude_request_id
				)
			);
		} else {
			$requests = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table name is built from trusted $wpdb->prefix.
					"SELECT * FROM $table_name 
						WHERE asset_id = %d 
						AND status = 'pending'",
					$asset_id
				)
			);
		}

		if ( empty( $requests ) ) {
			return 0;
		}

		$canceled_count = 0;

		foreach ( $requests as $request ) {
			// Log history entry for canceled request.
			$history_logged = $this->log_history_entry(
				$request->id,
				$request->asset_id,
				$request->requester_id,
				$request->owner_id,
				'canceled',
				$cancel_message,
				(int) $this->settings->get( 'workflow.automatic_operations_actor_user_id', self::AUTOMATIC_OPERATIONS_OPERATOR_ID )
			);

			if ( ! $history_logged ) {
				throw new Exception(
					sprintf(
						/* translators: %d: loan request ID */
						esc_html__( 'Failed to log cancellation for request ID %d.', 'asset-lending-manager' ),
						(int) $request->id
					)
				);
			}

			// Delete request from table.
			$deleted = $wpdb->delete(
				$table_name,
				array( 'id' => $request->id ),
				array( '%d' )
			);

			if ( false === $deleted ) {
				throw new Exception(
					sprintf(
						/* translators: %d: loan request ID */
						esc_html__( 'Failed to delete request ID %d.', 'asset-lending-manager' ),
						(int) $request->id
					)
				);
			}

			++$canceled_count;

			ALM_Logger::info(
				'Request automatically canceled',
				array(
					'request_id'   => $request->id,
					'asset_id'     => $request->asset_id,
					'requester_id' => $request->requester_id,
					'reason'       => $cancel_message,
				)
			);

			$canceled_notification_events[] = array(
				'requester_id' => (int) $request->requester_id,
				'asset_id'     => (int) $request->asset_id,
			);
		}

		return $canceled_count;
	}

	/**
	 * Dispatch queued cancellation notifications after a successful commit.
	 *
	 * @param array $canceled_notification_events List of notification payloads.
	 * @return void
	 */
	private function dispatch_cancellation_notifications( $canceled_notification_events ) {
		foreach ( $canceled_notification_events as $notification_event ) {
			if ( ! is_array( $notification_event ) ) {
				continue;
			}

			$requester_id = isset( $notification_event['requester_id'] ) ? (int) $notification_event['requester_id'] : 0;
			$asset_id     = isset( $notification_event['asset_id'] ) ? (int) $notification_event['asset_id'] : 0;
			if ( $requester_id <= 0 || $asset_id <= 0 ) {
				continue;
			}

			do_action( 'alm_loan_request_canceled', $requester_id, $asset_id );
		}
	}

	/**
	 * AJAX handler for direct asset assignment by operator.
	 *
	 * @return void
	 */
	public function ajax_direct_assign_asset() {
		// Verify nonce.
		check_ajax_referer( 'alm_direct_assign_nonce', 'nonce' );

		// Operator-only capability check.
		if ( ! current_user_can( ALM_EDIT_ASSET ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to assign assets.', 'asset-lending-manager' ),
				)
			);
		}

		// Get and validate input.
		$asset_id    = isset( $_POST['asset_id'] ) ? absint( $_POST['asset_id'] ) : 0;
		$assignee_id = isset( $_POST['assignee_id'] ) ? absint( $_POST['assignee_id'] ) : 0;
		$reason      = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( $asset_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid asset ID.', 'asset-lending-manager' ) ) );
		}

		if ( $assignee_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid assignee ID.', 'asset-lending-manager' ) ) );
		}

		if ( empty( $reason ) ) {
			wp_send_json_error( array( 'message' => __( 'Assignment reason is required.', 'asset-lending-manager' ) ) );
		}

		$assign_max = (int) $this->settings->get( 'loans.direct_assign_reason_max_length', self::DIRECT_ASSIGN_REASON_MAX_LENGTH );
		if ( $assign_max > 0 && mb_strlen( $reason ) > $assign_max ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: maximum number of characters allowed */
						__( 'Assignment reason must not exceed %d characters.', 'asset-lending-manager' ),
						$assign_max
					),
				)
			);
		}

		// Verify asset exists.
		$asset = get_post( $asset_id );
		if ( ! $asset || ALM_ASSET_CPT_SLUG !== $asset->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Asset not found.', 'asset-lending-manager' ) ) );
		}

		// Verify assignee exists and has an ALM role.
		$assignee = get_userdata( $assignee_id );
		if ( ! $assignee ) {
			wp_send_json_error( array( 'message' => __( 'Assignee user not found.', 'asset-lending-manager' ) ) );
		}

		$assignee_roles = (array) $assignee->roles;
		if (
			! in_array( ALM_MEMBER_ROLE, $assignee_roles, true ) &&
			! in_array( ALM_OPERATOR_ROLE, $assignee_roles, true )
		) {
			wp_send_json_error(
				array(
					'message' => __( 'Assignee must be a member or operator.', 'asset-lending-manager' ),
				)
			);
		}

		$current_user_id = get_current_user_id();

		// Perform the assignment.
		$result = $this->direct_assign_asset( $asset_id, $assignee_id, $reason, $current_user_id );

		if ( ! $result['success'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}

		// Log the assignment.
		ALM_Logger::info(
			'Asset directly assigned',
			array(
				'asset_id'    => $asset_id,
				'assignee_id' => $assignee_id,
				'assigned_by' => $current_user_id,
			)
		);

		// Fire direct assign action so ALM_Notification_Manager can send emails.
		// Pass $assignee->ID (int) rather than the WP_User object for consistency with other actions.
		$previous_owner_id = isset( $result['previous_owner_id'] ) ? (int) $result['previous_owner_id'] : 0;
		do_action( 'alm_direct_assign', $asset_id, $assignee->ID, $current_user_id, $reason, $previous_owner_id );

		wp_send_json_success(
			array(
				'message' => __( 'Asset assigned successfully.', 'asset-lending-manager' ),
			)
		);
	}

	/**
	 * Perform a direct asset assignment (atomic operation).
	 *
	 * Operators can assign any non-retired asset, overriding any current owner.
	 * Components of a kit are updated without conflict checks.
	 *
	 * Operations:
	 * 1. Reject retired assets.
	 * 2. Change owner of main asset.
	 * 3. Change state of main asset to "on-loan".
	 * 4. If kit: propagate owner and state to all components (no conflict checks).
	 * 5. Cancel all concurrent requests for the asset.
	 * 6. Cancel requests for components if kit.
	 * 7. Insert history entry with status "direct_assign".
	 *
	 * @param int    $asset_id    Asset ID.
	 * @param int    $assignee_id Target user ID.
	 * @param string $reason      Assignment reason.
	 * @param int    $actor_id    Operator user ID performing the assignment.
	 * @throws Exception When a transactional operation fails.
	 * @return array ['success' => bool, 'message' => string]
	 */
	private function direct_assign_asset( $asset_id, $assignee_id, $reason, $actor_id ) {
		global $wpdb;

		// Start transaction.
		$wpdb->query( 'START TRANSACTION' );

		try {
			// 1. Reject retired assets.
			$current_state = $this->get_asset_state_slug( $asset_id );
			if ( 'retired' === $current_state ) {
				throw new Exception( __( 'Cannot assign a retired asset.', 'asset-lending-manager' ) );
			}

			// 2. Record previous owner before overwriting.
			$previous_owner_id = $this->get_current_owner( $asset_id );

			// 3–7. Execute ownership transfer (set owner, set state, propagate to kit, cancel concurrent requests).
			$canceled_notification_events = array();
			$component_ids                = $this->execute_ownership_transfer(
				$asset_id,
				$assignee_id,
				0,
				__( 'Request canceled: asset directly assigned by operator.', 'asset-lending-manager' ),
				false,
				$canceled_notification_events
			);

			// 8. Log history entry (loan_request_id = 0 for direct assignments).
			$history_logged = $this->log_history_entry(
				0,
				$asset_id,
				$assignee_id,
				$previous_owner_id,
				'direct_assign',
				$reason,
				$actor_id
			);

			if ( ! $history_logged ) {
				throw new Exception( __( 'Failed to log history entry.', 'asset-lending-manager' ) );
			}

			// Commit transaction.
			$wpdb->query( 'COMMIT' );

			$this->dispatch_cancellation_notifications( $canceled_notification_events );

			ALM_Logger::info(
				'Direct assignment completed successfully',
				array(
					'asset_id'    => $asset_id,
					'assignee_id' => $assignee_id,
					'is_kit'      => ! empty( $component_ids ),
					'components'  => $component_ids,
				)
			);

			return array(
				'success'           => true,
				'message'           => __( 'Asset assigned successfully.', 'asset-lending-manager' ),
				'previous_owner_id' => $previous_owner_id,
			);

		} catch ( Exception $e ) {
			// Rollback transaction on error.
			$wpdb->query( 'ROLLBACK' );

			ALM_Logger::error(
				'Failed to complete direct assignment',
				array(
					'asset_id' => $asset_id,
					'error'    => $e->getMessage(),
					'db_error' => $wpdb->last_error,
				)
			);

			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}
}
