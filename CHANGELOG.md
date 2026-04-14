# Change Log

Any notable changes to this project will be documented in this file.

This file is based on [Keep a Changelog](http://keepachangelog.com/).
This project uses [Semantic Versioning](http://semver.org/).


TAGS: Added, Changed, Deprecated, Removed, Fixed, Security.



## [0.2.2] - 2026-04-30
### Added
### Changed
### Fixed
### Security


## [0.2.1] - 2026-04-14
### Added
- Back-office Tools page (ALM -> Tools) with Import, Export, and Utilities tabs.
- Users CSV import from Tools (admin only).
- Users CSV export from Tools (admin and operator).
- Assets CSV import from Tools (admin and operator).
- Assets CSV export from Tools (admin and operator).
- Kit import and export: kit components and their ACF fields are included in the asset CSV.
- Notification policy setting to control if/when all operators are notified for a new loan request (`never`, `no_owner`, `always`).
- `ALMGR_REMOVE_ALL_DATA` constant: define as `true` in `wp-config.php` before uninstalling to remove all plugin data including assets.
### Changed
- Massive refactoring: changed all plugin identifiers from `alm_` to `almgr_`.
- All ACF custom field storage keys now use the `almgr_` prefix for WordPress.org namespace compliance.
### Fixed
- An operator can approve/reject requests for resources without a current owner.
### Security
- Security fixes and hardening from code audit.

## [0.1.1] - 2026-04-01
### Added
- Added a module to manage a REST API with these endpoints: `GET /almgr/v1/assets`, `GET /almgr/v1/assets/{id}`, `GET /almgr/v1/members` and `GET /almgr/v1/members/{id}/assets/`.
- Added the REST API settings tab (admin-only): toggle to enable/disable all routes, endpoint reference table, and authentication guide.
### Fixed
### Changed
### Security
- Added checks on resource status in ajax scripts.

## [0.1.0] - 2026-03-15
First public release.
### Added
- Asset and kit management with full loan workflow (request, approve, reject, direct assign).
- Role-based access control: `almgr_member` and `almgr_operator` roles with scoped capabilities.
- Email notifications for all loan workflow events (request submitted, approved, rejected, canceled, direct assignment).
- Loan history tracking per asset, including per-component entries for kit operations.
- Frontend asset browsing with text search and taxonomy filters.
- QR code generation and printable label from asset detail page.
- QR scanner from asset list (camera-based quick lookup).
- Asset state management (`available`, `on-loan`, `maintenance`, `retired`) with full kit propagation.
- Operator-driven state change and restore from frontend, with history tracking.
- Direct assignment by operator with mandatory reason.
- Automatic cancellation of concurrent pending requests on approval or direct assignment.
- Settings page in wp-admin for email, loan, and workflow configuration.
- Configurable message max lengths (loan request, rejection, direct assign reason, state change notes) propagated to frontend.
- Translation-ready: English and Italian included, `.pot` file provided.
- Frontend shortcodes: `[almgr_asset_list]`, `[almgr_asset_view]`.
- An operator cannot approve the lending request sent to a member.
- Changing the state from on loan to available, in maintenance or dismissed requires specifying a location and a note.
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
