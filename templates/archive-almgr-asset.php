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
	<div class="almgr-container almgr-asset-archive">

		<header class="almgr-archive-header">
			<h1 class="almgr-archive-title">
				<?php post_type_archive_title(); ?>
			</h1>
		</header>

		<?php echo do_shortcode( '[almgr_asset_list]' ); ?>

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

<div class="almgr-container almgr-asset-archive">

	<header class="almgr-archive-header">
		<h1 class="almgr-archive-title">
			<?php post_type_archive_title(); ?>
		</h1>
	</header>

	<?php echo do_shortcode( '[almgr_asset_list]' ); ?>

</div>

<?php get_footer(); ?>
