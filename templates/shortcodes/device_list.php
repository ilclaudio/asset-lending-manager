<?php
/**
 * Template for device list shortcode
 *
 * Available variables:
 * - $devices: Array of device wrapper objects from ALM_Device_Manager::get_device_wrapper()
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

$alm_current_search = '';
if ( isset( $_GET['s'] ) ) {
	$alm_current_search = sanitize_text_field( wp_unslash( $_GET['s'] ) );
}
?>

<div id="alm_device_search_form">
	<form method="get" class="alm-device-search-form">
		<input
			type="search"
			name="s"
			value="<?php echo esc_attr( $alm_current_search ); ?>"
			placeholder="<?php esc_attr_e( 'Search devices...', 'asset-lending-manager' ); ?>"
		/>
		<button type="submit">
			<?php esc_html_e( 'Search', 'asset-lending-manager' ); ?>
		</button>
	</form>
	<div id="alm_device_autocomplete_dropdown" class="alm-autocomplete-dropdown"></div>
</div>

<div id="alm_device_search_results">

	<?php if ( $devices_count > 0 ) : ?>
		<p class="alm-device-search-count">
			<?php
			printf(
				esc_html__( 'Devices found: %d', 'asset-lending-manager' ),
				(int) $devices_count
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $devices ) ) : ?>

		<div class="alm-device-list">
			<?php foreach ( $devices as $alm_device ) : ?>

				<article class="alm-device-card">
					<a href="<?php echo esc_url( $alm_device->permalink ); ?>" class="alm-device-link">
						<?php if ( $alm_device->thumbnail ) : ?>
							<div class="alm-device-thumbnail">
								<?php echo $alm_device->thumbnail; ?>
							</div>
						<?php endif; ?>
						<div class="alm-device-content-wrapper">
							<h2 class="alm-device-title"><?php echo esc_html( $alm_device->title ); ?></h2>
							<div class="alm-device-taxonomies">
								<?php foreach ( array( 'alm_structure', 'alm_type', 'alm_state' ) as $taxonomy ) : ?>
									<?php if ( ! empty( $alm_device->{$taxonomy} ) ) : ?>
										<div class="alm-device-taxonomy alm-tax-<?php echo esc_attr( $taxonomy ); ?>">
											<span class="alm-tax-label"><?php echo esc_html( get_taxonomy( $taxonomy )->labels->singular_name ); ?>:</span>
											<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $alm_device->{$taxonomy} ) ); ?></span>
										</div>
									<?php endif; ?>
								<?php endforeach; ?>
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
				esc_html_e( 'No devices found.', 'asset-lending-manager' );
			}
			?>
		</p>

	<?php endif; ?>
</div>
