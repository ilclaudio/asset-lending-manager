<?php
/**
 * Shared transport-neutral projections for asset responses.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the common asset response shapes used by REST and Abilities.
 */
class ALMGR_Asset_Projection_Service {

	/**
	 * Project the common asset fields.
	 *
	 * @param object $asset Shared asset record.
	 * @return array
	 */
	public static function asset( $asset ) {
		return array(
			'id'             => (int) $asset->id,
			'code'           => (string) $asset->code,
			'title'          => (string) $asset->title,
			'permalink'      => (string) $asset->permalink,
			'thumbnail_url'  => $asset->thumbnail_url,
			'structure'      => $asset->structure_slugs,
			'type'           => $asset->type_slugs,
			'state'          => $asset->almgr_state_slugs ?? array(),
			'level'          => $asset->level_slugs,
			'warehouse'      => $asset->warehouse_slugs ?? array(),
			'owner_id'       => (int) $asset->owner_id,
			'owner_name'     => (string) $asset->owner_name,
			'owner_username' => (string) $asset->owner_username,
		);
	}

	/**
	 * Project the common member-held asset fields.
	 *
	 * @param object $asset Shared asset record.
	 * @return array
	 */
	public static function member_asset( $asset ) {
		return array(
			'id'            => (int) $asset->id,
			'code'          => (string) $asset->code,
			'title'         => (string) $asset->title,
			'structure'     => $asset->structure_slugs,
			'type'          => $asset->type_slugs,
			'external_code' => (string) $asset->external_code,
			'location'      => (string) $asset->location,
			'thumbnail_url' => $asset->thumbnail_url,
			'permalink'     => (string) $asset->permalink,
		);
	}
}
