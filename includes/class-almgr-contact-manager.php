<?php
/**
 * Asset Lending Manager - Contact Manager
 *
 * Handles the "Contact current owner" form on the asset detail page.
 * Logged-in users with view permission can send a plain-text message to
 * the current asset owner (if assigned) or to all operators/administrators.
 * The sender receives a CC copy; Reply-To is set to the sender's address.
 *
 * No message storage: the plugin acts only as a delivery relay.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ALMGR_Contact_Manager
 *
 * Registers the AJAX endpoint for the contact form and sends the email.
 */
class ALMGR_Contact_Manager {

	/**
	 * Plugin settings.
	 *
	 * @var ALMGR_Settings_Manager
	 */
	private $settings;

	/**
	 * Role manager used for capability and email lookups.
	 *
	 * @var ALMGR_Role_Manager
	 */
	private $role_manager;

	/**
	 * Constructor.
	 *
	 * @param ALMGR_Settings_Manager $settings     Plugin settings instance.
	 * @param ALMGR_Role_Manager     $role_manager Role manager instance.
	 */
	public function __construct( ALMGR_Settings_Manager $settings, ALMGR_Role_Manager $role_manager ) {
		$this->settings     = $settings;
		$this->role_manager = $role_manager;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_almgr_send_contact_message', array( $this, 'handle_send_contact_message' ) );
	}

	/**
	 * AJAX handler for the contact form submission.
	 *
	 * Validates capability, nonce, asset, and message; then sends the email.
	 *
	 * @return void
	 */
	public function handle_send_contact_message() {
		check_ajax_referer( 'almgr_contact_nonce', 'nonce' );

		if ( ! $this->role_manager->current_user_can_contact_resource() ) {
			wp_send_json_error(
				array( 'message' => __( 'Unauthorized.', 'asset-lending-manager' ) ),
				403
			);
		}

		$asset_id = absint( wp_unslash( $_POST['asset_id'] ?? 0 ) );
		$asset    = get_post( $asset_id );
		if ( ! $asset || ALMGR_ASSET_CPT_SLUG !== $asset->post_type ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid asset.', 'asset-lending-manager' ) ),
				400
			);
		}

		if ( ! (bool) $this->settings->get( 'contact_form.enabled', true ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Feature disabled.', 'asset-lending-manager' ) ),
				403
			);
		}

		$max_length = (int) $this->settings->get( 'contact_form.max_message_length', 500 );
		$message    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

		if ( '' === $message ) {
			wp_send_json_error(
				array( 'message' => __( 'Message cannot be empty.', 'asset-lending-manager' ) ),
				400
			);
		}

		if ( mb_strlen( $message ) > $max_length ) {
			wp_send_json_error(
				array( 'message' => __( 'Message too long.', 'asset-lending-manager' ) ),
				400
			);
		}

		$sender      = wp_get_current_user();
		$asset_title = get_the_title( $asset_id );
		$owner_id    = (int) get_post_meta( $asset_id, '_almgr_current_owner', true );

		$to = $this->build_recipient_list( $owner_id );

		$subject = sprintf(
			/* translators: 1: sender display name, 2: asset title. */
			__( '[ALM] Message from %1$s about %2$s', 'asset-lending-manager' ),
			$sender->display_name,
			$asset_title
		);

		$body = sprintf(
			/* translators: 1: sender display name, 2: asset title, 3: message text, 4: asset URL. */
			__( "%1\$s sent you a message about the asset \"%2\$s\":\n\n---\n\n%3\$s\n\n---\n\nView the asset: %4\$s", 'asset-lending-manager' ),
			$sender->display_name,
			$asset_title,
			$message,
			get_permalink( $asset_id )
		);

		$sender_email = sanitize_email( $sender->user_email );
		$headers      = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $sender->display_name . ' <' . $sender_email . '>',
			'Cc: ' . $sender_email,
		);

		ALMGR_Logger::debug(
			'Contact form: sending email.',
			array(
				'to'       => $to,
				'asset_id' => $asset_id,
				'sender'   => $sender_email,
			)
		);

		$sent = wp_mail( $to, $subject, $body, $headers );

		ALMGR_Logger::debug( 'Contact form: wp_mail result.', array( 'sent' => $sent ) );

		if ( $sent ) {
			wp_send_json_success(
				array(
					'message' => __( 'Message sent. A copy has been delivered to your registered email address.', 'asset-lending-manager' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to send the message. Please try again in a moment.', 'asset-lending-manager' ),
				),
				500
			);
		}
	}

	/**
	 * Build the recipient list for the contact email.
	 *
	 * If the asset has a current owner, the message goes to that user.
	 * Otherwise it goes to all operators and administrators.
	 * Falls back to the site admin email when the list is empty.
	 *
	 * @param int $owner_id Current owner user ID (0 when unassigned).
	 * @return string[] Non-empty array of sanitized email addresses.
	 */
	private function build_recipient_list( $owner_id ) {
		if ( $owner_id > 0 ) {
			$owner_data = get_userdata( $owner_id );
			if ( $owner_data && $owner_data->user_email ) {
				return array( sanitize_email( $owner_data->user_email ) );
			}
		}

		$emails = $this->role_manager->get_operator_emails();

		$admin_query = new WP_User_Query(
			array(
				'role'   => 'administrator',
				'fields' => array( 'user_email' ),
			)
		);
		foreach ( $admin_query->get_results() as $user ) {
			if ( ! is_object( $user ) || ! isset( $user->user_email ) ) {
				continue;
			}
			$email = sanitize_email( $user->user_email );
			if ( $email ) {
				$emails[] = $email;
			}
		}

		$emails = array_values( array_filter( array_unique( $emails ) ) );

		if ( empty( $emails ) ) {
			$emails[] = sanitize_email( (string) get_option( 'admin_email' ) );
		}

		return $emails;
	}
}
