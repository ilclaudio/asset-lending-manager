# ARCHITECTURE.md

## Environment and Dependencies
- **PHP:** 7.4+ (WordPress minimum; no PHP 8-only syntax used).
- **WordPress:** 6.0+.
- **Required plugin:** Advanced Custom Fields (ACF) — field groups for `alm_asset`.
- **Dev tooling:** Composer (`phpcs`/`phpcbf` via WPCS, PHPUnit). See `composer.json`.

## Architecture Notes
This project is a WordPress plugin for asset lending workflows.
The plugin text domain is `asset-lending-manager`.
All plugin classes use package `@package AssetLendingManager`.

Main content model:
- Custom Post Type: `alm_asset` (slug rewrite: `asset`).
- Asset structures: `component`, `kit` (`kit` cannot contain other kits).
- Taxonomies: `alm_structure`, `alm_type`, `alm_state`, `alm_level`.

## System Modules
- **ALM_Plugin_Manager**: Central bootstrap singleton. Loads dependencies, initializes modules, and registers plugin-level admin hooks.
- **ALM_Installer**: Activation/uninstall routines for ALM tables and default taxonomy terms.
- **ALM_Capabilities**: Defines domain capabilities and CPT capability map used by role management.
- **ALM_Settings_Manager**: Provides structured `alm_settings` defaults (email/notifications/loans/frontend/logging); admin UI is still partial.
- **ALM_Role_Manager**: Creates and maintains ALM roles (`alm_member`, `alm_operator`) and capability assignments.
- **ALM_Asset_Manager**: Registers `alm_asset`, related taxonomies, and asset-level helper methods.
- **ALM_ACF_Asset_Adapter**: Registers ACF field groups attached to `alm_asset`.
- **ALM_Loan_Manager**: Handles request/approve/reject loan workflow, owner transitions, history persistence, and operator-driven state change (maintenance/retired) with kit propagation.
- **ALM_Notification_Manager**: Notification layer placeholder; currently mostly stub/logging behavior.
- **ALM_Frontend_Manager**: Frontend templates/shortcodes, frontend asset enqueue, and login/logout redirects by role.
- **ALM_Admin_Manager**: Admin-area restrictions, menu cleanup, and admin asset enqueue.
- **ALM_Autocomplete_Manager**: Registers autocomplete REST endpoint and frontend autocomplete assets.
- **ALM_Logger**: Shared logger utility (writes to error log when `WP_DEBUG` is enabled).

## Bootstrapping Pattern
There is no autoloader.

1. `asset-lending-manager.php` loads core classes via `require_once`.
2. `alm_init_plugin()` runs on `plugins_loaded` and calls `ALM_Plugin_Manager::init()`.
3. `ALM_Plugin_Manager::__construct()` only initializes module instances.
4. `ALM_Plugin_Manager::init()` calls `register()` on runtime modules.

Note:
- Some classes are loaded indirectly by class files (`require_once` inside module files), not only by the main entrypoint.

## Public Runtime Surfaces
Core hooks/endpoints used across modules:
- `init`: asset post type and taxonomies registration.
- `acf/include_fields`: ACF field group registration.
- `wp_ajax_alm_submit_loan_request`, `wp_ajax_alm_approve_loan_request`, `wp_ajax_alm_reject_loan_request`: loan workflow actions.
- `wp_ajax_alm_direct_assign_asset`: direct asset assignment by operator (requires `ALM_EDIT_ASSET`).
- `wp_ajax_alm_change_asset_state`: set asset state to `maintenance` or `retired` from frontend (requires `ALM_EDIT_ASSET`); propagates to kit components or removes component from kit.
- `template_include`: frontend template override for ALM asset views.
- `wp_enqueue_scripts`: frontend and autocomplete assets.
- `admin_init`, `admin_menu`, `admin_enqueue_scripts`: admin restrictions/menu/assets.
- `admin_post_alm_reload_default_terms`: admin action to restore default terms.
- REST `POST /wp-json/alm/v1/assets/autocomplete`: asset autocomplete endpoint (public).
- REST `POST /wp-json/alm/v1/users/autocomplete`: ALM user autocomplete (requires `ALM_EDIT_ASSET`).
- Auth UX hooks: `login_redirect`, `logout_redirect`.

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
  - `status` known values: `approved`, `rejected`, `canceled`, `direct_assign`, `to_maintenance`, `to_retired`.
  - For `direct_assign` entries: `loan_request_id = 0`, `requester_id` = new owner (assignee), `owner_id` = previous owner.
  - For `to_maintenance`/`to_retired` entries: `loan_request_id = 0`, `requester_id = 0`, `owner_id` = previous owner (or 0), `changed_by` = operator ID.

### Post Meta
- `_alm_current_owner` (int user ID): current assignee for an asset. Always `0` when asset state is `maintenance` or `retired`.

### Asset State Semantics
- `available`: no active loan; `_alm_current_owner = 0`.
- `on-loan`: active loan; `_alm_current_owner` = borrower user ID.
- `maintenance`: temporarily unavailable; `_alm_current_owner = 0`. Triggers component removal from parent kit(s).
- `retired`: permanently decommissioned; `_alm_current_owner = 0`. Triggers component removal from parent kit(s).

State transition rules (enforced by `ALM_Loan_Manager`):
- Kit → `maintenance`/`retired`: all components follow; components stay in the kit.
- Component → `maintenance`/`retired`: component is removed from parent kit(s).
- Transition to `available` (return flow): not yet implemented; manual state change via WP admin for now.

### Permissions
- Roles: `alm_member`, `alm_operator`.
- Domain capabilities: `alm_view_assets`, `alm_view_asset`, `alm_edit_asset`.
- CPT capabilities are defined in `ALM_Capabilities::get_asset_cpt_caps()`.
- On activation, administrators/operators receive all ALM caps; members receive view caps.

## Templates and Shortcodes
- Templates: `templates/archive-alm_asset.php`, `templates/single-alm_asset.php`, `templates/shortcodes/asset-list.php`, `templates/shortcodes/asset-view.php`.
- Shortcodes: `[alm_asset_list]`, `[alm_asset_view]`.
