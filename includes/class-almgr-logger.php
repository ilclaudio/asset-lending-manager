<?php
/**
 * Simple logger for Asset Lending Manager plugin.
 *
 * Writes messages to the standard WordPress error log
 * and supports basic logging levels.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ALMGR_Logger
 */
class ALMGR_Logger {

	/**
	 * Logging levels.
	 */
	const DEBUG   = 10;
	const INFO    = 20;
	const WARNING = 30;
	const ERROR   = 40;

	/**
	 * Human-readable labels for log levels.
	 *
	 * @var array<int, string>
	 */
	protected static $level_labels = array(
		self::DEBUG   => 'DEBUG',
		self::INFO    => 'INFO',
		self::WARNING => 'WARNING',
		self::ERROR   => 'ERROR',
	);

	/**
	 * Whether logging is enabled (controlled by logging.enabled setting).
	 *
	 * @var bool
	 */
	protected static $enabled = true;

	/**
	 * Minimum log level to record (controlled by logging.level setting).
	 *
	 * @var int
	 */
	protected static $min_level = self::DEBUG;

	/**
	 * Whether to mask personal data in log context (controlled by logging.mask_personal_data setting).
	 *
	 * @var bool
	 */
	protected static $mask_personal = false;

	/**
	 * Configure logger from plugin settings.
	 * Call once after ALMGR_Settings_Manager is initialised.
	 *
	 * @param ALMGR_Settings_Manager $settings Settings manager instance.
	 * @return void
	 */
	public static function configure( ALMGR_Settings_Manager $settings ) {
		self::$enabled       = (bool) $settings->get( 'logging.enabled', true );
		$level_map           = array(
			'debug'   => self::DEBUG,
			'info'    => self::INFO,
			'warning' => self::WARNING,
			'error'   => self::ERROR,
		);
		$level_key           = $settings->get( 'logging.level', 'error' );
		self::$min_level     = isset( $level_map[ $level_key ] ) ? $level_map[ $level_key ] : self::ERROR;
		self::$mask_personal = (bool) $settings->get( 'logging.mask_personal_data', false );
	}

	/**
	 * Write a log message.
	 *
	 * @param int    $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Optional contextual data.
	 *
	 * @return void
	 */
	protected static function log( $level, $message, array $context = array() ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		if ( ! self::$enabled ) {
			return;
		}
		if ( $level < self::$min_level ) {
			return;
		}
		if ( ! isset( self::$level_labels[ $level ] ) ) {
			return;
		}
		$label = self::$level_labels[ $level ];
		$entry = sprintf( '[ALM][%s] %s', $label, $message );
		if ( ! empty( $context ) ) {
			$entry .= ' ' . wp_json_encode( self::maybe_mask_context( $context ) );
		}
		error_log( $entry );
	}

	/**
	 * Mask personal data keys in a context array when masking is enabled.
	 *
	 * @param array $context Log context array.
	 * @return array Context with personal values replaced by '***'.
	 */
	private static function maybe_mask_context( array $context ) {
		if ( ! self::$mask_personal ) {
			return $context;
		}
		$personal_keys = array( 'to', 'email', 'user_email', 'display_name', 'name', 'from' );
		foreach ( $context as $key => $value ) {
			if ( in_array( $key, $personal_keys, true ) && is_string( $value ) ) {
				$context[ $key ] = '***';
			}
		}
		return $context;
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional contextual data.
	 *
	 * @return void
	 */
	public static function debug( $message, array $context = array() ) {
		self::log( self::DEBUG, $message, $context );
	}

	/**
	 * Log an informational message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional contextual data.
	 *
	 * @return void
	 */
	public static function info( $message, array $context = array() ) {
		self::log( self::INFO, $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional contextual data.
	 *
	 * @return void
	 */
	public static function warning( $message, array $context = array() ) {
		self::log( self::WARNING, $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional contextual data.
	 *
	 * @return void
	 */
	public static function error( $message, array $context = array() ) {
		self::log( self::ERROR, $message, $context );
	}
}
