<?php
/**
 * Template for asset list shortcode.
 *
 * Variables injected from render_asset_list_template():
 * - $assets:             array    Asset wrapper objects (ALMGR_Asset_Manager::get_asset_wrapper()).
 * - $assets_count:       int      Total number of matching assets (all pages).
 * - $current_page:       int      Current pagination page number.
 * - $total_pages:        int      Total number of pagination pages.
 * - $almgr_current_search: string  Current search term (sanitized).
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
$almgr_terms_structure = get_terms(
	array(
		'taxonomy'   => ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
$almgr_terms_type      = get_terms(
	array(
		'taxonomy'   => ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
$almgr_terms_state     = get_terms(
	array(
		'taxonomy'   => ALMGR_ASSET_STATE_TAXONOMY_SLUG,
		'hide_empty' => false,
	)
);
$almgr_terms_level     = get_terms(
	array(
		'taxonomy'   => ALMGR_ASSET_LEVEL_TAXONOMY_SLUG,
		'hide_empty' => true,
	)
);
// Count active filters.
$almgr_active_filters_count = 0;
if ( ! empty( $filter_structure ) ) {
	++$almgr_active_filters_count;
}
if ( ! empty( $filter_type ) ) {
	++$almgr_active_filters_count;
}
if ( ! empty( $filter_state ) ) {
	++$almgr_active_filters_count;
}
if ( ! empty( $filter_level ) ) {
	++$almgr_active_filters_count;
}
if ( $filter_owner > 0 ) {
	++$almgr_active_filters_count;
}

?>

<div id="almgr_asset_search_form">
	<form method="get" class="almgr-asset-search-form">
		<!-- Input search text -->
		<div class="almgr-search-row">
			<div class="almgr-search-input-wrap">
				<span class="almgr-search-icon" aria-hidden="true"></span>
				<input
					type="search"
					name="s"
					id="almgr-search-input"
					aria-label="<?php esc_attr_e( 'Search assets', 'asset-lending-manager' ); ?>"
					aria-autocomplete="list"
					aria-expanded="false"
					aria-controls="almgr_asset_autocomplete_dropdown"
					value="<?php echo esc_attr( $almgr_current_search ); ?>"
					placeholder="<?php esc_attr_e( 'Search assets...', 'asset-lending-manager' ); ?>"
				/>
				<div
					id="almgr_asset_autocomplete_dropdown"
					class="almgr-autocomplete-dropdown"
					role="region"
					aria-live="polite"
					aria-label="<?php esc_attr_e( 'Search suggestions', 'asset-lending-manager' ); ?>"
				></div>
			</div>
		<?php if ( ! empty( $almgr_qr_scan_enabled ) ) : ?>
			<button
				type="button"
				class="almgr-button almgr-button--secondary almgr-qr-scan-btn"
				aria-label="<?php esc_attr_e( 'Scan QR code to find an asset', 'asset-lending-manager' ); ?>"
			>
				<?php esc_html_e( 'Scan QR', 'asset-lending-manager' ); ?>
			</button>
		<?php endif; ?>
		</div>

		<!-- Advanced Filters -->
		<details class="almgr-filters-collapsible"<?php echo ! empty( $almgr_default_filters_open ) ? ' open' : ''; ?>>
			<summary class="almgr-filters-toggle">
				<?php esc_html_e( 'Advanced Filters', 'asset-lending-manager' ); ?>
				<?php if ( $almgr_active_filters_count > 0 ) : ?>
					<span class="almgr-active-filters-badge"><?php echo (int) $almgr_active_filters_count; ?></span>
				<?php endif; ?>
			</summary>
			<!-- Taxonomy filters -->
			<div class="almgr-filters-content">
				<div class="almgr-filters-grid">
					<!-- Row 1: Structure + Type -->
					<div class="almgr-filter-row">
						<div class="almgr-filter-field">
							<label for="almgr_filter_structure"><?php esc_html_e( 'Structure', 'asset-lending-manager' ); ?></label>
							<select name="almgr_structure" id="almgr_filter_structure">
								<option value=""><?php esc_html_e( 'All structures', 'asset-lending-manager' ); ?></option>
									<?php if ( ! is_wp_error( $almgr_terms_structure ) && ! empty( $almgr_terms_structure ) ) : ?>
										<?php foreach ( $almgr_terms_structure as $almgr_term ) : ?>
											<option value="<?php echo esc_attr( $almgr_term->slug ); ?>" <?php selected( $filter_structure, $almgr_term->slug ); ?>>
												<?php echo esc_html( $almgr_term->name ); ?>
											</option>
										<?php endforeach; ?>
									<?php endif; ?>
							</select>
						</div>
						<div class="almgr-filter-field">
							<label for="almgr_filter_type"><?php esc_html_e( 'Type', 'asset-lending-manager' ); ?></label>
							<select name="almgr_type" id="almgr_filter_type">
								<option value=""><?php esc_html_e( 'All types', 'asset-lending-manager' ); ?></option>
									<?php if ( ! is_wp_error( $almgr_terms_type ) && ! empty( $almgr_terms_type ) ) : ?>
										<?php foreach ( $almgr_terms_type as $almgr_term ) : ?>
											<option value="<?php echo esc_attr( $almgr_term->slug ); ?>" <?php selected( $filter_type, $almgr_term->slug ); ?>>
												<?php echo esc_html( $almgr_term->name ); ?>
											</option>
										<?php endforeach; ?>
									<?php endif; ?>
							</select>
						</div>
					</div>
					<!-- Row 2: State + Level -->
					<div class="almgr-filter-row">
						<div class="almgr-filter-field">
							<label for="almgr_filter_state"><?php esc_html_e( 'State', 'asset-lending-manager' ); ?></label>
							<select name="almgr_state" id="almgr_filter_state">
								<option value=""><?php esc_html_e( 'All states', 'asset-lending-manager' ); ?></option>
										<?php if ( ! is_wp_error( $almgr_terms_state ) && ! empty( $almgr_terms_state ) ) : ?>
											<?php foreach ( $almgr_terms_state as $almgr_term ) : ?>
												<option value="<?php echo esc_attr( $almgr_term->slug ); ?>" <?php selected( $filter_state, $almgr_term->slug ); ?>>
													<?php echo esc_html( ALMGR_Asset_Manager::get_state_label( (string) $almgr_term->slug, (string) $almgr_term->name ) ); ?>
												</option>
											<?php endforeach; ?>
										<?php endif; ?>
							</select>
						</div>
						<div class="almgr-filter-field">
							<label for="almgr_filter_level"><?php esc_html_e( 'Level', 'asset-lending-manager' ); ?></label>
							<select name="almgr_level" id="almgr_filter_level">
								<option value=""><?php esc_html_e( 'All levels', 'asset-lending-manager' ); ?></option>
										<?php if ( ! is_wp_error( $almgr_terms_level ) && ! empty( $almgr_terms_level ) ) : ?>
											<?php foreach ( $almgr_terms_level as $almgr_term ) : ?>
												<option value="<?php echo esc_attr( $almgr_term->slug ); ?>" <?php selected( $filter_level, $almgr_term->slug ); ?>>
													<?php echo esc_html( ALMGR_Asset_Manager::get_level_label( (string) $almgr_term->slug, (string) $almgr_term->name ) ); ?>
												</option>
											<?php endforeach; ?>
										<?php endif; ?>
							</select>
						</div>
					</div>
					<!-- Row 3: Owner -->
					<?php if ( current_user_can( ALMGR_EDIT_ASSET ) ) : ?>
						<div class="almgr-filter-row">
							<div class="almgr-filter-field">
								<label for="almgr-owner-filter-input"><?php esc_html_e( 'Owner', 'asset-lending-manager' ); ?></label>
								<div class="almgr-autocomplete-wrap">
									<span class="almgr-search-icon" aria-hidden="true"></span>
									<input
										type="text"
										id="almgr-owner-filter-input"
										autocomplete="off"
										placeholder="<?php esc_attr_e( 'Search user...', 'asset-lending-manager' ); ?>"
										value="<?php echo esc_attr( $filter_owner_name ); ?>"
									/>
									<div id="almgr-owner-filter-dropdown" class="almgr-autocomplete-dropdown"></div>
									<input
										type="hidden"
										name="almgr_owner"
										id="almgr-owner-filter-id"
										value="<?php echo esc_attr( $filter_owner > 0 ? $filter_owner : '' ); ?>"
									/>
								</div>
							</div>
						</div>
					<?php elseif ( is_user_logged_in() ) : ?>
						<div class="almgr-filter-row">
							<div class="almgr-filter-field">
								<label class="almgr-checkbox-label">
									<input
										type="checkbox"
										name="almgr_my_assets"
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
		<div class="almgr-form-actions">
			<a href="<?php echo esc_url( remove_query_arg( array( 's', 'almgr_structure', 'almgr_type', 'almgr_state', 'almgr_level', 'almgr_owner', 'almgr_my_assets', 'almgr_paged' ) ) ); ?>" class="almgr-reset-filters">
				<?php esc_html_e( 'Reset Filters', 'asset-lending-manager' ); ?>
			</a>
			<button type="submit">
				<?php esc_html_e( 'Search', 'asset-lending-manager' ); ?>
			</button>
		</div>

	</form>
</div>

<div id="almgr_asset_search_results">

	<?php if ( $assets_count > 0 ) : ?>
		<p class="almgr-asset-search-count">
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

		<div class="almgr-asset-list">
			<?php foreach ( $assets as $almgr_asset ) : ?>
				<article class="almgr-asset-card">
					<a href="<?php echo esc_url( $almgr_asset->permalink ); ?>" class="almgr-asset-link">
						<div class="almgr-asset-thumbnail">
							<?php echo wp_kses_post( $almgr_asset->thumbnail ); ?>
						</div>
						<div class="almgr-asset-content-wrapper">
							<div class="almgr-card-title-row">
								<h2 class="almgr-asset-title"><?php echo esc_html( $almgr_asset->title ); ?></h2>
								<?php
								$almgr_state_classes = ALMGR_Asset_Manager::get_state_classes();
								foreach ( $almgr_asset->almgr_state as $almgr_si => $almgr_state_name ) :
									$almgr_state_slug  = $almgr_asset->almgr_state_slugs[ $almgr_si ] ?? '';
									$almgr_badge_class = $almgr_state_classes[ $almgr_state_slug ] ?? '';
									?>
									<span class="almgr-availability <?php echo esc_attr( $almgr_badge_class ); ?>">
										<?php echo esc_html( $almgr_state_name ); ?>
									</span>
								<?php endforeach; ?>
							</div>
							<div class="almgr-asset-taxonomies">
								<!-- Tax: Structure -->
								<div class="almgr-asset-taxonomy">
									<span class="almgr-tax-label"><?php esc_html_e( 'Structure', 'asset-lending-manager' ); ?>:</span>
									<span class="almgr-tax-value"><?php echo esc_html( implode( ', ', $almgr_asset->almgr_structure ) ); ?></span>
								</div>
								<!-- Tax: Type -->
								<div class="almgr-asset-taxonomy">
									<span class="almgr-tax-label"><?php esc_html_e( 'Type', 'asset-lending-manager' ); ?>:</span>
									<span class="almgr-tax-value"><?php echo esc_html( implode( ', ', $almgr_asset->almgr_type ) ); ?></span>
								</div>
								<!-- Tax: Level (shown only if assigned) -->
								<?php if ( ! empty( $almgr_asset->almgr_level ) ) : ?>
								<div class="almgr-asset-taxonomy">
									<span class="almgr-tax-label"><?php esc_html_e( 'Level', 'asset-lending-manager' ); ?>:</span>
									<span class="almgr-tax-value"><?php echo esc_html( implode( ', ', $almgr_asset->almgr_level ) ); ?></span>
								</div>
								<?php endif; ?>
								<!-- Owner: visible to logged-in users only. -->
								<?php if ( is_user_logged_in() && $almgr_asset->owner_name ) : ?>
									<div class="almgr-asset-taxonomy">
										<span class="almgr-tax-label"><?php esc_html_e( 'Owner', 'asset-lending-manager' ); ?>:</span>
										<span class="almgr-tax-value"><?php echo esc_html( $almgr_asset->owner_name ); ?></span>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</a>
					<?php if ( ! empty( $almgr_asset->parent_kits ) ) : ?>
						<div class="almgr-card-kit-footer" role="note" aria-label="<?php esc_attr_e( 'Kit membership', 'asset-lending-manager' ); ?>">
							<span class="almgr-card-kit-label"><?php esc_html_e( 'Kit', 'asset-lending-manager' ); ?>:</span>
							<?php foreach ( $almgr_asset->parent_kits as $almgr_ki => $almgr_kit ) : ?>
								<a class="almgr-card-kit-link" href="<?php echo esc_url( $almgr_kit['permalink'] ); ?>"><?php echo esc_html( $almgr_kit['title'] ); ?></a>
								<?php if ( $almgr_ki < count( $almgr_asset->parent_kits ) - 1 ) : ?>
									<span aria-hidden="true">,</span>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( $total_pages > 1 ) : ?>
			<nav class="almgr-pagination" aria-label="<?php esc_attr_e( 'Asset list pagination', 'asset-lending-manager' ); ?>">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'almgr_paged', '%#%' ),
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

		<p class="almgr-no-results">
		<?php
		if ( ! empty( $almgr_current_search ) ) {
			printf(
				/* translators: %s: Search term entered by the user. */
				esc_html__( 'No results found for "%s".', 'asset-lending-manager' ),
				esc_html( $almgr_current_search )
			);
		} else {
			esc_html_e( 'No assets found.', 'asset-lending-manager' );
		}
		?>
		</p>

	<?php endif; ?>
</div>
