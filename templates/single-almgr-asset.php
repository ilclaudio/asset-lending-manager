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

$almgr_is_block_theme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();

if ( $almgr_is_block_theme ) {
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php
	if ( function_exists( 'do_blocks' ) ) {
		echo wp_kses_post( do_blocks( '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->' ) );
	}
	?>
	<div class="almgr-container almgr-asset-single">

		<?php echo do_shortcode( '[almgr_asset_view]' ); ?>

	</div>
	<?php
	if ( function_exists( 'do_blocks' ) ) {
		echo wp_kses_post( do_blocks( '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->' ) );
	}
	?>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
	return;
}

get_header();
?>

<div class="almgr-container almgr-asset-single">

	<?php echo do_shortcode( '[almgr_asset_view]' ); ?>

</div>

<?php get_footer(); ?>
