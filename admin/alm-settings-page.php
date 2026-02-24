<?php
/**
 * ALM Settings Page template.
 *
 * Renders the plugin settings page in the WordPress admin.
 * Currently implements two tabs: Email & Notifications, and Email Templates.
 *
 * Access: any user with ALM_EDIT_ASSET capability can view the page.
 * Fields marked [A] are disabled for non-administrators (manage_options).
 * Fields marked [A/O] are editable by both administrators and operators.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET params for tab navigation and notice display.
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'email';
$saved      = isset( $_GET['saved'] ) && '1' === $_GET['saved'];
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$settings = new ALM_Settings_Manager();
$is_admin = current_user_can( 'manage_options' );

$tabs = array(
	'email'     => __( 'Email & Notifications', 'asset-lending-manager' ),
	'templates' => __( 'Email Templates', 'asset-lending-manager' ),
);

// Validate active tab.
if ( ! array_key_exists( $active_tab, $tabs ) ) {
	$active_tab = 'email';
}

// Email type labels for the templates tab.
$email_type_labels = array(
	'request_to_requester'        => __( 'Loan request — to requester', 'asset-lending-manager' ),
	'request_to_owner'            => __( 'Loan request — to operator', 'asset-lending-manager' ),
	'approved'                    => __( 'Request approved', 'asset-lending-manager' ),
	'rejected'                    => __( 'Request rejected', 'asset-lending-manager' ),
	'canceled'                    => __( 'Request canceled', 'asset-lending-manager' ),
	'direct_assign'               => __( 'Direct assignment — to assignee', 'asset-lending-manager' ),
	'direct_assign_to_prev_owner' => __( 'Direct assignment — to previous owner', 'asset-lending-manager' ),
);

// Available placeholders per template type.
$placeholders = array(
	'request_to_requester'        => '{ASSET_TITLE}, {ASSET_URL}, {REQUESTER_NAME}',
	'request_to_owner'            => '{ASSET_TITLE}, {ASSET_URL}, {REQUESTER_NAME}, {REQUEST_MESSAGE}',
	'approved'                    => '{ASSET_TITLE}, {ASSET_URL}, {REQUESTER_NAME}',
	'rejected'                    => '{ASSET_TITLE}, {ASSET_URL}, {REQUESTER_NAME}, {REJECTION_MESSAGE}',
	'canceled'                    => '{ASSET_TITLE}, {ASSET_URL}, {REQUESTER_NAME}',
	'direct_assign'               => '{ASSET_TITLE}, {ASSET_URL}, {ASSIGNEE_NAME}, {ACTOR_NAME}, {REASON}',
	'direct_assign_to_prev_owner' => '{ASSET_TITLE}, {ASSET_URL}, {PREV_OWNER_NAME}, {ASSIGNEE_NAME}, {ACTOR_NAME}, {REASON}',
);
?>

<div class="wrap">
	<h1><?php esc_html_e( 'ALM Settings', 'asset-lending-manager' ); ?></h1>

	<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'asset-lending-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Settings sections', 'asset-lending-manager' ); ?>">
		<?php foreach ( $tabs as $tab_slug => $tab_label ) : ?>
			<a
				href="<?php echo esc_url( admin_url( 'admin.php?page=alm-settings&tab=' . $tab_slug ) ); ?>"
				class="nav-tab<?php echo $active_tab === $tab_slug ? ' nav-tab-active' : ''; ?>"
			>
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alm-settings-form">
		<?php wp_nonce_field( 'alm_save_settings', 'alm_settings_nonce' ); ?>
		<input type="hidden" name="action" value="alm_save_settings">
		<input type="hidden" name="alm_active_tab" value="<?php echo esc_attr( $active_tab ); ?>">

		<?php if ( 'email' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Sender Configuration', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="alm_email_from_name">
							<?php esc_html_e( 'From name', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<input
							type="text"
							id="alm_email_from_name"
							name="alm_email_from_name"
							value="<?php echo esc_attr( $settings->get( 'email.from_name' ) ); ?>"
							class="regular-text"
							<?php disabled( ! $is_admin ); ?>
						>
						<p class="description">
							<?php esc_html_e( 'Sender name shown in outgoing emails. Leave empty to use the site name.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="alm_email_from_address">
							<?php esc_html_e( 'From address', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<input
							type="email"
							id="alm_email_from_address"
							name="alm_email_from_address"
							value="<?php echo esc_attr( $settings->get( 'email.from_address' ) ); ?>"
							class="regular-text"
							<?php disabled( ! $is_admin ); ?>
						>
						<p class="description">
							<?php esc_html_e( 'Sender email address. Leave empty to use the site admin email.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="alm_email_system_email">
							<?php esc_html_e( 'System email (BCC)', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<input
							type="email"
							id="alm_email_system_email"
							name="alm_email_system_email"
							value="<?php echo esc_attr( $settings->get( 'email.system_email' ) ); ?>"
							class="regular-text"
							<?php disabled( ! $is_admin ); ?>
						>
						<p class="description">
							<?php esc_html_e( 'Operator address that receives a copy of every loan request submission. Leave empty to disable.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Notification Toggles', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Enable notifications', 'asset-lending-manager' ); ?>
						<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_notifications_enabled"
								value="1"
								<?php checked( $settings->get( 'notifications.enabled' ) ); ?>
								<?php disabled( ! $is_admin ); ?>
							>
							<?php esc_html_e( 'Send email notifications', 'asset-lending-manager' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Master switch. When disabled, no emails are sent regardless of other settings.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Loan request submitted', 'asset-lending-manager' ); ?></th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_notifications_loan_request"
								value="1"
								<?php checked( $settings->get( 'notifications.loan_request' ) ); ?>
							>
							<?php esc_html_e( 'Notify requester and operator when a loan request is submitted', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Loan request decision', 'asset-lending-manager' ); ?></th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_notifications_loan_decision"
								value="1"
								<?php checked( $settings->get( 'notifications.loan_decision' ) ); ?>
							>
							<?php esc_html_e( 'Notify requester when a loan request is approved or rejected', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Loan confirmation', 'asset-lending-manager' ); ?></th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_notifications_loan_confirmation"
								value="1"
								<?php checked( $settings->get( 'notifications.loan_confirmation' ) ); ?>
							>
							<?php esc_html_e( 'Send confirmation email when a loan is confirmed (if/when the feature is introduced)', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>

		<?php elseif ( 'templates' === $active_tab ) : ?>

			<?php if ( ! $is_admin ) : ?>
				<div class="notice notice-warning inline" style="margin-top:16px;">
					<p><?php esc_html_e( 'Email templates can only be modified by administrators.', 'asset-lending-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<p class="description" style="margin-top:12px;">
				<?php esc_html_e( 'Customize the subject and body of each notification email. Placeholders in curly braces are replaced at send time. Leave a field empty to use the translated default.', 'asset-lending-manager' ); ?>
			</p>

			<?php foreach ( $email_type_labels as $type => $type_label ) : ?>
				<details class="alm-settings-template-section" open>
					<summary class="alm-settings-template-summary">
						<strong><?php echo esc_html( $type_label ); ?></strong>
					</summary>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="alm_tpl_subject_<?php echo esc_attr( $type ); ?>">
									<?php esc_html_e( 'Subject', 'asset-lending-manager' ); ?>
								</label>
							</th>
							<td>
								<input
									type="text"
									id="alm_tpl_subject_<?php echo esc_attr( $type ); ?>"
									name="alm_tpl_subject_<?php echo esc_attr( $type ); ?>"
									value="<?php echo esc_attr( $settings->get( 'template.subject.' . $type ) ); ?>"
									class="large-text"
									<?php disabled( ! $is_admin ); ?>
								>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="alm_tpl_body_<?php echo esc_attr( $type ); ?>">
									<?php esc_html_e( 'Body', 'asset-lending-manager' ); ?>
								</label>
							</th>
							<td>
								<textarea
									id="alm_tpl_body_<?php echo esc_attr( $type ); ?>"
									name="alm_tpl_body_<?php echo esc_attr( $type ); ?>"
									rows="6"
									class="large-text"
									<?php disabled( ! $is_admin ); ?>
								><?php echo esc_textarea( $settings->get( 'template.body.' . $type ) ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Available placeholders:', 'asset-lending-manager' ); ?>
									<code><?php echo esc_html( $placeholders[ $type ] ); ?></code>
								</p>
							</td>
						</tr>
					</table>
				</details>
			<?php endforeach; ?>

			<?php if ( $is_admin ) : ?>
				<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>
			<?php endif; ?>

		<?php endif; ?>
	</form>
</div>
