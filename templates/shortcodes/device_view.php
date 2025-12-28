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
?>

<article class="alm-device-detail">

	<header class="alm-device-header">

		<h1 class="alm-device-title"><?php echo esc_html( $device->title ); ?></h1>

		<?php if ( $device->thumbnail ) : ?>
			<div class="alm-device-thumbnail">
				<?php echo $device->thumbnail; ?>
			</div>
		<?php endif; ?>

	</header>

	<div class="alm-device-taxonomies">
		<?php foreach ( array( 'alm_structure', 'alm_type', 'alm_state' ) as $taxonomy ) : ?>
			<?php if ( ! empty( $device->{$taxonomy} ) ) : ?>
				<div class="alm-device-taxonomy alm-tax-<?php echo esc_attr( $taxonomy ); ?>">
					<span class="alm-tax-label"><?php echo esc_html( get_taxonomy( $taxonomy )->labels->singular_name ); ?>:</span>
					<span class="alm-tax-value"><?php echo esc_html( implode( ', ', $device->{$taxonomy} ) ); ?></span>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>

	<div class="alm-device-content">
		<?php echo $device->content; ?>
	</div>

</article>
