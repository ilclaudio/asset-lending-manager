<?php
/**
 * Single template for ALMGR Asset
 *
 * This template uses the [almgr_asset_view] shortcode.
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

<div class="almgr-container almgr-asset-single">

	<?php echo do_shortcode( '[almgr_asset_view]' ); ?>

</div>

<?php
if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
	get_footer();
}
?>
