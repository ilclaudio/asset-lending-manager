<?php
/**
 * Notification manager for ALM loan workflow events.
 *
 * Listens to custom WordPress actions fired by ALM_Loan_Manager and sends
 * transactional email notifications to the involved parties via wp_mail().
 *
 * Sender configuration is controlled by the constants ALM_EMAIL_FROM_NAME,
 * ALM_EMAIL_FROM_ADDRESS, and ALM_EMAIL_SYSTEM_ADDRESS defined in plugin-config.php.
 * Email subjects and body templates are also defined as constants there and are
 * translatable via __() at send time.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles email notifications for ALM loan workflow events.
 */
class ALM_Notification_Manager {

	/**
	 * Register WordPress action hooks for loan workflow events.
	 *
	 * Each hook maps a custom ALM action to the corresponding notification method.
	 * ALM_Loan_Manager fires these actions; this class reacts to them without
	 * any direct coupling between the two modules.
	 *
	 * @return void
	 */
	public function register() {
		// Notify requester and current owner when a new loan request is submitted.
		add_action( 'alm_loan_request_submitted', array( $this, 'send_loan_request_submitted_notification' ), 10, 4 );

		// Notify requester when their request is approved.
		add_action( 'alm_loan_request_approved', array( $this, 'send_loan_request_approved_notification' ), 10, 1 );

		// Notify requester when their request is rejected, including the rejection reason.
		add_action( 'alm_loan_request_rejected', array( $this, 'send_loan_request_rejected_notification' ), 10, 2 );

		// Notify requester when their pending request is automatically canceled
		// because the asset was assigned to someone else.
		add_action( 'alm_loan_request_canceled', array( $this, 'send_loan_request_canceled_notification' ), 10, 2 );

		// Notify the assignee when an operator directly assigns an asset to them.
		add_action( 'alm_direct_assign', array( $this, 'send_direct_assign_notification' ), 10, 4 );
	}

	// -------------------------------------------------------------------------
	// Public notification methods (action hook callbacks).
	// -------------------------------------------------------------------------

	/**
	 * Send email notifications when a new loan request is submitted.
	 *
	 * Sends:
	 * - A confirmation email to the requester.
	 * - A notification email to the current asset owner (if any).
	 * - A copy to the system/operator address (if ALM_EMAIL_SYSTEM_ADDRESS is set).
	 *
	 * @param int    $requester_id WordPress user ID of the requester.
	 * @param int    $owner_id     WordPress user ID of the current asset owner (0 if unassigned).
	 * @param int    $asset_id     Post ID of the asset.
	 * @param string $message      Optional message from the requester.
	 * @return void
	 */
	public function send_loan_request_submitted_notification( $requester_id, $owner_id, $asset_id, $message ) {
		$requester = get_userdata( $requester_id );
		if ( ! $requester ) {
			ALM_Logger::warning(
				'[NOTIFICATION] send_loan_request_submitted_notification: requester not found.',
				array( 'requester_id' => $requester_id )
			);
			return;
		}

		// Build the shared placeholder set used by all outgoing emails for this event.
		$placeholders = array_merge(
			$this->get_asset_base_placeholders( $asset_id ),
			array(
				'{REQUESTER_NAME}'  => $requester->display_name,
				'{REQUEST_MESSAGE}' => $message ? $message : '',
			)
		);

		// Email 1: confirmation to the requester.
		$this->send_notification_email(
			$requester->user_email,
			ALM_EMAIL_SUBJECT_REQUEST_TO_REQUESTER,
			ALM_EMAIL_BODY_REQUEST_TO_REQUESTER,
			$placeholders
		);

		// Email 2: notification to the current asset owner (if the asset is already assigned).
		if ( $owner_id > 0 ) {
			$owner = get_userdata( $owner_id );
			if ( $owner ) {
				$this->send_notification_email(
					$owner->user_email,
					ALM_EMAIL_SUBJECT_REQUEST_TO_OWNER,
					ALM_EMAIL_BODY_REQUEST_TO_OWNER,
					$placeholders
				);
			}
		}

		// Email 3: copy to the operator/system address (only if ALM_EMAIL_SYSTEM_ADDRESS is configured).
		if ( ! empty( ALM_EMAIL_SYSTEM_ADDRESS ) ) {
			$this->send_notification_email(
				ALM_EMAIL_SYSTEM_ADDRESS,
				ALM_EMAIL_SUBJECT_REQUEST_TO_OWNER,
				ALM_EMAIL_BODY_REQUEST_TO_OWNER,
				$placeholders
			);
		}
	}

	/**
	 * Send an approval notification to the requester.
	 *
	 * @param object $loan_request Loan request record with at least requester_id and asset_id.
	 * @return void
	 */
	public function send_loan_request_approved_notification( $loan_request ) {
		$requester = get_userdata( $loan_request->requester_id );
		if ( ! $requester ) {
			ALM_Logger::warning(
				'[NOTIFICATION] send_loan_request_approved_notification: requester not found.',
				array( 'requester_id' => $loan_request->requester_id )
			);
			return;
		}

		$placeholders = array_merge(
			$this->get_asset_base_placeholders( $loan_request->asset_id ),
			array( '{REQUESTER_NAME}' => $requester->display_name )
		);

		$this->send_notification_email(
			$requester->user_email,
			ALM_EMAIL_SUBJECT_APPROVED,
			ALM_EMAIL_BODY_APPROVED,
			$placeholders
		);
	}

	/**
	 * Send a rejection notification to the requester, including the rejection reason.
	 *
	 * @param object $loan_request      Loan request record with at least requester_id and asset_id.
	 * @param string $rejection_message Reason provided by the user who rejected the request.
	 * @return void
	 */
	public function send_loan_request_rejected_notification( $loan_request, $rejection_message ) {
		$requester = get_userdata( $loan_request->requester_id );
		if ( ! $requester ) {
			ALM_Logger::warning(
				'[NOTIFICATION] send_loan_request_rejected_notification: requester not found.',
				array( 'requester_id' => $loan_request->requester_id )
			);
			return;
		}

		$placeholders = array_merge(
			$this->get_asset_base_placeholders( $loan_request->asset_id ),
			array(
				'{REQUESTER_NAME}'    => $requester->display_name,
				'{REJECTION_MESSAGE}' => $rejection_message ? $rejection_message : '',
			)
		);

		$this->send_notification_email(
			$requester->user_email,
			ALM_EMAIL_SUBJECT_REJECTED,
			ALM_EMAIL_BODY_REJECTED,
			$placeholders
		);
	}

	/**
	 * Send a cancellation notification to a requester whose pending request was
	 * automatically canceled because the asset was assigned to another user.
	 *
	 * @param int $requester_id WordPress user ID of the requester to notify.
	 * @param int $asset_id     Post ID of the asset.
	 * @return void
	 */
	public function send_loan_request_canceled_notification( $requester_id, $asset_id ) {
		$requester = get_userdata( $requester_id );
		if ( ! $requester ) {
			ALM_Logger::warning(
				'[NOTIFICATION] send_loan_request_canceled_notification: requester not found.',
				array( 'requester_id' => $requester_id )
			);
			return;
		}

		$placeholders = array_merge(
			$this->get_asset_base_placeholders( $asset_id ),
			array( '{REQUESTER_NAME}' => $requester->display_name )
		);

		$this->send_notification_email(
			$requester->user_email,
			ALM_EMAIL_SUBJECT_CANCELED,
			ALM_EMAIL_BODY_CANCELED,
			$placeholders
		);
	}

	/**
	 * Send a notification to the assignee when an operator directly assigns an asset.
	 *
	 * @param int    $asset_id    Post ID of the asset.
	 * @param int    $assignee_id WordPress user ID of the new asset owner.
	 * @param int    $actor_id    WordPress user ID of the operator who performed the assignment.
	 * @param string $reason      Optional reason provided by the operator.
	 * @return void
	 */
	public function send_direct_assign_notification( $asset_id, $assignee_id, $actor_id, $reason ) {
		$assignee = get_userdata( $assignee_id );
		if ( ! $assignee ) {
			ALM_Logger::warning(
				'[NOTIFICATION] send_direct_assign_notification: assignee not found.',
				array( 'assignee_id' => $assignee_id )
			);
			return;
		}

		// Resolve actor name; fall back to "System" if the actor user is not found.
		$actor      = get_userdata( $actor_id );
		$actor_name = $actor ? $actor->display_name : __( 'System', 'asset-lending-manager' );

		$placeholders = array_merge(
			$this->get_asset_base_placeholders( $asset_id ),
			array(
				'{ASSIGNEE_NAME}' => $assignee->display_name,
				'{ACTOR_NAME}'    => $actor_name,
				'{REASON}'        => $reason ? $reason : '',
			)
		);

		$this->send_notification_email(
			$assignee->user_email,
			ALM_EMAIL_SUBJECT_DIRECT_ASSIGN,
			ALM_EMAIL_BODY_DIRECT_ASSIGN,
			$placeholders
		);
	}

	// -------------------------------------------------------------------------
	// Private infrastructure methods.
	// -------------------------------------------------------------------------

	/**
	 * Send a single notification email.
	 *
	 * This is the central send method used by all public notification methods.
	 * It translates the subject and body templates, fills the placeholders,
	 * logs the attempt, calls wp_mail(), and logs any failure.
	 *
	 * @param string $to_email     Recipient email address.
	 * @param string $subject_tpl  Subject template constant (may contain {PLACEHOLDER} tokens).
	 * @param string $body_tpl     Body template constant (may contain {PLACEHOLDER} tokens).
	 * @param array  $placeholders Associative array of '{TOKEN}' => 'value' pairs.
	 * @return bool True if wp_mail() reported success, false otherwise.
	 */
	private function send_notification_email( $to_email, $subject_tpl, $body_tpl, $placeholders ) {
		// Guard: do not attempt sending to an empty address.
		if ( empty( $to_email ) ) {
			ALM_Logger::warning(
				'[NOTIFICATION] Cannot send email: recipient address is empty.',
				array( 'subject_tpl' => $subject_tpl )
			);
			return false;
		}

		// Translate templates and fill runtime placeholders.
		// Note: __() is used at runtime so translations can be applied.
		// Strings must be added manually to the .pot file since makepot
		// cannot extract them from PHP constants passed as variables.
		$subject = $this->format_template( __( $subject_tpl, 'asset-lending-manager' ), $placeholders );
		$body    = $this->format_template( __( $body_tpl, 'asset-lending-manager' ), $placeholders );

		// Build email headers: plain text encoding and custom From address.
		$from_address = $this->get_from_address();
		$headers      = array(
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . ALM_EMAIL_FROM_NAME . ' <' . $from_address . '>',
		);

		// Log the outgoing email attempt for debugging (visible when WP_DEBUG is active).
		ALM_Logger::info(
			'[NOTIFICATION] Sending email.',
			array(
				'to'      => $to_email,
				'subject' => $subject,
			)
		);

		// Dispatch via WordPress mail API.
		$result = wp_mail( $to_email, $subject, $body, $headers );

		// Log delivery failure so it is visible in the error log.
		if ( ! $result ) {
			ALM_Logger::warning(
				'[NOTIFICATION] wp_mail() returned false — email may not have been delivered.',
				array(
					'to'      => $to_email,
					'subject' => $subject,
				)
			);
		}

		return $result;
	}

	/**
	 * Replace {PLACEHOLDER} tokens in a template string with their runtime values.
	 *
	 * @param string $template     Template string containing {TOKEN} placeholders.
	 * @param array  $placeholders Associative array mapping '{TOKEN}' => 'value'.
	 * @return string Template with all known tokens replaced.
	 */
	private function format_template( $template, $placeholders ) {
		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
	}

	/**
	 * Return the sender email address.
	 *
	 * Uses ALM_EMAIL_FROM_ADDRESS when set; falls back to the WordPress site
	 * admin email returned by get_bloginfo('admin_email').
	 *
	 * @return string Sender email address.
	 */
	private function get_from_address() {
		return ! empty( ALM_EMAIL_FROM_ADDRESS )
			? ALM_EMAIL_FROM_ADDRESS
			: get_bloginfo( 'admin_email' );
	}

	/**
	 * Build the set of asset-level placeholders common to all email templates.
	 *
	 * Returns {ASSET_TITLE} and {ASSET_URL} for the given asset post ID.
	 * Callers merge additional event-specific placeholders on top of this array.
	 *
	 * @param int $asset_id Post ID of the asset.
	 * @return array Associative array of placeholder tokens and their values.
	 */
	private function get_asset_base_placeholders( $asset_id ) {
		return array(
			'{ASSET_TITLE}' => get_the_title( $asset_id ),
			'{ASSET_URL}'   => get_permalink( $asset_id ),
		);
	}
}
