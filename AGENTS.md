# AGENTS.md

## Project Overview
We are developing a WordPress plugin called asset-lending-manager for the lending of assets (kits and simple components).

It is a plugin (ALM – Asset Lending Manager) that implements features allowing members of a non-profit astronomy association (AAGG) to track the association’s instruments (telescopes, eyepieces, mounts, charts, etc.), books, and magazines.

These objects (resources) are assigned on loan to association members, who manage and maintain them until they are requested by another member, who then takes them over.

The managed objects are generically called “resources” (assets) and are of two types:
- Components: simple items such as eyepieces, mounts, filters, books, etc.
- Kits: a collection of components such as telescopes equipped with eyepieces and mounts, book collections, etc.

A kit cannot contain other kits.
The roles defined in the system are: member and operator.

## Architecture Notes
In this codebase, a plugin usable on the WordPress CMS is defined.
The plugin is multilingual; all labels are translated using the text domain: asset-lending-manager.
The package for all files is: @package AssetLendingManager.

Within the plugin, an asset content type (alm_asset) is defined via ACF, which can be of type component or kit.

Taxonomies are also defined to classify the various assets:
- Structure (alm_structure): kit, component.
- Type (alm_type): accessory, binoculars, filter, generic, book, mount, ocular, telescope, e.g.
- State (alm_state): available, maintenance, on-loan, retired.
- Level (alm_level): advanced, base, intermediate.

## Main system features
1. Association members have a WordPress site account with the role “member” (alm_member) or “operator” (alm_operator).
2. alm_member users can view the list of assets and asset detail pages; request the loan of an asset; accept or reject a loan request for an asset currently assigned to them; browse the asset list with filters by free text, structure, type, state, and level.
3. alm_operator users can do everything alm_member users can, plus: create assets; edit and manage assets; cancel loan requests for all users; approve loan requests for all users; directly assign an asset to a member; change the state of an asset; manage plugin configuration parameters.
4. The loan request and approval workflow takes place between members and operators.
5. In the AAGG WordPress front-end, there will be a section that allows viewing the association’s assets. Only members will be able to request asset assignment; anonymous users will not.
6. From the WordPress back office, system operators can add, remove, review, and edit all technical records of assets (components and kits).
7. There is an asset list that members can browse using filters (e.g., type, kit, name, etc.).
8. Each asset has a descriptive detail page (fields to be defined; it will certainly include: id, name, description, photo, technical sheet, external code, internal code, maintenance status, kit, state, etc.).
9. Kits of kits cannot be created.
10. From the asset detail page, a member or an operator can request a loan.
11. A loan request sends three emails: to the requester, the current assignee, and a system email address.
12. The current assignee can approve or deny the loan request. This action triggers notification emails as in step 11.
13. The requester and the assignee agree offline on the asset handover details.
14. Once the handover has taken place, the previous assignee or the system administrator updates the current assignee of the asset. This operation triggers email notifications as in step 11.
15. The system stores the complete assignment history.
16. The operator can view the full assignment history for all devices.
17. A member can only view history entries that involve them as requester or assignee.

## Possible future extensions
A) Export of assets, loans, and loan requests to CSV.

B) REST API for managing entities and workflows from external applications.

## System Modules
- **ALM_Plugin_Manager**: Bootstraps the plugin and registers core modules and hooks.
- **ALM_Installer**: Handles activation tasks such as roles, capabilities, and setup routines.
- **ALM_Capabilities**: Defines and manages custom capabilities used by the plugin roles.
- **ALM_Settings_Manager**: Defines plugin configuration structure (email, notifications, loans, frontend, logging). NOTE: config is defined but never read by any module; no admin UI exists yet.
- **ALM_Role_Manager**: Creates and maintains the custom roles (alm_member, alm_operator).
- **ALM_Asset_Manager**: Manages assets and kits, including CRUD and taxonomy integration.
- **ALM_ACF_Asset_Adapter**: Bridges asset data with ACF fields and field groups.
- **ALM_Loan_Manager**: Handles the loan workflow, requests, approvals, and assignments.
- **ALM_Notification_Manager**: STUB — intended to send email notifications but currently an empty class (17 lines). All email "sending" in `ALM_Loan_Manager` only logs to `ALM_Logger`.
- **ALM_Frontend_Manager**: Renders frontend views like asset lists and detail pages.
- **ALM_Admin_Manager**: Provides admin UI pages and back-office functionality.
- **ALM_Autocomplete_Manager**: Provides autocomplete data sources for frontend/admin inputs.
- **ALM_Logger**: Logs plugin events and diagnostics for debugging and auditing.

## Verified Entry Points
- `asset-lending-manager.php` loads core classes, registers activation/deactivation hooks, and boots the plugin on `plugins_loaded` via `alm_init_plugin()`.
- `ALM_Plugin_Manager::init()` registers admin menu entries and hooks for tools and taxonomy menu behavior.

## Verified Hooks and Endpoints
- `admin_menu` -> `ALM_Plugin_Manager::register_alm_custom_menu()`.
- `parent_file` -> `ALM_Plugin_Manager::keep_alm_taxonomy_menu_open()`.
- `admin_post_alm_reload_default_terms` -> `ALM_Plugin_Manager::handle_reload_default_terms()`.
- `init` -> `ALM_Asset_Manager::register_post_type()` and `ALM_Asset_Manager::register_taxonomies()`.
- `acf/include_fields` -> `ALM_ACF_Asset_Adapter::register_asset_fields()`.
- `wp_ajax_alm_submit_loan_request` -> `ALM_Loan_Manager::ajax_submit_loan_request()`.
- `wp_ajax_alm_reject_loan_request` -> `ALM_Loan_Manager::ajax_reject_loan_request()`.
- `wp_ajax_alm_approve_loan_request` -> `ALM_Loan_Manager::ajax_approve_loan_request()`.
- `template_include` -> `ALM_Frontend_Manager::load_asset_template()`.
- `alm_locate_template` filter is applied inside `ALM_Frontend_Manager::locate_template()`.
- `wp_enqueue_scripts` -> `ALM_Frontend_Manager::enqueue_frontend_assets()` and `ALM_Autocomplete_Manager::enqueue_assets()`.
- `login_redirect` -> `ALM_Frontend_Manager::redirect_login_by_role()`.
- `logout_redirect` -> `ALM_Frontend_Manager::redirect_logout_by_role()`.
- `admin_init` -> `ALM_Admin_Manager::redirect_restricted_users()`.
- `admin_menu` -> `ALM_Admin_Manager::remove_menus()`.
- `admin_enqueue_scripts` -> `ALM_Admin_Manager::enqueue_admin_assets()`.
- REST route `POST /wp-json/alm/v1/assets/autocomplete` -> `ALM_Autocomplete_Manager::handle_autocomplete()`.

## Verified Data Model
- Custom Post Type: `alm_asset` with rewrite slug `asset`.
- Taxonomy slugs: `alm_structure`, `alm_type`, `alm_state`, `alm_level`.
- Default terms are created by `ALM_Installer::create_default_terms()`.
- Asset structure terms: `component`, `kit`.
- Asset type terms: `telescope`, `ocular`, `refractor`, `optical-tube`, `binoculars`, `tripod`, `filter`, `accessory`, `book`, `magazine`, `mount`, `generic`.
- Asset state terms: `on-loan`, `available`, `maintenance`, `retired`.
- Asset level terms: `basic`, `intermediate`, `advanced`.

## Verified ACF Fields (Asset)
- `manufacturer`
- `model`
- `data_acquisto`
- `cost`
- `dimensions`
- `weight`
- `location`
- `components`
- `user_manual`
- `technical_data_sheet`
- `serial_number`
- `external_code`
- `notes`

## Verified Database Tables
- `{$wpdb->prefix}alm_loan_requests` columns: `id`, `asset_id`, `requester_id`, `owner_id`, `request_date`, `request_message`, `status`, `response_date`, `response_message`.
- `{$wpdb->prefix}alm_loan_requests_history` columns: `id`, `loan_request_id`, `asset_id`, `requester_id`, `owner_id`, `status`, `message`, `changed_at`, `changed_by`.

## Verified Post Meta Fields
- `_alm_current_owner` (int, user ID): Stored on `alm_asset` posts. Tracks the current assignee of an asset. Set by `ALM_Loan_Manager::set_asset_owner()` during loan approval. Returns `0` if the asset has never been assigned.

## Verified Permissions
- Roles: `alm_member`, `alm_operator`.
- Domain capabilities: `alm_view_assets`, `alm_view_asset`, `alm_edit_asset`.
- CPT capabilities are defined in `ALM_Capabilities::get_asset_cpt_caps()`.
- On activation, administrators and operators receive all ALM capabilities; members receive `alm_view_assets` and `alm_view_asset`.

## Verified Templates and Shortcodes
- Templates: `templates/archive-alm_asset.php`, `templates/single-alm_asset.php`, `templates/shortcodes/asset-list.php`, `templates/shortcodes/asset-view.php`.
- Shortcodes: `[alm_asset_list]`, `[alm_asset_view]`.

## Verified Assets Enqueued
- Frontend CSS: `assets/css/frontend-assets.css`, `assets/css/asset-requests-table.css`, `assets/css/asset-history-table.css`.
- Frontend JS: `assets/js/frontend-assets.js`.
- Admin CSS: `assets/css/admin-assets.css`.
- Admin JS: `assets/js/admin-assets.js`.
- Autocomplete CSS: `assets/css/alm-asset-autocomplete.css`.
- Autocomplete JS: `assets/js/alm-asset-autocomplete.js`.

## Verified Logging
- `ALM_Logger` writes to the WordPress error log only when `WP_DEBUG` is true.
- Levels: DEBUG, INFO, WARNING, ERROR.

## Verified Limitations in Code
- `ALM_Notification_Manager` is an empty stub (17 lines). Email notifications are logged only; actual sending is not implemented.
- `ALM_Settings_Manager` defines a full config structure (email, notifications, loans, frontend, logging) but no module reads from it and there is no admin UI to manage settings.
- `ALM_ACF_Asset_Adapter::define_custom_field_labels()` is dead code (never called).
- `admin/plugin-main-page.php` is a stub — it only displays a placeholder message.
- `frontend-assets.js` contains hardcoded English strings (e.g., "Sending...", "Confirm Approval") that are not wrapped in a translation mechanism.

## Known Bugs (Prioritized)

### CRITICAL — Plugin cannot function correctly
1. **DB tables never created on activation.** `ALM_Plugin_Manager::activate()` does not call `ALM_Installer::create_tables()`. The `alm_loan_requests` and `alm_loan_requests_history` tables are never created, so the entire loan workflow fails on a fresh install. Fix: call `ALM_Installer::create_tables()` inside `ALM_Plugin_Manager::activate()`.
2. **`uninstall.php` crashes.** It references undefined variables `$alm_roles_to_modify` and `$caps_to_remove`, causing PHP warnings/errors on uninstall. The `ALM_Installer::drop_tables()` call is also commented out.

### HIGH — Security issues
3. **Autocomplete REST endpoint is open to anonymous users.** In `ALM_Autocomplete_Manager`, the permission callback is `__return_true`, the nonce verification is commented out, and the capability check is commented out. Fix: restore permission callback to check `ALM_VIEW_ASSETS`.
4. **Debug `error_log()` left in production code.** `class-alm-autocomplete-manager.php` line ~98 logs the nonce value to the error log.

### MEDIUM — Missing functionality
5. **`send_approval_email_notification()` is referenced in `approve_loan_request()` but the method does not exist.** If email code is ever uncommented, this will cause a fatal error.
6. **Hardcoded email address.** `class-alm-loan-manager.php` line ~687 uses `'operators@example.com'` instead of reading from `ALM_Settings_Manager`.

## Documentation
- `README.md`
- `readme.txt`

## Key Directories
- `admin/`: Templates for the pages of the back-office and code used only in the WordPress back-office.
- `assets/`: The images, the JavaScript code and the css files used by this plugin.
- `includes/`: The main classes of the modules used by this plugin.
- `languages/`: The files with the label translations.
- `SETUP/`: A backup of the ACF fields definition.
- `templates/`: Frontend templates.
- `tests/`: The unit tests and the integration tests.

## Main files
- `asset-lending-manager.php`: Entry point of the plugin, it creates the singleton ALM_Plugin_Manager that registers and activates all the components of the system.
- `plugin-config.php`: Constants used by the modules of the plugin.
- `phpcs.xml.dist`: The rules used by PHPCS to check the code syntax and the WordPress coding rules.
- `composer.json`: The JSON file that contains the needed libraries and the development tools needed by a developer.
- `README.md`: Developer documentation.
- `readme.txt`: WordPress plugin readme.
- `TODO.txt`: Current backlog.
- `CHANGELOG.md`: Change history (currently empty).
- `uninstall.php`: Uninstall hooks.
- `LICENSE`: The license file of this product.
- `AGENTS.md`: This file.
- `phpunit.xml`: Entry point for the unit tests included in the "tests/unit" folder.
- `phpunit-integration.xml`: Entry point for the integration tests included in the "tests/integration" folder.

## Project Repository
DEV: https://github.com/ilclaudio/asset-lending-manager/tree/dev

## Setup
- Install WordPress.
- Install and activate ACF (used for `alm_asset` fields).
- Import field groups from `SETUP/` if needed.
- Run `composer install`.

## Commands
- `composer lint`
- `composer lint:fix`
- `composer test`
- `composer test:unit`
- `composer test:integration`
- `composer test:all`

## Testing
- Unit tests: `composer test:unit`
- Integration tests: `composer test:integration`

## Dependencies
- WordPress
- ACF
- Composer (dev)

## Implementation Status

### Fully Working
- Asset CPT registration, taxonomies, and default terms.
- ACF field group registration (13 fields).
- Role creation (`alm_member`, `alm_operator`) and capability assignment.
- Asset archive page with filters (structure, type, state, level, free text).
- Asset detail page with ACF fields, taxonomy badges, and file downloads.
- Loan request submission (AJAX, with nonce, capability check, duplicate detection).
- Loan request rejection (AJAX, with transaction, history logging).
- Loan request approval (AJAX, full implementation with kit component propagation, concurrent request cancellation, owner/state updates, history logging).
- Autocomplete search (REST endpoint — but has security issues, see Known Bugs).
- Admin menu, tools page (reload default terms).
- Member redirect away from dashboard.
- Login/logout redirects to `/asset/`.
- Frontend JS: lightbox, modals, form validation, focus trapping, XSS protection (vanilla JS, no jQuery).
- Logging via `ALM_Logger` (writes to WP error log when `WP_DEBUG` is true).

### Stub / Not Implemented
- `ALM_Notification_Manager`: empty class, no email sending logic.
- `ALM_Settings_Manager`: config structure defined but never read by any module; no admin settings page.
- `admin/plugin-main-page.php`: placeholder only.
- `CHANGELOG.md`: empty.

### Partially Implemented
- `uninstall.php`: role removal works, capability cleanup and table drop are broken.
- Loan history: `get_asset_history()` method exists and works, but history is not displayed in any frontend template.
- Internationalization: PHP strings use `__()` / `esc_html__()`, but JS strings are hardcoded in English.

## Known Issues / TODO
- See `TODO.txt`.
- `CHANGELOG.md` is empty.

## Current Gaps (Prioritized)

### Must Fix (Blocking)
- DB tables never created on activation (see Known Bugs #1).
- `uninstall.php` is broken (see Known Bugs #2).
- Autocomplete endpoint security (see Known Bugs #3).

### High Priority — Core Features Missing
- Email notification sending not implemented (`ALM_Notification_Manager` is a stub).
- Loan history list: `get_asset_history()` exists but no template displays it.
- Direct assignment of an asset to a member by an operator (no UI or AJAX handler).
- Operator cancel loan request (no AJAX handler).
- Admin settings page for `ALM_Settings_Manager` (email addresses, notification toggles, loan limits).

### Medium Priority — UX and Quality
- Asset list pagination is missing.
- Asset list mobile layout needs improvements.
- JS hardcoded English strings need a translation mechanism (wp_localize_script).
- `admin/plugin-main-page.php` is a placeholder — needs a real dashboard or can be removed.
- Unit/integration/functional tests need recovery and expansion.
- `CHANGELOG.md` is empty.

## Code Style
### Guidelines
Act as an expert PHP and WordPress developer.
Write code in compliance with the official WordPress plugin and theme guidelines.
Explain the code you write and the inner workings of WordPress, describing standard practices as we move forward.
While developing this project, I want to learn everything needed to become an expert developer of WordPress core, themes, and plugins.

Some rules to follow:
- Use tabs, not spaces, and place comments in English at the beginning of each file and before every function.
- Comments must end with a period ".".
- Use WordPress naming conventions for classes, files, variables, constants, and functions.
- Align the assignments as required by WP rules, e.g.:
```
$tipo_risorsa     = dli_get_post_main_category( $post, RT_TYPE_TAXONOMY );
$archive_page_obj = dli_get_page_by_post_type( TECHNICAL_RESOURCE_POST_TYPE );
$archive_page     = $archive_page_obj ? get_permalink( $archive_page_obj->ID ) : '';
```

Pay particular attention to the following aspects when writing code:
- Absence of bugs.
- Absence of vulnerabilities: maximum security.
- Responsive pages (mobile-first design).
- Page accessibility (this point is very important).
- Compliance with WordPress best practices.
- Simple, readable, and modular code.

In any case, be concise but precise in your responses.

### Collaboration Expectations
When you have doubts about what to do, ask before writing code and propose alternatives.
Always suggest the next step in order to quickly achieve the requested goal.
Suggest code refactorings whenever you consider them appropriate.

## CHANGELOG AND TODO
- `CHANGELOG.md`
- `TODO.txt`
