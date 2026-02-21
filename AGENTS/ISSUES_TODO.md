# ISSUES TODO
Last update: 2026-02-21

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

---

## Accessibility

### [Low] No keyboard navigation for autocomplete dropdown
- **Status:** Open
- **Date:** 2026-02-12
- **Category:** Accessibility
- **Description:** Dropdown is mouse-only.
- **Expected behavior:** Support ArrowUp/ArrowDown, Enter, Escape.
- **Notes:** `assets/js/alm-asset-autocomplete.js`

### [High] Asset detail — no heading hierarchy beyond `<h1>`
- **Status:** Open
- **Date:** 2026-02-21
- **Category:** Accessibility
- **Description:** The asset detail page (`asset-view.php`) has a single `<h1>` and no `<h2>` headings. Collapsible sections use `aria-label` on `<section>` but expose no headings. Screen reader users navigating by heading (H key in NVDA/JAWS) cannot reach any section below the title.
- **Expected behavior:** Wrap each collapsible title (`.alm-collapsible__title`) in an `<h2>` or assign `role="heading" aria-level="2"` to give AT users heading-based navigation.
- **Notes:** Wrapping `<summary>` content in a heading is valid HTML5; verify cross-AT behavior before implementing. `templates/shortcodes/asset-view.php`

### [Medium] Asset detail — file links open in new tab without user warning (target=_blank)
- **Status:** Open
- **Date:** 2026-02-21
- **Category:** Accessibility
- **Description:** File download links (`user_manual`, `technical_data_sheet`) use `target="_blank"` without informing the user. WCAG 3.2.2 recommends warning users when following a link causes unexpected behavior.
- **Expected behavior:** Add a screen-reader-only span `(opens in new tab)` or an icon with `aria-label` equivalent to each `target="_blank"` link.
- **Notes:** `templates/shortcodes/asset-view.php` — the fix was attempted but the pattern was skipped (CRLF mismatch); requires re-verification.

### [Low] Asset detail — `aria-required` missing on required textareas
- **Status:** Open
- **Date:** 2026-02-21
- **Category:** Accessibility
- **Description:** Required textareas use the HTML `required` attribute but not `aria-required="true"`. Older AT may not expose `required` unless `aria-required` is also present.
- **Expected behavior:** Add `aria-required="true"` to all `required` form fields.
- **Notes:** `templates/shortcodes/asset-view.php`

### [Low] Asset detail — AJAX focus management missing after form submission
- **Status:** Open
- **Date:** 2026-02-21
- **Category:** Accessibility
- **Description:** After submitting the loan request or direct assignment form via AJAX, focus remains on the submit button (which may be disabled/hidden). No focus is moved toward the response message. Keyboard-only and AT users are left disoriented.
- **Expected behavior:** After AJAX response, programmatically move focus to the response div (`#alm-loan-request-response`, `#alm-direct-assign-response`).
- **Notes:** Requires JS change in `assets/js/frontend-assets.js`.

---

## Feature

### [High] Loan closure flow (return/check-in) for assets and kits
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Current workflow supports request/approve/reject, but there is no explicit return/check-in flow to close an active loan.
- **Expected behavior:** Add operator/owner-driven return flow that sets state back to `available`, clears or updates current owner correctly, propagates to kit components, and writes auditable history entries.
- **Notes:** Required for realistic end-to-end user testing of the lending lifecycle.

### [High] Minimum operator settings UI for runtime configuration
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Settings structure exists but is not exposed via complete admin UI and is not fully consumed at runtime.
- **Expected behavior:** Provide a minimal settings page to configure notification sender/system email and core workflow toggles used by runtime modules.
- **Notes:** Required before broad user testing to avoid hardcoded operational behavior.

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

### [High] Role-based UAT checklist for core lending flows
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Documentation
- **Description:** There is no explicit user acceptance test checklist/report that validates core workflows for `alm_member` and `alm_operator` across happy paths and error paths.
- **Expected behavior:** Create and maintain a concise UAT checklist with pass/fail evidence for core flows (request, approve, reject, return/closure, permission checks, error handling) before opening broader user testing.
- **Notes:** Go/No-Go prerequisite for test phase rollout.
