<?php
/**
 * Archive template for ALMGR Assets
 *
 * This template uses the [almgr_asset_list] shortcode.
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

<div class="almgr-container almgr-asset-archive">

	<header class="almgr-archive-header">
		<h1 class="almgr-archive-title">
			<?php post_type_archive_title(); ?>
		</h1>
	</header>

	<?php echo do_shortcode( '[almgr_asset_list]' ); ?>

</div>

<?php
if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
	get_footer();
}
?>
