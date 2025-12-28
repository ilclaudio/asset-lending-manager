<?php
/**
 * Single template for ALM Device
 *
 * This template uses the [alm_device_view] shortcode.
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

	<?php echo do_shortcode( '[alm_device_view]' ); ?>

</div>

<?php
if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
	get_footer();
}
?>
