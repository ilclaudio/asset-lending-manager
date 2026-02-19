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

// Email notifications — sender configuration.
// Set ALM_EMAIL_FROM_ADDRESS to a non-empty string to override the site admin email.
// Set ALM_EMAIL_SYSTEM_ADDRESS to receive operator-level copies on loan request submission.
define( 'ALM_EMAIL_FROM_NAME',      'AAGG Asset Manager' );
define( 'ALM_EMAIL_FROM_ADDRESS',   '' ); // Falls back to get_bloginfo('admin_email') if empty.
define( 'ALM_EMAIL_SYSTEM_ADDRESS', '' ); // Operator notification address; disabled if empty.

// Email notifications — subject templates.
// Each subject may contain the {ASSET_TITLE} placeholder, replaced at send time.
// Note: these constants are used as translation msgids in ALM_Notification_Manager
// via __( CONSTANT, 'asset-lending-manager' ); add them manually to the .pot file.
define( 'ALM_EMAIL_SUBJECT_REQUEST_TO_REQUESTER', '[ALM] Loan request submitted: {ASSET_TITLE}' );
define( 'ALM_EMAIL_SUBJECT_REQUEST_TO_OWNER',     '[ALM] New loan request received: {ASSET_TITLE}' );
define( 'ALM_EMAIL_SUBJECT_APPROVED',             '[ALM] Loan request approved: {ASSET_TITLE}' );
define( 'ALM_EMAIL_SUBJECT_REJECTED',             '[ALM] Loan request rejected: {ASSET_TITLE}' );
define( 'ALM_EMAIL_SUBJECT_CANCELED',             '[ALM] Loan request canceled: {ASSET_TITLE}' );
define( 'ALM_EMAIL_SUBJECT_DIRECT_ASSIGN',        '[ALM] Asset assigned to you: {ASSET_TITLE}' );

// Email notifications — body templates (plain text).
// Supported placeholders: {REQUESTER_NAME}, {ASSIGNEE_NAME}, {ACTOR_NAME},
// {ASSET_TITLE}, {ASSET_URL}, {REQUEST_MESSAGE}, {REJECTION_MESSAGE}, {REASON}.
// Note: same i18n consideration as subjects above applies.
define(
	'ALM_EMAIL_BODY_REQUEST_TO_REQUESTER',
	"Hello {REQUESTER_NAME},\n\n" .
	"Your loan request for \"{ASSET_TITLE}\" has been submitted and is pending approval.\n\n" .
	"View asset: {ASSET_URL}\n\n" .
	"-- ALM"
);
define(
	'ALM_EMAIL_BODY_REQUEST_TO_OWNER',
	"Hello,\n\n" .
	"{REQUESTER_NAME} has requested to borrow \"{ASSET_TITLE}\".\n\n" .
	"Message: {REQUEST_MESSAGE}\n\n" .
	"View asset: {ASSET_URL}\n\n" .
	"-- ALM"
);
define(
	'ALM_EMAIL_BODY_APPROVED',
	"Hello {REQUESTER_NAME},\n\n" .
	"Your loan request for \"{ASSET_TITLE}\" has been approved.\n\n" .
	"View asset: {ASSET_URL}\n\n" .
	"-- ALM"
);
define(
	'ALM_EMAIL_BODY_REJECTED',
	"Hello {REQUESTER_NAME},\n\n" .
	"Your loan request for \"{ASSET_TITLE}\" has been rejected.\n\n" .
	"Reason: {REJECTION_MESSAGE}\n\n" .
	"View asset: {ASSET_URL}\n\n" .
	"-- ALM"
);
define(
	'ALM_EMAIL_BODY_CANCELED',
	"Hello {REQUESTER_NAME},\n\n" .
	"Your loan request for \"{ASSET_TITLE}\" has been automatically canceled " .
	"because the asset was assigned to another user.\n\n" .
	"View asset: {ASSET_URL}\n\n" .
	"-- ALM"
);
define(
	'ALM_EMAIL_BODY_DIRECT_ASSIGN',
	"Hello {ASSIGNEE_NAME},\n\n" .
	"The asset \"{ASSET_TITLE}\" has been assigned to you by {ACTOR_NAME}.\n\n" .
	"Reason: {REASON}\n\n" .
	"View asset: {ASSET_URL}\n\n" .
	"-- ALM"
);

// Loan statuses.
define(
	'ALM_LOAN_STATUS_LABELS',
	array(
		'pending'       => 'Pending',
		'approved'      => 'Approved',
		'rejected'      => 'Rejected',
		'canceled'      => 'Canceled',
		'direct_assign' => 'Direct assignment',
	)
);
