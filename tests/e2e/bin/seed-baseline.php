<?php
/**
 * Idempotent baseline fixture seeder for the `alm-e2e` site.
 *
 * Run via WP-CLI against a WordPress site with the plugin active:
 *
 *   wp eval-file tests/e2e/bin/seed-baseline.php --path=<alm-e2e root>
 *
 * Creates (or repairs, if already present) the fixture described in
 * `DEV/TODO/TODO_ResetPlan.md`, Fase 2 (decisions 7-9):
 *   - 1 operator + 2 members, known usernames/passwords (see USERS below);
 *   - 1 available component;
 *   - 1 kit with 2 available components;
 *   - 1 additional component already on loan to the first member.
 *
 * Safe to re-run: every entity is looked up by a stable identifier (username,
 * or post slug) before being created, and taxonomy/meta assignments are
 * re-applied unconditionally on every run so drift self-heals.
 *
 * This script only seeds content; it does not touch unrelated existing users
 * or posts on the target site. Producing the actual `tests/e2e/fixtures/baseline.sql`
 * dump (Fase 3) requires running this against a otherwise-clean `alm-e2e` site.
 *
 * @package AssetLendingManager
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run through WP-CLI (wp eval-file).\n";
	exit( 1 );
}

/**
 * Users to create. Passwords are intentionally simple and documented in
 * tests/e2e/README.md: `alm-e2e` only ever holds synthetic fixture data
 * (see TODO_ResetPlan.md, decision 3), so there is no real secret to protect.
 * Override at test-run time via ALMGR_E2E_OPERATOR_USER/PASS and
 * ALMGR_E2E_MEMBER_USER/PASS (Fase 5) if you regenerate the baseline with
 * different credentials.
 */
const ALMGR_E2E_USERS = array(
	array(
		'user_login' => 'e2e-operator',
		'user_pass'  => 'e2e-operator-pw',
		'user_email' => 'e2e-operator@example.test',
		'role'       => ALMGR_OPERATOR_ROLE,
	),
	array(
		'user_login' => 'e2e-member-1',
		'user_pass'  => 'e2e-member-1-pw',
		'user_email' => 'e2e-member-1@example.test',
		'role'       => ALMGR_MEMBER_ROLE,
	),
	array(
		'user_login' => 'e2e-member-2',
		'user_pass'  => 'e2e-member-2-pw',
		'user_email' => 'e2e-member-2@example.test',
		'role'       => ALMGR_MEMBER_ROLE,
	),
);

/**
 * Create a user if missing, or fix its role if it already exists.
 *
 * @param array $spec One entry of ALMGR_E2E_USERS.
 * @return int User ID.
 */
function almgr_e2e_ensure_user( array $spec ): int {
	$existing = get_user_by( 'login', $spec['user_login'] );
	if ( $existing ) {
		$existing->set_role( $spec['role'] );
		WP_CLI::log( 'User already present, role re-applied: ' . $spec['user_login'] );
		return $existing->ID;
	}

	$user_id = wp_insert_user(
		array(
			'user_login' => $spec['user_login'],
			'user_pass'  => $spec['user_pass'],
			'user_email' => $spec['user_email'],
			'role'       => $spec['role'],
		)
	);

	if ( is_wp_error( $user_id ) ) {
		WP_CLI::error( 'Failed to create user ' . $spec['user_login'] . ': ' . $user_id->get_error_message() );
	}

	WP_CLI::log( 'User created: ' . $spec['user_login'] . ' (ID ' . $user_id . ')' );

	return (int) $user_id;
}

/**
 * Create an asset post if missing (looked up by slug), or return its existing ID.
 *
 * @param string $slug  Stable post_name to identify this fixture asset across runs.
 * @param string $title Post title.
 * @return int Post ID.
 */
function almgr_e2e_ensure_asset( string $slug, string $title ): int {
	$existing = get_page_by_path( $slug, OBJECT, ALMGR_ASSET_CPT_SLUG );
	if ( $existing ) {
		WP_CLI::log( 'Asset already present: ' . $title . ' (ID ' . $existing->ID . ')' );
		return $existing->ID;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => ALMGR_ASSET_CPT_SLUG,
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $slug,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::error( 'Failed to create asset ' . $title . ': ' . $post_id->get_error_message() );
	}

	WP_CLI::log( 'Asset created: ' . $title . ' (ID ' . $post_id . ')' );

	return (int) $post_id;
}

/**
 * Assign the standard taxonomy terms to a fixture asset, replacing any
 * previous assignment (idempotent by construction).
 *
 * @param int    $post_id   Asset post ID.
 * @param string $structure `ALMGR_ASSET_COMPONENT_SLUG` or `ALMGR_ASSET_KIT_SLUG`.
 * @param string $type      `almgr_type` term slug.
 * @param string $state     `almgr_state` term slug.
 * @param string $level     `almgr_level` term slug.
 * @return void
 */
function almgr_e2e_set_taxonomies( int $post_id, string $structure, string $type, string $state, string $level ): void {
	wp_set_object_terms( $post_id, $structure, ALMGR_ASSET_STRUCTURE_TAXONOMY_SLUG, false );
	wp_set_object_terms( $post_id, $type, ALMGR_ASSET_TYPE_TAXONOMY_SLUG, false );
	wp_set_object_terms( $post_id, $state, ALMGR_ASSET_STATE_TAXONOMY_SLUG, false );
	wp_set_object_terms( $post_id, $level, ALMGR_ASSET_LEVEL_TAXONOMY_SLUG, false );
}

WP_CLI::log( '--- Seeding users ---' );
$user_ids = array();
foreach ( ALMGR_E2E_USERS as $spec ) {
	$user_ids[ $spec['user_login'] ] = almgr_e2e_ensure_user( $spec );
}

WP_CLI::log( '--- Seeding assets ---' );

// 1. A standalone available component.
$available_id = almgr_e2e_ensure_asset( 'e2e-available-component', 'E2E Available Component' );
almgr_e2e_set_taxonomies( $available_id, ALMGR_ASSET_COMPONENT_SLUG, 'telescope', 'available', 'basic' );

// 2. A kit with two available components.
$kit_component_1_id = almgr_e2e_ensure_asset( 'e2e-kit-component-1', 'E2E Kit Component 1' );
almgr_e2e_set_taxonomies( $kit_component_1_id, ALMGR_ASSET_COMPONENT_SLUG, 'telescope', 'available', 'basic' );

$kit_component_2_id = almgr_e2e_ensure_asset( 'e2e-kit-component-2', 'E2E Kit Component 2' );
almgr_e2e_set_taxonomies( $kit_component_2_id, ALMGR_ASSET_COMPONENT_SLUG, 'ocular', 'available', 'basic' );

$kit_id = almgr_e2e_ensure_asset( 'e2e-kit', 'E2E Kit' );
almgr_e2e_set_taxonomies( $kit_id, ALMGR_ASSET_KIT_SLUG, 'generic', 'available', 'basic' );
update_field( 'almgr_components', array( $kit_component_1_id, $kit_component_2_id ), $kit_id );

// 3. An asset already on loan to the first member, for return/force-return/reject scenarios.
$on_loan_id = almgr_e2e_ensure_asset( 'e2e-on-loan-asset', 'E2E On Loan Asset' );
almgr_e2e_set_taxonomies( $on_loan_id, ALMGR_ASSET_COMPONENT_SLUG, 'telescope', 'on-loan', 'basic' );
update_post_meta( $on_loan_id, '_almgr_current_owner', $user_ids['e2e-member-1'] );

WP_CLI::success( 'Baseline fixture seeded.' );
