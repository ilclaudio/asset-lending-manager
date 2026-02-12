# ARCHITECTURE.md

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


## Module Registration Pattern

The plugin uses a singleton bootstrap pattern with no autoloader:

1. `asset-lending-manager.php` uses `require_once` to load all class files manually.
2. `alm_init_plugin()` creates the singleton `ALM_Plugin_Manager` instance on the `plugins_loaded` hook.
3. `ALM_Plugin_Manager::__construct()` calls `register()` on each module class, which in turn registers its own WordPress hooks and filters.

This means all modules must be explicitly required in the entry point file. There is no PSR-4 autoloading.

## Key Directories
- `admin/`: Templates for the pages of the back-office and code used only in the WordPress back-office.
- `assets/`: The images, the JavaScript code and the css files used by this plugin.
- `includes/`: The main classes of the modules used by this plugin.
- `languages/`: The files with the label translations.
- `SETUP/`: A backup of the ACF fields definition.
- `templates/`: Frontend templates.
- `tests/`: The unit tests and the integration tests.

## Main Files
- `asset-lending-manager.php`: Entry point of the plugin, it creates the singleton ALM_Plugin_Manager that registers and activates all the components of the system.
- `plugin-config.php`: Constants used by the modules of the plugin.
- `phpcs.xml.dist`: The rules used by PHPCS to check the code syntax and the WordPress coding rules.
- `composer.json`: The JSON file that contains the needed libraries and the development tools needed by a developer.
- `README.md`: Developer documentation.
- `readme.txt`: WordPress plugin readme.
- `TODO.txt`: Legacy backlog file (active issue tracking is in `AGENTS/ISSUES_TODO.md`).
- `CHANGELOG.md`: Version history (currently at v0.1.0).
- `uninstall.php`: Uninstall hooks.
- `LICENSE`: The license file of this product.
- `AGENTS.md`: Entry point for ChatGPT Codex, redirects to `AGENTS/AI_RULES_CHATGPT.md`.
- `phpunit.xml`: Entry point for the unit tests included in the "tests/unit" folder.
- `phpunit-integration.xml`: Entry point for the integration tests included in the "tests/integration" folder.

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

## Technology Stack

- **Platform:** WordPress
- **Language:** PHP
- **Custom Fields:** Advanced Custom Fields (ACF)
- **Build Tools:** See `composer.json` for details
- **Code Quality:** PHPCS for PHP code style checking

## Setup and Configuration

Detailed setup instructions are available in the main documentation (see PROJECT.md for links).

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

## Known Issues
For a complete list of issues and bugs, see `ISSUES_TODO.md`.
