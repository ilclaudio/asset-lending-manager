<?php
/**
 * Unit tests for ALMGR_Autocomplete_Manager helper methods.
 *
 * @package AssetLendingManager
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies term validation logic without registering REST routes or hitting the database.
 */
class ALMGR_Autocomplete_Manager_Helpers_Unit_Test extends TestCase {

	/**
	 * Autocomplete manager under test.
	 *
	 * @var ALMGR_Autocomplete_Manager
	 */
	private $autocomplete_manager;

	/**
	 * Settings instance shared with the manager.
	 *
	 * @var ALMGR_Settings_Manager
	 */
	private $settings;

	/**
	 * Create a fresh manager and reset the in-memory option store.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_option( 'almgr_settings' );
		$this->settings             = new ALMGR_Settings_Manager();
		$this->autocomplete_manager = new ALMGR_Autocomplete_Manager( $this->settings );
	}

	/**
	 * Verify that non-string arguments are rejected regardless of length.
	 *
	 * @return void
	 */
	public function test_validate_autocomplete_term_rejects_non_string_types(): void {
		$this->assertFalse( $this->autocomplete_manager->validate_autocomplete_term( 42 ) );
		$this->assertFalse( $this->autocomplete_manager->validate_autocomplete_term( null ) );
		$this->assertFalse( $this->autocomplete_manager->validate_autocomplete_term( array( 'a', 'b', 'c' ) ) );
		$this->assertFalse( $this->autocomplete_manager->validate_autocomplete_term( true ) );
	}

	/**
	 * Verify the default minimum of 3 characters is enforced after trimming whitespace.
	 *
	 * @return void
	 */
	public function test_validate_autocomplete_term_enforces_default_minimum_character_count(): void {
		$this->assertFalse( $this->autocomplete_manager->validate_autocomplete_term( '' ) );
		$this->assertFalse( $this->autocomplete_manager->validate_autocomplete_term( 'ab' ) );
		$this->assertTrue( $this->autocomplete_manager->validate_autocomplete_term( 'abc' ) );
		$this->assertTrue( $this->autocomplete_manager->validate_autocomplete_term( 'telescope' ) );

		// Padding whitespace is trimmed before the length check.
		$this->assertFalse( $this->autocomplete_manager->validate_autocomplete_term( '  a  ' ) );
		$this->assertTrue( $this->autocomplete_manager->validate_autocomplete_term( '  abc  ' ) );
	}

	/**
	 * Verify that a custom autocomplete.min_chars setting shifts the acceptance boundary.
	 *
	 * @return void
	 */
	public function test_validate_autocomplete_term_respects_custom_min_chars_setting(): void {
		$this->settings->set( 'autocomplete.min_chars', 5 );

		$this->assertFalse( $this->autocomplete_manager->validate_autocomplete_term( 'abc' ) );
		$this->assertFalse( $this->autocomplete_manager->validate_autocomplete_term( 'abcd' ) );
		$this->assertTrue( $this->autocomplete_manager->validate_autocomplete_term( 'abcde' ) );
	}
}
