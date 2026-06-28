<?php
/**
 * Unit tests for ALMGR_Notification_Manager helper methods.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies pure notification template formatting behavior.
 */
class ALMGR_Notification_Manager_Helpers_Unit_Test extends TestCase {

	/**
	 * Notification manager under test.
	 *
	 * @var ALMGR_Notification_Manager
	 */
	private $notification_manager;

	/**
	 * Create the helper subject without invoking the typed constructor.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->notification_manager = ( new ReflectionClass( ALMGR_Notification_Manager::class ) )->newInstanceWithoutConstructor();
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
}
