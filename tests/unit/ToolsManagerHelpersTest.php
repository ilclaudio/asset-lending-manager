<?php
/**
 * Unit tests for ALMGR_Tools_Manager helper methods.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies pure import/export helpers without bootstrapping WordPress.
 */
class ALMGR_Tools_Manager_Helpers_Unit_Test extends TestCase {

	/**
	 * Tools manager under test.
	 *
	 * @var ALMGR_Tools_Manager
	 */
	private $tools_manager;

	/**
	 * Create the helper subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->tools_manager = new ALMGR_Tools_Manager();
	}

	/**
	 * Invoke a private helper through reflection.
	 *
	 * @param string $method_name Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( $method_name, array $arguments = array() ) {
		$method = new ReflectionMethod( ALMGR_Tools_Manager::class, $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->tools_manager, $arguments );
	}

	/**
	 * Cover spreadsheet formula injection protection cases.
	 *
	 * @return void
	 */
	public function test_sanitize_users_export_csv_cell_handles_formula_like_values(): void {
		$this->assertSame( 'plain text', $this->invoke_private_method( 'sanitize_users_export_csv_cell', array( 'plain text' ) ) );
		$this->assertSame( '', $this->invoke_private_method( 'sanitize_users_export_csv_cell', array( '' ) ) );
		$this->assertSame( "'=1+1", $this->invoke_private_method( 'sanitize_users_export_csv_cell', array( '=1+1' ) ) );
		$this->assertSame( "' +SUM(A1:A2)", $this->invoke_private_method( 'sanitize_users_export_csv_cell', array( ' +SUM(A1:A2)' ) ) );
		$this->assertSame( "'\tTabbed", $this->invoke_private_method( 'sanitize_users_export_csv_cell', array( "\tTabbed" ) ) );
	}

	/**
	 * Verify export role mapping keeps operator precedence.
	 *
	 * @return void
	 */
	public function test_map_user_roles_to_export_role_prefers_operator_and_falls_back_cleanly(): void {
		$this->assertSame( 'operator', $this->invoke_private_method( 'map_user_roles_to_export_role', array( array( ALMGR_OPERATOR_ROLE ) ) ) );
		$this->assertSame( 'member', $this->invoke_private_method( 'map_user_roles_to_export_role', array( array( ALMGR_MEMBER_ROLE ) ) ) );
		$this->assertSame( 'operator', $this->invoke_private_method( 'map_user_roles_to_export_role', array( array( ALMGR_MEMBER_ROLE, ALMGR_OPERATOR_ROLE ) ) ) );
		$this->assertSame( '', $this->invoke_private_method( 'map_user_roles_to_export_role', array( array( 'subscriber' ) ) ) );
	}

	/**
	 * Verify import role mapping accepts only the supported CSV vocabulary.
	 *
	 * @return void
	 */
	public function test_map_import_role_to_wp_role_accepts_supported_values_and_rejects_invalid_ones(): void {
		$this->assertSame( ALMGR_MEMBER_ROLE, $this->invoke_private_method( 'map_import_role_to_wp_role', array( 'member' ) ) );
		$this->assertSame( ALMGR_OPERATOR_ROLE, $this->invoke_private_method( 'map_import_role_to_wp_role', array( ' OPERATOR ' ) ) );

		$invalid = $this->invoke_private_method( 'map_import_role_to_wp_role', array( 'administrator' ) );

		$this->assertInstanceOf( WP_Error::class, $invalid );
		$this->assertSame( 'invalid_role', $invalid->get_error_code() );
	}

	/**
	 * Verify users CSV row normalization trims values and strips BOM only from the first cell.
	 *
	 * @return void
	 */
	public function test_normalize_users_csv_row_trims_values_and_strips_utf8_bom(): void {
		$row = $this->invoke_private_method(
			'normalize_users_csv_row',
			array(
				array(
					"\xEF\xBB\xBF Username ",
					' member@example.com ',
					'  First ',
				),
			)
		);

		$this->assertSame(
			array(
				'Username',
				'member@example.com',
				'First',
			),
			$row
		);
		$this->assertSame( array(), $this->invoke_private_method( 'normalize_users_csv_row', array( false ) ) );
	}

	/**
	 * Verify assets CSV row normalization mirrors the users importer contract.
	 *
	 * @return void
	 */
	public function test_normalize_assets_csv_row_trims_values_and_strips_utf8_bom(): void {
		$row = $this->invoke_private_method(
			'normalize_assets_csv_row',
			array(
				array(
					"\xEF\xBB\xBF Telescope ",
					' kit ',
					' advanced ',
				),
			)
		);

		$this->assertSame(
			array(
				'Telescope',
				'kit',
				'advanced',
			),
			$row
		);
		$this->assertSame( array(), $this->invoke_private_method( 'normalize_assets_csv_row', array( false ) ) );
	}

	/**
	 * Verify empty-row detection is stable for both importers.
	 *
	 * @return void
	 */
	public function test_empty_csv_row_detection_is_consistent_for_users_and_assets(): void {
		$this->assertTrue( $this->invoke_private_method( 'is_empty_users_csv_row', array( array() ) ) );
		$this->assertTrue( $this->invoke_private_method( 'is_empty_users_csv_row', array( array( '' ) ) ) );
		$this->assertFalse( $this->invoke_private_method( 'is_empty_users_csv_row', array( array( 'value' ) ) ) );

		$this->assertTrue( $this->invoke_private_method( 'is_empty_assets_csv_row', array( array() ) ) );
		$this->assertTrue( $this->invoke_private_method( 'is_empty_assets_csv_row', array( array( '' ) ) ) );
		$this->assertFalse( $this->invoke_private_method( 'is_empty_assets_csv_row', array( array( 'value' ) ) ) );
	}

	/**
	 * Verify title normalization strips tags, trims, and lowercases.
	 *
	 * @return void
	 */
	public function test_normalize_assets_import_title_key_cleans_and_lowercases_titles(): void {
		$this->assertSame( 'alpha beta', $this->invoke_private_method( 'normalize_assets_import_title_key', array( ' <b>Alpha Beta</b> ' ) ) );
		$this->assertSame( '', $this->invoke_private_method( 'normalize_assets_import_title_key', array( '   ' ) ) );
	}

	/**
	 * Verify kit component title parsing preserves order and removes duplicates by normalized key.
	 *
	 * @return void
	 */
	public function test_parse_assets_import_kit_component_titles_preserves_first_occurrence_and_skips_empty_parts(): void {
		$parsed = $this->invoke_private_method(
			'parse_assets_import_kit_component_titles',
			array( ' Lens | | lens | Tripod |  TRIPOD  | Finder ' )
		);

		$this->assertSame(
			array(
				'Lens',
				'Tripod',
				'Finder',
			),
			$parsed
		);
		$this->assertSame( array(), $this->invoke_private_method( 'parse_assets_import_kit_component_titles', array( '' ) ) );
	}

	/**
	 * Verify export component ID normalization supports the different field shapes used by ACF/meta.
	 *
	 * @return void
	 */
	public function test_normalize_assets_export_component_ids_handles_numeric_object_and_post_values(): void {
		$wp_post     = new WP_Post();
		$wp_post->ID = 12;

		$generic_object     = new stdClass();
		$generic_object->ID = 34;

		$normalized = $this->invoke_private_method(
			'normalize_assets_export_component_ids',
			array(
				array(
					'12',
					$wp_post,
					$generic_object,
					0,
					'not-numeric',
					12,
				),
			)
		);

		$this->assertSame( array( 12, 34 ), $normalized );
	}
}
