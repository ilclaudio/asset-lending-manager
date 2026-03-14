<?php
/**
 * Configuration data of the plugin.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

// Define constants.
define( 'ALM_VERSION', '0.1.0' );
define( 'ALM_PLUGIN_FILE', __FILE__ );
define( 'ALM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALM_TEXT_DOMAIN', 'asset-lending-manager' );

// Main menu settings.
define( 'ALM_SLUG_MAIN_MENU', 'alm' );

// Permissions.
define( 'ALM_VIEW_ASSETS', 'alm_view_assets' );
define( 'ALM_VIEW_ASSET', 'alm_view_asset' );
define( 'ALM_EDIT_ASSET', 'alm_edit_asset' );

// Asset CPT.
define( 'ALM_ASSET_CPT_SLUG', 'alm_asset' );
define( 'ALM_MAIN_MENU_ICON', 'dashicons-hammer' );
define( 'ALM_ASSET_ICON', 'dashicons-hammer' );

// Asset structures.
define( 'ALM_ASSET_KIT_SLUG', 'kit' );
define( 'ALM_ASSET_COMPONENT_SLUG', 'component' );

// Taxonomies.
define( 'ALM_ASSET_STRUCTURE_TAXONOMY_SLUG', 'alm_structure' );
define( 'ALM_ASSET_TYPE_TAXONOMY_SLUG', 'alm_type' );
define( 'ALM_ASSET_STATE_TAXONOMY_SLUG', 'alm_state' );
define( 'ALM_ASSET_LEVEL_TAXONOMY_SLUG', 'alm_level' );
define(
	'ALM_CUSTOM_TAXONOMIES',
	array(
		ALM_ASSET_STRUCTURE_TAXONOMY_SLUG,
		ALM_ASSET_TYPE_TAXONOMY_SLUG,
		ALM_ASSET_STATE_TAXONOMY_SLUG,
		ALM_ASSET_LEVEL_TAXONOMY_SLUG,
	)
);

// Roles and permissions.
define( 'ALM_MEMBER_ROLE', 'alm_member' );
define( 'ALM_OPERATOR_ROLE', 'alm_operator' );

// Autocomplete.
define( 'ALM_AUTOCOMPLETE_MAX_RESULTS', 5 );
define( 'ALM_AUTOCOMPLETE_DESC_LENGTH', 20 );

// Asset list.
define( 'ALM_ASSET_LIST_PER_PAGE', 12 );

// Asset identifier code.
// ALM_ASSET_CODE_PREFIX is the alphanumeric prefix used to build the human-readable asset code
// displayed in the frontend detail view (e.g. "AAGG-00000045").
// ALM_ASSET_CODE_FORMAT is the sprintf format: %s = prefix, %08d = ID zero-padded to 8 digits.
define( 'ALM_ASSET_CODE_PREFIX', 'AAGG' );
define( 'ALM_ASSET_CODE_FORMAT', '%s-%08d' );

// Email notifications — sender configuration.
// Set ALM_EMAIL_FROM_ADDRESS to a non-empty string to override the site admin email.
// Set ALM_EMAIL_SYSTEM_ADDRESS to receive operator-level copies on loan request submission.
define( 'ALM_EMAIL_FROM_NAME', 'AAGG Asset Manager' );
define( 'ALM_EMAIL_FROM_ADDRESS', '' ); // Falls back to get_bloginfo('admin_email') if empty.
define( 'ALM_EMAIL_SYSTEM_ADDRESS', '' ); // Operator notification address; disabled if empty.

// Email notifications — templates.
// Runtime placeholders are replaced before sending the email.
/**
 * Return the default email templates used by ALM notifications.
 *
 * @return array<string, array<string, string>>
 */
function alm_get_email_templates() {
	return array(
		'subject' => array(
			'request_to_requester'        => __( '[ALM] Loan request submitted: {ASSET_TITLE}', 'asset-lending-manager' ),
			'request_to_owner'            => __( '[ALM] New loan request received: {ASSET_TITLE}', 'asset-lending-manager' ),
			'approved'                    => __( '[ALM] Loan request approved: {ASSET_TITLE}', 'asset-lending-manager' ),
			'rejected'                    => __( '[ALM] Loan request rejected: {ASSET_TITLE}', 'asset-lending-manager' ),
			'canceled'                    => __( '[ALM] Loan request canceled: {ASSET_TITLE}', 'asset-lending-manager' ),
			'direct_assign'               => __( '[ALM] Asset assigned to you: {ASSET_TITLE}', 'asset-lending-manager' ),
			'direct_assign_to_prev_owner' => __( '[ALM] Asset reassigned: {ASSET_TITLE}', 'asset-lending-manager' ),
		),
		'body'    => array(
			'request_to_requester'        => __(
				"Hello {REQUESTER_NAME},\n\nYour loan request for \"{ASSET_TITLE}\" has been submitted and is pending approval.\n\nView asset: {ASSET_URL}\n\n-- ALM",
				'asset-lending-manager'
			),
			'request_to_owner'            => __(
				"Hello,\n\n{REQUESTER_NAME} has requested to borrow \"{ASSET_TITLE}\".\n\nMessage: {REQUEST_MESSAGE}\n\nView asset: {ASSET_URL}\n\n-- ALM",
				'asset-lending-manager'
			),
			'approved'                    => __(
				"Hello {REQUESTER_NAME},\n\nYour loan request for \"{ASSET_TITLE}\" has been approved.\n\nView asset: {ASSET_URL}\n\n-- ALM",
				'asset-lending-manager'
			),
			'rejected'                    => __(
				"Hello {REQUESTER_NAME},\n\nYour loan request for \"{ASSET_TITLE}\" has been rejected.\n\nReason: {REJECTION_MESSAGE}\n\nView asset: {ASSET_URL}\n\n-- ALM",
				'asset-lending-manager'
			),
			'canceled'                    => __(
				"Hello {REQUESTER_NAME},\n\nYour loan request for \"{ASSET_TITLE}\" has been automatically canceled because the asset was assigned to another user.\n\nView asset: {ASSET_URL}\n\n-- ALM",
				'asset-lending-manager'
			),
			'direct_assign'               => __(
				"Hello {ASSIGNEE_NAME},\n\nThe asset \"{ASSET_TITLE}\" has been assigned to you by {ACTOR_NAME}.\n\nReason: {REASON}\n\nView asset: {ASSET_URL}\n\n-- ALM",
				'asset-lending-manager'
			),
			'direct_assign_to_prev_owner' => __(
				"Hello {PREV_OWNER_NAME},\n\nThe asset \"{ASSET_TITLE}\" has been reassigned to {ASSIGNEE_NAME} by {ACTOR_NAME}.\n\nReason: {REASON}\n\nView asset: {ASSET_URL}\n\n-- ALM",
				'asset-lending-manager'
			),
		),
	);
}

/**
 * Return the translatable labels for loan request statuses.
 *
 * Returns an array keyed by status slug with the corresponding translated label.
 * Defined as a function (not a constant) so that __() is called at render time,
 * after the plugin text domain has been loaded.
 *
 * @return array<string, string>
 */
function alm_get_loan_status_labels() {
	return array(
		'pending'        => __( 'Pending', 'asset-lending-manager' ),
		'approved'       => __( 'Approved', 'asset-lending-manager' ),
		'rejected'       => __( 'Rejected', 'asset-lending-manager' ),
		'canceled'       => __( 'Canceled', 'asset-lending-manager' ),
		'direct_assign'  => __( 'Direct assignment', 'asset-lending-manager' ),
		'to_maintenance' => __( 'Set to maintenance', 'asset-lending-manager' ),
		'to_retired'     => __( 'Set to retired', 'asset-lending-manager' ),
		'to_available'   => __( 'Restored to available', 'asset-lending-manager' ),
	);
}
