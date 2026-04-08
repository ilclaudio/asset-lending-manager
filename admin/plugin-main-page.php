<?php
/**
 * ALM Main Page template — About section.
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

	<div class="card alm-about">

		<!-- Header: logo + name + description -->
		<div class="alm-about__header">
			<img
				src="<?php echo esc_url( $almgr_logo_url ); ?>"
				alt="<?php esc_attr_e( 'Asset Lending Manager logo', 'asset-lending-manager' ); ?>"
				class="alm-about__logo"
			/>
			<div class="alm-about__intro">
				<p class="alm-about__name"><?php echo esc_html( $almgr_data['Name'] ); ?></p>
				<p><?php echo esc_html( $almgr_data['Description'] ); ?></p>
			</div>
		</div>

		<!-- Metadata row -->
		<p class="alm-about__meta">
			<span>
				<?php
				/* translators: %s: plugin version string. */
				printf( esc_html__( 'Version %s', 'asset-lending-manager' ), esc_html( $almgr_data['Version'] ) );
				?>
			</span>
			<span class="alm-about__sep" aria-hidden="true">&middot;</span>
			<span>
				<?php
				/* translators: %s: minimum supported WordPress version. */
				printf( esc_html__( 'Requires WordPress %s', 'asset-lending-manager' ), esc_html( $almgr_data['RequiresWP'] ) );
				?>
			</span>
			<span class="alm-about__sep" aria-hidden="true">&middot;</span>
			<span><?php echo esc_html( $almgr_data['License'] ); ?></span>
		</p>

		<!-- Quick links -->
		<div class="alm-about__links">
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

		<hr class="alm-about__divider" />

		<!-- Shortcode reference -->
		<h2><?php esc_html_e( 'Quick start', 'asset-lending-manager' ); ?></h2>
		<p>
			<?php esc_html_e( 'The plugin works out of the box on both classic and block themes — no shortcodes required for normal use.', 'asset-lending-manager' ); ?>
			<?php esc_html_e( 'Asset pages are served automatically via the plugin\'s built-in templates:', 'asset-lending-manager' ); ?>
		</p>
		<ul>
			<li><code>/asset/</code> &mdash; <?php esc_html_e( 'asset catalog with search filters', 'asset-lending-manager' ); ?></li>
			<li><code>/asset/asset-name/</code> &mdash; <?php esc_html_e( 'single asset detail page', 'asset-lending-manager' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'Use the shortcodes below only if you need to embed a view inside an existing WordPress page:', 'asset-lending-manager' ); ?></p>

		<table class="widefat striped alm-about__shortcodes">
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
					<td><?php esc_html_e( 'Embeds the detail view for a single asset. Not needed on the standard asset permalink — use only for custom layouts.', 'asset-lending-manager' ); ?></td>
				</tr>
			</tbody>
		</table>

	</div><!-- .alm-about -->

</div><!-- .wrap -->
