# ISSUES TODO
Last update: 2026-02-19

---

## Security

### [Low] REST autocomplete handler logs nonce in plaintext
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Security
- **Description:** `handle_autocomplete()` writes the received nonce to PHP error log.
- **Expected behavior:** Remove nonce logging and keep security token values out of logs.
- **Notes:** The autocomplete endpoint is intended to remain public for frontend usage; treat it as public read-only surface and harden it (minimal response data, strict input validation, result limits, optional rate limiting/caching, no sensitive token logging). `includes/class-alm-autocomplete-manager.php`

---

## Bug

### [Low] wp_redirect() used instead of wp_safe_redirect()
- **Status:** Open
- **Date:** 2026-02-15
- **Category:** Bug
- **Description:** `redirect_restricted_users()` calls `wp_redirect( home_url() )`. While `home_url()` is normally safe, `wp_safe_redirect()` is the WordPress-recommended function for internal redirects as it validates the destination against the allowed hosts list.
- **Expected behavior:** Replace `wp_redirect()` with `wp_safe_redirect()`.
- **Notes:** `includes/class-alm-admin-manager.php`, line 60.

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

### [Low] Hardcoded operator user ID for automatic operations
- **Status:** Open
- **Date:** 2026-02-12
- **Category:** Refactoring
- **Description:** Automatic operations use fixed user ID `1`, unsafe across installations.
- **Expected behavior:** Make configurable or use context-aware actor resolution.
- **Notes:** `includes/class-alm-loan-manager.php`

---

## Performance

### [High] Asset detail tables perform repeated user lookups inside loops
- **Status:** Open
- **Date:** 2026-02-18
- **Category:** Performance
- **Description:** Loan requests and history tables call `get_userdata()` inside foreach loops for each row. This creates repeated user lookups during page generation and scales poorly with more entries.
- **Expected behavior:** Resolve all involved user IDs once (prefetch map) and render rows using the preloaded dictionary.
- **Notes:** `templates/shortcodes/asset-view.php:301`, `templates/shortcodes/asset-view.php:303`, `templates/shortcodes/asset-view.php:474`, `templates/shortcodes/asset-view.php:477`, `templates/shortcodes/asset-view.php:479`

### [High] Taxonomy filter term lists are queried on every list render without caching
- **Status:** Open
- **Date:** 2026-02-18
- **Category:** Performance
- **Description:** The list template performs 4 separate `get_terms()` calls on each request to build filter dropdowns. On high traffic this adds avoidable taxonomy query overhead.
- **Expected behavior:** Cache filter term datasets (object cache/transient) and invalidate on term changes, or build them once in controller with shared cached helpers.
- **Notes:** `templates/shortcodes/asset-list.php:25`, `templates/shortcodes/asset-list.php:31`, `templates/shortcodes/asset-list.php:37`, `templates/shortcodes/asset-list.php:43`

### [High] Reverse kit lookup uses expensive meta LIKE query in asset detail
- **Status:** Open
- **Date:** 2026-02-18
- **Category:** Performance
- **Description:** To detect kit membership, `get_asset_custom_fields()` runs a `WP_Query` with `meta_query` `LIKE` against serialized component data. This pattern is expensive and non-scalable for larger datasets.
- **Expected behavior:** Replace reverse `LIKE` lookup with normalized relation storage (e.g., dedicated relation meta/table) or maintain a direct parent-kit reference index.
- **Notes:** `includes/class-alm-asset-manager.php:350`, `includes/class-alm-asset-manager.php:355`, `includes/class-alm-asset-manager.php:363`

### [High] Loan tables miss composite indexes for real query patterns
- **Status:** Open
- **Date:** 2026-02-18
- **Category:** Performance
- **Description:** Runtime queries filter by multiple columns and sort by date (`asset_id + status + request_date`, `asset_id + changed_at`) but table schemas define only single-column indexes, causing less efficient plans as data grows.
- **Expected behavior:** Add composite indexes aligned with read patterns and include safe migration path for existing installs.
- **Notes:** `includes/class-alm-loan-manager.php:748`, `includes/class-alm-loan-manager.php:812`, `includes/class-alm-installer.php:75`, `includes/class-alm-installer.php:138`

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

### [High] Loan closure flow (return/check-in) for assets and kits
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Current workflow supports request/approve/reject, but there is no explicit return/check-in flow to close an active loan.
- **Expected behavior:** Add operator/owner-driven return flow that sets state back to `available`, clears or updates current owner correctly, propagates to kit components, and writes auditable history entries.
- **Notes:** Required for realistic end-to-end user testing of the lending lifecycle.

### [High] Real notification delivery for loan workflow events
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Notification logic is mostly placeholder/logging and does not send real emails to requester/owner/operators.
- **Expected behavior:** Implement production-ready notifications for request submitted, request approved/rejected/canceled, and loan closure, using configurable sender/recipient settings.
- **Notes:** Required for user testing because actors otherwise miss workflow events.

### [High] Minimum operator settings UI for runtime configuration
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Settings structure exists but is not exposed via complete admin UI and is not fully consumed at runtime.
- **Expected behavior:** Provide a minimal settings page to configure notification sender/system email and core workflow toggles used by runtime modules.
- **Notes:** Required before broad user testing to avoid hardcoded operational behavior.

### [High] Frontend styles must not override site typography
- **Status:** Open
- **Date:** 2026-02-18
- **Category:** Feature
- **Description:** Plugin styles currently force a custom font family and can override the active theme typography in site pages.
- **Expected behavior:** Remove hardcoded `font-family` declarations (or inherit from theme) so plugin frontend UI uses the site's default typography.
- **Notes:** Keep only spacing/color/layout styles that are plugin-specific.

### [Medium] Member dashboard for "My requests" and "My active loans"
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Members can request assets from detail pages but lack a consolidated personal view of pending/approved/canceled requests and active assignments.
- **Expected behavior:** Add frontend area (shortcode/template) with filters and statuses for personal requests and currently assigned assets.
- **Notes:** Improves usability and reduces support friction during testing.

### [Medium] Security hardening for autocomplete endpoint access
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Autocomplete endpoint is currently public and still includes debug-oriented token handling behavior.
- **Expected behavior:** Keep endpoint public for anonymous frontend search, but enforce public-endpoint hardening: no sensitive logging, strict input validation, rate limiting and/or caching, and responses limited to publish-safe fields only.
- **Notes:** Endpoint openness is acceptable for UX requirements; security posture must match a public read-only API.

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

### [Low] Extend loan history visibility for involved members
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Loan history is currently surfaced in UI only for operators, even though backend filtering already supports user-scoped history access.
- **Expected behavior:** Show history entries relevant to involved members (requester/owner/actor) in a safe, filtered frontend section.
- **Notes:** Useful for transparency during user validation.

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

### [High] Role-based UAT checklist for core lending flows
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Documentation
- **Description:** There is no explicit user acceptance test checklist/report that validates core workflows for `alm_member` and `alm_operator` across happy paths and error paths.
- **Expected behavior:** Create and maintain a concise UAT checklist with pass/fail evidence for core flows (request, approve, reject, return/closure, permission checks, error handling) before opening broader user testing.
- **Notes:** Go/No-Go prerequisite for test phase rollout.
