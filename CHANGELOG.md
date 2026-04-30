# Change Log

Any notable changes to this project will be documented in this file.

This file is based on [Keep a Changelog](http://keepachangelog.com/).
This project uses [Semantic Versioning](http://semver.org/).


TAGS: Added, Changed, Deprecated, Removed, Fixed, Security.



## [0.2.2] - 2026-04-30
### Fixed
- Operators can upload/insert images and edit image title/alternative text from the Media Library and featured-image flow.
### Security
- Migrated REST API from custom rewrite rules and manual Basic Auth to native WordPress REST API routes; removed `wp_authenticate()` call.
- Escaped `do_blocks()` output with `wp_kses_post()` in fallback templates.


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
