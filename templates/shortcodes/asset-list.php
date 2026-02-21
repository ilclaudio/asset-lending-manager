<?php
/**
 * Template for asset list shortcode.
 *
 * Variables injected from render_asset_list_template():
 * - $assets:             array    Asset wrapper objects (ALM_Asset_Manager::get_asset_wrapper()).
 * - $assets_count:       int      Total number of matching assets (all pages).
 * - $current_page:       int      Current pagination page number.
 * - $total_pages:        int      Total number of pagination pages.
 * - $alm_current_search: string  Current search term (sanitized).
 * - $filter_structure:   string  Active structure taxonomy filter slug.
 * - $filter_type:        string  Active type taxonomy filter slug.
 * - $filter_state:       string  Active state taxonomy filter slug.
 * - $filter_level:       string  Active level taxonomy filter slug.
 * - $filter_owner:       int     Active owner user ID (0 if none).
 * - $filter_owner_name:  string  Display name of the active owner filter user.
 * - $filter_my_assets:   bool    True if member's "show only my assets" is active.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

// Get taxonomy terms for filters.
$alm_terms_structure = get_terms(
	array(
		'taxonomy'   => ALM_ASSET_STRUCTURE_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
$alm_terms_type      = get_terms(
	array(
		'taxonomy'   => ALM_ASSET_TYPE_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
$alm_terms_state     = get_terms(
	array(
		'taxonomy'   => ALM_ASSET_STATE_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
$alm_terms_level     = get_terms(
	array(
		'taxonomy'   => ALM_ASSET_LEVEL_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
// Count active filters.
$alm_active_filters_count = 0;
if ( ! empty( $filter_structure ) ) {
	++$alm_active_filters_count;
}
if ( ! empty( $filter_type ) ) {
	++$alm_active_filters_count;
}
if ( ! empty( $filter_state ) ) {
	++$alm_active_filters_count;
}
if ( ! empty( $filter_level ) ) {
	++$alm_active_filters_count;
}
if ( $filter_owner > 0 ) {
	++$alm_active_filters_count;
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
					id="alm-search-input"
					aria-label="<?php esc_attr_e( 'Search assets', 'asset-lending-manager' ); ?>"
					aria-autocomplete="list"
					aria-expanded="false"
					aria-controls="alm_asset_autocomplete_dropdown"
					value="<?php echo esc_attr( $alm_current_search ); ?>"
					placeholder="<?php esc_attr_e( 'Search assets...', 'asset-lending-manager' ); ?>"
				/>
				<div
					id="alm_asset_autocomplete_dropdown"
					class="alm-autocomplete-dropdown"
					role="region"
					aria-live="polite"
					aria-label="<?php esc_attr_e( 'Search suggestions', 'asset-lending-manager' ); ?>"
				></div>
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
										<?php foreach ( $alm_terms_structure as $alm_term ) : ?>
											<option value="<?php echo esc_attr( $alm_term->slug ); ?>" <?php selected( $filter_structure, $alm_term->slug ); ?>>
												<?php echo esc_html( $alm_term->name ); ?>
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
										<?php foreach ( $alm_terms_type as $alm_term ) : ?>
											<option value="<?php echo esc_attr( $alm_term->slug ); ?>" <?php selected( $filter_type, $alm_term->slug ); ?>>
												<?php echo esc_html( $alm_term->name ); ?>
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
										<?php foreach ( $alm_terms_state as $alm_term ) : ?>
											<option value="<?php echo esc_attr( $alm_term->slug ); ?>" <?php selected( $filter_state, $alm_term->slug ); ?>>
												<?php echo esc_html( $alm_term->name ); ?>
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
										<?php foreach ( $alm_terms_level as $alm_term ) : ?>
											<option value="<?php echo esc_attr( $alm_term->slug ); ?>" <?php selected( $filter_level, $alm_term->slug ); ?>>
												<?php echo esc_html( $alm_term->name ); ?>
											</option>
										<?php endforeach; ?>
									<?php endif; ?>
							</select>
						</div>
					</div>
					<!-- Row 3: Owner -->
					<?php if ( current_user_can( ALM_EDIT_ASSET ) ) : ?>
						<div class="alm-filter-row">
							<div class="alm-filter-field">
								<label for="alm-owner-filter-input"><?php esc_html_e( 'Owner', 'asset-lending-manager' ); ?></label>
								<div class="alm-autocomplete-wrap">
									<span class="alm-search-icon" aria-hidden="true"></span>
									<input
										type="text"
										id="alm-owner-filter-input"
										autocomplete="off"
										placeholder="<?php esc_attr_e( 'Search user...', 'asset-lending-manager' ); ?>"
										value="<?php echo esc_attr( $filter_owner_name ); ?>"
									/>
									<div id="alm-owner-filter-dropdown" class="alm-autocomplete-dropdown"></div>
									<input
										type="hidden"
										name="alm_owner"
										id="alm-owner-filter-id"
										value="<?php echo esc_attr( $filter_owner > 0 ? $filter_owner : '' ); ?>"
									/>
								</div>
							</div>
						</div>
					<?php elseif ( is_user_logged_in() ) : ?>
						<div class="alm-filter-row">
							<div class="alm-filter-field">
								<label class="alm-checkbox-label">
									<input
										type="checkbox"
										name="alm_my_assets"
										value="1"
										<?php checked( $filter_my_assets, true ); ?>
									/>
									<?php esc_html_e( 'Show only my assets', 'asset-lending-manager' ); ?>
								</label>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</details>

		<!-- Form actions: Reset filters + Search -->
		<div class="alm-form-actions">
			<a href="<?php echo esc_url( remove_query_arg( array( 's', 'alm_structure', 'alm_type', 'alm_state', 'alm_level', 'alm_owner', 'alm_my_assets', 'alm_paged' ) ) ); ?>" class="alm-reset-filters">
				<?php esc_html_e( 'Reset Filters', 'asset-lending-manager' ); ?>
			</a>
			<button type="submit">
				<?php esc_html_e( 'Search', 'asset-lending-manager' ); ?>
			</button>
		</div>

	</form>
</div>

<div id="alm_asset_search_results">

	<?php if ( $assets_count > 0 ) : ?>
		<p class="alm-asset-search-count">
			<?php
			printf(
				/* translators: %d: Number of assets found. */
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
						<div class="alm-asset-thumbnail">
							<?php echo wp_kses_post( $alm_asset->thumbnail ); ?>
						</div>
						<div class="alm-asset-content-wrapper">
							<div class="alm-card-title-row">
								<h2 class="alm-asset-title"><?php echo esc_html( $alm_asset->title ); ?></h2>
								<?php
								$alm_state_classes = ALM_Asset_Manager::get_state_classes();
								foreach ( $alm_asset->alm_state as $alm_si => $alm_state_name ) :
									$alm_state_slug  = $alm_asset->alm_state_slugs[ $alm_si ] ?? '';
									$alm_badge_class = $alm_state_classes[ $alm_state_slug ] ?? '';
									?>
									<span class="alm-availability <?php echo esc_attr( $alm_badge_class ); ?>">
										<?php echo esc_html( $alm_state_name ); ?>
									</span>
								<?php endforeach; ?>
							</div>
							<div class="alm-asset-taxonomies">
								<!-- Tax: Structure -->
								<div class="alm-asset-taxonomy">
									<span class="alm-tax-label"><?php esc_html_e( 'Structure', 'asset-lending-manager' ); ?>:</span>
									<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $alm_asset->alm_structure ) ); ?></span>
								</div>
								<!-- Tax: Type -->
								<div class="alm-asset-taxonomy">
									<span class="alm-tax-label"><?php esc_html_e( 'Type', 'asset-lending-manager' ); ?>:</span>
									<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $alm_asset->alm_type ) ); ?></span>
								</div>
								<!-- Tax: Level (shown only if assigned) -->
								<?php if ( ! empty( $alm_asset->alm_level ) ) : ?>
								<div class="alm-asset-taxonomy">
									<span class="alm-tax-label"><?php esc_html_e( 'Level', 'asset-lending-manager' ); ?>:</span>
									<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $alm_asset->alm_level ) ); ?></span>
								</div>
								<?php endif; ?>
								<!-- Owner -->
								<?php if ( $alm_asset->owner_name ) : ?>
									<div class="alm-asset-taxonomy">
										<span class="alm-tax-label"><?php esc_html_e( 'Owner', 'asset-lending-manager' ); ?>:</span>
										<span class="alm-tax-value"><?php echo esc_html( $alm_asset->owner_name ); ?></span>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</a>
				</article>
			<?php endforeach; ?>
		</div>

			<?php if ( $total_pages > 1 ) : ?>
				<nav class="alm-pagination" aria-label="<?php esc_attr_e( 'Asset list pagination', 'asset-lending-manager' ); ?>">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'alm_paged', '%#%' ),
								'format'    => '',
								'current'   => $current_page,
								'total'     => $total_pages,
								'prev_text' => '<span aria-hidden="true">&laquo;</span><span class="screen-reader-text">' . esc_html__( 'Previous page', 'asset-lending-manager' ) . '</span>',
								'next_text' => '<span aria-hidden="true">&raquo;</span><span class="screen-reader-text">' . esc_html__( 'Next page', 'asset-lending-manager' ) . '</span>',
							)
						)
					);
					?>
				</nav>
			<?php endif; ?>

		<?php else : ?>

			<p class="alm-no-results">
			<?php
			if ( ! empty( $alm_current_search ) ) {
				printf(
					/* translators: %s: Search term entered by the user. */
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
