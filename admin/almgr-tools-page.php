<?php
/**
 * ALMGR Tools Page template.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only GET param for status display.
$almgr_status = isset( $_GET['almgr_status'] ) ? sanitize_key( wp_unslash( $_GET['almgr_status'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only GET param to show last report.
$almgr_show_users_import_report = isset( $_GET['almgr_users_import_report'] ) ? sanitize_key( wp_unslash( $_GET['almgr_users_import_report'] ) ) : '';
$almgr_users_import_report      = array();

if ( '1' === $almgr_show_users_import_report && current_user_can( 'manage_options' ) ) {
	$almgr_users_import_report_key = 'almgr_users_import_report_' . get_current_user_id();
	$almgr_users_import_report_raw = get_transient( $almgr_users_import_report_key );
	if ( is_array( $almgr_users_import_report_raw ) ) {
		$almgr_users_import_report = $almgr_users_import_report_raw;
		delete_transient( $almgr_users_import_report_key );
	}
}

$almgr_allowed_tabs = array( 'overview', 'import', 'export', 'utilities' );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only GET param for tab selection.
$almgr_current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';

if ( ! in_array( $almgr_current_tab, $almgr_allowed_tabs, true ) ) {
	$almgr_current_tab = 'overview';
}

$almgr_tabs        = array(
	'overview'  => __( 'Overview', 'asset-lending-manager' ),
	'import'    => __( 'Import', 'asset-lending-manager' ),
	'export'    => __( 'Export', 'asset-lending-manager' ),
	'utilities' => __( 'Utilities', 'asset-lending-manager' ),
);
$almgr_section_map = array(
	'import' => array(
		'users'  => __( 'Users', 'asset-lending-manager' ),
		'assets' => __( 'Assets', 'asset-lending-manager' ),
	),
	'export' => array(
		'users'  => __( 'Users', 'asset-lending-manager' ),
		'assets' => __( 'Assets', 'asset-lending-manager' ),
	),
);

$almgr_tools_page_url        = admin_url( 'admin.php?page=almgr-tools' );
$almgr_users_csv_example_url = trailingslashit( ALMGR_PLUGIN_URL ) . 'assets/examples/almgr-users-import-example.csv';
$almgr_current_section       = '';

if ( isset( $almgr_section_map[ $almgr_current_tab ] ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only GET param for section selection.
	$almgr_current_section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'users';
	if ( ! isset( $almgr_section_map[ $almgr_current_tab ][ $almgr_current_section ] ) ) {
		$almgr_current_section = 'users';
	}
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'ALM Tools', 'asset-lending-manager' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<?php foreach ( $almgr_tabs as $almgr_tab_key => $almgr_tab_label ) : ?>
			<?php
			$almgr_tab_args = array(
				'tab' => $almgr_tab_key,
			);
			if ( isset( $almgr_section_map[ $almgr_tab_key ] ) ) {
				$almgr_tab_args['section'] = 'users';
			}
			$almgr_tab_url = add_query_arg( $almgr_tab_args, $almgr_tools_page_url );
			$almgr_tab_css = 'nav-tab';
			if ( $almgr_tab_key === $almgr_current_tab ) {
				$almgr_tab_css .= ' nav-tab-active';
			}
			?>
			<a href="<?php echo esc_url( $almgr_tab_url ); ?>" class="<?php echo esc_attr( $almgr_tab_css ); ?>">
				<?php echo esc_html( $almgr_tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<?php if ( $almgr_status ) : ?>
		<?php if ( 'success' === $almgr_status ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Default terms loaded successfully.', 'asset-lending-manager' ); ?></p>
			</div>
		<?php else : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'An error occurred while loading default terms.', 'asset-lending-manager' ); ?></p>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( 'overview' === $almgr_current_tab ) : ?>
		<p><?php esc_html_e( 'Use this section to manage data import, export and utility operations.', 'asset-lending-manager' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Import: upload structured CSV files to create or update records.', 'asset-lending-manager' ); ?></li>
			<li><?php esc_html_e( 'Export: generate CSV reports from plugin data.', 'asset-lending-manager' ); ?></li>
			<li><?php esc_html_e( 'Utilities: execute administrative utility operations.', 'asset-lending-manager' ); ?></li>
		</ul>
	<?php elseif ( 'import' === $almgr_current_tab ) : ?>
		<h2><?php esc_html_e( 'Import Tools', 'asset-lending-manager' ); ?></h2>
		<ul class="subsubsub">
			<?php foreach ( $almgr_section_map['import'] as $almgr_section_key => $almgr_section_label ) : ?>
				<?php
				$almgr_section_url = add_query_arg(
					array(
						'tab'     => 'import',
						'section' => $almgr_section_key,
					),
					$almgr_tools_page_url
				);
				$almgr_section_css = ( $almgr_section_key === $almgr_current_section ) ? 'current' : '';
				?>
				<li>
					<a class="<?php echo esc_attr( $almgr_section_css ); ?>" href="<?php echo esc_url( $almgr_section_url ); ?>">
						<?php echo esc_html( $almgr_section_label ); ?>
					</a> |
				</li>
			<?php endforeach; ?>
		</ul>
		<br class="clear">

		<?php if ( 'users' === $almgr_current_section ) : ?>
			<?php if ( ! current_user_can( 'manage_options' ) ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'Only administrators can import users.', 'asset-lending-manager' ); ?></p>
				</div>
			<?php else : ?>
				<div class="postbox">
					<div class="inside">
						<h3><?php esc_html_e( 'How It Works', 'asset-lending-manager' ); ?></h3>
						<p><?php esc_html_e( 'Import users from CSV with strict header validation.', 'asset-lending-manager' ); ?></p>
						<p>
							<strong><?php esc_html_e( 'Required CSV header:', 'asset-lending-manager' ); ?></strong>
							<code>Username;Email;First_Name;Last_Name;Role</code>
						</p>
						<p>
							<strong><?php esc_html_e( 'Allowed role values:', 'asset-lending-manager' ); ?></strong>
							<code><?php esc_html_e( 'member', 'asset-lending-manager' ); ?></code>,
							<code><?php esc_html_e( 'operator', 'asset-lending-manager' ); ?></code>.
							<?php esc_html_e( 'Delimiter: semicolon (;).', 'asset-lending-manager' ); ?>
						</p>
						<p><?php esc_html_e( 'Username is required and must be unique together with email.', 'asset-lending-manager' ); ?></p>
						<p>
							<a class="button button-secondary" href="<?php echo esc_url( $almgr_users_csv_example_url ); ?>">
								<?php esc_html_e( 'Download Sample CSV', 'asset-lending-manager' ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="postbox">
					<div class="inside">
						<h3><?php esc_html_e( 'Run Import', 'asset-lending-manager' ); ?></h3>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
							<?php wp_nonce_field( 'almgr_import_users_csv_action', 'almgr_import_users_csv_nonce' ); ?>
							<input type="hidden" name="action" value="almgr_import_users_csv">

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row">
											<label for="almgr_users_csv_file"><?php esc_html_e( 'CSV file', 'asset-lending-manager' ); ?></label>
										</th>
										<td>
											<input type="file" name="almgr_users_csv_file" id="almgr_users_csv_file" accept=".csv,text/csv" required>
											<p class="description"><?php esc_html_e( 'Maximum size: 1MB.', 'asset-lending-manager' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="almgr_users_import_mode"><?php esc_html_e( 'Import mode', 'asset-lending-manager' ); ?></label>
										</th>
										<td>
											<select name="almgr_users_import_mode" id="almgr_users_import_mode">
												<option value="create_only"><?php esc_html_e( 'create_only', 'asset-lending-manager' ); ?></option>
												<option value="update_only"><?php esc_html_e( 'update_only', 'asset-lending-manager' ); ?></option>
												<option value="upsert" selected><?php esc_html_e( 'upsert', 'asset-lending-manager' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="almgr_users_run_mode"><?php esc_html_e( 'Execution mode', 'asset-lending-manager' ); ?></label>
										</th>
										<td>
											<select name="almgr_users_run_mode" id="almgr_users_run_mode">
												<option value="dry_run" selected><?php esc_html_e( 'dry_run (simulation)', 'asset-lending-manager' ); ?></option>
												<option value="execute"><?php esc_html_e( 'execute (write changes)', 'asset-lending-manager' ); ?></option>
											</select>
										</td>
									</tr>
								</tbody>
							</table>

							<?php submit_button( __( 'Run Users Import', 'asset-lending-manager' ) ); ?>
						</form>
					</div>
				</div>

				<?php if ( ! empty( $almgr_users_import_report ) ) : ?>
					<?php
					$almgr_report_counts = isset( $almgr_users_import_report['counts'] ) && is_array( $almgr_users_import_report['counts'] )
						? $almgr_users_import_report['counts']
						: array();
					$almgr_report_logs   = isset( $almgr_users_import_report['logs'] ) && is_array( $almgr_users_import_report['logs'] )
						? $almgr_users_import_report['logs']
						: array();
					$almgr_report_errors = isset( $almgr_users_import_report['errors'] ) && is_array( $almgr_users_import_report['errors'] )
						? $almgr_users_import_report['errors']
						: array();
					$almgr_is_dry_run    = 'dry_run' === (string) ( $almgr_users_import_report['run_mode'] ?? '' );
					$almgr_log_statuses  = array(
						'ok'      => array(
							'label' => __( 'OK', 'asset-lending-manager' ),
							'color' => '#1f7a1f',
						),
						'skipped' => array(
							'label' => __( 'skipped', 'asset-lending-manager' ),
							'color' => '#8a6d3b',
						),
						'error'   => array(
							'label' => __( 'error', 'asset-lending-manager' ),
							'color' => '#b32d2e',
						),
					);
					?>
					<h3>
						<?php esc_html_e( 'Import Report', 'asset-lending-manager' ); ?>
						<?php if ( $almgr_is_dry_run ) : ?>
							<span style="<?php echo esc_attr( 'margin-left: 6px; color: #8a6d3b; font-weight: 600;' ); ?>">
								<?php esc_html_e( 'dry run', 'asset-lending-manager' ); ?>
							</span>
						<?php endif; ?>
					</h3>
					<p>
						<?php
						printf(
							/* translators: 1: run mode, 2: import mode, 3: file name. */
							esc_html__( 'Run mode: %1$s | Import mode: %2$s | File: %3$s', 'asset-lending-manager' ),
							esc_html( (string) ( $almgr_users_import_report['run_mode'] ?? '' ) ),
							esc_html( (string) ( $almgr_users_import_report['import_mode'] ?? '' ) ),
							esc_html( (string) ( $almgr_users_import_report['file_name'] ?? '' ) )
						);
						?>
					</p>

					<table class="widefat striped" style="max-width: 680px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Processed', 'asset-lending-manager' ); ?></th>
								<th><?php esc_html_e( 'Created', 'asset-lending-manager' ); ?></th>
								<th><?php esc_html_e( 'Updated', 'asset-lending-manager' ); ?></th>
								<th><?php esc_html_e( 'Skipped', 'asset-lending-manager' ); ?></th>
								<th><?php esc_html_e( 'Errors', 'asset-lending-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?php echo esc_html( (string) ( $almgr_report_counts['processed'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( (string) ( $almgr_report_counts['created'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( (string) ( $almgr_report_counts['updated'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( (string) ( $almgr_report_counts['skipped'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( (string) ( $almgr_report_counts['errors'] ?? 0 ) ); ?></td>
							</tr>
						</tbody>
					</table>

					<?php if ( ! empty( $almgr_report_errors ) ) : ?>
						<h4><?php esc_html_e( 'Errors', 'asset-lending-manager' ); ?></h4>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Line', 'asset-lending-manager' ); ?></th>
									<th><?php esc_html_e( 'Username', 'asset-lending-manager' ); ?></th>
									<th><?php esc_html_e( 'Email', 'asset-lending-manager' ); ?></th>
									<th><?php esc_html_e( 'Message', 'asset-lending-manager' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $almgr_report_errors as $almgr_report_error ) : ?>
									<tr>
										<td><?php echo esc_html( (string) ( $almgr_report_error['line'] ?? '' ) ); ?></td>
										<td><?php echo esc_html( (string) ( $almgr_report_error['username'] ?? '' ) ); ?></td>
										<td><?php echo esc_html( (string) ( $almgr_report_error['email'] ?? '' ) ); ?></td>
										<td><?php echo esc_html( (string) ( $almgr_report_error['message'] ?? '' ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<div class="postbox">
						<div class="inside">
							<h3><?php esc_html_e( 'Import Log', 'asset-lending-manager' ); ?></h3>
							<?php if ( ! empty( $almgr_report_logs ) ) : ?>
								<table class="widefat striped">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Line', 'asset-lending-manager' ); ?></th>
											<th><?php esc_html_e( 'Username', 'asset-lending-manager' ); ?></th>
											<th><?php esc_html_e( 'Email', 'asset-lending-manager' ); ?></th>
											<th><?php esc_html_e( 'Status', 'asset-lending-manager' ); ?></th>
											<th><?php esc_html_e( 'Message', 'asset-lending-manager' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $almgr_report_logs as $almgr_report_log ) : ?>
											<?php
											$almgr_status_key   = sanitize_key( (string) ( $almgr_report_log['status'] ?? '' ) );
											$almgr_status_style = $almgr_log_statuses[ $almgr_status_key ] ?? $almgr_log_statuses['error'];
											?>
											<tr>
												<td><?php echo esc_html( (string) ( $almgr_report_log['line'] ?? '' ) ); ?></td>
												<td><?php echo esc_html( (string) ( $almgr_report_log['username'] ?? '' ) ); ?></td>
												<td><?php echo esc_html( (string) ( $almgr_report_log['email'] ?? '' ) ); ?></td>
												<td>
													<span style="<?php echo esc_attr( 'font-weight: 600; color: ' . $almgr_status_style['color'] . ';' ); ?>">
														<?php echo esc_html( $almgr_status_style['label'] ); ?>
													</span>
												</td>
												<td><?php echo esc_html( (string) ( $almgr_report_log['message'] ?? '' ) ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php else : ?>
								<p><?php esc_html_e( 'No rows were processed for this run.', 'asset-lending-manager' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		<?php elseif ( 'assets' === $almgr_current_section ) : ?>
			<div class="postbox">
				<div class="inside">
					<h3><?php esc_html_e( 'How It Works', 'asset-lending-manager' ); ?></h3>
					<p><?php esc_html_e( 'Assets CSV import will be configured in this section with the same structure used for users.', 'asset-lending-manager' ); ?></p>
					<p><?php esc_html_e( 'Target permission: administrators and operators.', 'asset-lending-manager' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<div class="inside">
					<h3><?php esc_html_e( 'Run Import', 'asset-lending-manager' ); ?></h3>
					<p><?php esc_html_e( 'Assets import UI is not yet enabled.', 'asset-lending-manager' ); ?></p>
					<p><button type="button" class="button button-secondary" disabled><?php esc_html_e( 'Run Assets Import', 'asset-lending-manager' ); ?></button></p>
				</div>
			</div>
		<?php endif; ?>
	<?php elseif ( 'export' === $almgr_current_tab ) : ?>
		<h2><?php esc_html_e( 'Export Tools', 'asset-lending-manager' ); ?></h2>
		<ul class="subsubsub">
			<?php foreach ( $almgr_section_map['export'] as $almgr_section_key => $almgr_section_label ) : ?>
				<?php
				$almgr_section_url = add_query_arg(
					array(
						'tab'     => 'export',
						'section' => $almgr_section_key,
					),
					$almgr_tools_page_url
				);
				$almgr_section_css = ( $almgr_section_key === $almgr_current_section ) ? 'current' : '';
				?>
				<li>
					<a class="<?php echo esc_attr( $almgr_section_css ); ?>" href="<?php echo esc_url( $almgr_section_url ); ?>">
						<?php echo esc_html( $almgr_section_label ); ?>
					</a> |
				</li>
			<?php endforeach; ?>
		</ul>
		<br class="clear">

		<?php if ( 'users' === $almgr_current_section ) : ?>
			<?php if ( ! current_user_can( 'manage_options' ) && ! current_user_can( ALMGR_EDIT_ASSET ) ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'Only administrators and operators can export users.', 'asset-lending-manager' ); ?></p>
				</div>
			<?php else : ?>
				<div class="postbox">
					<div class="inside">
						<h3><?php esc_html_e( 'How It Works', 'asset-lending-manager' ); ?></h3>
						<p><?php esc_html_e( 'Export users to CSV with strict columns compatible with users import.', 'asset-lending-manager' ); ?></p>
						<p>
							<strong><?php esc_html_e( 'Exported CSV header:', 'asset-lending-manager' ); ?></strong>
							<code>Username;Email;First_Name;Last_Name;Role</code>
						</p>
						<p>
							<strong><?php esc_html_e( 'Allowed role values:', 'asset-lending-manager' ); ?></strong>
							<code><?php esc_html_e( 'member', 'asset-lending-manager' ); ?></code>,
							<code><?php esc_html_e( 'operator', 'asset-lending-manager' ); ?></code>.
							<?php esc_html_e( 'Delimiter: semicolon (;).', 'asset-lending-manager' ); ?>
						</p>
						<p><?php esc_html_e( 'Only users with ALM roles are exported. If a user has both roles, operator is exported.', 'asset-lending-manager' ); ?></p>
					</div>
				</div>
				<div class="postbox">
					<div class="inside">
						<h3><?php esc_html_e( 'Run Export', 'asset-lending-manager' ); ?></h3>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'almgr_export_users_csv_action', 'almgr_export_users_csv_nonce' ); ?>
							<input type="hidden" name="action" value="almgr_export_users_csv">
							<?php submit_button( __( 'Export Users CSV', 'asset-lending-manager' ) ); ?>
						</form>
					</div>
				</div>
			<?php endif; ?>
		<?php elseif ( 'assets' === $almgr_current_section ) : ?>
			<div class="postbox">
				<div class="inside">
					<h3><?php esc_html_e( 'How It Works', 'asset-lending-manager' ); ?></h3>
					<p><?php esc_html_e( 'Assets export will support CSV format.', 'asset-lending-manager' ); ?></p>
					<p><?php esc_html_e( 'Target permission: administrators and operators.', 'asset-lending-manager' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<div class="inside">
					<h3><?php esc_html_e( 'Run Export', 'asset-lending-manager' ); ?></h3>
					<p><?php esc_html_e( 'Assets export actions will be added here.', 'asset-lending-manager' ); ?></p>
					<p>
						<button type="button" class="button button-secondary" disabled><?php esc_html_e( 'Export Assets CSV', 'asset-lending-manager' ); ?></button>
					</p>
				</div>
			</div>
		<?php endif; ?>
	<?php elseif ( 'utilities' === $almgr_current_tab ) : ?>
		<h2><?php esc_html_e( 'Utilities', 'asset-lending-manager' ); ?></h2>
		<p><?php esc_html_e( 'Run utility actions for taxonomy and plugin data alignment.', 'asset-lending-manager' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'almgr_reload_terms_action', 'almgr_reload_terms_nonce' ); ?>
			<input type="hidden" name="action" value="almgr_reload_default_terms">
			<?php submit_button( __( 'Reload Default Taxonomies Terms', 'asset-lending-manager' ) ); ?>
		</form>
	<?php endif; ?>
</div>
