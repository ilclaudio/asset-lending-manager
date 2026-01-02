<?php
/**
 * Template for device view shortcode
 *
 * Available variables:
 * - $device: Device wrapper object from ALM_Device_Manager::get_device_wrapper()
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alm_device_id = isset( $device->id ) ? (int) $device->id : 0;
if ( $alm_device_id <= 0 ) {
	return;
}

/**
 * Helper: render a taxonomy row.
 */
$alm_render_tax_row = function( $taxonomy_slug, $values ) {
	if ( empty( $values ) || ! is_array( $values ) ) {
		return;
	}

	$taxonomy_obj = get_taxonomy( $taxonomy_slug );
	$label        = $taxonomy_obj && ! empty( $taxonomy_obj->labels->singular_name ) ? $taxonomy_obj->labels->singular_name : $taxonomy_slug;

	?>
	<div class="alm-device-tax-row alm-tax-<?php echo esc_attr( $taxonomy_slug ); ?>">
		<span class="alm-tax-label"><?php echo esc_html( $label ); ?></span>
		<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $values ) ); ?></span>
	</div>
	<?php
};


$alm_device_fields = ALM_Device_Manager::get_device_custom_fields( $alm_device_id );
/**
 * Big image for detail (do not change list thumbnail).
 */
$alm_detail_image_html = '';
if ( has_post_thumbnail( $alm_device_id ) ) {
	$alm_detail_image_html = get_the_post_thumbnail( $alm_device_id, 'large' );
} else {
	// Fallback to wrapper thumbnail (already includes plugin default image).
	$alm_detail_image_html = isset( $device->thumbnail ) ? (string) $device->thumbnail : '';
}
?>

<article class="alm-device-detail alm-device-view">

	<!-- I section: Title -->
	<header class="alm-device-view__title">
		<h1 class="alm-device-title"><?php echo esc_html( $device->title ); ?></h1>
	</header>

	<!-- II section: FOTO (sx) + Taxonomies box (dx) -->
	<?php
		$alm_state_slug  = '';
		$alm_state_label = '';

		$alm_state_terms = get_the_terms( $device_id, 'alm_state' );
		if ( ! is_wp_error( $alm_state_terms ) && ! empty( $alm_state_terms ) ) {
			$alm_state_slug  = (string) $alm_state_terms[0]->slug;
			$alm_state_label = (string) $alm_state_terms[0]->name;
		}
	?>
	<section class="alm-device-view__hero" aria-label="<?php esc_attr_e( 'Device overview', 'asset-lending-manager' ); ?>">
		<div class="alm-device-view__media">
			<?php if ( $alm_detail_image_html ) : ?>
				<div class="alm-device-thumbnail alm-device-thumbnail--large">
					<?php echo $alm_detail_image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

	<?php
		$alm_state_class_map = ALM_Device_Manager::get_state_classes();
		$alm_state_css_class = '';
		if ( $alm_state_slug && isset( $alm_state_class_map[ $alm_state_slug ] ) ) {
			$alm_state_css_class = $alm_state_class_map[ $alm_state_slug ];
		}
	?>
	<aside class="alm-device-view__taxbox" aria-label="<?php esc_attr_e( 'Device taxonomies', 'asset-lending-manager' ); ?>">
		<div class="alm-device-taxonomies alm-device-taxonomies--boxed">
			<?php
			$alm_render_tax_row( 'alm_structure', isset( $device->alm_structure ) ? $device->alm_structure : array() );
			$alm_render_tax_row( 'alm_type', isset( $device->alm_type ) ? $device->alm_type : array() );
			// State as badge + text.
			if ( $alm_state_label ) :
				?>
				<div class="alm-device-tax-row alm-tax-alm_state">
					<span class="alm-tax-label">
						<?php echo esc_html( get_taxonomy( 'alm_state' )->labels->singular_name ); ?>
					</span>
					<span class="alm-tax-value">
						<span class="alm-availability <?php echo esc_attr( $alm_state_css_class ); ?>">
							<?php echo esc_html( $alm_state_label ); ?>
						</span>
					</span>
				</div>
			<?php endif; ?>
		</div	>
	</aside>

	</section>

	<!-- III section: Device description -->
	<section class="alm-device-view__content" aria-label="<?php esc_attr_e( 'Full description', 'asset-lending-manager' ); ?>">
		<div class="alm-device-content">
			<?php echo $device->content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</section>

	<!-- IV section: Device optional fields -->
	<section class="alm-device-view__acf" aria-label="<?php esc_attr_e( 'Additional fields', 'asset-lending-manager' ); ?>">
		<details class="alm-collapsible alm-collapsible--acf">
			<summary class="alm-collapsible__summary">
				<span class="alm-collapsible__title">
					<?php esc_html_e( 'Read details', 'asset-lending-manager' ); ?>
				</span>
				<span class="alm-collapsible__hint" aria-hidden="true">
					<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>
			<div class="alm-collapsible__body">
				<?php if ( ! empty( $alm_device_fields ) ) : ?>
					<dl class="alm-device-acf-list">
						<?php foreach ( $alm_device_fields as $row ) : ?>
							<div class="alm-device-acf-row alm-acf-<?php echo esc_attr( $row['name'] ); ?>">
								<dt class="alm-device-acf-label"><?php echo esc_html( $row['label'] ); ?></dt>
								<dd class="alm-device-acf-value">
									<?php
									// Render by type.
									if ( 'file' === $row['type'] && is_array( $row['value'] ) && ! empty( $row['value']['url'] ) ) {
										$file_url  = (string) $row['value']['url'];
										$file_name = ! empty( $row['value']['filename'] ) ? (string) $row['value']['filename'] : $file_url;
										?>
										<a href="<?php echo esc_url( $file_url ); ?>" class="alm-link" target="_blank" rel="noopener">
											<?php echo esc_html( $file_name ); ?>
										</a>
										<?php
									} elseif ( 'post_object' === $row['type'] && is_array( $row['value'] ) ) {
										// Multiple components (objects).
										echo '<ul class="alm-device-components">';
										foreach ( $row['value'] as $component_post ) {
											if ( is_object( $component_post ) && ! empty( $component_post->ID ) ) {
												$title = get_the_title( $component_post->ID );
												$link  = get_permalink( $component_post->ID );
												echo '<li><a class="alm-link" href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></li>';
											}
										}
										echo '</ul>';
									} elseif ( 'number' === $row['type'] ) {
										// Cost / numeric fields.
										echo esc_html( (string) $row['value'] );
									} else {
										// Default (text, date, textarea, etc).
										if ( is_array( $row['value'] ) ) {
											echo esc_html( implode( ', ', array_map( 'strval', $row['value'] ) ) );
										} else {
											echo esc_html( (string) $row['value'] );
										}
									}
									?>
								</dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php else : ?>
					<p class="alm-muted"><?php esc_html_e( 'No additional details available.', 'asset-lending-manager' ); ?></p>
				<?php endif; ?>
			</div>
		</details>
	</section>

	<!-- V section: Book loan request -->
	<section class="alm-device-view__loan-request" aria-label="<?php esc_attr_e( 'Loan request', 'asset-lending-manager' ); ?>">
		<h2 class="alm-device-section-title"><?php esc_html_e( 'Ask for a loan', 'asset-lending-manager' ); ?></h2>

		<?php if ( is_user_logged_in() && current_user_can( 'alm_member' ) ) : ?>
			<button type="button" class="alm-button" disabled="disabled">
				<?php esc_html_e( 'Ask for a loan', 'asset-lending-manager' ); ?>
			</button>
			<p class="alm-muted"><?php esc_html_e( 'Features under development.', 'asset-lending-manager' ); ?></p>
		<?php else : ?>
			<p class="alm-muted">
				<?php esc_html_e( 'To request a loan, you must log in as a member.', 'asset-lending-manager' ); ?>
			</p>
		<?php endif; ?>
	</section>

	<!-- VI section: Loan requests -->
	<section class="alm-device-view__loan-requests" aria-label="<?php esc_attr_e( 'Loan requests', 'asset-lending-manager' ); ?>">
		<h2 class="alm-device-section-title"><?php esc_html_e( 'Loan requests', 'asset-lending-manager' ); ?></h2>
		<p class="alm-muted"><?php esc_html_e( 'No requests to show (section under development).', 'asset-lending-manager' ); ?></p>
	</section>

	<!-- VII section: Loan history -->
	<section class="alm-device-view__loan-history" aria-label="<?php esc_attr_e( 'Loan history', 'asset-lending-manager' ); ?>">
		<details class="alm-collapsible alm-collapsible--history">
			<summary class="alm-collapsible__summary">
				<span class="alm-collapsible__title"><?php esc_html_e( 'Loan history', 'asset-lending-manager' ); ?></span>
				<span class="alm-collapsible__hint" aria-hidden="true"><?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?></span>
			</summary>

			<div class="alm-collapsible__body">
				<p class="alm-muted">
					<?php esc_html_e( 'History not available (section under development).', 'asset-lending-manager' ); ?>
				</p>
			</div>
		</details>
	</section>

</article>
