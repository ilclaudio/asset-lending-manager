<?php
/**
 * Notification manager for ALM loan workflow events.
 *
 * Listens to custom WordPress actions fired by ALM_Loan_Manager and sends
 * transactional email notifications to the involved parties via wp_mail().
 *
 * Sender configuration is controlled by the constants ALM_EMAIL_FROM_NAME,
 * ALM_EMAIL_FROM_ADDRESS, and ALM_EMAIL_SYSTEM_ADDRESS defined in plugin-config.php.
 * Email subjects and body templates are resolved via alm_get_email_templates()
 * to keep translation strings discoverable by Loco/makepot.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles email notifications for ALM loan workflow events.
 */
class ALM_Notification_Manager {

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

		// Notify the assignee, the previous owner (if any), and the system address when an asset is directly assigned.
		add_action( 'alm_direct_assign', array( $this, 'send_direct_assign_notification' ), 10, 5 );
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
		if ( ! $this->settings->get( 'notifications.enabled', true ) ) {
			return;
		}
		if ( ! $this->settings->get( 'notifications.loan_request', true ) ) {
			return;
		}

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
			$this->get_email_template( 'subject', 'request_to_requester' ),
			$this->get_email_template( 'body', 'request_to_requester' ),
			$placeholders
		);

		// Email 2: notification to the current asset owner (if the asset is already assigned).
		if ( $owner_id > 0 ) {
			$owner = get_userdata( $owner_id );
			if ( $owner ) {
				$this->send_notification_email(
					$owner->user_email,
					$this->get_email_template( 'subject', 'request_to_owner' ),
					$this->get_email_template( 'body', 'request_to_owner' ),
					$placeholders
				);
			}
		}

		// Email 3: copy to the system/operator address (only if configured in settings).
		$system_email = $this->settings->get( 'email.system_email', '' );
		if ( ! empty( $system_email ) ) {
			$this->send_notification_email(
				$system_email,
				$this->get_email_template( 'subject', 'request_to_owner' ),
				$this->get_email_template( 'body', 'request_to_owner' ),
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
		if ( ! $this->settings->get( 'notifications.enabled', true ) ) {
			return;
		}
		if ( ! $this->settings->get( 'notifications.loan_decision', true ) ) {
			return;
		}

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
			$this->get_email_template( 'subject', 'approved' ),
			$this->get_email_template( 'body', 'approved' ),
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
		if ( ! $this->settings->get( 'notifications.enabled', true ) ) {
			return;
		}
		if ( ! $this->settings->get( 'notifications.loan_decision', true ) ) {
			return;
		}

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
			$this->get_email_template( 'subject', 'rejected' ),
			$this->get_email_template( 'body', 'rejected' ),
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
		if ( ! $this->settings->get( 'notifications.enabled', true ) ) {
			return;
		}
		if ( ! $this->settings->get( 'notifications.loan_request', true ) ) {
			return;
		}

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
			$this->get_email_template( 'subject', 'canceled' ),
			$this->get_email_template( 'body', 'canceled' ),
			$placeholders
		);
	}

	/**
	 * Send notifications when an operator directly assigns an asset.
	 *
	 * Sends:
	 * - A notification email to the assignee (new owner).
	 * - A notification email to the previous owner (if any and different from the assignee).
	 * - A copy to the system/operator address (if ALM_EMAIL_SYSTEM_ADDRESS is set).
	 *
	 * @param int    $asset_id          Post ID of the asset.
	 * @param int    $assignee_id       WordPress user ID of the new asset owner.
	 * @param int    $actor_id          WordPress user ID of the operator who performed the assignment.
	 * @param string $reason            Optional reason provided by the operator.
	 * @param int    $previous_owner_id WordPress user ID of the previous asset owner (0 if unassigned).
	 * @return void
	 */
	public function send_direct_assign_notification( $asset_id, $assignee_id, $actor_id, $reason, $previous_owner_id = 0 ) {
		if ( ! $this->settings->get( 'notifications.enabled', true ) ) {
			return;
		}
		if ( ! $this->settings->get( 'notifications.loan_confirmation', true ) ) {
			return;
		}

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

		$base_placeholders = array_merge(
			$this->get_asset_base_placeholders( $asset_id ),
			array(
				'{ASSIGNEE_NAME}' => $assignee->display_name,
				'{ACTOR_NAME}'    => $actor_name,
				'{REASON}'        => $reason ? $reason : '',
			)
		);

		// Email 1: notification to the assignee (new owner).
		$this->send_notification_email(
			$assignee->user_email,
			$this->get_email_template( 'subject', 'direct_assign' ),
			$this->get_email_template( 'body', 'direct_assign' ),
			$base_placeholders
		);

		// Email 2: notification to the previous owner (if assigned and different from the new assignee).
		if ( $previous_owner_id > 0 && $previous_owner_id !== $assignee_id ) {
			$prev_owner = get_userdata( $previous_owner_id );
			if ( $prev_owner ) {
				$prev_owner_placeholders = array_merge(
					$base_placeholders,
					array( '{PREV_OWNER_NAME}' => $prev_owner->display_name )
				);
				$this->send_notification_email(
					$prev_owner->user_email,
					$this->get_email_template( 'subject', 'direct_assign_to_prev_owner' ),
					$this->get_email_template( 'body', 'direct_assign_to_prev_owner' ),
					$prev_owner_placeholders
				);
			}
		}

		// Email 3: copy to the system/operator address (only if configured in settings).
		$system_email = $this->settings->get( 'email.system_email', '' );
		if ( ! empty( $system_email ) ) {
			$prev_owner          = $previous_owner_id > 0 ? get_userdata( $previous_owner_id ) : null;
			$system_placeholders = array_merge(
				$base_placeholders,
				array( '{PREV_OWNER_NAME}' => $prev_owner ? $prev_owner->display_name : '' )
			);
			$this->send_notification_email(
				$system_email,
				$this->get_email_template( 'subject', 'direct_assign_to_prev_owner' ),
				$this->get_email_template( 'body', 'direct_assign_to_prev_owner' ),
				$system_placeholders
			);
		}
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
	 * @param string $subject_tpl  Subject template (may contain {PLACEHOLDER} tokens).
	 * @param string $body_tpl     Body template (may contain {PLACEHOLDER} tokens).
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

		// Fill runtime placeholders on the already-translated templates.
		$subject = $this->format_template( $subject_tpl, $placeholders );
		$body    = $this->format_template( $body_tpl, $placeholders );

		// Build email headers: plain text encoding and custom From address.
		$from_address = $this->get_from_address();
		$from_name    = $this->settings->get( 'email.from_name', '' );
		$from_name    = $from_name ? $from_name : get_bloginfo( 'name' );
		$headers      = array(
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_address . '>',
		);

		// Log the outgoing email attempt when email event logging is enabled.
		if ( $this->settings->get( 'logging.log_email_events', false ) ) {
			ALM_Logger::info(
				'[NOTIFICATION] Sending email.',
				array(
					'to'      => $to_email,
					'subject' => $subject,
				)
			);
		}

		// Dispatch via WordPress mail API.
		$result = wp_mail( $to_email, $subject, $body, $headers );

		// Log delivery failure when email event logging is enabled.
		if ( ! $result && $this->settings->get( 'logging.log_email_events', false ) ) {
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
	 * Return a translated email template by group/key.
	 *
	 * @param string $group Template group (subject/body).
	 * @param string $key   Template key.
	 * @return string Template string or empty string when not found.
	 */
	private function get_email_template( $group, $key ) {
		// Settings store custom overrides under template.{group}.{key};
		// defaults are pre-populated with translated values from alm_get_email_templates().
		$value = $this->settings->get( 'template.' . $group . '.' . $key, '' );
		if ( '' !== $value ) {
			return $value;
		}

		ALM_Logger::warning(
			'[NOTIFICATION] Missing email template.',
			array(
				'group' => $group,
				'key'   => $key,
			)
		);

		return '';
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
		$address = $this->settings->get( 'email.from_address', '' );
		return $address ? $address : get_bloginfo( 'admin_email' );
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
