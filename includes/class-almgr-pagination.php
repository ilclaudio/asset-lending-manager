<?php
/**
 * Shared pagination rules for ALMGR services and adapters.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides one source of truth for page defaults, limits and schemas.
 */
class ALMGR_Pagination {

	/**
	 * Default number of items per page.
	 *
	 * @var int
	 */
	const DEFAULT_PER_PAGE = 20;

	/**
	 * Maximum number of items per page accepted from callers.
	 *
	 * @var int
	 */
	const MAX_PER_PAGE = 100;

	/**
	 * Normalize a page number.
	 *
	 * @param mixed $value Requested page.
	 * @return int
	 */
	public static function normalize_page( $value = 1 ) {
		return max( 1, (int) $value );
	}

	/**
	 * Normalize a per-page value.
	 *
	 * @param mixed $value            Requested page size.
	 * @param bool  $allow_unlimited Whether -1 is accepted.
	 * @return int
	 */
	public static function normalize_per_page( $value = null, $allow_unlimited = false ) {
		if ( null === $value || '' === $value ) {
			return self::DEFAULT_PER_PAGE;
		}

		$value = (int) $value;
		if ( $allow_unlimited && -1 === $value ) {
			return -1;
		}

		return min( self::MAX_PER_PAGE, max( 1, $value ) );
	}

	/**
	 * Return the common REST/Ability pagination schema.
	 *
	 * @return array
	 */
	public static function input_schema() {
		return array(
			'page'     => array(
				'type'    => 'integer',
				'minimum' => 1,
				'default' => 1,
			),
			'per_page' => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => self::MAX_PER_PAGE,
				'default' => self::DEFAULT_PER_PAGE,
			),
		);
	}
}
