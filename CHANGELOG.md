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


## [Unreleased]
### Changed
- Cooperative return now always asks for the current physical location, with warehouse management on or off (previously the location was skipped when warehouse management was on). The home warehouse is shown as a reference and is never changed by a return.
- Labels: "Location" is now "Current physical location" and "Warehouse" is "Home warehouse" in forms, the asset editor and filters; the asset detail shows a combined "Warehouse / Location" line when warehouse management is on. Slugs, field keys and REST/Abilities keys are unchanged.
- Return, maintenance, retired and restore forms prefill the current physical location with the current value or, failing that, the home warehouse name.
- Kit warehouse propagation is all or nothing: if a component also belongs to another Kit with a different warehouse, no component is changed and the conflict is shown in the Kit's warehouse field. The warehouse of an asset included in a Kit is read-only in its editor.

## [0.4.0] - 2026-10-10
### Added
- Shared domain services used identically by the frontend, the REST API, and Abilities for every equivalent use case (catalog, asset detail, history, member assets, loan requests, pagination, access policy).
- Optional WordPress Abilities v1 (category `almgr`): catalog, asset detail and history, own assets, loan-request lists and detail, and `create-loan-request`. New **ALM > Settings > Abilities** tab with `abilities.public` and `abilities.actions_public` (both off by default).
- New REST endpoints: `GET /me/assets`, `GET /me/loan-requests`, `GET /assets/{id}/loan-requests`.
- New `almgr_warehouse` taxonomy: the asset's administrative home location, independent from `almgr_location`, with default term, member-only visibility, frontend and wp-admin filters, and Kit-to-component propagation on save.
- Cooperative "Return asset" action (history status `returned`, hook `almgr_asset_returned`) for operators and, with the new `workflow.member_return_enabled` setting (off by default), for the current-owner member. Operators are notified by email.
- `DOC/REST_API_REFERENCE.md` and `DOC/AI_ABILITIES_REFERENCE.md`.
### Changed
- REST and Abilities documentation aligned with the implemented behavior; `readme.txt`/`README.md` made generic and pointing to the two reference documents.
- Abilities input schemas now declare `additionalProperties: false`.
### Fixed
- Asset editor showed the warehouse taxonomy twice (core checkbox panel plus the plugin's single-select); only the single-select remains.
- The default warehouse term is no longer auto-assigned on save; assignment is always explicit.
- Removed a duplicated loan-request visibility check in the asset detail template and the unused `ALMGR_Loan_Manager::get_asset_history()`.
### Security
- The `almgr/get-asset` Ability no longer exposes operator-only fields (cost, purchase date, internal notes) to members.


## [0.3.3] - 2026-10-19
### Fixed
- Search autocomplete dropdown layout for logged-in users: a WordPress core CSS rule was overriding its positioning.

## [0.3.2] - 2026-08-21
### Fixed
- readme.txt Installation section: Classic themes and Block themes sub-steps were rendering as a single merged numbered list because the sub-section labels were plain bold text instead of proper subheadings; they now render as distinct subsections (`= Classic themes =` / `= Block themes =`).
- readme.txt Installation section: the optional email sender settings step was listed only under Block themes even though it applies to both; it is now a shared step common to both paths.

## [0.3.1] - 2026-08-21
### Fixed
- Checked compatibility with WordPress 7.1: no code changes required (reviewed iframed post/site editor canvas, client-side media processing, `@wordpress/components` updates, persistent editor toolbar, jQuery UI 1.14.2 upgrade, and block registration — none apply to this plugin).

## [0.3.0] - 2026-07-01
### Added
- Full asset history page for operators.
- Email messages to the current owner of an asset.
- Basic unit, integration, and read-only end-to-end test coverage.
### Fixed
- Kit transfers (loan approval, direct assignment) and state changes (maintenance, retired, force-return, restore) now modify only eligible components; excluded ones are left untouched and reported to the operator with reasons.
- A component sent to maintenance stays in its parent kit(s); `_almgr_removed_from_kit_ids` is written only on permanent retirement, and restore from maintenance is a state-only change.
- ACF writes are now verified with read-back: failures inside transactions trigger a rollback, post-commit failures are logged.
- Operator AJAX handlers (direct assignment, state change, restore) now reject non-published assets.
- Asset list search parameter renamed from `s` to `almgr_search` to avoid the WordPress reserved query variable (`s` is still read as a fallback for old links).
- REST `count_active_loans()` now uses `$wpdb->prepare()` with placeholders.
### Changed
- Kit approval and direct assignment use a single pre-computed transfer plan; `almgr_location` is cleared (on assignment) or set (on state changes) only on the kit and its included components.
- The asset detail page now uses the full height for readability.


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
