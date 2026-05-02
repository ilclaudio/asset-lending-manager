<?php
/**
 * Archive template for ALMGR Assets (classic themes only).
 *
 * Block themes do not use this template: they rely on the [almgr_asset_list]
 * shortcode placed on a dedicated page instead.
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$almgr_allowed_html = almgr_get_allowed_html();

get_header();
?>

<div class="almgr-container almgr-asset-archive">

	<header class="almgr-archive-header">
		<h1 class="almgr-archive-title">
			<?php post_type_archive_title(); ?>
		</h1>
	</header>

	<?php echo wp_kses( do_shortcode( '[almgr_asset_list]' ), $almgr_allowed_html ); ?>

</div>

<?php get_footer(); ?>
