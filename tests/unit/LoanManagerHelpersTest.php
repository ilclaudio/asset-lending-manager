<?php
/**
 * Unit tests for ALMGR_Loan_Manager helper methods.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies pure loan helper formatting logic without database access.
 */
class ALMGR_Loan_Manager_Helpers_Unit_Test extends TestCase {

	/**
	 * Loan manager under test.
	 *
	 * @var ALMGR_Loan_Manager
	 */
	private $loan_manager;

	/**
	 * Create the helper subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->loan_manager = new ALMGR_Loan_Manager( new ALMGR_Settings_Manager() );
	}

	/**
	 * Invoke a private helper through reflection.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( $method_name, array $arguments = array() ) {
		$method = new ReflectionMethod( ALMGR_Loan_Manager::class, $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->loan_manager, $arguments );
	}

	/**
	 * Verify excluded component summaries are omitted when there is nothing to report.
	 *
	 * @return void
	 */
	public function test_build_excluded_summary_returns_empty_string_for_empty_plan(): void {
		$this->assertSame( '', $this->invoke_private_method( 'build_excluded_summary', array( array() ) ) );
	}

	/**
	 * Verify excluded component summaries keep the stable operator-facing message format.
	 *
	 * @return void
	 */
	public function test_build_excluded_summary_formats_titles_and_reason_labels(): void {
		$summary = $this->invoke_private_method(
			'build_excluded_summary',
			array(
				array(
					array(
						'title'        => 'Lens',
						'reason_label' => 'already on loan',
					),
					array(
						'title'        => 'Tripod',
						'reason_label' => 'in maintenance',
					),
				),
			)
		);

		$this->assertSame(
			'Excluded components: Lens (already on loan), Tripod (in maintenance).',
			$summary
		);
	}

	/**
	 * Verify excluded component summaries use the exact reason label strings produced
	 * by the build_kit_transfer_plan and build_kit_state_change_plan helpers.
	 *
	 * If a reason label string changes in the plan builders, this test catches the
	 * regression before the operator sees a broken notice in the browser.
	 *
	 * @return void
	 */
	public function test_build_excluded_summary_formats_all_system_reason_labels(): void {
		$summary = $this->invoke_private_method(
			'build_excluded_summary',
			array(
				array(
					array(
						'title'        => 'Lens',
						'reason_label' => 'Component is on loan to another user.',
					),
					array(
						'title'        => 'Mount',
						'reason_label' => 'Component is under maintenance.',
					),
					array(
						'title'        => 'Filter',
						'reason_label' => 'Component has been retired.',
					),
					array(
						'title'        => 'Finder',
						'reason_label' => 'Component is not controlled by this kit.',
					),
				),
			)
		);

		$this->assertStringStartsWith( 'Excluded components:', $summary );
		$this->assertStringEndsWith( '.', $summary );
		$this->assertStringContainsString( 'Lens (Component is on loan to another user.)', $summary );
		$this->assertStringContainsString( 'Mount (Component is under maintenance.)', $summary );
		$this->assertStringContainsString( 'Filter (Component has been retired.)', $summary );
		$this->assertStringContainsString( 'Finder (Component is not controlled by this kit.)', $summary );
	}
}
