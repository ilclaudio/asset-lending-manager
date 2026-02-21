<?php
/**
 * Template for asset view shortcode
 *
 * Available variables:
 * - $asset: Asset wrapper object from ALM_Asset_Manager::get_asset_wrapper()
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alm_current_user_id = get_current_user_id();
$alm_asset_id        = isset( $asset->id ) ? (int) $asset->id : 0;
if ( $alm_asset_id <= 0 ) {
	return;
}

$alm_asset_fields     = ALM_Asset_Manager::get_asset_custom_fields( $alm_asset_id );
$alm_loan_manager     = ALM_Plugin_Manager::get_instance()->get_module( 'loan' );
$alm_owner_id         = $alm_loan_manager->get_current_owner( $alm_asset_id );
$alm_asset_title      = isset( $asset->title ) ? (string) $asset->title : '';
$alm_asset_content    = isset( $asset->content ) ? (string) $asset->content : '';
$alm_owner_name       = '';
$alm_is_current_owner = is_user_logged_in() && $alm_owner_id > 0 && ( $alm_current_user_id === (int) $alm_owner_id );
$alm_is_operator      = is_user_logged_in() && current_user_can( ALM_EDIT_ASSET );
if ( $alm_owner_id > 0 ) {
	$alm_owner_data = get_userdata( $alm_owner_id );
	$alm_owner_name = $alm_owner_data ? $alm_owner_data->display_name : '';
}
/**
 * Big image for detail (do not change list thumbnail).
 */
$alm_detail_image_html = '';
if ( has_post_thumbnail( $alm_asset_id ) ) {
	$alm_detail_image_html = get_the_post_thumbnail( $alm_asset_id, 'large' );
} else {
	// Fallback to wrapper thumbnail (already includes plugin default image).
	$alm_detail_image_html = isset( $asset->thumbnail ) ? (string) $asset->thumbnail : '';
}
?>

<article class="alm-asset-detail alm-asset-view" data-asset-id="<?php echo esc_attr( $alm_asset_id ); ?>">

	<!-- I section: Title -->
	<header class="alm-asset-view__title">
		<h1 class="alm-asset-title"><?php echo esc_html( $alm_asset_title ); ?></h1>
	</header>

	<!-- II section: FOTO (sx) + Taxonomies box (dx) -->
	<section class="alm-asset-view__hero" aria-label="<?php esc_attr_e( 'Asset overview', 'asset-lending-manager' ); ?>">
		<!-- Asset foto -->
		<div class="alm-asset-view__media">
			<?php if ( $alm_detail_image_html ) : ?>
				<div class="alm-asset-thumbnail alm-asset-thumbnail--large">
					<?php echo wp_kses_post( $alm_detail_image_html ); ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Asset taxonomies -->
		<?php
		// Get taxonomy values.
		$alm_structure   = isset( $asset->alm_structure ) ? implode( ', ', $asset->alm_structure ) : '-';
		$alm_type        = isset( $asset->alm_type ) ? implode( ', ', $asset->alm_type ) : '-';
		$alm_level       = isset( $asset->alm_level ) ? implode( ', ', $asset->alm_level ) : '-';
		$alm_state_terms = get_the_terms( $alm_asset_id, 'alm_state' );
		$alm_state_slug  = '';
		$alm_state_label = '';
		if ( ! is_wp_error( $alm_state_terms ) && ! empty( $alm_state_terms ) ) {
			$alm_state_slug  = (string) $alm_state_terms[0]->slug;
			$alm_state_label = (string) $alm_state_terms[0]->name;
		}
		$alm_state_class_map = ALM_Asset_Manager::get_state_classes();
		$alm_state_css_class = '';
		if ( $alm_state_slug && isset( $alm_state_class_map[ $alm_state_slug ] ) ) {
			$alm_state_css_class = $alm_state_class_map[ $alm_state_slug ];
		}
		?>
		<aside class="alm-asset-view__taxbox" aria-label="<?php esc_attr_e( 'Asset taxonomies', 'asset-lending-manager' ); ?>">
			<div class="alm-asset-taxonomies alm-asset-taxonomies--boxed">
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php esc_html_e( 'Structure', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<?php echo esc_html( $alm_structure ); ?>
					</span>
				</div>
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php esc_html_e( 'Type', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<?php echo esc_html( $alm_type ); ?>
					</span>
				</div>
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php esc_html_e( 'Level', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<?php echo esc_html( $alm_level ); ?>
					</span>
				</div>
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php esc_html_e( 'State', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<span class="alm-availability <?php echo esc_attr( $alm_state_css_class ); ?>">
							<?php echo esc_html( $alm_state_label ); ?>
						</span>
					</span>
				</div>
			</div>
		</aside>
	</section>

	<!-- III section: Asset description -->
	<section class="alm-asset-view__content" aria-label="<?php esc_attr_e( 'Full description', 'asset-lending-manager' ); ?>">
		<div class="alm-asset-content">
			<?php echo wp_kses_post( $alm_asset_content ); ?>
		</div>
	</section>

	<!-- IV section: Asset optional fields -->
	<section class="alm-asset-view__acf" aria-label="<?php esc_attr_e( 'Additional fields', 'asset-lending-manager' ); ?>">
		<details class="alm-collapsible alm-collapsible--acf" open>
			<summary class="alm-collapsible__summary">
				<span class="alm-collapsible__title">
					<?php esc_html_e( 'Read details', 'asset-lending-manager' ); ?>
				</span>
				<span class="alm-collapsible__hint" aria-hidden="true">
					<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>
			<div class="alm-collapsible__body">
				<?php if ( ! empty( $alm_asset_fields ) ) : ?>
					<dl class="alm-asset-acf-list">
						<?php if ( is_user_logged_in() && ! empty( $alm_owner_name ) ) : ?>
							<div class="alm-asset-acf-row alm-acf-current-owner">
								<dt class="alm-asset-acf-label">
									<?php esc_html_e( 'Current owner', 'asset-lending-manager' ); ?>
								</dt>
								<dd class="alm-asset-acf-value">
									<?php echo esc_html( $alm_owner_name ); ?>
								</dd>
							</div>
						<?php endif; ?>
						<?php foreach ( $alm_asset_fields as $alm_asset_row ) : ?>
							<?php if ( $alm_asset_row['value'] ) : ?>
								<div class="alm-asset-acf-row alm-acf-<?php echo esc_attr( $alm_asset_row['name'] ); ?>">
									<dt class="alm-asset-acf-label">
										<?php echo esc_html( (string) $alm_asset_row['label'] ); ?>
									</dt>
									<dd class="alm-asset-acf-value">
										<?php
										// Render by type.
										if ( 'file' === $alm_asset_row['type'] && is_array( $alm_asset_row['value'] ) && ! empty( $alm_asset_row['value']['url'] ) ) {
											$alm_file_url  = (string) $alm_asset_row['value']['url'];
											$alm_file_name = ! empty( $alm_asset_row['value']['filename'] ) ? (string) $alm_asset_row['value']['filename'] : $alm_file_url;
											?>
											<a href="<?php echo esc_url( $alm_file_url ); ?>" class="alm-link" target="_blank" rel="noopener">
												<?php echo esc_html( $alm_file_name ); ?>
											</a>
											<?php
										} elseif ( 'post_object' === $alm_asset_row['type'] && is_array( $alm_asset_row['value'] ) ) {
											// Multiple components (objects).
											echo '<ul class="alm-asset-components">';
											foreach ( $alm_asset_row['value'] as $alm_component_post ) {
												if ( is_object( $alm_component_post ) && ! empty( $alm_component_post->ID ) ) {
													$alm_asset_title = get_the_title( $alm_component_post->ID );
													$alm_asset_link  = get_permalink( $alm_component_post->ID );
													echo '<li><a class="alm-link" href="' . esc_url( $alm_asset_link ) . '">' . esc_html( $alm_asset_title ) . '</a></li>';
												}
											}
											echo '</ul>';
										} elseif ( 'number' === $alm_asset_row['type'] ) {
											// Cost / numeric fields.
											echo esc_html( (string) $alm_asset_row['value'] );
										} elseif ( is_array( $alm_asset_row['value'] ) ) {
											// Default array values.
											echo esc_html( implode( ', ', array_map( 'strval', $alm_asset_row['value'] ) ) );
										} else {
											// Default (text, date, textarea, etc).
											echo esc_html( (string) $alm_asset_row['value'] );
										}
										?>
									</dd>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
						<div class="alm-asset-acf-row alm-acf-asset-code">
							<dt class="alm-asset-acf-label">
								<?php esc_html_e( 'Asset code', 'asset-lending-manager' ); ?>
							</dt>
							<dd class="alm-asset-acf-value">
								<?php echo esc_html( ALM_Asset_Manager::get_asset_code( $alm_asset_id ) ); ?>
							</dd>
						</div>
					</dl>
				<?php else : ?>
					<p class="alm-muted"><?php esc_html_e( 'No additional details available.', 'asset-lending-manager' ); ?></p>
				<?php endif; ?>
			</div>
		</details>
	</section>

	<!-- V section: Loan request form -->
	<?php if ( ! $alm_is_current_owner ) : ?>
		<section class="alm-asset-view__loan-request" aria-label="<?php esc_attr_e( 'Loan request', 'asset-lending-manager' ); ?>">
			<details class="alm-collapsible alm-collapsible--requestbutton" id="alm-loan-request-section">
				<summary class="alm-collapsible__summary">
					<span class="alm-collapsible__title">
						<?php esc_html_e( 'Request loan', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-collapsible__hint" aria-hidden="true">
						<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
					</span>
				</summary>
				<div class="alm-collapsible__body">

					<?php if ( is_user_logged_in() && current_user_can( ALM_VIEW_ASSET ) ) : ?>
						<!-- People eligible to apply for the loan -->

						<?php if ( $alm_loan_manager->has_pending_request( $alm_asset_id, $alm_current_user_id ) ) : ?>
							<p class="alm-muted">
								<?php esc_html_e( 'You have already requested a loan for this asset.', 'asset-lending-manager' ); ?>
							</p>

						<?php else : ?>
							<form id="alm-loan-request-form" class="alm-loan-form">
								<?php wp_nonce_field( 'alm_loan_request_nonce', 'nonce' ); ?>
								<div class="alm-form-field">
									<label for="alm-request-message">
										<?php esc_html_e( 'Message for the current owner:', 'asset-lending-manager' ); ?>
									</label>
									<textarea
										id="alm-request-message"
										name="message"
										rows="4"
										maxlength="500"
										placeholder="<?php esc_attr_e( 'Write a brief message explaining why you need this asset...', 'asset-lending-manager' ); ?>"
										required
									></textarea>
									<div class="alm-char-count" id="alm-request-char-count">0 / 500</div>
								</div>
								<div class="alm-form-actions">
									<button type="submit" class="alm-button alm-button--primary">
										<?php esc_html_e( 'Send request', 'asset-lending-manager' ); ?>
									</button>
								</div>
								<div id="alm-loan-request-response" class="alm-response-message" style="display:none;"></div>
							</form>
						<?php endif; ?>

					<?php else : ?>
						<!-- People not eligible to apply for the loan -->
						<p class="alm-muted">
							<?php esc_html_e( 'To request a loan, you must log in as a member.', 'asset-lending-manager' ); ?>
						</p>
					<?php endif; ?>

				</div>
			</details>
		</section>
	<?php endif; ?>

	<!-- VI section: Loan requests -->
	<?php
	if (
				is_user_logged_in() &&
				(
					$alm_is_operator ||
					( (int) $alm_owner_id === (int) $alm_current_user_id )
				)
			) {
		$alm_requests        = $alm_loan_manager->get_asset_requests( $alm_asset_id );
		$alm_requester_names = array();
		$alm_requester_ids   = array();

		foreach ( $alm_requests as $alm_request ) {
			$alm_requester_id = absint( $alm_request->requester_id );
			if ( $alm_requester_id > 0 ) {
				$alm_requester_ids[] = $alm_requester_id;
			}
		}
		$alm_requester_ids = array_unique( $alm_requester_ids );
		if ( ! empty( $alm_requester_ids ) ) {
			cache_users( $alm_requester_ids );
			foreach ( $alm_requester_ids as $alm_requester_id ) {
				$alm_requester_data = get_userdata( $alm_requester_id );
				if ( $alm_requester_data ) {
					$alm_requester_names[ $alm_requester_id ] = $alm_requester_data->display_name;
				}
			}
		}
		?>
	<section class="alm-asset-view__loan-requests" aria-label="<?php esc_attr_e( 'Loan requests', 'asset-lending-manager' ); ?>">
		<details class="alm-collapsible alm-collapsible--requestlist">
			<summary class="alm-collapsible__summary">
				<span class="alm-collapsible__title">
				<?php esc_html_e( 'Loan requests', 'asset-lending-manager' ); ?>
				</span>
				<span class="alm-collapsible__hint" aria-hidden="true">
				<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>

			<div class="alm-collapsible__body">
			<?php if ( ! empty( $alm_requests ) ) : ?>
					<table class="alm-requests-table">
						<caption class="screen-reader-text">
							<?php esc_html_e( 'Pending loan requests for this asset', 'asset-lending-manager' ); ?>
						</caption>
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Requester', 'asset-lending-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Message', 'asset-lending-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Date', 'asset-lending-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Actions', 'asset-lending-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $alm_requests as $alm_request ) : ?>
								<?php
								$alm_requester_id   = absint( $alm_request->requester_id );
								$alm_requester_name = isset( $alm_requester_names[ $alm_requester_id ] )
									? $alm_requester_names[ $alm_requester_id ]
									: __( 'Unknown', 'asset-lending-manager' );
								$alm_request_date   = mysql2date( 'd/m/Y', $alm_request->request_date );
								$alm_request_status = $alm_request->status;
								// Handle long messages.
								$alm_full_message  = sanitize_text_field( (string) $alm_request->request_message );
								$alm_short_message = mb_strlen( $alm_full_message ) > 60
									? mb_substr( $alm_full_message, 0, 60 ) . '...'
									: $alm_full_message;
								// Status label and CSS class.
								$alm_status_label = isset( ALM_LOAN_STATUS_LABELS[ $alm_request_status ] )
									? ALM_LOAN_STATUS_LABELS[ $alm_request_status ]
									: $alm_request_status;
								$alm_status_class = 'alm-status--' . $alm_request_status;
								?>
								<tr class="alm-request-row" data-request-id="<?php echo esc_attr( $alm_request->id ); ?>">
									<td class="alm-request-requester">
										<?php echo esc_html( $alm_requester_name ); ?>
									</td>
									<td class="alm-request-message">
										<span class="alm-message-text" title="<?php echo esc_attr( $alm_full_message ); ?>">
											<?php echo esc_html( $alm_short_message ); ?>
										</span>
									</td>
									<td class="alm-request-date">
										<?php echo esc_html( $alm_request_date ); ?>
									</td>
									<td class="alm-request-actions">
										<?php if ( 'pending' === $alm_request_status && $alm_is_current_owner ) : ?>
											<button 
												type="button" 
												class="alm-button alm-button--small alm-button--approve" 
												data-action="approve" 
												data-request-id="<?php echo esc_attr( $alm_request->id ); ?>"
												data-asset-id="<?php echo esc_attr( $alm_asset_id ); ?>"
											>
												<?php esc_html_e( 'Approve', 'asset-lending-manager' ); ?>
											</button>
											<button 
												type="button" 
												class="alm-button alm-button--small alm-button--reject" 
												data-action="reject" 
												data-request-id="<?php echo esc_attr( $alm_request->id ); ?>"
												data-asset-id="<?php echo esc_attr( $alm_asset_id ); ?>"
											>
												<?php esc_html_e( 'Reject', 'asset-lending-manager' ); ?>
											</button>
										<?php else : ?>
											<span class="alm-muted">—</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="alm-muted">
						<?php esc_html_e( 'No pending requests for this asset.', 'asset-lending-manager' ); ?>
					</p>
				<?php endif; ?>
			</div>

		</details>
	</section>
		<?php
	}
	?>

	<!-- VIII section: Direct assignment (operator only) -->
	<?php if ( $alm_is_operator ) : ?>
	<section class="alm-asset-view__direct-assign" aria-label="<?php esc_attr_e( 'Direct assignment', 'asset-lending-manager' ); ?>">
		<details class="alm-collapsible alm-collapsible--directassign">
			<summary class="alm-collapsible__summary">
				<span class="alm-collapsible__title">
					<?php esc_html_e( 'Direct assignment', 'asset-lending-manager' ); ?>
				</span>
				<span class="alm-collapsible__hint" aria-hidden="true">
					<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
				</span>
			</summary>
			<div class="alm-collapsible__body">
				<form id="alm-direct-assign-form" class="alm-loan-form">
					<?php wp_nonce_field( 'alm_direct_assign_nonce', 'alm_direct_assign_nonce_field' ); ?>

					<div class="alm-form-field">
						<label for="alm-direct-assign-user-input">
							<?php esc_html_e( 'Assign to user:', 'asset-lending-manager' ); ?>
						</label>
						<div class="alm-autocomplete-wrap">
							<span class="alm-search-icon" aria-hidden="true"></span>
							<input
								type="text"
								id="alm-direct-assign-user-input"
								autocomplete="off"
								placeholder="<?php esc_attr_e( 'Search member or operator...', 'asset-lending-manager' ); ?>"
							/>
							<input
								type="hidden"
								id="alm-direct-assign-user-id"
								name="assignee_id"
								value=""
							/>
						</div>
					</div>

					<div class="alm-form-field">
						<label for="alm-direct-assign-reason">
							<?php esc_html_e( 'Reason:', 'asset-lending-manager' ); ?>
						</label>
						<textarea
							id="alm-direct-assign-reason"
							name="reason"
							rows="3"
							maxlength="500"
							placeholder="<?php esc_attr_e( 'Explain the reason for this assignment...', 'asset-lending-manager' ); ?>"
							required
						></textarea>
						<div class="alm-char-count" id="alm-direct-assign-char-count">0 / 500</div>
					</div>

					<div class="alm-form-actions">
						<button type="submit" class="alm-button alm-button--primary">
							<?php esc_html_e( 'Assign asset', 'asset-lending-manager' ); ?>
						</button>
					</div>
					<div id="alm-direct-assign-response" class="alm-response-message" style="display:none;"></div>
				</form>
			</div>
		</details>
	</section>
	<?php endif; ?>

	<!-- VII section: Loan history -->
	<?php if ( is_user_logged_in() && current_user_can( ALM_EDIT_ASSET ) ) : ?>
		<section class="alm-asset-view__loan-history" aria-label="<?php esc_attr_e( 'Loan history', 'asset-lending-manager' ); ?>">
			<details class="alm-collapsible alm-collapsible--history">
				<summary class="alm-collapsible__summary">
					<span class="alm-collapsible__title">
						<?php esc_html_e( 'Loan history', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-collapsible__hint" aria-hidden="true">
						<?php esc_html_e( 'Open/Close', 'asset-lending-manager' ); ?>
					</span>
				</summary>

				<div class="alm-collapsible__body">
					<?php
					// Get loan history for this asset.
					$alm_history            = $alm_loan_manager->get_asset_history( $alm_asset_id, $alm_current_user_id );
					$alm_history_user_names = array();
					$alm_history_user_ids   = array();

					foreach ( $alm_history as $alm_entry ) {
						$alm_requester_id = absint( $alm_entry->requester_id );
						$alm_changed_by   = absint( $alm_entry->changed_by );

						if ( $alm_requester_id > 0 ) {
							$alm_history_user_ids[] = $alm_requester_id;
						}
						if ( $alm_changed_by > 0 ) {
							$alm_history_user_ids[] = $alm_changed_by;
						}
					}
					$alm_history_user_ids = array_unique( $alm_history_user_ids );
					if ( ! empty( $alm_history_user_ids ) ) {
						cache_users( $alm_history_user_ids );
						foreach ( $alm_history_user_ids as $alm_history_user_id ) {
							$alm_history_user_data = get_userdata( $alm_history_user_id );
							if ( $alm_history_user_data ) {
								$alm_history_user_names[ $alm_history_user_id ] = $alm_history_user_data->display_name;
							}
						}
					}
					?>

					<?php if ( ! empty( $alm_history ) ) : ?>
						<div class="alm-history-limit-notice">
							<strong><?php esc_html_e( 'Note:', 'asset-lending-manager' ); ?></strong>
							<?php esc_html_e( 'Showing the last 10 loan history entries for this asset.', 'asset-lending-manager' ); ?>
						</div>

						<table class="alm-history-table">
							<caption class="screen-reader-text">
								<?php esc_html_e( 'Recent loan history entries for this asset', 'asset-lending-manager' ); ?>
							</caption>
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Recipient', 'asset-lending-manager' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Changed by', 'asset-lending-manager' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Request Date', 'asset-lending-manager' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Status', 'asset-lending-manager' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Message', 'asset-lending-manager' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $alm_history as $alm_entry ) : ?>
									<?php
										$alm_requester_id    = absint( $alm_entry->requester_id );
										$alm_changed_by_id   = absint( $alm_entry->changed_by );
										$alm_requester_name  = isset( $alm_history_user_names[ $alm_requester_id ] )
											? $alm_history_user_names[ $alm_requester_id ]
											: __( 'Unknown', 'asset-lending-manager' );
										$alm_changed_by_name = isset( $alm_history_user_names[ $alm_changed_by_id ] )
											? $alm_history_user_names[ $alm_changed_by_id ]
											: __( 'System', 'asset-lending-manager' );
									// Format dates.
									$alm_request_date = isset( $alm_entry->changed_at ) ? mysql2date( 'd/m/Y', $alm_entry->changed_at ) : '-';
									// Get status.
									$alm_entry_status = $alm_entry->status;
									// Status labels and CSS classes.
									$alm_status_label = isset( ALM_LOAN_STATUS_LABELS[ $alm_entry_status ] )
										? ALM_LOAN_STATUS_LABELS[ $alm_entry_status ]
										: $alm_entry_status;
									$alm_status_class = 'alm-status--' . $alm_entry_status;

									// Handle message (truncate for display, full in tooltip).
									$alm_full_message  = sanitize_text_field( (string) $alm_entry->message );
									$alm_short_message = mb_strlen( $alm_full_message ) > 50
										? mb_substr( $alm_full_message, 0, 50 ) . '...'
										: $alm_full_message;
									?>
									<tr class="alm-history-row">
										<td class="alm-history-requester" data-label="<?php esc_attr_e( 'Recipient', 'asset-lending-manager' ); ?>">
											<?php echo esc_html( $alm_requester_name ); ?>
										</td>
										<td class="alm-history-changed-by" data-label="<?php esc_attr_e( 'Changed by', 'asset-lending-manager' ); ?>">
											<?php echo esc_html( $alm_changed_by_name ); ?>
										</td>
										<td class="alm-history-request-date" data-label="<?php esc_attr_e( 'Request Date', 'asset-lending-manager' ); ?>">
											<?php echo esc_html( $alm_request_date ); ?>
										</td>
										<td class="alm-history-status" data-label="<?php esc_attr_e( 'Status', 'asset-lending-manager' ); ?>">
											<span class="alm-status-badge <?php echo esc_attr( $alm_status_class ); ?>">
												<?php echo esc_html( $alm_status_label ); ?>
											</span>
										</td>
										<td class="alm-history-message" data-label="<?php esc_attr_e( 'Message', 'asset-lending-manager' ); ?>">
											<span class="alm-message-text" title="<?php echo esc_attr( $alm_full_message ); ?>">
												<?php echo esc_html( $alm_short_message ); ?>
											</span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p class="alm-history-empty alm-muted">
							<?php esc_html_e( 'No loan history available for this asset.', 'asset-lending-manager' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</details>
		</section>
	<?php endif; ?>

</article>
