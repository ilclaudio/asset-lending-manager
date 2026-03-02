# ISSUES TODO
Last update: 2026-03-02

---

## Security

### [Medium] Email template placeholder injection via user display names
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Security
- **Description:** `format_template()` uses `str_replace()` with arrays, which processes replacement pairs left-to-right. User-controlled values (e.g., `display_name`, request message) are used as replacement values. If a user sets their display name to a placeholder token used later in the array (e.g., `{REQUEST_MESSAGE}`), PHP's iterative `str_replace` will substitute that token in the next pass, causing a later placeholder's value to appear in the wrong position in the email.
- **Expected behavior:** Placeholder replacement values should be sanitized to remove or escape `{...}` tokens before substitution, or the template engine should be replaced with one that is not vulnerable to recursive substitution (e.g., replace all tokens in a single pass using `strtr()`).
- **Notes:** `includes/class-alm-notification-manager.php:414` (`format_template`). Impact: data leakage / wrong data in wrong email field. No code execution possible (plain-text emails).

### [Medium] Email header injection risk when settings UI exposes From fields
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Security
- **Description:** The `From:` header is built by direct string concatenation of `$from_name` and `$from_address`: `'From: ' . $from_name . ' <' . $from_address . '>'`. `$from_name` is now read from `$this->settings->get('email.from_name', '')` (settings are live), so an admin saving `\r\n` in that field would inject arbitrary mail headers. Risk is real, not just pre-emptive.
- **Expected behavior:** Sanitize `from_name` and `from_address` before building the header by stripping newline/carriage-return characters. Use `sanitize_email()` for the address and a CRLF-stripping sanitizer for the name.
- **Notes:** `includes/class-alm-notification-manager.php:374` (settings read), `includes/class-alm-notification-manager.php:378` (header build). Now active risk since settings UI is complete.

### [Medium] `insertAdjacentHTML` with unvalidated DOM data in admin JS
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Security
- **Description:** In `admin-assets.js`, `initQuickActions()` extracts `postId` from `row.id.replace('post-', '')` and concatenates it directly into an HTML string passed to `insertAdjacentHTML`. If another script modifies a table row's `id` attribute, or if unexpected characters appear (e.g., `post-123"><script>`), the concatenated HTML could execute arbitrary code.
- **Expected behavior:** Replace `insertAdjacentHTML` with the `createElement` / `textContent` / `setAttribute` DOM API to ensure all values are treated as data and never interpreted as markup.
- **Notes:** `assets/js/admin-assets.js:80-86`. Practical risk is low (WordPress core controls the DOM), but the pattern is unsafe by construction.

### [Low] Notification logs expose recipient email addresses and subjects
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Security
- **Description:** `send_notification_email()` logs recipient (`to`) and email subject in plaintext via `ALM_Logger::info()`. With `WP_DEBUG` enabled, this can persist personal data and workflow details in server logs.
- **Expected behavior:** Avoid logging recipient addresses/subjects (or mask them), and keep verbose mail tracing behind an explicit opt-in debug setting.
- **Notes:** `includes/class-alm-notification-manager.php:382` (attempt log), `includes/class-alm-notification-manager.php:395` (failure log)

---

## Bug

### [Low] wp_redirect() used instead of wp_safe_redirect()
- **Status:** Open
- **Date:** 2026-02-15
- **Category:** Bug
- **Description:** `redirect_restricted_users()` calls `wp_redirect( home_url() )`. While `home_url()` is normally safe, `wp_safe_redirect()` is the WordPress-recommended function for internal redirects as it validates the destination against the allowed hosts list.
- **Expected behavior:** Replace `wp_redirect()` with `wp_safe_redirect()`.
- **Notes:** `includes/class-alm-admin-manager.php`, line 60.

### [High] Reject flow can write inconsistent history under concurrent processing
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Bug
- **Description:** The reject path reads the request row outside the transaction and, inside `reject_loan_request()`, inserts a `rejected` history entry before deleting the request. Deletion checks only `false` and treats `0 affected rows` as success. If another action processes/deletes the same request first, the reject path can still commit a stale/incorrect history row.
- **Expected behavior:** Re-read and lock the request row inside the transaction (`SELECT ... FOR UPDATE`), validate it is still pending, and require one affected row on delete before commit.
- **Notes:** `includes/class-alm-loan-manager.php:244` (stale read outside tx), `includes/class-alm-loan-manager.php:362` (history insert), `includes/class-alm-loan-manager.php:378` (delete), `includes/class-alm-loan-manager.php:384` (check `false` only)

### [Medium] ACF unavailability silently skips kit component propagation
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Bug
- **Description:** `get_kit_components()` calls `ALM_ACF_Asset_Adapter::get_custom_field('components', $asset_id)`. The adapter returns `null` when ACF is not available (`! function_exists('get_field')`); `get_kit_components()` treats `null` as empty array and silently returns `[]`. If ACF is deactivated while a kit loan is being approved or a direct assignment is processed, the kit is treated as if it has no components: the owner and state are updated on the kit post, but all component posts remain in their previous state. This leaves the data model in an inconsistent state with no error logged or surfaced to the user.
- **Expected behavior:** When ACF is not available during a kit operation, `execute_ownership_transfer()` should throw an exception to abort the transaction, or at minimum log an error and surface a user-visible failure response.
- **Notes:** `includes/class-alm-loan-manager.php:1158` (`get_kit_components`), `includes/class-alm-acf-asset-adapter.php:73` (silent null return). Only relevant if ACF is deactivated after kit assets are already created.

### [Medium] Duplicate pending requests possible under concurrent submissions
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Bug
- **Description:** Submission checks `has_pending_request()` and inserts via `create_loan_request()` in separate steps without row-level locking or DB uniqueness. Two near-simultaneous requests from the same user/asset can both pass the check and insert duplicate pending rows.
- **Expected behavior:** Make submission idempotent under concurrency (transactional lock/check at insert time and/or schema-level uniqueness strategy).
- **Notes:** `includes/class-alm-loan-manager.php:144` (pending check in AJAX handler), `includes/class-alm-loan-manager.php:153` (insert call in AJAX handler), `includes/class-alm-loan-manager.php:583` (`create_loan_request`), `includes/class-alm-loan-manager.php:651` (`has_pending_request`)

### [Medium] Concurrent cancellation can create stale history rows
- **Status:** Open
- **Date:** 2026-03-02
- **Category:** Bug
- **Description:** In `cancel_concurrent_requests()`, each pending request is logged to history before deletion, and delete failure checks only `false`. Under concurrent processing, `DELETE` can affect `0` rows while history has already been written, producing inconsistent audit entries.
- **Expected behavior:** Lock target rows before processing and require exactly one deleted row per request; rollback if row count is not `1`.
- **Notes:** `includes/class-alm-loan-manager.php:1188` (function), `includes/class-alm-loan-manager.php:1225` (history insert), `includes/class-alm-loan-manager.php:1246` (delete), `includes/class-alm-loan-manager.php:1252` (check `false` only)

---

## Refactoring

### [Low] Centralise loan status CSS class generation
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Refactoring
- **Description:** The loan status CSS modifier class is built inline via string concatenation (`'alm-status--' . $status`) in `asset-view.php`. The analogous asset-state mapping already uses `ALM_Asset_Manager::get_state_classes()`. Adding a `get_loan_status_classes()` method would centralise the mapping and make the slug→class relationship explicit rather than implicit.
- **Expected behavior:** Add `ALM_Asset_Manager::get_loan_status_classes()` returning a slug→CSS-class map; update `asset-view.php` to use it.
- **Notes:** Low urgency; useful if the badge is extended to other contexts. `templates/shortcodes/asset-view.php`, `includes/class-alm-asset-manager.php`

### [Low] Extract reusable PHP helper for loan status badge HTML
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Refactoring
- **Description:** The `<span class="alm-status-badge ...">` markup is produced inline in `asset-view.php`. A shared helper `alm_render_status_badge( $status, $label )` would eliminate duplication if the badge is reused in other templates or admin views.
- **Expected behavior:** Create helper function (e.g. in `plugin-config.php` or a new `functions-helpers.php`); replace inline markup.
- **Notes:** Premature abstraction at current single-usage; revisit if a second template adopts the badge. `templates/shortcodes/asset-view.php`

### [Low] Dead code: `$nonce` variable read but never used in `handle_autocomplete()`
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** Refactoring
- **Description:** `handle_autocomplete()` reads and sanitizes `$nonce = $request->get_param('nonce')` but never uses the variable for any purpose. The endpoint has `'permission_callback' => '__return_true'` so nonce validation is also not performed. The dead code is confusing and may mislead future developers into thinking nonce validation is active.
- **Expected behavior:** Remove the dead `$nonce` assignment until the endpoint actually validates nonces or is restricted to authenticated users.
- **Notes:** `includes/class-alm-autocomplete-manager.php:161-163`

### [Low] `echo` without escaping wrapper for pre-escaped `aria-label` variables
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** CodeStyle
- **Description:** In the loan requests table, `$alm_approve_label` and `$alm_reject_label` are escaped with `esc_attr()` at assignment time, then output with bare `echo`. WordPress Coding Standards require escaping at the point of output, not at assignment, because the escape context may not be obvious to future readers and PHPCS reports this pattern as a violation.
- **Expected behavior:** Move `esc_attr()` to the `echo` call site (e.g., `echo esc_attr( $alm_approve_label )`), or use `esc_attr_e()` directly.
- **Notes:** `templates/shortcodes/asset-view.php:386`, `templates/shortcodes/asset-view.php:396`

### [Low] `in_array()` without strict flag in `keep_alm_taxonomy_menu_open()`
- **Status:** Open
- **Date:** 2026-02-22
- **Category:** CodeStyle
- **Description:** `in_array( $taxonomy, ALM_CUSTOM_TAXONOMIES )` is called without the `true` strict comparison flag. This allows type-coercive matching, which can produce unexpected results when the taxonomy value is `null`, `false`, or a non-string type. WordPress Coding Standards require strict comparisons by default.
- **Expected behavior:** Add `true` as the third argument: `in_array( $taxonomy, ALM_CUSTOM_TAXONOMIES, true )`.
- **Notes:** `includes/class-alm-plugin-manager.php:317`

### [Low] Excessive debug console logs in JS
- **Status:** Open
- **Date:** 2026-02-12
- **Category:** Refactoring
- **Description:** Many debug logs expose runtime details and clutter browser console.
- **Expected behavior:** Remove logs or gate them behind explicit debug flag.
- **Notes:** `assets/js/frontend-assets.js`, `assets/js/admin-assets.js`

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
