<?php
/**
 * Unit Tests Bootstrap.
 *
 * @package AssetLendingManager
 */

// Minimal WordPress-like environment for lightweight unit tests.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'ALMGR_MEMBER_ROLE' ) ) {
	define( 'ALMGR_MEMBER_ROLE', 'almgr_member' );
}

if ( ! defined( 'ALMGR_OPERATOR_ROLE' ) ) {
	define( 'ALMGR_OPERATOR_ROLE', 'almgr_operator' );
}

if ( ! defined( 'ALMGR_ASSET_LIST_PER_PAGE' ) ) {
	define( 'ALMGR_ASSET_LIST_PER_PAGE', 12 );
}

if ( ! defined( 'ALMGR_AUTOCOMPLETE_MAX_RESULTS' ) ) {
	define( 'ALMGR_AUTOCOMPLETE_MAX_RESULTS', 5 );
}

if ( ! defined( 'ALMGR_AUTOCOMPLETE_DESC_LENGTH' ) ) {
	define( 'ALMGR_AUTOCOMPLETE_DESC_LENGTH', 20 );
}

if ( ! defined( 'ALMGR_ASSET_CODE_PREFIX' ) ) {
	define( 'ALMGR_ASSET_CODE_PREFIX', 'ALMGR' );
}

global $almgr_unit_options;
$almgr_unit_options = array();

if ( ! function_exists( '__' ) ) {
	/**
	 * Return untranslated text in unit tests.
	 *
	 * @param string $text Text to translate.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'did_action' ) ) {
	/**
	 * Pretend no WordPress actions have fired in lightweight unit tests.
	 *
	 * @param string $hook_name Hook name.
	 * @return int
	 */
	function did_action( $hook_name ) {
		return 0;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * In-memory get_option() stub.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_option( $option, $default = false ) {
		global $almgr_unit_options;

		return array_key_exists( $option, $almgr_unit_options ) ? $almgr_unit_options[ $option ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * In-memory update_option() stub.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool
	 */
	function update_option( $option, $value ) {
		global $almgr_unit_options;

		$almgr_unit_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * In-memory delete_option() stub.
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	function delete_option( $option ) {
		global $almgr_unit_options;

		unset( $almgr_unit_options[ $option ] );
		return true;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-almgr-settings-manager.php';
