<?php
/**
 * ALM Setup Manager.
 *
 * Handles initial setup tasks for the Asset Lending Manager plugin.
 * Ensures that required default taxonomy terms exist.
 *
 * This class is intentionally idempotent: all operations can be safely
 * executed multiple times without causing side effects.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ALM_Installer
 */
class ALM_Installer {

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public function activate() {
		// Do nothing.
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Do nothing.
	}

	/**
	 * Public entry point.
	 *
	 * Ensures that all default taxonomy terms required by ALM
	 * are present in the system.
	 *
	 * This method can be safely called:
	 * - on plugin activation
	 * - during maintenance operations
	 * - after accidental data deletion
	 *
	 * @return void
	 */
	public static function create_default_terms(): void {

		// Device structure.
		self::add_default_terms(
			ALM_DEVICE_STRUCTURE_TAXONOMY_SLUG,
			array(
				array(
					'slug'  => 'component',
					'label' => __( 'Component', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'kit',
					'label' => __( 'Kit', 'asset-lending-manager' ),
				),
			)
		);

		// Device types.
		self::add_default_terms(
			ALM_DEVICE_TYPE_TAXONOMY_SLUG,
			array(
				array(
					'slug'  => 'telescope',
					'label' => __( 'Telescope', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'ocular',
					'label' => __( 'Ocular', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'refractor',
					'label' => __( 'Refractor', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'optical-tube',
					'label' => __( 'Optical Tube', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'binoculars',
					'label' => __( 'Binoculars', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'tripod',
					'label' => __( 'Tripod', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'filter',
					'label' => __( 'Filter', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'accessory',
					'label' => __( 'Accessory', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'book',
					'label' => __( 'Book', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'magazine',
					'label' => __( 'Magazine', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'mount',
					'label' => __( 'Mount', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'generic',
					'label' => __( 'Generic', 'asset-lending-manager' ),
				),
			)
		);

		// Device state.
		self::add_default_terms(
			ALM_DEVICE_STATE_TAXONOMY_SLUG,
			array(
				array(
					'slug'  => 'on-loan',
					'label' => __( 'On loan', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'available',
					'label' => __( 'Available', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'maintenance',
					'label' => __( 'Maintenance', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'retired',
					'label' => __( 'Retired', 'asset-lending-manager' ),
				),
			)
		);

		// Device level.
		self::add_default_terms(
			ALM_DEVICE_LEVEL_TAXONOMY_SLUG,
			array(
				array(
					'slug'  => 'basic',
					'label' => __( 'Basic', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'intermediate',
					'label' => __( 'Intermediate', 'asset-lending-manager' ),
				),
				array(
					'slug'  => 'advanced',
					'label' => __( 'Advanced', 'asset-lending-manager' ),
				),
			)
		);
	}

	/**
	 * Adds terms to a taxonomy if they do not exist.
	 *
	 * @param string $taxonomy_slug Taxonomy slug.
	 * @param array  $terms         Array of arrays: [ 'slug' => string, 'label' => string ].
	 * @return void
	 */
	private static function add_default_terms( string $taxonomy_slug, array $terms ): void {
		if ( ! taxonomy_exists( $taxonomy_slug ) ) {
			return;
		}
		foreach ( $terms as $term ) {
			$term_exists = term_exists( $term['slug'], $taxonomy_slug );
			if ( $term_exists ) {
				continue;
			}
			wp_insert_term(
				$term['label'],
				$taxonomy_slug,
				array(
					'slug' => $term['slug'],
				)
			);
		}
	}
}
