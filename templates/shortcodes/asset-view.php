<?php
/**
 * Template for asset view shortcode
 *
 * Available variables:
 * - $asset: Asset wrapper object from ALMGR_Asset_Manager::get_asset_wrapper()
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$almgr_current_user_id = get_current_user_id();
$almgr_asset_id        = isset( $asset->id ) ? (int) $asset->id : 0;
if ( $almgr_asset_id <= 0 ) {
	return;
}

$almgr_asset_fields             = ALMGR_Asset_Manager::get_asset_custom_fields( $almgr_asset_id );
$almgr_loan_manager             = ALMGR_Plugin_Manager::get_instance()->get_module( 'loan' );
$almgr_settings                 = ALMGR_Plugin_Manager::get_instance()->get_module( 'settings' );
$almgr_loan_requests_enabled    = (bool) $almgr_settings->get( 'loans.loan_requests_enabled', true );
$almgr_request_message_max      = (int) $almgr_settings->get( 'loans.request_message_max_length', 500 );
$almgr_rejection_message_max    = (int) $almgr_settings->get( 'loans.rejection_message_max_length', 500 );
$almgr_direct_assign_reason_max = (int) $almgr_settings->get( 'loans.direct_assign_reason_max_length', 500 );
$almgr_change_state_notes_max   = (int) $almgr_settings->get( 'loans.change_state_notes_max_length', 500 );
$almgr_asset_location           = function_exists( 'get_field' ) ? (string) get_field( 'location', $almgr_asset_id ) : '';
$almgr_owner_id                 = $almgr_loan_manager->get_current_owner( $almgr_asset_id );
$almgr_asset_title              = isset( $asset->title ) ? (string) $asset->title : '';
$almgr_asset_content            = isset( $asset->content ) ? (string) $asset->content : '';
$almgr_owner_name               = '';
$almgr_is_current_owner         = is_user_logged_in() && $almgr_owner_id > 0 && ( $almgr_current_user_id === (int) $almgr_owner_id );
$almgr_is_operator              = is_user_logged_in() && current_user_can( ALMGR_EDIT_ASSET );
if ( $almgr_owner_id > 0 ) {
	$almgr_owner_data = get_userdata( $almgr_owner_id );
	$almgr_owner_name = $almgr_owner_data ? $almgr_owner_data->display_name : '';
}
/**
 * Big image for detail (do not change list thumbnail).
 */
$almgr_detail_image_html = '';
if ( has_post_thumbnail( $almgr_asset_id ) ) {
	$almgr_detail_image_html = get_the_post_thumbnail( $almgr_asset_id, 'large' );
} else {
	// Fallback to wrapper thumbnail (already includes plugin default image).
	$almgr_detail_image_html = isset( $asset->thumbnail ) ? (string) $asset->thumbnail : '';
}
?>

<article class="almgr-asset-detail almgr-asset-view" data-asset-id="<?php echo esc_attr( $almgr_asset_id ); ?>">

	<!-- I section: Title -->
	<header class="almgr-asset-view__title">
		<h1 class="almgr-asset-title"><?php echo esc_html( $almgr_asset_title ); ?></h1>
	</header>

	<?php
	$almgr_asset_code_str = ALMGR_Asset_Manager::get_asset_code( $almgr_asset_id );
	$almgr_scan_url       = home_url( '/?almgr_scan=' . rawurlencode( $almgr_asset_code_str ) );
	?>

	<!-- II section: FOTO (sx) + Taxonomies box (dx) -->
	<section class="almgr-asset-view__hero" aria-label="<?php esc_attr_e( 'Asset overview', 'asset-lending-manager' ); ?>">
		<!-- Asset foto -->
		<div class="almgr-asset-view__media">
			<?php if ( $almgr_detail_image_html ) : ?>
				<div class="almgr-asset-thumbnail almgr-asset-thumbnail--large">
					<?php echo wp_kses_post( $almgr_detail_image_html ); ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Asset taxonomies -->
		<?php
		// Get taxonomy values.
		$almgr_structure   = isset( $asset->almgr_structure ) ? implode( ', ', $asset->almgr_structure ) : '-';
		$almgr_type        = isset( $asset->almgr_type ) ? implode( ', ', $asset->almgr_type ) : '-';
		$almgr_level       = isset( $asset->almgr_level ) ? implode( ', ', $asset->almgr_level ) : '-';
		$almgr_state_terms = get_the_terms( $almgr_asset_id, 'almgr_state' );
		$almgr_state_slug  = '';
		$almgr_state_label = '';
		if ( ! is_wp_error( $almgr_state_terms ) && ! empty( $almgr_state_terms ) ) {
			$almgr_state_slug  = (string) $almgr_state_terms[0]->slug;
			$almgr_state_label = ALMGR_Asset_Manager::get_state_label(
				$almgr_state_slug,
				(string) $almgr_state_terms[0]->name
			);
		}
		$almgr_state_class_map = ALMGR_Asset_Manager::get_state_classes();
		$almgr_state_css_class = '';
		if ( $almgr_state_slug && isset( $almgr_state_class_map[ $almgr_state_slug ] ) ) {
			$almgr_state_css_class = $almgr_state_class_map[ $almgr_state_slug ];
		}
		?>
		<aside class="almgr-asset-view__taxbox" aria-label="<?php esc_attr_e( 'Asset taxonomies', 'asset-lending-manager' ); ?>">
			<div class="almgr-asset-taxonomies almgr-asset-taxonomies--boxed">
				<div class="almgr-asset-tax-row">
					<span class="almgr-tax-label">
						<?php esc_html_e( 'Structure', 'asset-lending-manager' ); ?>
					</span>
					<span class="almgr-tax-value">
						<?php echo esc_html( $almgr_structure ); ?>
					</span>
				</div>
				<div class="almgr-asset-tax-row">
					<span class="almgr-tax-label">
						<?php esc_html_e( 'Type', 'asset-lending-manager' ); ?>
					</span>
					<span class="almgr-tax-value">
						<?php echo esc_html( $almgr_type ); ?>
					</span>
				</div>
				<div class="almgr-asset-tax-row">
					<span class="almgr-tax-label">
						<?php esc_html_e( 'Level', 'asset-lending-manager' ); ?>
					</span>
					<span class="almgr-tax-value">
						<?php echo esc_html( $almgr_level ); ?>
					</span>
				</div>
				<div class="almgr-asset-tax-row">
					<span class="almgr-tax-label">
						<?php esc_html_e( 'State', 'asset-lending-manager' ); ?>
					</span>
					<span class="almgr-tax-value">
						<span class="almgr-availability <?php echo esc_attr( $almgr_state_css_class ); ?>">
							<?php echo esc_html( $almgr_state_label ); ?>
						</span>
					</span>
				</div>
			</div>
		</aside>
	</section>

	<!-- III section: Asset description -->
	<?php if ( $almgr_asset_content ) : ?>
	<section class="almgr-asset-view__content" aria-label="<?php esc_attr_e( 'Full description', 'asset-lending-manager' ); ?>">
		<div class="almgr-asset-content">
			<?php echo wp_kses_post( $almgr_asset_content ); ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- IV section: Asset optional fields -->
	<section class="almgr-asset-view__acf" aria-label="<?php esc_attr_e( 'Additional fields', 'asset-lending-manager' ); ?>">
		<details class="almgr-collapsible almgr-collapsible--acf" open>
			<summary class="almgr-collapsible__summary">
				<span class="almgr-collapsible__title">
					<?php esc_html_e( 'Read details', 'asset-lending-manager' ); ?>
				</span>
				<span class="almgr-collapsible__hint" aria-hidden="true">
					<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>
			<div class="almgr-collapsible__body">
				<?php if ( ! empty( $almgr_asset_fields ) ) : ?>
					<dl class="almgr-asset-acf-list">
						<?php if ( is_user_logged_in() && ! empty( $almgr_owner_name ) ) : ?>
							<div class="almgr-asset-acf-row almgr-acf-current-owner">
								<dt class="almgr-asset-acf-label">
									<?php esc_html_e( 'Current owner', 'asset-lending-manager' ); ?>
								</dt>
								<dd class="almgr-asset-acf-value">
									<?php echo esc_html( $almgr_owner_name ); ?>
								</dd>
							</div>
						<?php endif; ?>
						<?php foreach ( $almgr_asset_fields as $almgr_asset_row ) : ?>
							<?php if ( $almgr_asset_row['value'] ) : ?>
								<div class="almgr-asset-acf-row almgr-acf-<?php echo esc_attr( $almgr_asset_row['name'] ); ?>">
									<dt class="almgr-asset-acf-label">
										<?php echo esc_html( (string) $almgr_asset_row['label'] ); ?>
									</dt>
									<dd class="almgr-asset-acf-value">
										<?php
										// Render by type.
										if ( 'file' === $almgr_asset_row['type'] && is_array( $almgr_asset_row['value'] ) && ! empty( $almgr_asset_row['value']['url'] ) ) {
											$almgr_file_url  = (string) $almgr_asset_row['value']['url'];
											$almgr_file_name = ! empty( $almgr_asset_row['value']['filename'] ) ? (string) $almgr_asset_row['value']['filename'] : $almgr_file_url;
											?>
											<a href="<?php echo esc_url( $almgr_file_url ); ?>" class="almgr-link" target="_blank" rel="noopener">
												<?php echo esc_html( $almgr_file_name ); ?>
												<span class="screen-reader-text"><?php esc_html_e( '(opens in new tab)', 'asset-lending-manager' ); ?></span>
											</a>
											<?php
										} elseif ( 'post_object' === $almgr_asset_row['type'] && is_array( $almgr_asset_row['value'] ) ) {
											// Multiple components (objects).
											echo '<ul class="almgr-asset-components">';
											foreach ( $almgr_asset_row['value'] as $almgr_component_post ) {
												if ( is_object( $almgr_component_post ) && ! empty( $almgr_component_post->ID ) ) {
													$almgr_component_title = get_the_title( $almgr_component_post->ID );
													$almgr_component_link  = get_permalink( $almgr_component_post->ID );
													echo '<li><a class="almgr-link" href="' . esc_url( $almgr_component_link ) . '">' . esc_html( $almgr_component_title ) . '</a></li>';
												}
											}
											echo '</ul>';
										} elseif ( 'number' === $almgr_asset_row['type'] ) {
											// Cost / numeric fields.
											echo esc_html( (string) $almgr_asset_row['value'] );
										} elseif ( is_array( $almgr_asset_row['value'] ) ) {
											// Default array values.
											echo esc_html( implode( ', ', array_map( 'strval', $almgr_asset_row['value'] ) ) );
										} else {
											// Default (text, date, textarea, etc).
											echo esc_html( (string) $almgr_asset_row['value'] );
										}
										?>
									</dd>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
						<div class="almgr-asset-acf-row almgr-acf-asset-code">
							<dt class="almgr-asset-acf-label">
								<?php esc_html_e( 'Code', 'asset-lending-manager' ); ?>
							</dt>
							<dd class="almgr-asset-acf-value">
								<?php echo esc_html( $almgr_asset_code_str ); ?>
							</dd>
						</div>
						<!-- QR code row -->
						<div class="almgr-asset-acf-row almgr-acf-qr">
							<dt class="almgr-asset-acf-label">
								<?php esc_html_e( 'QR code', 'asset-lending-manager' ); ?>
							</dt>
							<dd class="almgr-asset-acf-value">
								<div class="almgr-qr-inline">
									<div
										class="almgr-qr-canvas almgr-qr-canvas--small"
										data-scan-url="<?php echo esc_url( $almgr_scan_url ); ?>"
										data-asset-code="<?php echo esc_attr( $almgr_asset_code_str ); ?>"
										aria-label="<?php esc_attr_e( 'QR code for this asset', 'asset-lending-manager' ); ?>"
										role="img"
									></div>
									<button type="button" class="almgr-button almgr-button--secondary almgr-qr-print">
										<?php esc_html_e( 'Print QR code', 'asset-lending-manager' ); ?>
									</button>
								</div>
							</dd>
						</div>
					</dl>
				<?php else : ?>
					<p class="almgr-muted"><?php esc_html_e( 'No additional details available.', 'asset-lending-manager' ); ?></p>
				<?php endif; ?>
			</div>
		</details>
	</section>

	<!-- V section: Loan request form (hidden for maintenance/retired assets and when loan requests are disabled) -->
	<?php if ( $almgr_loan_requests_enabled && ! $almgr_is_current_owner && in_array( $almgr_state_slug, array( 'available', 'on-loan' ), true ) ) : ?>
		<?php
		// Check if this component belongs to one or more kits.
		// Show only to users who can actually request a loan (logged-in members/operators).
		$almgr_parent_kits = isset( $asset->parent_kits ) ? $asset->parent_kits : array();
		if ( ! empty( $almgr_parent_kits ) && is_user_logged_in() && current_user_can( ALMGR_VIEW_ASSET ) ) :
			?>
			<div class="almgr-notice almgr-notice--info almgr-kit-notice" role="note">
				<strong><?php esc_html_e( 'Note:', 'asset-lending-manager' ); ?></strong>
				<?php
				$almgr_kit_links = array();
				foreach ( $almgr_parent_kits as $almgr_parent_kit ) {
					$almgr_kit_links[] = '<a class="almgr-link" href="' . esc_url( $almgr_parent_kit['permalink'] ) . '">'
						. esc_html( $almgr_parent_kit['title'] ) . '</a>';
				}
				if ( 1 === count( $almgr_kit_links ) ) {
					echo wp_kses_post(
						sprintf(
							/* translators: %s: kit name as a link. */
							__( 'This component is part of the kit %s. You may also request the entire kit instead.', 'asset-lending-manager' ),
							$almgr_kit_links[0]
						)
					);
				} else {
					echo wp_kses_post(
						sprintf(
							/* translators: %s: comma-separated kit names as links. */
							__( 'This component is part of the following kits: %s. You may also request an entire kit instead.', 'asset-lending-manager' ),
							implode( ', ', $almgr_kit_links )
						)
					);
				}
				?>
			</div>
		<?php endif; ?>
		<section class="almgr-asset-view__loan-request" aria-label="<?php esc_attr_e( 'Loan request', 'asset-lending-manager' ); ?>">
			<details class="almgr-collapsible almgr-collapsible--requestbutton" id="almgr-loan-request-section">
				<summary class="almgr-collapsible__summary">
					<span class="almgr-collapsible__title">
						<?php esc_html_e( 'Request loan', 'asset-lending-manager' ); ?>
					</span>
					<span class="almgr-collapsible__hint" aria-hidden="true">
						<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
					</span>
				</summary>
				<div class="almgr-collapsible__body">

					<?php if ( is_user_logged_in() && current_user_can( ALMGR_VIEW_ASSET ) ) : ?>
						<!-- People eligible to apply for the loan -->

						<?php if ( $almgr_loan_manager->has_pending_request( $almgr_asset_id, $almgr_current_user_id ) ) : ?>
							<p class="almgr-muted">
								<?php esc_html_e( 'You have already requested a loan for this asset.', 'asset-lending-manager' ); ?>
							</p>

						<?php else : ?>
							<form id="almgr-loan-request-form" class="almgr-loan-form">
								<?php wp_nonce_field( 'almgr_loan_request_nonce', 'nonce' ); ?>
								<div class="almgr-form-field">
									<label for="almgr-request-message">
										<?php esc_html_e( 'Message for the current owner:', 'asset-lending-manager' ); ?>
									</label>
									<textarea
										id="almgr-request-message"
										name="message"
										rows="4"
										maxlength="<?php echo esc_attr( $almgr_request_message_max ); ?>"
										placeholder="<?php esc_attr_e( 'Write a brief message explaining why you need this asset...', 'asset-lending-manager' ); ?>"
										aria-describedby="almgr-request-char-count"
										aria-required="true"
										required
									></textarea>
									<div class="almgr-char-count" id="almgr-request-char-count">0 / <?php echo esc_html( $almgr_request_message_max ); ?></div>
								</div>
								<div class="almgr-form-actions">
									<button type="submit" class="almgr-button almgr-button--primary">
										<?php esc_html_e( 'Send request', 'asset-lending-manager' ); ?>
									</button>
								</div>
								<div id="almgr-loan-request-response" class="almgr-response-message" role="status" aria-live="polite" style="display:none;"></div>
							</form>
						<?php endif; ?>

					<?php else : ?>
						<!-- People not eligible to apply for the loan -->
						<p class="almgr-muted">
							<?php esc_html_e( 'To request a loan, you must log in as a member.', 'asset-lending-manager' ); ?>
						</p>
					<?php endif; ?>

				</div>
			</details>
		</section>
	<?php endif; ?>

	<!-- VI section: Loan requests (hidden for maintenance/retired assets and when loan requests are disabled) -->
	<?php
	if (
				$almgr_loan_requests_enabled &&
				in_array( $almgr_state_slug, array( 'available', 'on-loan' ), true ) &&
				is_user_logged_in() &&
				( (int) $almgr_owner_id === (int) $almgr_current_user_id )
			) {
		$almgr_requests        = $almgr_loan_manager->get_asset_requests( $almgr_asset_id );
		$almgr_requester_names = array();
		$almgr_requester_ids   = array();

		foreach ( $almgr_requests as $almgr_request ) {
			$almgr_requester_id = absint( $almgr_request->requester_id );
			if ( $almgr_requester_id > 0 ) {
				$almgr_requester_ids[] = $almgr_requester_id;
			}
		}
		$almgr_requester_ids = array_unique( $almgr_requester_ids );
		if ( ! empty( $almgr_requester_ids ) ) {
			cache_users( $almgr_requester_ids );
			foreach ( $almgr_requester_ids as $almgr_requester_id ) {
				$almgr_requester_data = get_userdata( $almgr_requester_id );
				if ( $almgr_requester_data ) {
					$almgr_requester_names[ $almgr_requester_id ] = $almgr_requester_data->display_name;
				}
			}
		}
		?>
	<section class="almgr-asset-view__loan-requests" aria-label="<?php esc_attr_e( 'Loan requests', 'asset-lending-manager' ); ?>">
		<details class="almgr-collapsible almgr-collapsible--requestlist">
			<summary class="almgr-collapsible__summary">
				<span class="almgr-collapsible__title">
				<?php esc_html_e( 'Loan requests', 'asset-lending-manager' ); ?>
				</span>
				<span class="almgr-collapsible__hint" aria-hidden="true">
				<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>

			<div class="almgr-collapsible__body">
			<?php if ( ! empty( $almgr_requests ) ) : ?>
						<table class="almgr-requests-table almgr-responsive-table">
						<caption class="screen-reader-text">
							<?php esc_html_e( 'Pending loan requests for this asset', 'asset-lending-manager' ); ?>
						</caption>
						<thead role="rowgroup">
							<tr role="row">
								<th scope="col" role="columnheader"><?php esc_html_e( 'Requester', 'asset-lending-manager' ); ?></th>
								<th scope="col" role="columnheader"><?php esc_html_e( 'Message', 'asset-lending-manager' ); ?></th>
								<th scope="col" role="columnheader"><?php esc_html_e( 'Date', 'asset-lending-manager' ); ?></th>
								<th scope="col" role="columnheader"><?php esc_html_e( 'Actions', 'asset-lending-manager' ); ?></th>
							</tr>
						</thead>
						<tbody role="rowgroup">
								<?php foreach ( $almgr_requests as $almgr_request ) : ?>
									<?php
									$almgr_requester_id     = absint( $almgr_request->requester_id );
									$almgr_requester_name   = isset( $almgr_requester_names[ $almgr_requester_id ] )
										? $almgr_requester_names[ $almgr_requester_id ]
										: __( 'Unknown', 'asset-lending-manager' );
									$almgr_request_date     = mysql2date( 'd/m/Y', $almgr_request->request_date );
									$almgr_request_status   = $almgr_request->status;
									$almgr_full_message     = sanitize_text_field( (string) $almgr_request->request_message );
									$almgr_has_long_message = mb_strlen( $almgr_full_message ) > 80;
									$almgr_short_message    = $almgr_has_long_message
										? mb_substr( $almgr_full_message, 0, 80 ) . '...'
										: $almgr_full_message;
									/* translators: %s: loan requester display name. */
									$almgr_approve_label = sprintf( __( 'Approve request from %s', 'asset-lending-manager' ), $almgr_requester_name );
									/* translators: %s: loan requester display name. */
									$almgr_reject_label = sprintf( __( 'Reject request from %s', 'asset-lending-manager' ), $almgr_requester_name );
									?>
								<tr class="almgr-request-row" role="row" data-request-id="<?php echo esc_attr( $almgr_request->id ); ?>">
										<td class="almgr-request-requester" role="cell" data-label="<?php esc_attr_e( 'Requester', 'asset-lending-manager' ); ?>">
											<?php echo esc_html( $almgr_requester_name ); ?>
										</td>
										<td class="almgr-request-message" role="cell" data-label="<?php esc_attr_e( 'Message', 'asset-lending-manager' ); ?>">
											<p class="almgr-message-preview">
												<?php echo esc_html( $almgr_short_message ); ?>
											</p>
											<?php if ( $almgr_has_long_message ) : ?>
												<details class="almgr-message-details">
													<summary class="almgr-message-toggle">
														<span class="almgr-message-toggle-open">
															<?php esc_html_e( 'Read details', 'asset-lending-manager' ); ?>
														</span>
														<span class="almgr-message-toggle-close">
															<?php esc_html_e( 'Close message', 'asset-lending-manager' ); ?>
														</span>
													</summary>
													<div class="almgr-message-full">
														<?php echo esc_html( $almgr_full_message ); ?>
													</div>
												</details>
											<?php endif; ?>
										</td>
										<td class="almgr-request-date" role="cell" data-label="<?php esc_attr_e( 'Date', 'asset-lending-manager' ); ?>">
											<?php echo esc_html( $almgr_request_date ); ?>
										</td>
										<td class="almgr-request-actions" role="cell" data-label="<?php esc_attr_e( 'Actions', 'asset-lending-manager' ); ?>">
										<?php if ( 'pending' === $almgr_request_status && $almgr_is_current_owner ) : ?>
											<button
												type="button"
												class="almgr-button almgr-button--small almgr-button--approve"
												data-action="approve"
												data-request-id="<?php echo esc_attr( $almgr_request->id ); ?>"
												data-asset-id="<?php echo esc_attr( $almgr_asset_id ); ?>"
												aria-label="<?php echo esc_attr( $almgr_approve_label ); ?>"
											>
												<?php esc_html_e( 'Approve', 'asset-lending-manager' ); ?>
											</button>
											<button
												type="button"
												class="almgr-button almgr-button--small almgr-button--reject"
												data-action="reject"
												data-request-id="<?php echo esc_attr( $almgr_request->id ); ?>"
												data-asset-id="<?php echo esc_attr( $almgr_asset_id ); ?>"
												aria-label="<?php echo esc_attr( $almgr_reject_label ); ?>"
											>
												<?php esc_html_e( 'Reject', 'asset-lending-manager' ); ?>
											</button>
										<?php else : ?>
											<span class="almgr-muted">—</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="almgr-muted">
						<?php esc_html_e( 'No pending requests for this asset.', 'asset-lending-manager' ); ?>
					</p>
				<?php endif; ?>
			</div>

		</details>
	</section>
		<?php
	}
	?>

	<!-- VII section: Direct assignment (operator only, hidden for maintenance/retired assets) -->
	<?php if ( $almgr_is_operator && ! in_array( $almgr_state_slug, array( 'maintenance', 'retired' ), true ) ) : ?>
	<section class="almgr-asset-view__direct-assign" aria-label="<?php esc_attr_e( 'Direct assignment', 'asset-lending-manager' ); ?>">
		<details class="almgr-collapsible almgr-collapsible--directassign">
			<summary class="almgr-collapsible__summary">
				<span class="almgr-collapsible__title">
					<?php esc_html_e( 'Direct assignment', 'asset-lending-manager' ); ?>
				</span>
				<span class="almgr-collapsible__hint" aria-hidden="true">
					<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>
			<div class="almgr-collapsible__body">
				<form id="almgr-direct-assign-form" class="almgr-loan-form">
					<?php wp_nonce_field( 'almgr_direct_assign_nonce', 'almgr_direct_assign_nonce_field' ); ?>

					<div class="almgr-form-field">
						<label for="almgr-direct-assign-user-input">
							<?php esc_html_e( 'Assign to user:', 'asset-lending-manager' ); ?>
						</label>
						<div class="almgr-autocomplete-wrap">
							<span class="almgr-search-icon" aria-hidden="true"></span>
							<input
								type="text"
								id="almgr-direct-assign-user-input"
								autocomplete="off"
								placeholder="<?php esc_attr_e( 'Search member or operator...', 'asset-lending-manager' ); ?>"
							/>
							<input
								type="hidden"
								id="almgr-direct-assign-user-id"
								name="assignee_id"
								value=""
							/>
						</div>
					</div>

					<div class="almgr-form-field">
						<label for="almgr-direct-assign-reason">
							<?php esc_html_e( 'Reason:', 'asset-lending-manager' ); ?>
						</label>
						<textarea
							id="almgr-direct-assign-reason"
							name="reason"
							rows="3"
							maxlength="<?php echo esc_attr( $almgr_direct_assign_reason_max ); ?>"
							placeholder="<?php esc_attr_e( 'Explain the reason for this assignment...', 'asset-lending-manager' ); ?>"
							aria-describedby="almgr-direct-assign-char-count"
							aria-required="true"
							required
						></textarea>
						<div class="almgr-char-count" id="almgr-direct-assign-char-count">0 / <?php echo esc_html( $almgr_direct_assign_reason_max ); ?></div>
					</div>

					<div class="almgr-form-actions">
						<button type="submit" class="almgr-button almgr-button--primary">
							<?php esc_html_e( 'Assign asset', 'asset-lending-manager' ); ?>
						</button>
					</div>
					<div id="almgr-direct-assign-response" class="almgr-response-message" role="status" aria-live="polite" style="display:none;"></div>
				</form>
			</div>
		</details>
	</section>
	<?php endif; ?>

	<!-- VIII section: Asset state management (operator only) -->
	<?php if ( $almgr_is_operator ) : ?>
		<section class="almgr-asset-view__change-state" aria-label="<?php esc_attr_e( 'Asset state management', 'asset-lending-manager' ); ?>">
			<details class="almgr-collapsible almgr-collapsible--changestate">
				<summary class="almgr-collapsible__summary">
					<span class="almgr-collapsible__title">
						<?php esc_html_e( 'Asset state management', 'asset-lending-manager' ); ?>
					</span>
					<span class="almgr-collapsible__hint" aria-hidden="true">
						<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
					</span>
				</summary>
				<div class="almgr-collapsible__body">
					<?php if ( in_array( $almgr_state_slug, array( 'available', 'on-loan' ), true ) ) : ?>
						<!-- Sub-form: set to maintenance or retired -->
						<?php if ( 'on-loan' === $almgr_state_slug ) : ?>
							<p class="almgr-notice almgr-notice--warning">
								<?php
								$almgr_owner_display = $almgr_owner_name ? $almgr_owner_name : __( 'an unknown user', 'asset-lending-manager' );
								echo wp_kses_post(
									sprintf(
										/* translators: %s: current owner display name */
										__( '<strong>Warning:</strong> this asset is currently on loan to %s. Changing state will terminate the active loan.', 'asset-lending-manager' ),
										esc_html( $almgr_owner_display )
									)
								);
								?>
							</p>
						<?php endif; ?>
						<form id="almgr-change-state-form" class="almgr-loan-form" method="post">
							<?php wp_nonce_field( 'almgr_change_state_nonce', 'nonce' ); ?>
							<input type="hidden" name="asset_id" value="<?php echo esc_attr( $almgr_asset_id ); ?>" />
							<div class="almgr-form-field">
								<label for="almgr-change-state-location">
									<?php esc_html_e( 'Location (required):', 'asset-lending-manager' ); ?>
								</label>
								<input
									type="text"
									id="almgr-change-state-location"
									name="location"
									value="<?php echo esc_attr( $almgr_asset_location ); ?>"
									maxlength="255"
									required
									placeholder="<?php esc_attr_e( "e.g. Room A, shelf 3 or at David's house", 'asset-lending-manager' ); ?>"
								/>
							</div>
							<div class="almgr-form-field">
								<label for="almgr-change-state-notes">
									<?php esc_html_e( 'Notes (optional):', 'asset-lending-manager' ); ?>
								</label>
								<textarea
									id="almgr-change-state-notes"
									name="notes"
									rows="3"
									maxlength="<?php echo esc_attr( $almgr_change_state_notes_max ); ?>"
									placeholder="<?php esc_attr_e( 'Describe the reason for this state change...', 'asset-lending-manager' ); ?>"
									aria-describedby="almgr-change-state-char-count"
								></textarea>
								<div class="almgr-char-count" id="almgr-change-state-char-count">0 / <?php echo esc_html( $almgr_change_state_notes_max ); ?></div>
							</div>
							<div class="almgr-form-actions almgr-form-actions--row">
								<?php if ( 'on-loan' === $almgr_state_slug ) : ?>
									<button type="submit" class="almgr-button almgr-button--approve" data-target-state="available">
										<?php esc_html_e( 'Set to available', 'asset-lending-manager' ); ?>
									</button>
								<?php endif; ?>
								<button type="submit" class="almgr-button almgr-button--warning" data-target-state="maintenance">
									<?php esc_html_e( 'Set to maintenance', 'asset-lending-manager' ); ?>
								</button>
								<button type="submit" class="almgr-button almgr-button--danger" data-target-state="retired">
									<?php esc_html_e( 'Set to retired', 'asset-lending-manager' ); ?>
								</button>
							</div>
							<div id="almgr-change-state-response" class="almgr-response-message" role="status" aria-live="polite" style="display:none;"></div>
						</form>
					<?php elseif ( in_array( $almgr_state_slug, array( 'maintenance', 'retired' ), true ) ) : ?>
						<!-- Sub-form: restore to available -->
						<p class="almgr-notice almgr-notice--info">
							<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %s: current asset state label */
									__( 'This asset is currently <strong>%s</strong>. Restoring it will set it back to available.', 'asset-lending-manager' ),
									esc_html( $almgr_state_label )
								)
							);
							?>
						</p>
						<form id="almgr-restore-state-form" class="almgr-loan-form" method="post">
							<?php wp_nonce_field( 'almgr_restore_state_nonce', 'nonce' ); ?>
							<input type="hidden" name="asset_id" value="<?php echo esc_attr( $almgr_asset_id ); ?>" />
							<div class="almgr-form-field">
								<label for="almgr-restore-state-location">
									<?php esc_html_e( 'Location (required):', 'asset-lending-manager' ); ?>
								</label>
								<input
									type="text"
									id="almgr-restore-state-location"
									name="location"
									value="<?php echo esc_attr( $almgr_asset_location ); ?>"
									maxlength="255"
									required
									placeholder="<?php esc_attr_e( 'e.g. Room A, shelf 3', 'asset-lending-manager' ); ?>"
								/>
							</div>
							<div class="almgr-form-field">
								<label for="almgr-restore-state-notes">
									<?php esc_html_e( 'Notes (optional):', 'asset-lending-manager' ); ?>
								</label>
								<textarea
									id="almgr-restore-state-notes"
									name="notes"
									rows="3"
									maxlength="<?php echo esc_attr( $almgr_change_state_notes_max ); ?>"
									placeholder="<?php esc_attr_e( 'Describe the reason for restoring this asset...', 'asset-lending-manager' ); ?>"
									aria-describedby="almgr-restore-state-char-count"
								></textarea>
								<div class="almgr-char-count" id="almgr-restore-state-char-count">0 / <?php echo esc_html( $almgr_change_state_notes_max ); ?></div>
							</div>
							<div class="almgr-form-actions">
								<button type="submit" class="almgr-button almgr-button--approve">
									<?php esc_html_e( 'Make available', 'asset-lending-manager' ); ?>
								</button>
							</div>
							<div id="almgr-restore-state-response" class="almgr-response-message" role="status" aria-live="polite" style="display:none;"></div>
						</form>
					<?php endif; ?>
				</div>
			</details>
		</section>
	<?php endif; ?>

	<!-- IX section: Loan history -->
	<?php if ( is_user_logged_in() && current_user_can( ALMGR_EDIT_ASSET ) ) : ?>
		<section class="almgr-asset-view__loan-history" aria-label="<?php esc_attr_e( 'Loan history', 'asset-lending-manager' ); ?>">
			<details class="almgr-collapsible almgr-collapsible--history">
				<summary class="almgr-collapsible__summary">
					<span class="almgr-collapsible__title">
						<?php esc_html_e( 'Loan history', 'asset-lending-manager' ); ?>
					</span>
					<span class="almgr-collapsible__hint" aria-hidden="true">
						<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
					</span>
				</summary>

				<div class="almgr-collapsible__body">
					<?php
					// Get loan history for this asset.
					$almgr_history            = $almgr_loan_manager->get_asset_history( $almgr_asset_id, $almgr_current_user_id );
					$almgr_history_user_names = array();
					$almgr_history_user_ids   = array();

					foreach ( $almgr_history as $almgr_entry ) {
						$almgr_requester_id = absint( $almgr_entry->requester_id );
						$almgr_changed_by   = absint( $almgr_entry->changed_by );

						if ( $almgr_requester_id > 0 ) {
							$almgr_history_user_ids[] = $almgr_requester_id;
						}
						if ( $almgr_changed_by > 0 ) {
							$almgr_history_user_ids[] = $almgr_changed_by;
						}
					}
					$almgr_history_user_ids = array_unique( $almgr_history_user_ids );
					if ( ! empty( $almgr_history_user_ids ) ) {
						cache_users( $almgr_history_user_ids );
						foreach ( $almgr_history_user_ids as $almgr_history_user_id ) {
							$almgr_history_user_data = get_userdata( $almgr_history_user_id );
							if ( $almgr_history_user_data ) {
								$almgr_history_user_names[ $almgr_history_user_id ] = $almgr_history_user_data->display_name;
							}
						}
					}
					?>

					<?php if ( ! empty( $almgr_history ) ) : ?>
						<div class="almgr-history-limit-notice">
							<strong><?php esc_html_e( 'Note:', 'asset-lending-manager' ); ?></strong>
							<?php esc_html_e( 'Showing the last 10 loan history entries for this asset.', 'asset-lending-manager' ); ?>
						</div>

							<table class="almgr-history-table almgr-responsive-table">
							<caption class="screen-reader-text">
								<?php esc_html_e( 'Recent loan history entries for this asset', 'asset-lending-manager' ); ?>
							</caption>
							<thead role="rowgroup">
								<tr role="row">
									<th scope="col" role="columnheader"><?php esc_html_e( 'Recipient', 'asset-lending-manager' ); ?></th>
									<th scope="col" role="columnheader"><?php esc_html_e( 'Changed by', 'asset-lending-manager' ); ?></th>
									<th scope="col" role="columnheader"><?php esc_html_e( 'Request Date', 'asset-lending-manager' ); ?></th>
									<th scope="col" role="columnheader"><?php esc_html_e( 'Status', 'asset-lending-manager' ); ?></th>
									<th scope="col" role="columnheader"><?php esc_html_e( 'Message', 'asset-lending-manager' ); ?></th>
								</tr>
							</thead>
							<tbody role="rowgroup">
								<?php foreach ( $almgr_history as $almgr_entry ) : ?>
									<?php
										$almgr_requester_id    = absint( $almgr_entry->requester_id );
										$almgr_changed_by_id   = absint( $almgr_entry->changed_by );
										$almgr_requester_name  = isset( $almgr_history_user_names[ $almgr_requester_id ] )
											? $almgr_history_user_names[ $almgr_requester_id ]
											: __( 'Unknown', 'asset-lending-manager' );
										$almgr_changed_by_name = isset( $almgr_history_user_names[ $almgr_changed_by_id ] )
											? $almgr_history_user_names[ $almgr_changed_by_id ]
											: __( 'System', 'asset-lending-manager' );
									// Format dates.
									$almgr_request_date = isset( $almgr_entry->changed_at ) ? mysql2date( 'd/m/Y', $almgr_entry->changed_at ) : '-';
									// Get status.
									$almgr_entry_status = $almgr_entry->status;
									// Status labels and CSS classes.
									$almgr_loan_labels  = almgr_get_loan_status_labels();
									$almgr_status_label = $almgr_loan_labels[ $almgr_entry_status ] ?? $almgr_entry_status;
									$almgr_status_class = 'almgr-status--' . $almgr_entry_status;

									// Handle message (truncate for display, full in expandable details).
									$almgr_full_message     = sanitize_text_field( (string) $almgr_entry->message );
									$almgr_has_long_message = mb_strlen( $almgr_full_message ) > 80;
									$almgr_short_message    = $almgr_has_long_message
										? mb_substr( $almgr_full_message, 0, 80 ) . '...'
										: $almgr_full_message;
									?>
									<tr class="almgr-history-row" role="row">
										<td class="almgr-history-requester" role="cell" data-label="<?php esc_attr_e( 'Recipient', 'asset-lending-manager' ); ?>">
											<?php echo esc_html( $almgr_requester_name ); ?>
										</td>
										<td class="almgr-history-changed-by" role="cell" data-label="<?php esc_attr_e( 'Changed by', 'asset-lending-manager' ); ?>">
											<?php echo esc_html( $almgr_changed_by_name ); ?>
										</td>
										<td class="almgr-history-request-date" role="cell" data-label="<?php esc_attr_e( 'Request Date', 'asset-lending-manager' ); ?>">
											<?php echo esc_html( $almgr_request_date ); ?>
										</td>
										<td class="almgr-history-status" role="cell" data-label="<?php esc_attr_e( 'Status', 'asset-lending-manager' ); ?>">
											<span class="almgr-status-badge <?php echo esc_attr( $almgr_status_class ); ?>">
												<?php echo esc_html( $almgr_status_label ); ?>
											</span>
										</td>
											<td class="almgr-history-message" role="cell" data-label="<?php esc_attr_e( 'Message', 'asset-lending-manager' ); ?>">
												<p class="almgr-message-preview">
													<?php echo esc_html( $almgr_short_message ); ?>
												</p>
												<?php if ( $almgr_has_long_message ) : ?>
													<details class="almgr-message-details">
														<summary class="almgr-message-toggle">
															<span class="almgr-message-toggle-open">
																<?php esc_html_e( 'Read details', 'asset-lending-manager' ); ?>
															</span>
															<span class="almgr-message-toggle-close">
																<?php esc_html_e( 'Close message', 'asset-lending-manager' ); ?>
															</span>
														</summary>
														<div class="almgr-message-full">
															<?php echo esc_html( $almgr_full_message ); ?>
														</div>
													</details>
												<?php endif; ?>
											</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p class="almgr-history-empty almgr-muted">
							<?php esc_html_e( 'No loan history available for this asset.', 'asset-lending-manager' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</details>
		</section>
	<?php endif; ?>

</article>
