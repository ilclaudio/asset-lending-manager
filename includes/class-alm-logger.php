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
 * Class ALM_Logger
 */
class ALM_Logger {

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
		if ( ! isset( self::$level_labels[ $level ] ) ) {
			return;
		}
		$label = self::$level_labels[ $level ];
		$entry = sprintf( '[ALM][%s] %s', $label, $message );
		if ( ! empty( $context ) ) {
			$entry .= ' ' . wp_json_encode( $context );
		}
		error_log( $entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
