<?php
/**
 * ALM Settings Page template.
 *
 * Renders the plugin settings page in the WordPress admin.
 * Implements 10 tabs: Notifications, Email Templates, Loan Rules,
 * Direct Assignment, Workflow, Frontend, Research, Logging,
 * Advanced Settings, Maintenance.
 *
 * Access: any user with ALMGR_EDIT_ASSET capability can view the page.
 * Fields marked [A] are disabled for non-administrators (manage_options).
 * Fields marked [A/O] are editable by both administrators and operators.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET params for tab navigation and notice display.
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'email';
$saved      = isset( $_GET['saved'] ) && '1' === sanitize_key( wp_unslash( $_GET['saved'] ) );
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$settings = new ALMGR_Settings_Manager();
$is_admin = current_user_can( 'manage_options' );

$alm_tabs = array(
	'email'         => __( 'Notifications', 'asset-lending-manager' ),
	'templates'     => __( 'Email Templates', 'asset-lending-manager' ),
	'loans'         => __( 'Loan Rules', 'asset-lending-manager' ),
	'direct_assign' => __( 'Direct Assignment', 'asset-lending-manager' ),
	'workflow'      => __( 'Workflow', 'asset-lending-manager' ),
	'frontend'      => __( 'Frontend', 'asset-lending-manager' ),
	'autocomplete'  => __( 'Search Settings', 'asset-lending-manager' ),
	'logging'       => __( 'Logging', 'asset-lending-manager' ),
	'asset'         => __( 'Advanced Settings', 'asset-lending-manager' ),
	'rest_api'      => __( 'REST API', 'asset-lending-manager' ),
);

// Validate active tab.
if ( ! array_key_exists( $active_tab, $alm_tabs ) ) {
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
		<?php foreach ( $alm_tabs as $tab_slug => $tab_label ) : ?>
			<a
				href="<?php echo esc_url( admin_url( 'admin.php?page=alm-settings&tab=' . $tab_slug ) ); ?>"
				class="nav-tab<?php echo esc_attr( $active_tab === $tab_slug ? ' nav-tab-active' : '' ); ?>"
			>
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alm-settings-form">
		<?php wp_nonce_field( 'almgr_save_settings', 'almgr_settings_nonce' ); ?>
		<input type="hidden" name="action" value="almgr_save_settings">
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

			<?php foreach ( $email_type_labels as $alm_type => $alm_type_label ) : ?>
				<details class="alm-settings-template-section" open>
					<summary class="alm-settings-template-summary">
						<strong><?php echo esc_html( $alm_type_label ); ?></strong>
					</summary>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="alm_tpl_subject_<?php echo esc_attr( $alm_type ); ?>">
									<?php esc_html_e( 'Subject', 'asset-lending-manager' ); ?>
								</label>
							</th>
							<td>
								<input
									type="text"
									id="alm_tpl_subject_<?php echo esc_attr( $alm_type ); ?>"
									name="alm_tpl_subject_<?php echo esc_attr( $alm_type ); ?>"
									value="<?php echo esc_attr( $settings->get( 'template.subject.' . $alm_type ) ); ?>"
									class="large-text"
									<?php disabled( ! $is_admin ); ?>
								>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="alm_tpl_body_<?php echo esc_attr( $alm_type ); ?>">
									<?php esc_html_e( 'Body', 'asset-lending-manager' ); ?>
								</label>
							</th>
							<td>
								<textarea
									id="alm_tpl_body_<?php echo esc_attr( $alm_type ); ?>"
									name="alm_tpl_body_<?php echo esc_attr( $alm_type ); ?>"
									rows="6"
									class="large-text"
									<?php disabled( ! $is_admin ); ?>
								><?php echo esc_textarea( $settings->get( 'template.body.' . $alm_type ) ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Available placeholders:', 'asset-lending-manager' ); ?>
									<code><?php echo esc_html( $placeholders[ $alm_type ] ); ?></code>
								</p>
							</td>
						</tr>
					</table>
				</details>
			<?php endforeach; ?>

			<?php if ( $is_admin ) : ?>
				<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>
			<?php endif; ?>

		<?php elseif ( 'loans' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Loan Requests', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Enable loan requests', 'asset-lending-manager' ); ?>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_loans_loan_requests_enabled"
								value="1"
								<?php checked( $settings->get( 'loans.loan_requests_enabled' ) ); ?>
							>
							<?php esc_html_e( 'Members can submit loan requests. When disabled, assets can only change ownership via direct assignment by an operator.', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Loan Limits', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="alm_loans_max_active_per_user">
							<?php esc_html_e( 'Max active loans per user', 'asset-lending-manager' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="alm_loans_max_active_per_user"
							name="alm_loans_max_active_per_user"
							value="<?php echo esc_attr( $settings->get( 'loans.max_active_per_user' ) ); ?>"
							min="0"
							class="small-text"
						>
						<p class="description">
							<?php esc_html_e( 'Maximum number of active loans a single user can hold simultaneously. Set to 0 for unlimited.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Allow multiple requests', 'asset-lending-manager' ); ?>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_loans_allow_multiple_requests"
								value="1"
								<?php checked( $settings->get( 'loans.allow_multiple_requests' ) ); ?>
							>
							<?php esc_html_e( 'A user can have more than one pending loan request at the same time', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Message Length Limits', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="alm_loans_request_message_max_length">
							<?php esc_html_e( 'Request message max length', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="alm_loans_request_message_max_length"
							name="alm_loans_request_message_max_length"
							value="<?php echo esc_attr( $settings->get( 'loans.request_message_max_length' ) ); ?>"
							min="0"
							class="small-text"
							<?php disabled( ! $is_admin ); ?>
						>
						<p class="description">
							<?php esc_html_e( 'Maximum character length of the message a member writes when submitting a loan request. Set to 0 for unlimited.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="alm_loans_rejection_message_max_length">
							<?php esc_html_e( 'Rejection message max length', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="alm_loans_rejection_message_max_length"
							name="alm_loans_rejection_message_max_length"
							value="<?php echo esc_attr( $settings->get( 'loans.rejection_message_max_length' ) ); ?>"
							min="0"
							class="small-text"
							<?php disabled( ! $is_admin ); ?>
						>
						<p class="description">
							<?php esc_html_e( 'Maximum character length of the rejection reason an operator enters when declining a request. Set to 0 for unlimited.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="alm_loans_direct_assign_reason_max_length">
							<?php esc_html_e( 'Direct-assign reason max length', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="alm_loans_direct_assign_reason_max_length"
							name="alm_loans_direct_assign_reason_max_length"
							value="<?php echo esc_attr( $settings->get( 'loans.direct_assign_reason_max_length' ) ); ?>"
							min="0"
							class="small-text"
							<?php disabled( ! $is_admin ); ?>
						>
						<p class="description">
							<?php esc_html_e( 'Maximum character length of the reason field when performing a direct assignment. Set to 0 for unlimited.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>

		<?php elseif ( 'direct_assign' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Direct Assignment Settings', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Enable direct assignment', 'asset-lending-manager' ); ?>
						<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_direct_assign_enabled"
								value="1"
								<?php checked( $settings->get( 'direct_assign.enabled' ) ); ?>
								<?php disabled( ! $is_admin ); ?>
							>
							<?php esc_html_e( 'Allow operators/administrators to assign an asset directly to a user without a loan request', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Allowed target roles', 'asset-lending-manager' ); ?>
						<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
					</th>
					<td>
						<?php
						$allowed_roles   = (array) $settings->get( 'direct_assign.allowed_target_roles' );
						$available_roles = array(
							ALMGR_MEMBER_ROLE   => __( 'Member', 'asset-lending-manager' ),
							ALMGR_OPERATOR_ROLE => __( 'Operator', 'asset-lending-manager' ),
						);
						foreach ( $available_roles as $role_slug => $role_label ) :
							?>
							<label style="display:block; margin-bottom:4px;">
								<input
									type="checkbox"
									name="alm_direct_assign_roles[]"
									value="<?php echo esc_attr( $role_slug ); ?>"
									<?php checked( in_array( $role_slug, $allowed_roles, true ) ); ?>
									<?php disabled( ! $is_admin ); ?>
								>
								<?php echo esc_html( $role_label ); ?>
							</label>
						<?php endforeach; ?>
						<p class="description">
							<?php esc_html_e( 'Which user roles can be selected as the target of a direct assignment.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>

		<?php elseif ( 'workflow' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Automatic Cancellations', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Cancel concurrent requests on assign', 'asset-lending-manager' ); ?>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_workflow_cancel_concurrent"
								value="1"
								<?php checked( $settings->get( 'workflow.cancel_concurrent_requests_on_assign' ) ); ?>
							>
							<?php esc_html_e( 'When an asset is assigned, automatically cancel all other pending loan requests for that asset', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Cancel component requests when kit assigned', 'asset-lending-manager' ); ?>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_workflow_cancel_component_requests"
								value="1"
								<?php checked( $settings->get( 'workflow.cancel_component_requests_when_kit_assigned' ) ); ?>
							>
							<?php esc_html_e( 'When a kit is assigned, automatically cancel pending requests for its individual components', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'System Actor', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="alm_workflow_actor_user_id">
							<?php esc_html_e( 'Automatic operations actor user ID', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="alm_workflow_actor_user_id"
							name="alm_workflow_actor_user_id"
							value="<?php echo esc_attr( $settings->get( 'workflow.automatic_operations_actor_user_id' ) ); ?>"
							min="1"
							class="small-text"
							<?php disabled( ! $is_admin ); ?>
						>
						<p class="description">
							<?php esc_html_e( 'WordPress user ID recorded as the actor for automatic system operations (e.g. auto-cancellations). Defaults to 1 (site administrator).', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>

		<?php elseif ( 'frontend' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Page Links', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="alm_frontend_assets_page_id">
							<?php esc_html_e( 'Asset archive page', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'alm_frontend_assets_page_id',
								'id'                => 'alm_frontend_assets_page_id',
								'selected'          => (int) $settings->get( 'frontend.assets_page_id' ),
								'show_option_none'  => esc_html__( '— Not set —', 'asset-lending-manager' ),
								'option_none_value' => '0',
								'disabled'          => absint( ! $is_admin ),
							)
						);
						?>
						<p class="description">
							<?php esc_html_e( 'Page that contains the asset list. Used as the login redirect target for members with no specific destination.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="alm_frontend_login_redirect_page_id">
							<?php esc_html_e( 'Login redirect page', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'alm_frontend_login_redirect_page_id',
								'id'                => 'alm_frontend_login_redirect_page_id',
								'selected'          => (int) $settings->get( 'frontend.login_redirect_page_id' ),
								'show_option_none'  => esc_html__( '— Default (/asset/) —', 'asset-lending-manager' ),
								'option_none_value' => '0',
								'disabled'          => absint( ! $is_admin ),
							)
						);
						?>
						<p class="description">
							<?php esc_html_e( 'Page members are redirected to after login. Leave empty to use the default asset archive URL (/asset/).', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="alm_frontend_logout_redirect_page_id">
							<?php esc_html_e( 'Logout redirect page', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'alm_frontend_logout_redirect_page_id',
								'id'                => 'alm_frontend_logout_redirect_page_id',
								'selected'          => (int) $settings->get( 'frontend.logout_redirect_page_id' ),
								'show_option_none'  => esc_html__( '— Default (home) —', 'asset-lending-manager' ),
								'option_none_value' => '0',
								'disabled'          => absint( ! $is_admin ),
							)
						);
						?>
						<p class="description">
							<?php esc_html_e( 'Page members are redirected to after logout. Leave empty to use the site home page.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Asset List', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="alm_frontend_asset_list_per_page">
							<?php esc_html_e( 'Assets per page', 'asset-lending-manager' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="alm_frontend_asset_list_per_page"
							name="alm_frontend_asset_list_per_page"
							value="<?php echo esc_attr( $settings->get( 'frontend.asset_list_per_page' ) ); ?>"
							min="1"
							max="100"
							class="small-text"
						>
						<p class="description">
							<?php esc_html_e( 'Number of assets displayed per page in the frontend list. Minimum 1, maximum 100.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Filters open by default', 'asset-lending-manager' ); ?>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_frontend_default_filters_open"
								value="1"
								<?php checked( $settings->get( 'frontend.default_filters_open' ) ); ?>
							>
							<?php esc_html_e( 'Show the search/filter panel expanded when the asset list loads', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>

		<?php elseif ( 'autocomplete' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Search Behaviour', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="alm_autocomplete_min_chars">
							<?php esc_html_e( 'Minimum characters', 'asset-lending-manager' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="alm_autocomplete_min_chars"
							name="alm_autocomplete_min_chars"
							value="<?php echo esc_attr( $settings->get( 'autocomplete.min_chars' ) ); ?>"
							min="1"
							max="10"
							class="small-text"
						>
						<p class="description">
							<?php esc_html_e( 'Minimum characters typed before autocomplete activates. Default: 3.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="alm_autocomplete_max_results">
							<?php esc_html_e( 'Max results', 'asset-lending-manager' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="alm_autocomplete_max_results"
							name="alm_autocomplete_max_results"
							value="<?php echo esc_attr( $settings->get( 'autocomplete.max_results' ) ); ?>"
							min="1"
							max="20"
							class="small-text"
						>
						<p class="description">
							<?php esc_html_e( 'Maximum number of suggestions returned by the autocomplete endpoint. Default: 5.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="alm_autocomplete_description_length">
							<?php esc_html_e( 'Description snippet length', 'asset-lending-manager' ); ?>
						</label>
					</th>
					<td>
						<input
							type="number"
							id="alm_autocomplete_description_length"
							name="alm_autocomplete_description_length"
							value="<?php echo esc_attr( $settings->get( 'autocomplete.description_length' ) ); ?>"
							min="0"
							max="200"
							class="small-text"
						>
						<p class="description">
							<?php esc_html_e( 'Number of characters of the asset description shown in each suggestion. Set to 0 to hide the description.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'API Access', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Public asset search endpoint', 'asset-lending-manager' ); ?>
						<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_autocomplete_public_endpoint"
								value="1"
								<?php checked( $settings->get( 'autocomplete.public_assets_endpoint_enabled' ) ); ?>
								<?php disabled( ! $is_admin ); ?>
							>
							<?php esc_html_e( 'Allow unauthenticated requests to the asset autocomplete REST endpoint (/wp-json/alm/v1/assets/autocomplete)', 'asset-lending-manager' ); ?>
							</label>
						</td>
					</tr>
				</table>

			<h2><?php esc_html_e( 'QR Code', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Enable QR code search', 'asset-lending-manager' ); ?>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_autocomplete_qr_scan_enabled"
								value="1"
								<?php checked( $settings->get( 'autocomplete.qr_scan_enabled' ) ); ?>
							>
							<?php esc_html_e( 'Show the "Scan QR" button on the asset list page, allowing users to find an asset by scanning its QR code with the device camera.', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>

		<?php elseif ( 'logging' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Logging', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Enable ALM logging', 'asset-lending-manager' ); ?>
						<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_logging_enabled"
								value="1"
								<?php checked( $settings->get( 'logging.enabled' ) ); ?>
								<?php disabled( ! $is_admin ); ?>
							>
							<?php esc_html_e( 'Write ALM events to the debug log (requires WP_DEBUG to be enabled)', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="alm_logging_level">
							<?php esc_html_e( 'Log level', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<select
							id="alm_logging_level"
							name="alm_logging_level"
							<?php disabled( ! $is_admin ); ?>
						>
							<?php
							$log_levels = array(
								'debug'   => __( 'Debug — all events', 'asset-lending-manager' ),
								'info'    => __( 'Info — informational and above', 'asset-lending-manager' ),
								'warning' => __( 'Warning — warnings and errors only', 'asset-lending-manager' ),
								'error'   => __( 'Error — errors only', 'asset-lending-manager' ),
							);
							foreach ( $log_levels as $level_value => $level_label ) :
								?>
								<option value="<?php echo esc_attr( $level_value ); ?>" <?php selected( $settings->get( 'logging.level' ), $level_value ); ?>>
									<?php echo esc_html( $level_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Privacy', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Mask personal data in logs', 'asset-lending-manager' ); ?>
						<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_logging_mask_personal_data"
								value="1"
								<?php checked( $settings->get( 'logging.mask_personal_data' ) ); ?>
								<?php disabled( ! $is_admin ); ?>
							>
							<?php esc_html_e( 'Redact user names and email addresses from log entries', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Log email events', 'asset-lending-manager' ); ?>
						<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_logging_log_email_events"
								value="1"
								<?php checked( $settings->get( 'logging.log_email_events' ) ); ?>
								<?php disabled( ! $is_admin ); ?>
							>
							<?php esc_html_e( 'Record each notification email dispatch attempt in the log', 'asset-lending-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>

		<?php elseif ( 'asset' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'Asset Code', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="alm_asset_code_prefix">
							<?php esc_html_e( 'Code prefix', 'asset-lending-manager' ); ?>
							<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
						</label>
					</th>
					<td>
						<input
							type="text"
							id="alm_asset_code_prefix"
							name="alm_asset_code_prefix"
							value="<?php echo esc_attr( $settings->get( 'asset.code_prefix' ) ); ?>"
							maxlength="10"
							class="small-text"
							<?php disabled( ! $is_admin ); ?>
						>
						<p class="description">
							<?php
							printf(
								/* translators: %s: example formatted code */
								esc_html__( 'Alphanumeric prefix prepended to the asset ID to form the human-readable asset code (e.g. %s).', 'asset-lending-manager' ),
								'<code>' . esc_html( $settings->get( 'asset.code_prefix' ) . '-00000001' ) . '</code>'
							);
							?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>

		<?php elseif ( 'rest_api' === $active_tab ) : ?>

			<h2><?php esc_html_e( 'REST API', 'asset-lending-manager' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Enable REST API', 'asset-lending-manager' ); ?>
						<span class="alm-badge-admin" title="<?php esc_attr_e( 'Administrator only', 'asset-lending-manager' ); ?>">A</span>
					</th>
					<td>
						<label>
							<input
								type="checkbox"
								name="alm_rest_api_enabled"
								value="1"
								<?php checked( $settings->get( 'rest_api.enabled', true ) ); ?>
								<?php disabled( ! $is_admin ); ?>
							>
							<?php esc_html_e( 'Enable the ALM JSON API endpoints under /alm/v1/.', 'asset-lending-manager' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'When disabled, all /alm/v1/ routes return a 503 response. The API works independently of the WordPress REST API global setting.', 'asset-lending-manager' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Authentication', 'asset-lending-manager' ); ?></h2>
			<div class="alm-info-box" style="background:#f0f6fc;border-left:4px solid #0073aa;padding:12px 16px;margin-bottom:16px;">
				<p><strong><?php esc_html_e( 'How to authenticate with the ALM API', 'asset-lending-manager' ); ?></strong></p>
				<ol style="margin:8px 0 8px 20px;">
					<li>
						<?php
						printf(
							/* translators: %s: link to WP admin profile page */
							esc_html__( 'Go to %s → Application Passwords.', 'asset-lending-manager' ),
							'<a href="' . esc_url( admin_url( 'profile.php' ) ) . '">' . esc_html__( 'WP Admin → Your Profile', 'asset-lending-manager' ) . '</a>'
						);
						?>
					</li>
					<li><?php esc_html_e( 'Create a new Application Password and copy it.', 'asset-lending-manager' ); ?></li>
					<li>
						<?php
						esc_html_e( 'Send requests with the Authorization header:', 'asset-lending-manager' );
						?>
						<code style="display:block;margin-top:4px;">Authorization: Basic base64(username:app_password)</code>
					</li>
				</ol>
				<p>
					<?php
					printf(
						/* translators: %s: link to WP Application Passwords documentation */
						esc_html__( 'Requires WordPress 5.6 or later. %s', 'asset-lending-manager' ),
						'<a href="https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Learn more', 'asset-lending-manager' ) . '</a>'
					);
					?>
				</p>
			</div>

			<h2><?php esc_html_e( 'Available Endpoints', 'asset-lending-manager' ); ?></h2>
			<table class="widefat striped" style="max-width:800px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Endpoint', 'asset-lending-manager' ); ?></th>
						<th><?php esc_html_e( 'Description', 'asset-lending-manager' ); ?></th>
						<th><?php esc_html_e( 'Required capability', 'asset-lending-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>GET /alm/v1/assets</code></td>
						<td><?php esc_html_e( 'Paginated asset list. Supports ?state, ?type, ?structure, ?search, ?owner, ?page, ?per_page.', 'asset-lending-manager' ); ?></td>
						<td><code>almgr_view_assets</code></td>
					</tr>
					<tr>
						<td><code>GET /alm/v1/assets/{id}</code></td>
						<td><?php esc_html_e( 'Single asset detail with ACF fields. Operators additionally see cost, purchase date, notes, and loan history.', 'asset-lending-manager' ); ?></td>
						<td><code>almgr_view_asset</code></td>
					</tr>
					<tr>
						<td><code>GET /alm/v1/members</code></td>
						<td><?php esc_html_e( 'Paginated list of ALM members and operators. Supports ?search, ?role, ?page, ?per_page.', 'asset-lending-manager' ); ?></td>
						<td><code>almgr_edit_asset</code></td>
					</tr>
					<tr>
						<td><code>GET /alm/v1/members/{id}/assets</code></td>
						<td><?php esc_html_e( 'Assets currently held by a specific member (on-loan). Returns id, code, title, structure, type, external_code, location, thumbnail_url, permalink.', 'asset-lending-manager' ); ?></td>
						<td><code>almgr_edit_asset</code></td>
					</tr>
				</tbody>
			</table>

			<p class="description" style="margin-top:12px;">
				<?php
				printf(
					/* translators: %s: example base URL */
					esc_html__( 'Base URL: %s', 'asset-lending-manager' ),
					'<code>' . esc_url( home_url( '/alm/v1/' ) ) . '</code>'
				);
				?>
			</p>
			<p class="description">
				<?php esc_html_e( 'After enabling the API for the first time, visit Settings → Permalinks and click Save to flush rewrite rules.', 'asset-lending-manager' ); ?>
			</p>

			<?php if ( $is_admin ) : ?>
				<?php submit_button( __( 'Save Settings', 'asset-lending-manager' ) ); ?>
			<?php endif; ?>

		<?php endif; ?>
	</form>
</div>
