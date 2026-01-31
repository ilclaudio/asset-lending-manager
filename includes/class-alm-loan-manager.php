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
	 * Create a loan request in the database.
	 *
	 * @param int    $asset_id     Asset ID.
	 * @param int    $requester_id Requester user ID.
	 * @param int    $owner_id     Current owner user ID (0 if none).
	 * @param string $message      Request message.
	 * @return int|false Request ID on success, false on failure.
	 */
	private function create_loan_request( $asset_id, $requester_id, $owner_id, $message ) {
		global $wpdb;
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
			ALM_Logger::error(
				'Failed to insert loan request',
				array( 'db_error' => $wpdb->last_error )
			);
			return false;
		}
		return $wpdb->insert_id;
	}

	/**
	 * Get the current owner of an asset.
	 *
	 * For now returns 0 (no owner tracking yet).
	 * Future: check ACF field 'current_owner' or loan history.
	 *
	 * @param int $asset_id Asset ID.
	 * @return int Owner user ID or 0 if none.
	 */
	private function get_current_owner( $asset_id ) {
		// TODO: Implement owner tracking.
		// For now, return 0 (available asset).
		return 0;
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
}


