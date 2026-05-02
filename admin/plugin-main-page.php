<?php
/**
 * ALMGR Main Page template — About section.
 *
 * Displays plugin identity, metadata, quick links and shortcode reference.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

$almgr_data     = get_file_data(
	ALMGR_PLUGIN_DIR . 'asset-lending-manager.php',
	array(
		'Name'        => 'Plugin Name',
		'PluginURI'   => 'Plugin URI',
		'Version'     => 'Version',
		'Description' => 'Description',
		'AuthorURI'   => 'Author URI',
		'RequiresWP'  => 'Requires at least',
		'License'     => 'License',
	)
);
$almgr_logo_url = ALMGR_PLUGIN_URL . 'assets/img/ALM-logo-128x128.png';
?>
<div class="wrap">

	<h1 class="screen-reader-text"><?php echo esc_html( $almgr_data['Name'] ); ?></h1>

	<div class="card almgr-about">

		<!-- Header: logo + name + description -->
		<div class="almgr-about__header">
			<img
				src="<?php echo esc_url( $almgr_logo_url ); ?>"
				alt="<?php esc_attr_e( 'Asset Lending Manager logo', 'asset-lending-manager' ); ?>"
				class="almgr-about__logo"
			/>
			<div class="almgr-about__intro">
				<p class="almgr-about__name"><?php echo esc_html( $almgr_data['Name'] ); ?></p>
				<p><?php echo esc_html( $almgr_data['Description'] ); ?></p>
			</div>
		</div>

		<!-- Metadata row -->
		<p class="almgr-about__meta">
			<span>
				<?php
				/* translators: %s: plugin version string. */
				printf( esc_html__( 'Version %s', 'asset-lending-manager' ), esc_html( $almgr_data['Version'] ) );
				?>
			</span>
			<span class="almgr-about__sep" aria-hidden="true">&middot;</span>
			<span>
				<?php
				/* translators: %s: minimum supported WordPress version. */
				printf( esc_html__( 'Requires WordPress %s', 'asset-lending-manager' ), esc_html( $almgr_data['RequiresWP'] ) );
				?>
			</span>
			<span class="almgr-about__sep" aria-hidden="true">&middot;</span>
			<span><?php echo esc_html( $almgr_data['License'] ); ?></span>
		</p>

		<!-- Quick links -->
		<div class="almgr-about__links">
			<a href="<?php echo esc_url( $almgr_data['PluginURI'] ); ?>" class="button" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'GitHub repository', 'asset-lending-manager' ); ?> <span aria-hidden="true">&#8599;</span>
			</a>
			<a href="<?php echo esc_url( $almgr_data['AuthorURI'] ); ?>" class="button" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Author site', 'asset-lending-manager' ); ?> <span aria-hidden="true">&#8599;</span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=almgr-settings' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Settings', 'asset-lending-manager' ); ?> <span aria-hidden="true">&rarr;</span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=almgr-tools' ) ); ?>" class="button">
				<?php esc_html_e( 'Tools', 'asset-lending-manager' ); ?> <span aria-hidden="true">&rarr;</span>
			</a>
		</div>

		<hr class="almgr-about__divider" />

		<!-- Shortcode reference -->
		<h2><?php esc_html_e( 'Quick start', 'asset-lending-manager' ); ?></h2>

		<h3><?php esc_html_e( 'Classic themes', 'asset-lending-manager' ); ?></h3>
		<p><?php esc_html_e( 'Asset pages are served automatically via the plugin\'s built-in templates — no shortcodes required:', 'asset-lending-manager' ); ?></p>
		<ul>
			<li><code>/asset/</code> &mdash; <?php esc_html_e( 'asset catalog with search filters', 'asset-lending-manager' ); ?></li>
			<li><code>/asset/asset-name/</code> &mdash; <?php esc_html_e( 'single asset detail page', 'asset-lending-manager' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'If /asset/ returns a 404, go to Settings > Permalinks and click Save Changes once.', 'asset-lending-manager' ); ?></p>

		<h3><?php esc_html_e( 'Block themes', 'asset-lending-manager' ); ?></h3>
		<p><?php esc_html_e( 'Block themes do not support automatic PHP template overrides. The /asset/ and /asset/slug/ URLs exist but render the theme\'s default layout without any plugin content.', 'asset-lending-manager' ); ?></p>
		<p><?php esc_html_e( 'To use the plugin with a block theme:', 'asset-lending-manager' ); ?></p>
		<ol>
			<li><?php esc_html_e( 'Create a page and add the [almgr_asset_list] shortcode. This becomes your asset catalog.', 'asset-lending-manager' ); ?></li>
			<li><?php esc_html_e( 'Create a second page and add the [almgr_asset_view] shortcode. This becomes the asset detail view.', 'asset-lending-manager' ); ?></li>
			<li>
				<?php
				printf(
					/* translators: %s: link to Frontend settings tab */
					esc_html__( 'In %s, set "Asset archive page" to the first page and "Asset detail page" to the second. This ensures all asset links in the catalog point to the correct detail page.', 'asset-lending-manager' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=almgr-settings&tab=frontend' ) ) . '">' . esc_html__( 'Settings &rarr; Frontend', 'asset-lending-manager' ) . '</a>'
				);
				?>
			</li>
		</ol>

		<h3><?php esc_html_e( 'Shortcode reference', 'asset-lending-manager' ); ?></h3>
		<table class="widefat striped almgr-about__shortcodes">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'asset-lending-manager' ); ?></th>
					<th><?php esc_html_e( 'Description', 'asset-lending-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>[almgr_asset_list]</code></td>
					<td><?php esc_html_e( 'Embeds the full asset catalog with search filters into any page or post.', 'asset-lending-manager' ); ?></td>
				</tr>
				<tr>
					<td><code>[almgr_asset_view]</code></td>
					<td><?php esc_html_e( 'Embeds the detail view for a single asset. On classic themes this is only needed for custom layouts; on block themes it is required.', 'asset-lending-manager' ); ?></td>
				</tr>
			</tbody>
		</table>

	</div><!-- .almgr-about -->

</div><!-- .wrap -->
