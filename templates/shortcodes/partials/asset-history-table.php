<?php
/**
 * Shared asset history table partial.
 *
 * Expected variables:
 * - $almgr_history         array  History rows.
 * - $almgr_history_caption string Optional table caption.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

$almgr_history_caption    = isset( $almgr_history_caption ) ? (string) $almgr_history_caption : __( 'Loan history entries', 'asset-lending-manager' );
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
<table class="almgr-history-table almgr-responsive-table">
	<caption class="screen-reader-text">
		<?php echo esc_html( $almgr_history_caption ); ?>
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
			$almgr_requester_id     = absint( $almgr_entry->requester_id );
			$almgr_changed_by_id    = absint( $almgr_entry->changed_by );
			$almgr_requester_name   = isset( $almgr_history_user_names[ $almgr_requester_id ] )
				? $almgr_history_user_names[ $almgr_requester_id ]
				: __( 'Unknown', 'asset-lending-manager' );
			$almgr_changed_by_name  = isset( $almgr_history_user_names[ $almgr_changed_by_id ] )
				? $almgr_history_user_names[ $almgr_changed_by_id ]
				: __( 'System', 'asset-lending-manager' );
			$almgr_request_date     = isset( $almgr_entry->changed_at ) ? mysql2date( 'd/m/Y', $almgr_entry->changed_at ) : '-';
			$almgr_entry_status     = $almgr_entry->status;
			$almgr_loan_labels      = almgr_get_loan_status_labels();
			$almgr_status_label     = $almgr_loan_labels[ $almgr_entry_status ] ?? $almgr_entry_status;
			$almgr_status_class     = 'almgr-status--' . $almgr_entry_status;
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
