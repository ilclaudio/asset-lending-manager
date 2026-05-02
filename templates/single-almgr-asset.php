<?php
/**
 * Single template for ALMGR Asset (classic themes only).
 *
 * Block themes do not use this template: they rely on the [almgr_asset_view]
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

<div class="almgr-container almgr-asset-single">

	<?php echo wp_kses( do_shortcode( '[almgr_asset_view]' ), $almgr_allowed_html ); ?>

</div>

<?php get_footer(); ?>
