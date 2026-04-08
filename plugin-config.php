<?php
/**
 * Configuration data of the plugin.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

// Define constants.
define( 'ALMGR_VERSION', '0.2.1' );
define( 'ALMGR_PLUGIN_FILE', __FILE__ );
define( 'ALMGR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALMGR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALMGR_TEXT_DOMAIN', 'asset-lending-manager' );

// Main menu settings.
define( 'ALMGR_SLUG_MAIN_MENU', 'almgr' );

// Permissions.
define( 'ALMGR_VIEW_ASSETS', 'almgr_view_assets' );
define( 'ALMGR_VIEW_ASSET', 'almgr_view_asset' );
define( 'ALMGR_EDIT_ASSET', 'almgr_edit_asset' );

// Asset CPT.
define( 'ALMGR_ASSET_CPT_SLUG', 'almgr_asset' );
define( 'ALMGR_MAIN_MENU_ICON', 'dashicons-hammer' );
define( 'ALMGR_ASSET_ICON', 'dashicons-hammer' );

// Asset structures.
define( 'ALMGR_ASSET_KIT_SLUG', 'kit' );
define( 'ALMGR_ASSET_COMPONENT_SLUG', 'component' );

// Taxonomies.
define( 'ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG', 'almgr_structure' );
define( 'ALMGR_ASSET_TYPE_TAXONOMY_SLUG', 'almgr_type' );
define( 'ALMGR_ASSET_STATE_TAXONOMY_SLUG', 'almgr_state' );
define( 'ALMGR_ASSET_LEVEL_TAXONOMY_SLUG', 'almgr_level' );
define(
	'ALMGR_CUSTOM_TAXONOMIES',
	array(
		ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG,
		ALMGR_ASSET_TYPE_TAXONOMY_SLUG,
		ALMGR_ASSET_STATE_TAXONOMY_SLUG,
		ALMGR_ASSET_LEVEL_TAXONOMY_SLUG,
	)
);

// Roles and permissions.
define( 'ALMGR_MEMBER_ROLE', 'almgr_member' );
define( 'ALMGR_OPERATOR_ROLE', 'almgr_operator' );

// Autocomplete.
define( 'ALMGR_AUTOCOMPLETE_MAX_RESULTS', 5 );
define( 'ALMGR_AUTOCOMPLETE_DESC_LENGTH', 20 );

// Asset list.
define( 'ALMGR_ASSET_LIST_PER_PAGE', 12 );

// Asset identifier code.
// ALMGR_ASSET_CODE_PREFIX is the alphanumeric prefix used to build the human-readable asset code
// displayed in the frontend detail view (e.g. "ALMGR-00000045").
// ALMGR_ASSET_CODE_FORMAT is the sprintf format: %s = prefix, %08d = ID zero-padded to 8 digits.
define( 'ALMGR_ASSET_CODE_PREFIX', 'ALMGR' );
define( 'ALMGR_ASSET_CODE_FORMAT', '%s-%08d' );

// Email notifications — sender configuration.
// Set ALMGR_EMAIL_FROM_ADDRESS to a non-empty string to override the site admin email.
// Set ALMGR_EMAIL_SYSTEM_ADDRESS to receive operator-level copies on loan request submission.
define( 'ALMGR_EMAIL_FROM_NAME', '' ); // Falls back to get_bloginfo('name') if empty.
define( 'ALMGR_EMAIL_FROM_ADDRESS', '' ); // Falls back to get_bloginfo('admin_email') if empty.
define( 'ALMGR_EMAIL_SYSTEM_ADDRESS', '' ); // Operator notification address; disabled if empty.

// Email notifications — templates.
// Runtime placeholders are replaced before sending the email.
/**
 * Return the default email templates used by ALMGR notifications.
 *
 * @return array<string, array<string, string>>
 */
function almgr_get_email_templates() {
	return array(
		'subject' => array(
			'request_to_requester'        => __( '[ALM] Loan request submitted: {ASSET_TITLE}', 'asset-lending-manager' ),
			'request_to_owner'            => __( '[ALM] New loan request received: {ASSET_TITLE}', 'asset-lending-manager' ),
			'approved'                    => __( '[ALM] Loan request approved: {ASSET_TITLE}', 'asset-lending-manager' ),
			'rejected'                    => __( '[ALM] Loan request rejected: {ASSET_TITLE}', 'asset-lending-manager' ),
			'canceled'                    => __( '[ALM] Loan request canceled: {ASSET_TITLE}', 'asset-lending-manager' ),
			'direct_assign'               => __( '[ALM] Asset assigned to you: {ASSET_TITLE}', 'asset-lending-manager' ),
			'direct_assign_to_prev_owner' => __( '[ALM] Asset reassigned: {ASSET_TITLE}', 'asset-lending-manager' ),
			'force_return'                => __( '[ALM] Asset returned: {ASSET_TITLE}', 'asset-lending-manager' ),
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
			'force_return'                => __(
				"Hello {BORROWER_NAME},\n\nThe loan for \"{ASSET_TITLE}\" has been closed by the operator {ACTOR_NAME}.\n\nNotes: {NOTES}\n\nView asset: {ASSET_URL}\n\n-- ALM",
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
function almgr_get_loan_status_labels() {
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
