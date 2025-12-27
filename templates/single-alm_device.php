<?php
/**
 * Single template for ALM Device using view model
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
	get_header();
}
?>

<div class="alm-container alm-device-single">

	<?php while ( have_posts() ) : the_post(); ?>

		<?php $alm_device= ALM_Device_Manager::get_device_wrapper( get_the_ID() ); ?>
		<?php if ( ! $alm_device ) continue; ?>

		<article class="alm-device-detail">

			<header class="alm-device-header">

				<h1 class="alm-device-title"><?php echo esc_html( $alm_device->title ); ?></h1>

				<?php if ( $alm_device->thumbnail ) : ?>
					<div class="alm-device-thumbnail">
						<?php echo $alm_device->thumbnail; ?>
					</div>
				<?php endif; ?>

			</header>

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

			<div class="alm-device-content">
				<?php echo $alm_device->content; ?>
			</div>

		</article>

	<?php endwhile; ?>

</div>

<?php
if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
	get_footer();
}
?>
