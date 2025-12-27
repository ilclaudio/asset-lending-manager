<?php
/**
 * Archive template for ALM Devices using view model
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

<div class="alm-container alm-device-archive">

	<header class="alm-archive-header">
		<h1 class="alm-archive-title"><?php post_type_archive_title(); ?></h1>
	</header>

	<?php if ( have_posts() ) : ?>

		<div class="alm-device-list">

			<?php
			while ( have_posts() ) {
				the_post();
				?>

				<?php
				$alm_device = ALM_Device_Manager::get_device_wrapper( get_the_ID() );
				if ( $alm_device ) {
					?>

					<article class="alm-device-card">

						<a href="<?php echo esc_url( $alm_device->permalink ); ?>" class="alm-device-link">

							<?php if ( $alm_device->thumbnail ) : ?>
								<div class="alm-device-thumbnail"><?php echo $alm_device->thumbnail; ?></div>
							<?php endif; ?>

							<h2 class="alm-device-title"><?php echo esc_html( $alm_device->title ); ?></h2>

						</a>

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

					</article>

					<?php
				}
			}
			?>

		</div>

	<?php else : ?>
		<p class="alm-no-results">
			<?php esc_html_e( 'No devices found.', 'asset-lending-manager' ); ?>
		</p>
	<?php endif; ?>

</div>

<?php
if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
	get_footer();
}
?>
