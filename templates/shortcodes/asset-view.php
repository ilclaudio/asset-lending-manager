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

$alm_asset_fields = ALM_Asset_Manager::get_asset_custom_fields( $alm_asset_id );
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
		<h1 class="alm-asset-title"><?php echo esc_html( $asset->title ); ?></h1>
	</header>

	<!-- II section: FOTO (sx) + Taxonomies box (dx) -->
	<section class="alm-asset-view__hero" aria-label="<?php esc_attr_e( 'Asset overview', 'asset-lending-manager' ); ?>">
		<!-- Asset foto -->
		<div class="alm-asset-view__media">
			<?php if ( $alm_detail_image_html ) : ?>
				<div class="alm-asset-thumbnail alm-asset-thumbnail--large">
					<?php echo $alm_detail_image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Asset taxonomies -->
		<?php
		// Get taxonomy values.
		$alm_structure   = isset( $asset->alm_structure ) ? implode( ', ', $asset->alm_structure ) : '-';
		$alm_type        = isset( $asset->alm_type ) ? implode( ', ', $asset->alm_type ) : '-';
		$alm_level       = isset( $asset->alm_level ) ? implode( ', ', $asset->alm_level ) : '-';
		$alm_state_terms = get_the_terms( $asset_id, 'alm_state' );
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
						<?php echo esc_attr_e( 'Structure', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<?php echo esc_attr( $alm_structure ); ?>
					</span>
				</div>
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php echo esc_attr_e( 'Type', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<?php echo esc_attr( $alm_type ); ?>
					</span>
				</div>
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php echo esc_attr_e( 'Level', 'asset-lending-manager' ); ?>
					</span>
					<span class="alm-tax-value">
						<?php echo esc_attr( $alm_level ); ?>
					</span>
				</div>
				<div class="alm-asset-tax-row">
					<span class="alm-tax-label">
						<?php echo esc_attr_e( 'State', 'asset-lending-manager' ); ?>
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
			<?php echo $asset->content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
						<?php foreach ( $alm_asset_fields as $alm_asset_row ) : ?>
							<?php if ( $alm_asset_row['value'] ): ?>
								<div class="alm-asset-acf-row alm-acf-<?php echo esc_attr( $alm_asset_row['name'] ); ?>">
									<dt class="alm-asset-acf-label">
										<?php echo esc_attr_e( $alm_asset_row['label'], 'asset-lending-manager' ); ?>
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
										} else {
											// Default (text, date, textarea, etc).
											if ( is_array( $alm_asset_row['value'] ) ) {
												echo esc_html( implode( ', ', array_map( 'strval', $alm_asset_row['value'] ) ) );
											} else {
												echo esc_html( (string) $alm_asset_row['value'] );
											}
										}
										?>
									</dd>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</dl>
				<?php else : ?>
					<p class="alm-muted"><?php esc_html_e( 'No additional details available.', 'asset-lending-manager' ); ?></p>
				<?php endif; ?>
			</div>
		</details>
	</section>

	<!-- V section: Loan request form -->
	<?php
		$alm_loan_manager = ALM_Plugin_Manager::get_instance()->get_module( 'loan' );
	?>

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

	<!-- VI section: Loan requests -->
	<?php
		if (
				is_user_logged_in() &&
				(
					current_user_can( ALM_EDIT_ASSET ) ||
					( $alm_loan_manager->get_current_owner( $alm_asset_id ) === $alm_current_user_id )
				)
			) {
			$alm_requests = $alm_loan_manager->get_asset_requests( $alm_asset_id );
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
						<thead>
							<tr>
								<th><?php esc_html_e( 'Requester', 'asset-lending-manager' ); ?></th>
								<th><?php esc_html_e( 'Message', 'asset-lending-manager' ); ?></th>
								<th><?php esc_html_e( 'Date', 'asset-lending-manager' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'asset-lending-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $alm_requests as $alm_request ) : ?>
								<?php
								$alm_requester      = get_userdata( $alm_request->requester_id );
								$alm_requester_name = $alm_requester ? $alm_requester->display_name : __( 'Unknown', 'asset-lending-manager' );
								$alm_request_date   = mysql2date( 'd/m/Y', $alm_request->request_date );
								$alm_request_status = $alm_request->status;
								// Handle long messages.
								$alm_full_message  = esc_html( $alm_request->request_message );
								$alm_short_message = mb_strlen( $alm_full_message ) > 60
									? mb_substr( $alm_full_message, 0, 60 ) . '...'
									: $alm_full_message;
								// Status label and CSS class.
								$alm_status_labels = array(
									'pending'  => __( 'Pending', 'asset-lending-manager' ),
									'approved' => __( 'Approved', 'asset-lending-manager' ),
									'rejected' => __( 'Rejected', 'asset-lending-manager' ),
									'canceled' => __( 'Canceled', 'asset-lending-manager' ),
								);
								$alm_status_label = isset( $alm_status_labels[ $alm_request_status ] ) ? $alm_status_labels[ $alm_request_status ] : $alm_request_status;
								$alm_status_class = 'alm-status--' . $alm_request_status;
								?>
								<tr class="alm-request-row" data-request-id="<?php echo esc_attr( $alm_request->id ); ?>">
									<td class="alm-request-requester">
										<?php echo esc_html( $alm_requester_name ); ?>
									</td>
									<td class="alm-request-message">
										<span class="alm-message-text" title="<?php echo esc_attr( $alm_full_message ); ?>">
											<?php echo $alm_short_message; ?>
										</span>
									</td>
									<td class="alm-request-date">
										<?php echo esc_html( $alm_request_date ); ?>
									</td>
									<td class="alm-request-actions">
										<?php if ( 'pending' === $alm_request_status ) : ?>
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

	<?php if ( is_user_logged_in() && current_user_can( ALM_EDIT_ASSET ) ) : ?>
		<!-- VII section: Loan history -->
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
					<p class="alm-muted">
						<?php esc_html_e( 'History not available (section under development).', 'asset-lending-manager' ); ?>
					</p>
				</div>
			</details>
		</section>
	<?php endif; ?>

</article>
