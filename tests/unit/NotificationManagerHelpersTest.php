<?php
/**
 * Unit tests for ALMGR_Notification_Manager helper methods.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies pure notification template formatting and routing decision logic.
 */
class ALMGR_Notification_Manager_Helpers_Unit_Test extends TestCase {

	/**
	 * Notification manager under test.
	 *
	 * @var ALMGR_Notification_Manager
	 */
	private $notification_manager;

	/**
	 * Settings instance shared with the manager so tests can override individual keys.
	 *
	 * @var ALMGR_Settings_Manager
	 */
	private $settings;

	/**
	 * Create the helper subject without invoking the typed constructor, then inject
	 * a fresh settings manager so routing decision tests can change configuration.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_option( 'almgr_settings' );
		$this->settings             = new ALMGR_Settings_Manager();
		$this->notification_manager = ( new ReflectionClass( ALMGR_Notification_Manager::class ) )->newInstanceWithoutConstructor();

		$prop = new ReflectionProperty( ALMGR_Notification_Manager::class, 'settings' );
		$prop->setAccessible( true );
		$prop->setValue( $this->notification_manager, $this->settings );
	}

	/**
	 * Invoke a private helper through reflection.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( $method_name, array $arguments = array() ) {
		$method = new ReflectionMethod( ALMGR_Notification_Manager::class, $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->notification_manager, $arguments );
	}

	/**
	 * Verify template formatting replaces known placeholders and leaves unknown tokens intact.
	 *
	 * @return void
	 */
	public function test_format_template_replaces_known_placeholders_only(): void {
		$result = $this->invoke_private_method(
			'format_template',
			array(
				'Hello {REQUESTER_NAME}, asset: {ASSET_TITLE}. Missing: {UNKNOWN}.',
				array(
					'{REQUESTER_NAME}' => 'Mario Rossi',
					'{ASSET_TITLE}'    => 'Dobson 200',
					'{IGNORED}'        => 'unused',
				),
			)
		);

		$this->assertSame(
			'Hello Mario Rossi, asset: Dobson 200. Missing: {UNKNOWN}.',
			$result
		);
	}

	/**
	 * Verify that 'always' mode returns true regardless of whether there is an asset owner.
	 *
	 * @return void
	 */
	public function test_should_notify_all_operators_returns_true_in_always_mode(): void {
		$this->settings->set( 'notifications.loan_request_operator_mode', 'always' );

		$this->assertTrue( $this->invoke_private_method( 'should_notify_all_operators_for_loan_request', array( 5 ) ) );
		$this->assertTrue( $this->invoke_private_method( 'should_notify_all_operators_for_loan_request', array( 0 ) ) );
	}

	/**
	 * Verify that 'never' mode returns false regardless of whether there is an asset owner.
	 *
	 * @return void
	 */
	public function test_should_notify_all_operators_returns_false_in_never_mode(): void {
		$this->settings->set( 'notifications.loan_request_operator_mode', 'never' );

		$this->assertFalse( $this->invoke_private_method( 'should_notify_all_operators_for_loan_request', array( 0 ) ) );
		$this->assertFalse( $this->invoke_private_method( 'should_notify_all_operators_for_loan_request', array( 5 ) ) );
	}

	/**
	 * Verify that 'no_owner' mode returns true only when owner_id is <= 0 (no owner assigned).
	 *
	 * @return void
	 */
	public function test_should_notify_all_operators_gates_on_owner_presence_in_no_owner_mode(): void {
		$this->settings->set( 'notifications.loan_request_operator_mode', 'no_owner' );

		$this->assertTrue( $this->invoke_private_method( 'should_notify_all_operators_for_loan_request', array( 0 ) ) );
		$this->assertTrue( $this->invoke_private_method( 'should_notify_all_operators_for_loan_request', array( -1 ) ) );
		$this->assertFalse( $this->invoke_private_method( 'should_notify_all_operators_for_loan_request', array( 1 ) ) );
		$this->assertFalse( $this->invoke_private_method( 'should_notify_all_operators_for_loan_request', array( 99 ) ) );
	}
}
