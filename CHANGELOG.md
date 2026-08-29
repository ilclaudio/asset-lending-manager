# Change Log

Any notable changes to this project will be documented in this file.

This file is based on [Keep a Changelog](http://keepachangelog.com/).
This project uses [Semantic Versioning](http://semver.org/).


TAGS: Added, Changed, Deprecated, Removed, Fixed, Security.

## DESIDERATA 2.0.0
1) Add a dashboard and user badge with: resources on loan, loans to be approved, and resources under maintenance (operator).
2) Dedicated landing page for the plugin.

## DESIDERATA 1.1.0
1) Mass printing of all QR codes in A4 pages.
2) Simple statistics page.

## DESIDERATA 1.0.0
1) WordPress Abilities v1 completed (read-only catalog/requests plus `create-loan-request`; see `DOC/AI_ABILITIES_REFERENCE.md`). MCP adapter integration remains future/optional, not required by the plugin.
2) Integration test suite for core AJAX and workflow state transitions: nonce/capability guard paths, rollback/failure paths (see `ISSUES_TODO.md`).
3) PHPUnit round-trip test for `almgr_get_allowed_html()` allowlist completeness (see `ISSUES_TODO.md`).
4) Reset procedure for e2e tests.


## [0.4.0] - 2026-08-29
### Added
- Shared `ALMGR_Asset_Projection_Service` for common asset and member-asset response fields used by REST and Abilities.
- Shared domain services layer used identically by the frontend, the REST API, and Abilities for every equivalent use case: `ALMGR_Asset_Query_Service`, `ALMGR_Asset_Read_Service`, `ALMGR_Asset_Detail_Service`, `ALMGR_Asset_History_Service`, `ALMGR_Member_Assets_Service`, `ALMGR_Loan_Request_Query_Service`, `ALMGR_Access_Policy`, `ALMGR_Pagination`, `ALMGR_Active_Loan_Count_Service`.
- WordPress Abilities v1 (optional, feature-detected, category `almgr`): `almgr/list-assets`, `almgr/get-asset`, `almgr/list-my-assets`, `almgr/get-asset-loan-history`, `almgr/list-my-loan-requests`, `almgr/list-asset-loan-requests`, `almgr/get-loan-request`, `almgr/create-loan-request`.
- New REST endpoints: `GET /me/assets` (current user's held assets) and paginated `GET /me/loan-requests`/`GET /assets/{id}/loan-requests` for loan requests.
- New setting tab **ALM > Settings > Abilities** with `abilities.public` and `abilities.actions_public` toggles, controlling whether Abilities are marked public for external clients such as an MCP adapter (default: off for both).
- `DOC/REST_API_REFERENCE.md` and `DOC/AI_ABILITIES_REFERENCE.md`: full reference documentation for the REST API and for the Abilities layer (endpoints/abilities, authentication, permissions, input/output, error codes).
- New `almgr_warehouse` taxonomy: the asset's administrative home location, independent from the existing dynamic `almgr_location` field. Includes default term seeding, member-only exposure in the frontend/REST/Abilities, a frontend catalog filter, a wp-admin list filter, and automatic propagation from a Kit to its currently included components on save.
- New cooperative "Return asset" action (`ALMGR_Loan_Manager::return_asset()`, AJAX `almgr_return_asset`): a dedicated form on the asset detail page that closes an active loan, distinct from the existing operator-only forced return — its own history status (`returned`) and hook (`almgr_asset_returned`), with the location field pre-filled from the asset's warehouse when one is assigned.
- New setting `workflow.member_return_enabled` (default off, **ALM > Settings > Automations**) letting operators allow members to return the asset they currently hold, in addition to operators.
- Cooperative returns notify all operators by email (new `returned_to_operators` template), since the actor may be the borrower themselves.

### Changed
- REST and Abilities reference documentation aligned with the implemented pagination defaults, filters, response types, and error codes.
- Replaced `DOC/RestApiReference.md` with the more complete `DOC/REST_API_REFERENCE.md`.
- `readme.txt`/`README.md`: REST API and Abilities descriptions made generic, pointing to the two dedicated reference documents instead of listing endpoint details inline.
- Several Abilities input schemas now declare `additionalProperties: false` and complete descriptions for required fields, for consistency across all Abilities.

### Fixed
- Removed a duplicated visibility check in the asset detail template that recalculated loan-request visibility by hand instead of using the shared `ALMGR_Access_Policy`, already used by REST and Abilities for the same use case.
- Removed dead code: `ALMGR_Loan_Manager::get_asset_history()`, an unused legacy history query superseded by `ALMGR_Asset_History_Service`.

### Security
- Fixed the `almgr/get-asset` Ability exposing operator-only fields (cost, purchase date, internal notes) to any authenticated member; it now applies the same field-visibility rule as the REST asset-detail endpoint, via a new shared `ALMGR_Asset_Projection_Service::filter_acf_fields()`.


## [0.3.2] - 2026-08-21
### Fixed
- readme.txt Installation section: Classic themes and Block themes sub-steps were rendering as a single merged numbered list because the sub-section labels were plain bold text instead of proper subheadings; they now render as distinct subsections (`= Classic themes =` / `= Block themes =`).
- readme.txt Installation section: the optional email sender settings step was listed only under Block themes even though it applies to both; it is now a shared step common to both paths.


## [0.3.1] - 2026-08-21
### Fixed
- Checked compatibility with WordPress 7.1: no code changes required (reviewed iframed post/site editor canvas, client-side media processing, `@wordpress/components` updates, persistent editor toolbar, jQuery UI 1.14.2 upgrade, and block registration — none apply to this plugin).


## [0.3.0] - 2026-07-01
### Added
- Added asset history page.
- Added unit tests: basic coverage for now.
- Added integration: basic coverage for now.
- Added e2e tests: covered only read only features for now.
- Added feature to send e-mail messages to the owner of a resource.
### Fixed
- Kit transfer (loan approval and direct assignment) no longer overwrites components assigned to other users or in maintenance/retired state; excluded components are left untouched and reported to the operator with explicit reasons.
- Kit state changes (maintenance, retired, force-return, restore) no longer affect components outside the kit's control; only components in a compatible state and with the expected owner are modified.
- Component sent to maintenance no longer removed from parent kit(s); `_almgr_removed_from_kit_ids` is now written only on permanent retirement.
- Restore from maintenance is now a state-only change; no kit re-attach is performed because the component was never removed.
- ACF write return values now checked with read-back: failures inside transactions throw and trigger rollback; post-commit failures log a warning.
- Operator AJAX handlers (direct assignment, state change, restore state) now reject non-published (draft/private/trash) assets, matching the existing guard on loan request submission.
- Asset list search field renamed from `s` to `almgr_search` to avoid colliding with the WordPress-reserved global search query variable; `s` is still read as a fallback for previously bookmarked links.
- Hardened the REST API active-loan-count query (`count_active_loans()`) to use `$wpdb->prepare()` with placeholders instead of an interpolated ID list.
### Changed
- Kit loan approval and direct assignment use a unified pre-computed transfer plan; the previous `$check_component_conflicts` bypass parameter has been removed.
- Location field cleared only on kit and included components when an asset moves to on-loan.
- Location field set only on kit and included components on operator-driven state changes.
- Excluded and skipped components returned in AJAX response and shown as a warning notice after page redirect.
- Changed the layout of the asset detail page, now is full height to improve readability.


## [0.2.3] - 2026-05-23
### Fixed
- Checked compatibility with WordPress 7.0: No intervention required.
- Corrected minor typos in release documentation.
- Applied a small admin CSS compatibility adjustment.

## [0.2.2] - 2026-05-02
### Fixed
- Operators can upload/insert images and edit image title/alternative text from the Media Library and featured-image flow.
### Security
- Migrated REST API from custom rewrite rules and manual Basic Auth to native WordPress REST API routes; removed `wp_authenticate()` call.
- Escaped `do_blocks()` output with `wp_kses_post()` in fallback templates.
### Changed
- Maintenance release for WordPress.org submission follow-up.

## [0.2.1] - 2026-04-14
### Added
- Back-office Tools page (ALM → Tools) with Import, Export, and Utilities tabs.
- Users CSV import from Tools (admin only) and users CSV export (admin and operator).
- Assets CSV import (admin and operator) and assets CSV export (admin and operator) in Tools.
- Kit import and export: kit components and their ACF fields are included in the asset CSV.
- Notification policy setting to control if/when all operators are notified for a new loan request (`never`, `no_owner`, `always`).
- `ALMGR_REMOVE_ALL_DATA` constant: define as `true` in `wp-config.php` before uninstalling to remove all plugin data including assets.
### Changed
- Internal refactoring: all plugin identifiers migrated from the `alm_` prefix to `almgr_` for namespace safety.
- All ACF custom field storage keys now use the `almgr_` prefix for WordPress.org namespace compliance.
### Fixed
- Operators can approve/reject requests for assets without a current owner.
### Security
- Security fixes and hardening from code audit.


## [0.1.1] - 2026-04-01
### Added
- Added a module to manage a REST API with these endpoints: `GET /wp-json/almgr/v1/assets`, `GET /wp-json/almgr/v1/assets/{id}`, `GET /wp-json/almgr/v1/members` and `GET /wp-json/almgr/v1/members/{id}/assets/`.
- REST API settings tab in wp-admin (admin only) with enable/disable toggle, endpoint reference, and authentication guide.
### Security
- Added resource-status checks on all AJAX endpoints.


## [0.1.0] - 2026-03-15
First public release.
### Added
- Asset and kit management with full loan workflow (request, approve, reject, direct assign).
- Role-based access control: `almgr_member` and `almgr_operator` roles with scoped capabilities.
- Email notifications for all loan workflow events.
- Loan history tracking, including per-component entries for kit operations.
- Frontend asset browsing with filters, QR code generation, and QR scanner.
- Asset state management (`available`, `on-loan`, `maintenance`, `retired`) with kit propagation; operators can force-return on-loan assets to available from the frontend, closing the active loan and notifying the borrower.
- Location field required on every state change; propagated to kit components.
- Translation-ready with English and Italian included.
- Settings page in wp-admin.
- Frontend shortcodes: `[almgr_asset_list]`, `[almgr_asset_view]`.
### Fixed
- All message max length limits (loan request, rejection, state-change notes) are now read from settings and passed to frontend, eliminating frontend/backend divergence.
- Kit loan approval and direct assignment now write individual history entries for each affected component.
- Invalid QR scan codes no longer cause silent home-page redirects.
- Unowned assets approver policy is consistent across settings, UI, and backend.


## [DEV-0.0.2] - 2026-03-11
Internal development version — not released publicly.
### Changed
- Modified the management of the loan flow.
### Added
- New documentation files.
- QR Code display.
- Search asset by QR code.
- ALM settings management.
- Management of the `maintenance` and `retired` statuses.
### Fixed
- Bug-fixing.
- Accessibility improvements.
### Security
- Multiple security fixes applied.


## [DEV-0.0.1] - 2026-02-22
First internal development version, ready for internal tests.
