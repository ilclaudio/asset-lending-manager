<?php
/**
 * Archive template for ALM Devices
 *
 * This template uses the [alm_device_list] shortcode.
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
		<h1 class="alm-archive-title">
			<?php post_type_archive_title(); ?>
		</h1>
	</header>

	<?php echo do_shortcode( '[alm_device_list]' ); ?>

</div>

<?php
if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
	get_footer();
}
?>
