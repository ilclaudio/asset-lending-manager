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
	 * User ID for automatic system operations.
	 * 
	 * TODO: Make this configurable via Settings Manager in future versions.
	 */
	const AUTOMATIC_OPERATIONS_OPERATOR_ID = 1;

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
		if ( mb_strlen( $message ) > self::SEND_REQUEST_MESSAGE_MAX_LENGTH ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						__( 'Request message must not exceed %d characters.', 'asset-lending-manager' ),
						self::SEND_REQUEST_MESSAGE_MAX_LENGTH
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
					'message' => __( 'Failed to create loan request.', 'asset-lending-manager' )
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
		// @TODO: Send email notifications (future implementation).
		$this->log_email_notification( $requester_id, $owner_id, $asset_id, $message );
		wp_send_json_success(
			array(
				'message' => __( 'Loan request sent successfully!', 'asset-lending-manager' ),
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

		if ( mb_strlen( $rejection_message ) > self::REJECTION_MESSAGE_MAX_LENGTH ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						__( 'Rejection message must not exceed %d characters.', 'asset-lending-manager' ),
						self::REJECTION_MESSAGE_MAX_LENGTH
					),
				)
			);
		}

		// Get loan request from database.
		global $wpdb;
		$table_name    = $wpdb->prefix . 'alm_loan_requests';
		$loan_request  = $wpdb->get_row(
			$wpdb->prepare(
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

		// Send email notification (placeholder).
		$this->send_rejection_email_notification( $loan_request, $rejection_message );

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
	 * Send email notification for rejected loan request (placeholder).
	 *
	 * @param object $loan_request      Loan request object.
	 * @param string $rejection_message Rejection message.
	 * @return void
	 */
	private function send_rejection_email_notification( $loan_request, $rejection_message ) {
		$requester   = get_userdata( $loan_request->requester_id );
		$asset_title = get_the_title( $loan_request->asset_id );

		if ( ! $requester ) {
			return;
		}

		// Log email to requester.
		ALM_Logger::info(
			'[EMAIL] To requester: Loan request rejected',
			array(
				'to'      => $requester->user_email,
				'subject' => sprintf( 
					__( 'Your loan request for "%s" has been rejected', 'asset-lending-manager' ),
					$asset_title
				),
				'message' => $rejection_message,
			)
		);

		// TODO: Implement actual email sending.
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
		$table_name   = $wpdb->prefix . 'alm_loan_requests';
		$loan_request = $wpdb->get_row(
			$wpdb->prepare(
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

		// Send email notification (placeholder).
		$this->send_approval_email_notification( $loan_request );

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
					'asset_id'  => $asset_id,
					'error'     => $e->getMessage(),
					'db_error'  => $wpdb->last_error,
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
	 * Log email notification (placeholder for future implementation).
	 *
	 * @param int    $requester_id Requester user ID.
	 * @param int    $owner_id     Owner user ID.
	 * @param int    $asset_id     Asset ID.
	 * @param string $message      Request message.
	 * @return void
	 */
	private function log_email_notification( $requester_id, $owner_id, $asset_id, $message ) {
		$requester   = get_userdata( $requester_id );
		$asset_title = get_the_title( $asset_id );

		// Log notification to requester.
		ALM_Logger::info(
			'[EMAIL] To requester: Loan request confirmation',
			array(
				'to'      => $requester->user_email,
				'subject' => sprintf( 
					__( 'Your loan request for "%s"', 'asset-lending-manager' ),
					$asset_title
				),
			)
		);

		// Log notification to owner (if exists).
		if ( $owner_id > 0 ) {
			$owner = get_userdata( $owner_id );
			ALM_Logger::info(
				'[EMAIL] To owner: New loan request received',
				array(
					'to'      => $owner->user_email,
					'subject' => sprintf(
						__( 'Loan request for your asset "%s"', 'asset-lending-manager' ),
						$asset_title
					),
					'from' => $requester->display_name,
				)
			);
		}

		// Log notification to system operators.
		$operators_email = 'operators@example.com';
		ALM_Logger::info(
			'[EMAIL] To operators: New loan request',
			array(
				'to' => $operators_email,
				'subject' => sprintf(
					__( 'New loan request for "%s"', 'asset-lending-manager' ),
					$asset_title
				),
			)
		);
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
					"SELECT * FROM $table_name 
					WHERE requester_id = %d 
					ORDER BY request_date DESC",
					$user_id
				)
			);
		}
		return $wpdb->get_results(
			$wpdb->prepare(
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
	 * Approve a loan request (atomic operation with full kit support).
	 *
	 * This method performs the following operations atomically:
	 * 1. Validate request is pending
	 * 2. Change owner of main asset
	 * 3. Change state of main asset to "on-loan"
	 * 4. If kit: propagate owner and state to all components
	 * 5. Cancel concurrent requests for the same asset
	 * 6. Cancel requests for components if this is a kit
	 * 7. Update request status to approved
	 * 8. Insert record into history table
	 *
	 * @param object $loan_request Loan request object from database.
	 * @param int    $approved_by  User ID who approved the request.
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
					"SELECT id FROM $table_name
					WHERE asset_id = %d
					FOR UPDATE",
					$asset_id
				)
			);

			// Re-read and lock the target request row inside the transaction.
			$loan_request = $wpdb->get_row(
				$wpdb->prepare(
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

			$asset_id     = (int) $loan_request->asset_id;
			$requester_id = (int) $loan_request->requester_id;
			$previous_owner_id = (int) $loan_request->owner_id;

			// Verify asset exists.
			$asset = get_post( $asset_id );
			if ( ! $asset || ALM_ASSET_CPT_SLUG !== $asset->post_type ) {
				throw new Exception( __( 'Asset not found.', 'asset-lending-manager' ) );
			}

			// 2. Check if asset is already on loan (conflict prevention).
			$current_state = $this->get_asset_state_slug( $asset_id );
			if ( 'on-loan' === $current_state ) {
				throw new Exception( __( 'Asset is already on loan.', 'asset-lending-manager' ) );
			}

			// 3. Change owner of main asset.
			$this->set_asset_owner( $asset_id, $requester_id );

			// 4. Change state of main asset to "on-loan".
			$this->set_asset_state( $asset_id, 'on-loan' );

			// 5. If asset is a kit, propagate to components.
			$is_kit = $this->is_asset_kit( $asset_id );
			$component_ids = array();

			if ( $is_kit ) {
				$component_ids = $this->get_kit_components( $asset_id );

				if ( ! empty( $component_ids ) ) {
					// Check if any component is already on loan.
					foreach ( $component_ids as $component_id ) {
						$component_state = $this->get_asset_state_slug( $component_id );
						if ( 'on-loan' === $component_state ) {
							$component_title = get_the_title( $component_id );
							throw new Exception(
								sprintf(
									__( 'Component "%s" is already on loan and cannot be assigned as part of this kit.', 'asset-lending-manager' ),
									$component_title
								)
							);
						}
					}

					// Update all components.
					foreach ( $component_ids as $component_id ) {
						$this->set_asset_owner( $component_id, $requester_id );
						$this->set_asset_state( $component_id, 'on-loan' );
					}
				}
			}

			// 6. Cancel concurrent requests for the same asset.
			$this->cancel_concurrent_requests(
				$asset_id,
				$loan_request->id,
				__( 'Request automatically canceled: asset approved for another user.', 'asset-lending-manager' )
			);

			// 7. If kit, cancel requests for all components.
			if ( $is_kit && ! empty( $component_ids ) ) {
				foreach ( $component_ids as $component_id ) {
					$this->cancel_concurrent_requests(
						$component_id,
						0, // Cancel all requests for this component.
						sprintf(
							__( 'Request canceled: component assigned as part of kit "%s".', 'asset-lending-manager' ),
							get_the_title( $asset_id )
						)
					);
				}
			}

			// 8. Update request status to approved and delete from requests table.
			$deleted    = $wpdb->delete(
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

			ALM_Logger::info(
				'Loan request approved successfully',
				array(
					'request_id'   => $loan_request->id,
					'asset_id'     => $asset_id,
					'requester_id' => $requester_id,
					'is_kit'       => $is_kit,
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
	 * @return bool True on success, false on failure.
	 */
	private function set_asset_owner( $asset_id, $user_id ) {
		$result = update_post_meta( $asset_id, '_alm_current_owner', $user_id );
		
		if ( false === $result ) {
			throw new Exception(
				sprintf(
					__( 'Failed to set owner for asset ID %d.', 'asset-lending-manager' ),
					$asset_id
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
	 * @return bool True on success, false on failure.
	 */
	private function set_asset_state( $asset_id, $state_slug ) {
		$term = get_term_by( 'slug', $state_slug, ALM_ASSET_STATE_TAXONOMY_SLUG );

		if ( ! $term ) {
			throw new Exception(
				sprintf(
					__( 'Invalid state slug: %s', 'asset-lending-manager' ),
					$state_slug
				)
			);
		}

		$result = wp_set_object_terms( $asset_id, $term->term_id, ALM_ASSET_STATE_TAXONOMY_SLUG );

		if ( is_wp_error( $result ) ) {
			throw new Exception(
				sprintf(
					__( 'Failed to set state for asset ID %d: %s', 'asset-lending-manager' ),
					$asset_id,
					$result->get_error_message()
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
		if ( ! function_exists( 'get_field' ) ) {
			return array();
		}

		$components = get_field( 'components', $asset_id );

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
	 * @return int Number of requests canceled.
	 */
	private function cancel_concurrent_requests( $asset_id, $exclude_request_id, $cancel_message ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alm_loan_requests';

		// Get all pending requests for this asset (excluding the approved one).
		if ( $exclude_request_id > 0 ) {
			$requests = $wpdb->get_results(
				$wpdb->prepare(
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
				self::AUTOMATIC_OPERATIONS_OPERATOR_ID
			);

			if ( ! $history_logged ) {
				throw new Exception(
					sprintf(
						__( 'Failed to log cancellation for request ID %d.', 'asset-lending-manager' ),
						$request->id
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
						__( 'Failed to delete request ID %d.', 'asset-lending-manager' ),
						$request->id
					)
				);
			}

			$canceled_count++;

			// Log notification.
			ALM_Logger::info(
				'Request automatically canceled',
				array(
					'request_id'   => $request->id,
					'asset_id'     => $request->asset_id,
					'requester_id' => $request->requester_id,
					'reason'       => $cancel_message,
				)
			);
		}

		return $canceled_count;
	}

	/**
	 * Send email notification for approved loan request (placeholder).
	 *
	 * @param object $loan_request Loan request object.
	 * @return void
	 */
	private function send_approval_email_notification( $loan_request ) {
		$requester   = get_userdata( $loan_request->requester_id );
		$asset_title = get_the_title( $loan_request->asset_id );

		if ( ! $requester ) {
			return;
		}

		// Log email to requester.
		ALM_Logger::info(
			'[EMAIL] To requester: Loan request approved',
			array(
				'to'      => $requester->user_email,
				'subject' => sprintf(
					__( 'Your loan request for "%s" has been approved', 'asset-lending-manager' ),
					$asset_title
				),
			)
		);

		// TODO: Implement actual email sending.
	}
}
