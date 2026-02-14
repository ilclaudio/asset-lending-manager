# ARCHITECTURE.md

## Architecture Notes
This project is a WordPress plugin for asset lending workflows.
The plugin text domain is `asset-lending-manager`.
All plugin classes use package `@package AssetLendingManager`.

The main content model is:
- Custom Post Type: `alm_asset` (slug rewrite: `asset`).
- Asset structures: `component`, `kit` (`kit` cannot contain other kits).
- Taxonomies: `alm_structure`, `alm_type`, `alm_state`, `alm_level`.

## System Modules
- **ALM_Plugin_Manager**: Central bootstrap singleton. It checks dependencies, loads i18n, instantiates runtime modules, registers their hooks, and exposes plugin-level admin hooks.
- **ALM_Installer**: Installation helper for setup routines. It creates/drops ALM tables and creates default taxonomy terms.
- **ALM_Capabilities**: Capability catalog for ALM. It defines domain capabilities and CPT capability maps used by role assignment logic.
- **ALM_Settings_Manager**: Structured settings container (`alm_settings` option) for email, notifications, loans, frontend, and logging. No full admin UI is wired yet.
- **ALM_Role_Manager**: Creates and maintains ALM roles (`alm_member`, `alm_operator`) and assigns capabilities.
- **ALM_Asset_Manager**: Registers `alm_asset`, registers taxonomies, and provides asset wrapper/custom-fields helpers.
- **ALM_ACF_Asset_Adapter**: Registers ACF field groups for `alm_asset`.
- **ALM_Loan_Manager**: Core loan workflow module (submit/approve/reject, ownership/state transitions, request/history persistence).
- **ALM_Notification_Manager**: Notification placeholder module. Currently stubbed with no real sending implementation.
- **ALM_Frontend_Manager**: Frontend templates/shortcodes integration, frontend assets enqueue, and role-based login/logout redirects.
- **ALM_Admin_Manager**: Back-office integration for restricted-area redirects, menu adjustments, and admin assets.
- **ALM_Autocomplete_Manager**: Autocomplete REST route and frontend autocomplete asset enqueue.
- **ALM_Logger**: Central logging utility (writes to error log when `WP_DEBUG` is enabled).

## Bootstrapping Pattern
There is no autoloader.

1. `asset-lending-manager.php` loads core classes via `require_once`.
2. `alm_init_plugin()` runs on `plugins_loaded` and calls `ALM_Plugin_Manager::init()`.
3. `ALM_Plugin_Manager::__construct()` only initializes module instances.
4. `ALM_Plugin_Manager::init()` calls `register()` on runtime modules.

Note:
- Some classes are loaded indirectly by class files (`require_once` inside module files), not only by the main entrypoint.

## Runtime Hooks and Endpoints
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

## Data Model
### Taxonomies and Terms
- `alm_structure`: `component`, `kit`.
- `alm_type`: `telescope`, `ocular`, `refractor`, `optical-tube`, `binoculars`, `tripod`, `filter`, `accessory`, `book`, `magazine`, `mount`, `generic`.
- `alm_state`: `on-loan`, `available`, `maintenance`, `retired`.
- `alm_level`: `basic`, `intermediate`, `advanced`.
- Default terms are created by `ALM_Installer::create_default_terms()`.

### ACF Fields (`alm_asset`)
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

### Database Tables
- `{$wpdb->prefix}alm_loan_requests`: `id`, `asset_id`, `requester_id`, `owner_id`, `request_date`, `request_message`, `status`, `response_date`, `response_message`.
- `{$wpdb->prefix}alm_loan_requests_history`: `id`, `loan_request_id`, `asset_id`, `requester_id`, `owner_id`, `status`, `message`, `changed_at`, `changed_by`.

### Post Meta
- `_alm_current_owner` (int user ID): current assignee for an asset.

### Permissions
- Roles: `alm_member`, `alm_operator`.
- Domain capabilities: `alm_view_assets`, `alm_view_asset`, `alm_edit_asset`.
- CPT capabilities are defined in `ALM_Capabilities::get_asset_cpt_caps()`.
- On activation, administrators/operators receive all ALM caps; members receive view caps.

## Templates and Shortcodes
- Templates: `templates/archive-alm_asset.php`, `templates/single-alm_asset.php`, `templates/shortcodes/asset-list.php`, `templates/shortcodes/asset-view.php`.
- Shortcodes: `[alm_asset_list]`, `[alm_asset_view]`.

## Key Directories
- `includes/`: Core plugin classes.
- `templates/`: Frontend templates and shortcode templates.
- `assets/`: CSS, JS, and images.
- `admin/`: Admin pages rendered by plugin menu callbacks.
- `AGENTS/`: AI collaboration and project guidance docs.

## Dev Commands
- `composer lint`
- `composer lint:fix`
- `composer test`
- `composer test:unit`
- `composer test:integration`
- `composer test:all`

## Known Issues Source
For active issues and priorities, use `AGENTS/ISSUES_TODO.md`.
