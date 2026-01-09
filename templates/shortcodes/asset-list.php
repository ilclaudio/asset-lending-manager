<?php
/**
 * Template for asset list shortcode
 *
 * Available variables:
 * - $assets: Array of asset wrapper objects from ALM_Asset_Manager::get_asset_wrapper()
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

$alm_current_search = '';
if ( isset( $_GET['s'] ) ) {
	$alm_current_search = sanitize_text_field( wp_unslash( $_GET['s'] ) );
}
?>

<div id="alm_asset_search_form">
	<form method="get" class="alm-asset-search-form">
		<div class="alm-search-input-wrap">
			<span class="alm-search-icon" aria-hidden="true"></span>
			<input
				type="search"
				name="s"
				value="<?php echo esc_attr( $alm_current_search ); ?>"
				placeholder="<?php esc_attr_e( 'Search assets...', 'asset-lending-manager' ); ?>"
			/>
			<div id="alm_asset_autocomplete_dropdown" class="alm-autocomplete-dropdown"></div>
		</div>
		<button type="submit"><?php esc_html_e( 'Search', 'asset-lending-manager' ); ?></button>
	</form>
</div>

<div id="alm_asset_search_results">

	<?php if ( $assets_count > 0 ) : ?>
		<p class="alm-asset-search-count">
			<?php
			printf(
				esc_html__( 'Assets found: %d', 'asset-lending-manager' ),
				(int) $assets_count
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $assets ) ) : ?>

		<div class="alm-asset-list">
			<?php foreach ( $assets as $alm_asset ) : ?>
				<article class="alm-asset-card">
					<a href="<?php echo esc_url( $alm_asset->permalink ); ?>" class="alm-asset-link">
						<?php if ( $alm_asset->thumbnail ) : ?>
							<div class="alm-asset-thumbnail">
								<?php echo $alm_asset->thumbnail; ?>
							</div>
						<?php endif; ?>
						<div class="alm-asset-content-wrapper">
							<h2 class="alm-asset-title"><?php echo esc_html( $alm_asset->title ); ?></h2>
							<div class="alm-asset-taxonomies">
								<!-- Tax: Structure -->
								<div class="alm-asset-taxonomy">
									<span class="alm-tax-label"><?php echo esc_attr( __( 'Structure', 'asset-lending-manager' ) ); ?>:</span>
									<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $alm_asset->alm_structure ) ); ?></span>
								</div>
								<!-- Tax: Type -->
								<div class="alm-asset-taxonomy">
									<span class="alm-tax-label"><?php echo esc_attr( __( 'Type', 'asset-lending-manager' ) ); ?>:</span>
									<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $alm_asset->alm_type ) ); ?></span>
								</div>
								<!-- Tax: State -->
								<div class="alm-asset-taxonomy">
									<span class="alm-tax-label"><?php echo esc_attr( __( 'State', 'asset-lending-manager' ) ); ?>:</span>
									<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $alm_asset->alm_state ) ); ?></span>
								</div>
								<!-- Tax: Level -->
								<div class="alm-asset-taxonomy">
									<span class="alm-tax-label"><?php echo esc_attr( __( 'Level', 'asset-lending-manager' ) ); ?>:</span>
									<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $alm_asset->alm_level ) ); ?></span>
								</div>
							</div>
						</div>
					</a>
				</article>
			<?php endforeach; ?>
		</div>

	<?php else : ?>

		<p class="alm-no-results">
			<?php
			if ( ! empty( $alm_current_search ) ) {
				printf(
					esc_html__( 'No results found for "%s".', 'asset-lending-manager' ),
					esc_html( $alm_current_search )
				);
			} else {
				esc_html_e( 'No assets found.', 'asset-lending-manager' );
			}
			?>
		</p>

	<?php endif; ?>
</div>
