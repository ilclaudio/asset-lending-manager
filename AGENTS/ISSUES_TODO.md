# ISSUES TODO
Last update: 2026-02-15

---

## Security

### [Low] REST autocomplete handler logs nonce in plaintext
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Security
- **Description:** `handle_autocomplete()` writes the received nonce to PHP error log.
- **Expected behavior:** Remove nonce logging and keep security token values out of logs.
- **Notes:** `includes/class-alm-autocomplete-manager.php`

---

## Bug

### [Low] wp_redirect() used instead of wp_safe_redirect()
- **Status:** Open
- **Date:** 2026-02-15
- **Category:** Bug
- **Description:** `redirect_restricted_users()` calls `wp_redirect( home_url() )`. While `home_url()` is normally safe, `wp_safe_redirect()` is the WordPress-recommended function for internal redirects as it validates the destination against the allowed hosts list.
- **Expected behavior:** Replace `wp_redirect()` with `wp_safe_redirect()`.
- **Notes:** `includes/class-alm-admin-manager.php`, line 60.

### [Low] Plugin main admin page title is not rendered
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Bug
- **Description:** Template calls `esc_html__()` without `echo`, so the page heading is empty.
- **Expected behavior:** Print the translated title with `esc_html_e()` or `echo esc_html__()`.
- **Notes:** `admin/plugin-main-page.php`

---

## Refactoring

### [Medium] ALM_Notification_Manager is an empty stub
- **Status:** Open
- **Date:** 2026-02-09
- **Category:** Refactoring
- **Description:** Notification manager is non-functional; notification logic is mostly logging-only.
- **Expected behavior:** Implement sending logic or clearly mark as planned and decouple placeholders.
- **Notes:** `includes/class-alm-notification-manager.php`

### [Medium] Settings manager config is defined but not consumed
- **Status:** Open
- **Date:** 2026-02-09
- **Category:** Refactoring
- **Description:** Settings structure exists but is not wired to modules or admin UI.
- **Expected behavior:** Wire settings into runtime usage or remove dead configuration paths.
- **Notes:** `includes/class-alm-settings-manager.php`

### [Low] Excessive debug console logs in JS
- **Status:** Open
- **Date:** 2026-02-12
- **Category:** Refactoring
- **Description:** Many debug logs expose runtime details and clutter browser console.
- **Expected behavior:** Remove logs or gate them behind explicit debug flag.
- **Notes:** `assets/js/frontend-assets.js`, `assets/js/admin-assets.js`

### [Low] jQuery declared as script dependency but vanilla JS policy applies
- **Status:** Open
- **Date:** 2026-02-15
- **Category:** Refactoring
- **Description:** `wp_enqueue_script()` calls for `alm-frontend-assets`, `alm-admin-assets`, and `alm-asset-autocomplete` all declare `array( 'jquery' )` as dependency. CODING_STANDARDS mandates vanilla JS only. If jQuery is not actually used in the JS files, this forces unnecessary loading.
- **Expected behavior:** Audit JS files for jQuery usage; if none, remove the dependency. If jQuery is used, either refactor to vanilla JS or document the exception.
- **Notes:** `includes/class-alm-frontend-manager.php:187`, `includes/class-alm-admin-manager.php:112`, `includes/class-alm-autocomplete-manager.php:38`.

### [Low] Hardcoded operator user ID for automatic operations
- **Status:** Open
- **Date:** 2026-02-12
- **Category:** Refactoring
- **Description:** Automatic operations use fixed user ID `1`, unsafe across installations.
- **Expected behavior:** Make configurable or use context-aware actor resolution.
- **Notes:** `includes/class-alm-loan-manager.php`

---

## Accessibility

### [Low] No keyboard navigation for autocomplete dropdown
- **Status:** Open
- **Date:** 2026-02-12
- **Category:** Accessibility
- **Description:** Dropdown is mouse-only.
- **Expected behavior:** Support ArrowUp/ArrowDown, Enter, Escape.
- **Notes:** `assets/js/alm-asset-autocomplete.js`

---

## Feature

### [High] Direct assignment by operator to member/operator
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Operators need to assign assets directly to a target user without passing through loan request flow.
- **Expected behavior:** Add secure admin flow with reason, transactional update (owner/state), kit propagation, and history/audit logging.
- **Notes:** Restrict action to operators/admin; keep requests section read-only for non-owner operators.

### [Low] Asset list pagination
- **Status:** Open
- **Date:** 2026-02-12
- **Category:** Feature
- **Description:** Large lists degrade usability and performance.
- **Expected behavior:** Add pagination for list/archive views.
- **Notes:** Use WP pagination APIs.

### [Low] Better mobile list view
- **Status:** Open
- **Date:** 2026-02-12
- **Category:** Feature
- **Description:** Current list rendering can be hard to scan on small screens.
- **Expected behavior:** Improve mobile layout (horizontal scroll or card view).
- **Notes:** Validate all list contexts.

### [Low] CSV export for assets/loans/requests
- **Status:** Idea
- **Date:** 2026-02-12
- **Category:** Feature
- **Description:** Operators need offline reporting/export.
- **Expected behavior:** Add CSV export actions for key datasets.
- **Notes:** Admin page or secured AJAX endpoint; `fputcsv()`.

### [Low] REST API for external integrations
- **Status:** Idea
- **Date:** 2026-02-12
- **Category:** Feature
- **Description:** External tools may need programmatic access.
- **Expected behavior:** Authenticated endpoints for assets and loan workflow operations.
- **Notes:** Strict capability checks required.

---

## Documentation
_No open documentation issues._
