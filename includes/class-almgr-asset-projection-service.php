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
	 * ACF field keys visible only to callers holding ALMGR_EDIT_ASSET (operators).
	 *
	 * Single source of truth for both REST and Abilities, so the two channels
	 * cannot silently diverge on which asset fields are operator-only.
	 *
	 * @var string[]
	 */
	const OPERATOR_ONLY_ACF_KEYS = array( 'almgr_cost', 'almgr_data_acquisto', 'almgr_notes' );

	/**
	 * Filter a raw ACF field-object map (from get_field_objects()) down to the
	 * keys visible to the current caller: operator-only keys are removed for
	 * non-operators, everything else passes through unchanged.
	 *
	 * @param array $acf_fields Raw ACF field-object map.
	 * @param bool  $is_operator Whether the caller holds ALMGR_EDIT_ASSET.
	 * @return array
	 */
	public static function filter_acf_fields( array $acf_fields, $is_operator ) {
		if ( $is_operator ) {
			return $acf_fields;
		}

		return array_diff_key( $acf_fields, array_flip( self::OPERATOR_ONLY_ACF_KEYS ) );
	}

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
