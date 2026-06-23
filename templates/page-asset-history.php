<?php
/**
 * Full-width page template for the Asset History shortcode page (classic themes only).
 *
 * Loaded automatically by ALMGR_Frontend_Manager when the current page matches
 * the page configured in Settings > Frontend > "Asset history page".
 *
 * Block themes do not use this template: they rely on the [almgr_asset_history]
 * shortcode placed on a dedicated page rendered through the Site Editor instead.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

$almgr_allowed_html = almgr_get_allowed_html();

get_header();
?>

<div class="almgr-container almgr-asset-history-container">

	<?php echo wp_kses( do_shortcode( '[almgr_asset_history]' ), $almgr_allowed_html ); ?>

</div>

<?php get_footer(); ?>
