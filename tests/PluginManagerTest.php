<?php
/**
 * Unit tests fo the Plugin Manager.
 */

use PHPUnit\Framework\TestCase;


/**
 * Test the Plugin_Manager singleton.
 */
class Plugin_Manager_Test extends TestCase {

	/**
	 * Test that get_instance() returns the same instance.
	 */
	public function test_singleton_instance() {
		$instance1 = Plugin_Manager::get_instance();
		$instance2 = Plugin_Manager::get_instance();

		$this->assertInstanceOf( Plugin_Manager::class, $instance1, 'Instance should be of class Plugin_Manager.' );
		$this->assertSame( $instance1, $instance2, 'Both instances should be the same (singleton).' );
	}

	/**
	 * Test that cloning the singleton throws an error.
	 */
	public function test_prevent_clone() {
		$instance = Plugin_Manager::get_instance();

		$this->expectException( Error::class );
		$clone = clone $instance; // Cloning should throw an error.
	}

	/**
	 * Test that unserializing the singleton throws an exception.
	 */
	public function test_prevent_unserialize() {
		$instance = Plugin_Manager::get_instance();

		$this->expectException( Exception::class );
		unserialize( serialize( $instance ) ); // Unserialization should throw an exception.
	}

}
