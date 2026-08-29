<?php
/**
 * Unit tests for almgr_get_email_templates().
 *
 * Verifies that every template key is present and that each body and subject
 * contains the placeholders the notification and contact managers expect.
 * A silent change to any placeholder token will immediately break outgoing
 * emails — this test catches those regressions before deployment.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies the structure and placeholder contract of the default email templates.
 */
class ALMGR_Email_Templates_Unit_Test extends TestCase {

	/**
	 * Cached templates array to avoid repeated calls within the same test run.
	 *
	 * @var array
	 */
	private $templates;

	/**
	 * Load the templates once per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->templates = almgr_get_email_templates();
	}

	/**
	 * Verify that every expected template key exists in both the subject and body groups.
	 *
	 * Adding a new notification event without a corresponding template entry will
	 * produce a silent empty string; this test makes the omission visible immediately.
	 *
	 * @return void
	 */
	public function test_all_template_keys_are_present_in_subject_and_body(): void {
		$expected_keys = array(
			'request_to_requester',
			'request_to_owner',
			'approved',
			'rejected',
			'canceled',
			'direct_assign',
			'direct_assign_to_prev_owner',
			'force_return',
			'returned_to_operators',
			'contact_message',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $this->templates['subject'], "Missing subject key: {$key}" );
			$this->assertArrayHasKey( $key, $this->templates['body'], "Missing body key: {$key}" );
		}
	}

	/**
	 * Verify that every subject template contains {ASSET_TITLE}.
	 *
	 * All notification subjects reference the asset name. Losing this placeholder
	 * produces subjects like "[ALM] Loan request submitted: " with no title.
	 *
	 * @return void
	 */
	public function test_every_subject_contains_asset_title_placeholder(): void {
		foreach ( $this->templates['subject'] as $key => $tpl ) {
			$this->assertStringContainsString(
				'{ASSET_TITLE}',
				$tpl,
				"Subject '{$key}' is missing {ASSET_TITLE}"
			);
		}
	}

	/**
	 * Verify that the contact_message subject also contains {SENDER_NAME}.
	 *
	 * This subject is the only one that also embeds the sender's name.
	 *
	 * @return void
	 */
	public function test_contact_message_subject_contains_sender_name_placeholder(): void {
		$this->assertStringContainsString(
			'{SENDER_NAME}',
			$this->templates['subject']['contact_message']
		);
	}

	/**
	 * Verify that each body template contains all the placeholders the sending
	 * code injects before calling wp_mail().
	 *
	 * The placeholder map mirrors the $placeholders arrays assembled in
	 * ALMGR_Notification_Manager and ALMGR_Contact_Manager. Any rename or removal
	 * here must be reflected there, and vice versa.
	 *
	 * @return void
	 */
	public function test_body_templates_contain_expected_placeholders(): void {
		$body = $this->templates['body'];

		$map = array(
			'request_to_requester'        => array( '{REQUESTER_NAME}', '{ASSET_TITLE}', '{ASSET_URL}' ),
			'request_to_owner'            => array( '{REQUESTER_NAME}', '{ASSET_TITLE}', '{REQUEST_MESSAGE}', '{ASSET_URL}' ),
			'approved'                    => array( '{REQUESTER_NAME}', '{ASSET_TITLE}', '{ASSET_URL}' ),
			'rejected'                    => array( '{REQUESTER_NAME}', '{ASSET_TITLE}', '{REJECTION_MESSAGE}', '{ASSET_URL}' ),
			'canceled'                    => array( '{REQUESTER_NAME}', '{ASSET_TITLE}', '{ASSET_URL}' ),
			'direct_assign'               => array( '{ASSIGNEE_NAME}', '{ASSET_TITLE}', '{ACTOR_NAME}', '{REASON}', '{ASSET_URL}' ),
			'direct_assign_to_prev_owner' => array( '{PREV_OWNER_NAME}', '{ASSET_TITLE}', '{ASSIGNEE_NAME}', '{ACTOR_NAME}', '{REASON}', '{ASSET_URL}' ),
			'force_return'                => array( '{BORROWER_NAME}', '{ASSET_TITLE}', '{ACTOR_NAME}', '{NOTES}', '{ASSET_URL}' ),
			'returned_to_operators'       => array( '{ACTOR_NAME}', '{ASSET_TITLE}', '{NOTES}', '{ASSET_URL}' ),
			'contact_message'             => array( '{SENDER_NAME}', '{ASSET_TITLE}', '{MESSAGE}', '{ASSET_URL}' ),
		);

		foreach ( $map as $key => $placeholders ) {
			foreach ( $placeholders as $placeholder ) {
				$this->assertStringContainsString(
					$placeholder,
					$body[ $key ],
					"Body '{$key}' is missing placeholder {$placeholder}"
				);
			}
		}
	}
}
