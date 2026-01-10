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
// ========== Get taxonomy terms for filters ========== \\
$alm_terms_structure = get_terms(
	array(
		'taxonomy'   => ALM_ASSET_STRUCTURE_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
$alm_terms_type = get_terms(
	array(
		'taxonomy'   => ALM_ASSET_TYPE_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
$alm_terms_state = get_terms(
	array(
		'taxonomy'   => ALM_ASSET_STATE_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
$alm_terms_level = get_terms(
	array(
		'taxonomy'   => ALM_ASSET_LEVEL_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
// Count active filters.
$alm_active_filters_count = 0;
if ( ! empty( $filter_structure ) ) {
	$alm_active_filters_count++;
}
if ( ! empty( $filter_type ) ) {
	$alm_active_filters_count++;
}
if ( ! empty( $filter_state ) ) {
	$alm_active_filters_count++;
}
if ( ! empty( $filter_level ) ) {
	$alm_active_filters_count++;
}

?>

<div id="alm_asset_search_form">
	<form method="get" class="alm-asset-search-form">
		<!-- Input search text -->
		<div class="alm-search-row">
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
		</div>

		<!-- Advanced Filters -->
		<details class="alm-filters-collapsible">
			<summary class="alm-filters-toggle">
				<?php esc_html_e( 'Advanced Filters', 'asset-lending-manager' ); ?>
				<?php if ( $alm_active_filters_count > 0 ) : ?>
					<span class="alm-active-filters-badge"><?php echo (int) $alm_active_filters_count; ?></span>
				<?php endif; ?>
			</summary>
			<!-- Taxonomy filters -->
			<div class="alm-filters-content">
				<div class="alm-filters-grid">
					<!-- Row 1: Structure + Type -->
					<div class="alm-filter-row">
						<div class="alm-filter-field">
							<label for="alm_filter_structure"><?php esc_html_e( 'Structure', 'asset-lending-manager' ); ?></label>
							<select name="alm_structure" id="alm_filter_structure">
								<option value=""><?php esc_html_e( 'All structures', 'asset-lending-manager' ); ?></option>
								<?php if ( ! is_wp_error( $alm_terms_structure ) && ! empty( $alm_terms_structure ) ) : ?>
									<?php foreach ( $alm_terms_structure as $term ) : ?>
										<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter_structure, $term->slug ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
						<div class="alm-filter-field">
							<label for="alm_filter_type"><?php esc_html_e( 'Type', 'asset-lending-manager' ); ?></label>
							<select name="alm_type" id="alm_filter_type">
								<option value=""><?php esc_html_e( 'All types', 'asset-lending-manager' ); ?></option>
								<?php if ( ! is_wp_error( $alm_terms_type ) && ! empty( $alm_terms_type ) ) : ?>
									<?php foreach ( $alm_terms_type as $term ) : ?>
										<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter_type, $term->slug ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</div>
					<!-- Row 2: State + Level -->
					<div class="alm-filter-row">
						<div class="alm-filter-field">
							<label for="alm_filter_state"><?php esc_html_e( 'State', 'asset-lending-manager' ); ?></label>
							<select name="alm_state" id="alm_filter_state">
								<option value=""><?php esc_html_e( 'All states', 'asset-lending-manager' ); ?></option>
								<?php if ( ! is_wp_error( $alm_terms_state ) && ! empty( $alm_terms_state ) ) : ?>
									<?php foreach ( $alm_terms_state as $term ) : ?>
										<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter_state, $term->slug ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
						<div class="alm-filter-field">
							<label for="alm_filter_level"><?php esc_html_e( 'Level', 'asset-lending-manager' ); ?></label>
							<select name="alm_level" id="alm_filter_level">
								<option value=""><?php esc_html_e( 'All levels', 'asset-lending-manager' ); ?></option>
								<?php if ( ! is_wp_error( $alm_terms_level ) && ! empty( $alm_terms_level ) ) : ?>
									<?php foreach ( $alm_terms_level as $term ) : ?>
										<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter_level, $term->slug ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</div>
				</div>
				<!-- Reset filters button -->
				<div class="alm-filters-actions">
					<a href="<?php echo esc_url( remove_query_arg( array( 's', 'alm_structure', 'alm_type', 'alm_state', 'alm_level' ) ) ); ?>" class="alm-reset-filters">
						<?php esc_html_e( 'Reset Filters', 'asset-lending-manager' ); ?>
					</a>
				</div>
			</div>
		</details>

		<!-- Search Button -->
		<button type="submit">
			<?php esc_html_e( 'Search', 'asset-lending-manager' ); ?>
		</button>

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
