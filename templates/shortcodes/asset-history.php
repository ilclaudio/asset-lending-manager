<?php
/**
 * Template for full asset history shortcode.
 *
 * Variables injected from render_asset_history_template():
 * - $almgr_template_args array{
 *     almgr_asset_id:int,
 *     almgr_asset_title:string,
 *     almgr_asset_url:string,
 *     almgr_per_page:int,
 *     almgr_current_page:int,
 *     almgr_history:array,
 *     almgr_total:int,
 *     almgr_total_pages:int,
 *     almgr_qr_scan_enabled:bool
 *   }
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

$almgr_asset_id             = isset( $almgr_template_args['almgr_asset_id'] ) ? (int) $almgr_template_args['almgr_asset_id'] : 0;
$almgr_asset_title          = isset( $almgr_template_args['almgr_asset_title'] ) ? (string) $almgr_template_args['almgr_asset_title'] : '';
$almgr_per_page             = isset( $almgr_template_args['almgr_per_page'] ) ? (int) $almgr_template_args['almgr_per_page'] : 20;
$almgr_current_page         = isset( $almgr_template_args['almgr_current_page'] ) ? (int) $almgr_template_args['almgr_current_page'] : 1;
$almgr_history              = isset( $almgr_template_args['almgr_history'] ) && is_array( $almgr_template_args['almgr_history'] ) ? $almgr_template_args['almgr_history'] : array();
$almgr_total                = isset( $almgr_template_args['almgr_total'] ) ? (int) $almgr_template_args['almgr_total'] : 0;
$almgr_total_pages          = isset( $almgr_template_args['almgr_total_pages'] ) ? (int) $almgr_template_args['almgr_total_pages'] : 0;
$almgr_asset_url            = isset( $almgr_template_args['almgr_asset_url'] ) ? (string) $almgr_template_args['almgr_asset_url'] : '';
$almgr_qr_scan_enabled      = ! empty( $almgr_template_args['almgr_qr_scan_enabled'] );
$almgr_history_partial_path = trailingslashit( ALMGR_PLUGIN_DIR ) . 'templates/shortcodes/partials/asset-history-table.php';
$almgr_history_caption      = $almgr_asset_title
	? sprintf(
		/* translators: %s: asset title */
		__( 'Loan history entries for %s', 'asset-lending-manager' ),
		$almgr_asset_title
	)
	: __( 'Loan history entries', 'asset-lending-manager' );
?>

<section class="almgr-asset-history-page" aria-label="<?php esc_attr_e( 'Asset history', 'asset-lending-manager' ); ?>">
	<header class="almgr-asset-history-page__header">
		<h1 class="almgr-asset-title"><?php esc_html_e( 'Asset history', 'asset-lending-manager' ); ?></h1>
		<p class="almgr-muted">
			<?php esc_html_e( 'Search for an asset to browse its full loan history.', 'asset-lending-manager' ); ?>
		</p>
	</header>

	<div class="almgr-asset-history-filters" id="almgr_asset_history_form">
		<form method="get" class="almgr-asset-search-form" id="almgr-history-search-form">
			<input type="hidden" name="almgr_paged" value="1" />
			<div class="almgr-search-row">
				<div class="almgr-search-input-wrap almgr-autocomplete-wrap">
					<span class="almgr-search-icon" aria-hidden="true"></span>
					<input
						type="search"
						id="almgr-history-asset-input"
						value="<?php echo esc_attr( $almgr_asset_title ); ?>"
						placeholder="<?php esc_attr_e( 'Search and select an asset...', 'asset-lending-manager' ); ?>"
						autocomplete="off"
						aria-autocomplete="list"
						aria-expanded="false"
						aria-controls="almgr_history_asset_autocomplete_dropdown"
					/>
					<input
						type="hidden"
						id="almgr-history-asset-id"
						name="almgr_asset_id"
						value="<?php echo esc_attr( $almgr_asset_id ); ?>"
					/>
					<div
						id="almgr_history_asset_autocomplete_dropdown"
						class="almgr-autocomplete-dropdown"
						role="region"
						aria-live="polite"
						aria-label="<?php esc_attr_e( 'Asset suggestions', 'asset-lending-manager' ); ?>"
					></div>
				</div>
				<?php if ( $almgr_qr_scan_enabled ) : ?>
					<button
						type="button"
						class="almgr-button almgr-button--secondary almgr-qr-scan-btn"
						data-scan-dest="history"
						aria-label="<?php esc_attr_e( 'Scan QR code to find an asset', 'asset-lending-manager' ); ?>"
					>
						<?php esc_html_e( 'Scan QR', 'asset-lending-manager' ); ?>
					</button>
				<?php endif; ?>
			</div>
			<div class="almgr-form-actions">
				<button type="submit" class="almgr-search-submit-btn">
					<?php esc_html_e( 'Search', 'asset-lending-manager' ); ?>
				</button>
			</div>
		</form>
	</div>

	<?php if ( $almgr_asset_id > 0 ) : ?>
		<?php if ( $almgr_asset_title ) : ?>
			<p class="almgr-asset-history-page__asset-label">
				<?php esc_html_e( 'Asset:', 'asset-lending-manager' ); ?>
				<?php if ( $almgr_asset_url ) : ?>
					<a href="<?php echo esc_url( $almgr_asset_url ); ?>" class="almgr-asset-history-page__asset-link">
						<?php echo esc_html( $almgr_asset_title ); ?>
					</a>
				<?php else : ?>
					<?php echo esc_html( $almgr_asset_title ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<div class="almgr-asset-history-results">
			<?php if ( ! empty( $almgr_history ) ) : ?>
				<?php if ( file_exists( $almgr_history_partial_path ) ) : ?>
					<?php include $almgr_history_partial_path; ?>
				<?php endif; ?>

				<div class="almgr-history-per-page-row">
					<label for="almgr-history-per-page"><?php esc_html_e( 'Rows per page', 'asset-lending-manager' ); ?></label>
					<select
						id="almgr-history-per-page"
						name="almgr_per_page"
						form="almgr-history-search-form"
						onchange="this.form.requestSubmit()"
					>
						<?php foreach ( array( 10, 20, 50, 100, 200 ) as $almgr_page_size ) : ?>
							<option value="<?php echo esc_attr( $almgr_page_size ); ?>" <?php selected( $almgr_per_page, $almgr_page_size ); ?>>
								<?php echo esc_html( $almgr_page_size ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<?php if ( $almgr_total_pages > 1 ) : ?>
					<nav class="almgr-pagination" aria-label="<?php esc_attr_e( 'Asset history pagination', 'asset-lending-manager' ); ?>">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg( 'almgr_paged', '%#%' ),
									'format'    => '',
									'current'   => $almgr_current_page,
									'total'     => $almgr_total_pages,
									'prev_text' => '<span aria-hidden="true">&laquo;</span><span class="screen-reader-text">' . esc_html__( 'Previous page', 'asset-lending-manager' ) . '</span>',
									'next_text' => '<span aria-hidden="true">&raquo;</span><span class="screen-reader-text">' . esc_html__( 'Next page', 'asset-lending-manager' ) . '</span>',
								)
							)
						);
						?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<p class="almgr-history-empty almgr-muted">
					<?php esc_html_e( 'No loan history available for this asset.', 'asset-lending-manager' ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
