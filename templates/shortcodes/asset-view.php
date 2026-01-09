<?php
/**
 * Template for asset view shortcode
 *
 * Available variables:
 * - $asset: Asset wrapper object from ALM_Asset_Manager::get_asset_wrapper()
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alm_asset_id = isset( $asset->id ) ? (int) $asset->id : 0;
if ( $alm_asset_id <= 0 ) {
	return;
}


$alm_asset_fields = ALM_Asset_Manager::get_asset_custom_fields( $alm_asset_id );
/**
 * Big image for detail (do not change list thumbnail).
 */
$alm_detail_image_html = '';
if ( has_post_thumbnail( $alm_asset_id ) ) {
	$alm_detail_image_html = get_the_post_thumbnail( $alm_asset_id, 'large' );
} else {
	// Fallback to wrapper thumbnail (already includes plugin default image).
	$alm_detail_image_html = isset( $asset->thumbnail ) ? (string) $asset->thumbnail : '';
}
?>

<article class="alm-asset-detail alm-asset-view">

	<!-- I section: Title -->
	<header class="alm-asset-view__title">
		<h1 class="alm-asset-title"><?php echo esc_html( $asset->title ); ?></h1>
	</header>

	<!-- II section: FOTO (sx) + Taxonomies box (dx) -->
	<section class="alm-asset-view__hero" aria-label="<?php esc_attr_e( 'Asset overview', 'asset-lending-manager' ); ?>">
		<!-- Asset foto -->
		<div class="alm-asset-view__media">
			<?php if ( $alm_detail_image_html ) : ?>
				<div class="alm-asset-thumbnail alm-asset-thumbnail--large">
					<?php echo $alm_detail_image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Asset taxonomies -->
		<?php
		// Get taxonomy values.
		$alm_structure   = isset( $asset->alm_structure ) ? implode( ', ', $asset->alm_structure ) : '-';
		$alm_type        = isset( $asset->alm_type ) ? implode( ', ', $asset->alm_type ) : '-';
		$alm_level       = isset( $asset->alm_level ) ? implode( ', ', $asset->alm_level ) : '-';
		$alm_state_terms = get_the_terms( $asset_id, 'alm_state' );
		$alm_state_slug  = '';
		$alm_state_label = '';
		if ( ! is_wp_error( $alm_state_terms ) && ! empty( $alm_state_terms ) ) {
			$alm_state_slug  = (string) $alm_state_terms[0]->slug;
			$alm_state_label = (string) $alm_state_terms[0]->name;
		}
		$alm_state_class_map = ALM_Asset_Manager::get_state_classes();
		$alm_state_css_class = '';
		if ( $alm_state_slug && isset( $alm_state_class_map[ $alm_state_slug ] ) ) {
			$alm_state_css_class = $alm_state_class_map[ $alm_state_slug ];
		}
		?>
		<aside class="alm-asset-view__taxbox" aria-label="<?php esc_attr_e( 'Asset taxonomies', 'asset-lending-manager' ); ?>">
			<div class="alm-asset-taxonomies alm-asset-taxonomies--boxed">
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php echo esc_attr_e( 'Structure', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<?php echo esc_attr( $alm_structure ); ?>
					</span>
				</div>
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php echo esc_attr_e( 'Type', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<?php echo esc_attr( $alm_type ); ?>
					</span>
				</div>
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php echo esc_attr_e( 'Level', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<?php echo esc_attr( $alm_level ); ?>
					</span>
				</div>
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php echo esc_attr_e( 'State', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<span class="alm-availability <?php echo esc_attr( $alm_state_css_class ); ?>">
							<?php echo esc_html( $alm_state_label ); ?>
						</span>
					</span>
				</div>
			</div>
		</aside>
	</section>

	<!-- III section: Asset description -->
	<section class="alm-asset-view__content" aria-label="<?php esc_attr_e( 'Full description', 'asset-lending-manager' ); ?>">
		<div class="alm-asset-content">
			<?php echo $asset->content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</section>

	<!-- IV section: Asset optional fields -->
	<section class="alm-asset-view__acf" aria-label="<?php esc_attr_e( 'Additional fields', 'asset-lending-manager' ); ?>">
		<details class="alm-collapsible alm-collapsible--acf" open>
			<summary class="alm-collapsible__summary">
				<span class="alm-collapsible__title">
					<?php esc_html_e( 'Read details', 'asset-lending-manager' ); ?>
				</span>
				<span class="alm-collapsible__hint" aria-hidden="true">
					<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>
			<div class="alm-collapsible__body">
				<?php if ( ! empty( $alm_asset_fields ) ) : ?>
					<dl class="alm-asset-acf-list">
						<?php foreach ( $alm_asset_fields as $alm_asset_row ) : ?>
							<?php if ( $alm_asset_row['value'] ): ?>
								<div class="alm-asset-acf-row alm-acf-<?php echo esc_attr( $alm_asset_row['name'] ); ?>">
									<dt class="alm-asset-acf-label">
										<?php echo esc_attr_e( $alm_asset_row['label'], 'asset-lending-manager' ); ?>
									</dt>
									<dd class="alm-asset-acf-value">
										<?php
										// Render by type.
										if ( 'file' === $alm_asset_row['type'] && is_array( $alm_asset_row['value'] ) && ! empty( $alm_asset_row['value']['url'] ) ) {
											$alm_file_url  = (string) $alm_asset_row['value']['url'];
											$alm_file_name = ! empty( $alm_asset_row['value']['filename'] ) ? (string) $alm_asset_row['value']['filename'] : $alm_file_url;
											?>
											<a href="<?php echo esc_url( $alm_file_url ); ?>" class="alm-link" target="_blank" rel="noopener">
												<?php echo esc_html( $alm_file_name ); ?>
											</a>
											<?php
										} elseif ( 'post_object' === $alm_asset_row['type'] && is_array( $alm_asset_row['value'] ) ) {
											// Multiple components (objects).
											echo '<ul class="alm-asset-components">';
											foreach ( $alm_asset_row['value'] as $alm_component_post ) {
												if ( is_object( $alm_component_post ) && ! empty( $alm_component_post->ID ) ) {
													$alm_asset_title = get_the_title( $alm_component_post->ID );
													$alm_asset_link  = get_permalink( $alm_component_post->ID );
													echo '<li><a class="alm-link" href="' . esc_url( $alm_asset_link ) . '">' . esc_html( $alm_asset_title ) . '</a></li>';
												}
											}
											echo '</ul>';
										} elseif ( 'number' === $alm_asset_row['type'] ) {
											// Cost / numeric fields.
											echo esc_html( (string) $alm_asset_row['value'] );
										} else {
											// Default (text, date, textarea, etc).
											if ( is_array( $alm_asset_row['value'] ) ) {
												echo esc_html( implode( ', ', array_map( 'strval', $alm_asset_row['value'] ) ) );
											} else {
												echo esc_html( (string) $alm_asset_row['value'] );
											}
										}
										?>
									</dd>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</dl>
				<?php else : ?>
					<p class="alm-muted"><?php esc_html_e( 'No additional details available.', 'asset-lending-manager' ); ?></p>
				<?php endif; ?>
			</div>
		</details>
	</section>

	<!-- V section: Book loan request -->
	<section class="alm-asset-view__loan-request" aria-label="<?php esc_attr_e( 'Loan request', 'asset-lending-manager' ); ?>">
		<details class="alm-collapsible alm-collapsible--requestbutton">
			<summary class="alm-collapsible__summary">
				<span class="alm-collapsible__title">
					<?php esc_html_e( 'Ask for a loan', 'asset-lending-manager' ); ?>
				</span>
				<span class="alm-collapsible__hint" aria-hidden="true">
					<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>
			<div class="alm-collapsible__body">
				<?php if ( is_user_logged_in() && current_user_can( ALM_VIEW_ASSET ) ) : ?>
					<button type="button" class="alm-button" disabled="disabled">
						<?php esc_html_e( 'Send loan request...', 'asset-lending-manager' ); ?>
					</button>
					<p class="alm-muted">
						<?php esc_html_e( 'Features under development.', 'asset-lending-manager' ); ?>
					</p>
				<?php else : ?>
					<p class="alm-muted">
						<?php esc_html_e( 'To request a loan, you must log in as a member.', 'asset-lending-manager' ); ?>
					</p>
				<?php endif; ?>
			</div>
		</details>
	</section>

	<!-- VI section: Loan requests -->
	<section class="alm-asset-view__loan-requests" aria-label="<?php esc_attr_e( 'Loan requests', 'asset-lending-manager' ); ?>">
		<details class="alm-collapsible alm-collapsible--requestlist">
			<summary class="alm-collapsible__summary">
				<span class="alm-collapsible__title">
					<?php esc_html_e( 'Loan requests', 'asset-lending-manager' ); ?>
				</span>
				<span class="alm-collapsible__hint" aria-hidden="true">
					<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>

			<div class="alm-collapsible__body">
				<p class="alm-muted">
					<?php esc_html_e( 'No requests to show (section under development).', 'asset-lending-manager' ); ?>
				</p>
			</div>

		</details>
	</section>

	<?php if ( is_user_logged_in() && current_user_can( ALM_EDIT_ASSET ) ) : ?>
		<!-- VII section: Loan history -->
		<section class="alm-asset-view__loan-history" aria-label="<?php esc_attr_e( 'Loan history', 'asset-lending-manager' ); ?>">
			<details class="alm-collapsible alm-collapsible--history">
				<summary class="alm-collapsible__summary">
					<span class="alm-collapsible__title">
						<?php esc_html_e( 'Loan history', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-collapsible__hint" aria-hidden="true">
						<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
					</span>
				</summary>

				<div class="alm-collapsible__body">
					<p class="alm-muted">
						<?php esc_html_e( 'History not available (section under development).', 'asset-lending-manager' ); ?>
					</p>
				</div>
			</details>
		</section>
	<?php endif; ?>

</article>
