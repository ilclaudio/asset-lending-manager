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

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error replacement for unit tests.
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Constructor.
		 *
		 * @param string $code Error code.
		 * @param string $message Error message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = (string) $code;
			$this->message = (string) $message;
		}

		/**
		 * Return the first error code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Return the first error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	/**
	 * Minimal WP_Post replacement for unit tests.
	 */
	class WP_Post {
		/**
		 * Post ID.
		 *
		 * @var int
		 */
		public $ID = 0;
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

if ( ! function_exists( 'plugin_dir_path' ) ) {
	/**
	 * Lightweight plugin_dir_path() stub for unit tests.
	 *
	 * @param string $file Absolute path to a file inside the plugin.
	 * @return string Directory path with trailing slash.
	 */
	function plugin_dir_path( $file ) {
		return rtrim( dirname( $file ), '/\\' ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	/**
	 * Lightweight plugin_dir_url() stub for unit tests.
	 *
	 * @param string $file Absolute path to a file inside the plugin.
	 * @return string Placeholder URL with trailing slash.
	 */
	function plugin_dir_url( $file ) {
		return 'http://localhost/wp-content/plugins/asset-lending-manager/';
	}
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
	/**
	 * Lightweight maybe_unserialize() implementation.
	 *
	 * @param mixed $data Raw value.
	 * @return mixed
	 */
	function maybe_unserialize( $data ) {
		if ( ! is_string( $data ) ) {
			return $data;
		}

		$trimmed = trim( $data );
		if ( '' === $trimmed ) {
			return $data;
		}

		if ( ! preg_match( '/^(a|O|s|i|b|d)\:/', $trimmed ) ) {
			return $data;
		}

		$unserialized = @unserialize( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		if ( false === $unserialized && 'b:0;' !== $trimmed ) {
			return $data;
		}

		return $unserialized;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Lightweight sanitize_key() implementation.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	function sanitize_key( $key ) {
		$key = strtolower( (string) $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Lightweight sanitize_title() implementation.
	 *
	 * @param string $title Raw title.
	 * @return string
	 */
	function sanitize_title( $title ) {
		$title = strtolower( trim( wp_strip_all_tags( (string) $title ) ) );
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title );
		return trim( (string) $title, '-' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Lightweight wp_strip_all_tags() implementation.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Lightweight sanitize_text_field() implementation.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	function sanitize_text_field( $text ) {
		$text = wp_strip_all_tags( (string) $text );
		$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
		return trim( (string) $text );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Lightweight wp_unslash() implementation.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}

		return is_string( $value ) ? stripslashes( $value ) : $value;
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

require_once dirname( __DIR__ ) . '/plugin-config.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-settings-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-frontend-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-loan-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-notification-manager.php';
require_once dirname( __DIR__ ) . '/includes/class-almgr-tools-manager.php';
