<?php
defined( 'ABSPATH' ) || exit;
$alm_status = isset( $_GET['alm_status'] ) ? sanitize_key( $_GET['alm_status'] ) : '';
?>

<div class="wrap">
	<h1><?php esc_html_e( 'ALM Tools', 'asset-lending-manager' ); ?></h1>

	<?php if ( $alm_status ) : ?>
		<?php if ( 'success' === $alm_status ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Default terms loaded successfully.', 'asset-lending-manager' ); ?></p>
			</div>
		<?php else : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'An error occurred while loading default terms.', 'asset-lending-manager' ); ?></p>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'alm_reload_terms_action', 'alm_reload_terms_nonce' ); ?>
		<input type="hidden" name="action" value="alm_reload_default_terms">
		<?php submit_button( __( 'Reload Default Taxonomies Terms', 'asset-lending-manager' ) ); ?>
	</form>
</div>
