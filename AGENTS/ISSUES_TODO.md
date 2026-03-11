# ISSUES TODO
Last update: 2026-03-11 (rev 2)

---

## Security

### [Medium] Email template placeholder injection via user display names
- **Status:** Done
- **Date:** 2026-02-22
- **Category:** Security
- **Resolution:** Replaced `str_replace()` with `strtr()` in `format_template()`. `strtr()` performs all substitutions in a single pass so replacement values are never re-scanned as keys, eliminating the iterative substitution vulnerability. (`includes/class-alm-notification-manager.php:419`)

### [Medium] Email header injection risk when settings UI exposes From fields
- **Status:** Done
- **Date:** 2026-02-22
- **Category:** Security
- **Resolution:** Applied `sanitize_email()` to `$from_address` and `str_replace(["\r","\n"], '', ...)` to `$from_name` before building the `From:` header. (`includes/class-alm-notification-manager.php:376-379`)

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

### [Medium] QR scanner accepts arbitrary same-origin URLs
- **Status:** Open
- **Date:** 2026-03-11
- **Category:** Security
- **Description:** `initQrScanner()` validates only `parsed.origin === window.location.origin` and then navigates to the decoded URL. A malicious QR code can therefore force navigation to any same-origin route, not just ALM scan targets, increasing abuse surface for GET endpoints with side effects or sensitive admin routes.
- **Expected behavior:** Accept only ALM scan URLs (for example, `?alm_scan=...` or a dedicated allowlisted path) and reject all other same-origin URLs.
- **Notes:** `assets/js/frontend-assets.js:1350-1359` (origin-only check and redirect).

---

## Bug

### [Medium] `?alm_scan=` on any page silently redirects to home on invalid code
- **Status:** Done
- **Date:** 2026-03-11
- **Category:** Bug
- **Description:** `handle_alm_scan_redirect()` is hooked to `template_redirect` and fires on every WordPress page load. When `$_GET['alm_scan']` is present but the code is invalid (e.g. `?alm_scan=foo` appended to any URL), the handler unconditionally calls `wp_safe_redirect( home_url('/') )` and exits, discarding the intended page. Any URL on the site can be silently hijacked to a home-page redirect by appending `?alm_scan=anything_invalid`, disrupting navigation for users who follow links with stray query parameters or for QR codes that encode a malformed code.
- **Expected behavior:** On invalid code, do not redirect — simply return and let WordPress render the current page normally. Only redirect to the asset permalink on a valid, resolvable code.
- **Notes:** `includes/class-alm-frontend-manager.php:605-619` (`handle_alm_scan_redirect`). Fix: remove the fallback `wp_safe_redirect( home_url('/') )` and replace it with a plain `return`.

### [Low] wp_redirect() used instead of wp_safe_redirect()
- **Status:** Done
- **Date:** 2026-02-15
- **Category:** Bug
- **Resolution:** Replaced `wp_redirect()` with `wp_safe_redirect()` in `redirect_restricted_users()` (`includes/class-alm-admin-manager.php:60`).

### [Medium] Loan request form is visible for non-loanable asset states
- **Status:** Done
- **Date:** 2026-03-04
- **Category:** Bug
- **Description:** In the asset detail template, the "Request loan" section is shown based on ownership/login checks but not on asset state. As a result, users can see the form also when asset state is `maintenance` or `retired`, then receive backend rejection.
- **Expected behavior:** Show the request form only when asset state is `available` or `on-loan` (and existing role/ownership checks pass).
- **Notes:** Fixed in `templates/shortcodes/asset-view.php` — added `in_array( $alm_state_slug, ['available','on-loan'], true )` to the section guard condition.

### [Medium] Direct assignment tab visible for maintenance/retired assets
- **Status:** Done
- **Date:** 2026-03-08
- **Category:** Bug
- **Description:** The "Direct assignment" collapsible section in the asset detail template was shown to operators regardless of asset state. An operator who had set an asset to `maintenance` or `retired` could still see the form, even though the backend (`direct_assign_asset()`) correctly blocks the operation.
- **Expected behavior:** Hide the direct assignment tab when asset state is `maintenance` or `retired`, consistent with the loan request form behavior.
- **Notes:** Fixed in `templates/shortcodes/asset-view.php:422` — added `! in_array( $alm_state_slug, ['maintenance','retired'], true )` to the section guard condition alongside the existing `$alm_is_operator` check.

### [Medium] Loan request message max length is hardcoded in frontend
- **Status:** Open
- **Date:** 2026-03-04
- **Category:** Bug
- **Description:** Frontend request validation and textarea attributes use fixed `500` chars, while backend limit is settings-driven (`loans.request_message_max_length`). When admin changes the setting, frontend and backend can diverge.
- **Expected behavior:** Localize the configured max length to JS and template attributes so client-side constraints match backend validation.
- **Notes:** `assets/js/frontend-assets.js:229-233`, `templates/shortcodes/asset-view.php:248-255`, `includes/class-alm-loan-manager.php:105-113`.

### [Medium] Kit component updates are not fully traceable in history
- **Status:** Open
- **Date:** 2026-03-04
- **Category:** Bug
- **Description:** When approving a kit request, owner/state changes are propagated to each component, but only one `approved` history row is written for the main kit asset. Component-level history remains incomplete for audit/debug purposes.
- **Expected behavior:** Add explicit history entries for affected components (or a linked audit mechanism) when kit propagation updates their ownership/state.
- **Notes:** `includes/class-alm-loan-manager.php:890-893`, `includes/class-alm-loan-manager.php:1023-1032`.

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

### [Medium] Direct assignment reason is always mandatory in frontend, ignoring settings
- **Status:** Done
- **Date:** 2026-03-08
- **Category:** Bug
- **Resolution:** Removed `direct_assign.require_reason` setting entirely. Assignment reason is now always required, consistent with how request message and rejection message work. Removed from: `class-alm-settings-manager.php` (default), `class-alm-loan-manager.php` (conditional guard → always validates), `class-alm-plugin-manager.php` (save handler), `admin/alm-settings-page.php` (UI checkbox), and all three translation files.

### [High] Unowned-assets approver policy is inconsistent across settings, UI, and backend flow
- **Status:** Done
- **Date:** 2026-03-11
- **Category:** Bug
- **Resolution:** Removed the `loans.approver_policy_for_unowned_assets` setting entirely. Unowned assets can always receive loan requests; only operators can approve them (consistent with the existing permission model). Removed from: `class-alm-settings-manager.php` (default), `class-alm-loan-manager.php` (submission guard), `class-alm-plugin-manager.php` (save handler), `admin/alm-settings-page.php` (UI section), and all three translation files (.pot, en_US.po, it_IT.po).

### [Medium] Rejection message max length is hardcoded in frontend modal
- **Status:** Open
- **Date:** 2026-03-11
- **Category:** Bug
- **Description:** Rejection modal validation in frontend JS is hardcoded to `255` chars (`maxlength`, counter, and guard), while backend validation uses the configurable setting `loans.rejection_message_max_length`. Changing the setting can cause frontend/backend divergence and confusing error flows.
- **Expected behavior:** Localize `loans.rejection_message_max_length` to frontend and use it consistently in modal attributes, character counter, and client-side validation.
- **Notes:** `assets/js/frontend-assets.js:874-893` (hardcoded `255`), `includes/class-alm-loan-manager.php:293-303` (settings-driven backend limit).

### [Medium] Asset state-change endpoint does not enforce source-state constraints
- **Status:** Done
- **Date:** 2026-03-08
- **Category:** Bug
- **Resolution:** Added source-state guard in `ajax_change_asset_state()`: reads current state via `get_asset_state_slug()` and rejects with error if not `available` or `on-loan`. (`includes/class-alm-loan-manager.php`).

### [Medium] Restore state endpoint does not enforce source-state constraints
- **Status:** Done
- **Date:** 2026-03-08
- **Category:** Bug
- **Resolution:** Guard already present in `ajax_restore_asset_state()` at lines 1947-1950: reads current state via `get_asset_state_slug()` and rejects if not `maintenance` or `retired`. No change needed.

### [Medium] Component removal from kit ignores ACF write failures
- **Status:** Open
- **Date:** 2026-03-08
- **Category:** Bug
- **Description:** `remove_component_from_kit()` calls `update_field()` but does not check its return value. If the ACF write fails, the state-change transaction can still commit, leaving the component logically moved to maintenance/retired while still referenced by parent kit data.
- **Expected behavior:** Check `update_field()` result and throw on failure so `change_asset_state()` can rollback atomically.
- **Notes:** `includes/class-alm-loan-manager.php:1761-1765` (caller), `includes/class-alm-loan-manager.php:1848-1863` (unchecked `update_field`).

---

## Refactoring

### [Medium] Split `ALM_Loan_Manager` into focused services and shared transaction helpers
- **Status:** Open
- **Date:** 2026-03-08
- **Category:** Refactoring
- **Description:** `ALM_Loan_Manager` currently combines AJAX adapters, permission checks, payload parsing, domain transitions, persistence, and transaction orchestration in a single class (~1865 LOC). Multiple methods replicate the same transactional structure (`START TRANSACTION` / `COMMIT` / `ROLLBACK`) and error-to-response patterns, increasing cognitive load and regression risk.
- **Expected behavior:** Extract dedicated collaborators (for example: request policy/validation, transition service, persistence repository, transaction runner) and keep AJAX handlers thin. Reuse a shared transaction wrapper to remove duplicated try/catch transaction scaffolding across request creation, approval, direct assignment, and state change flows.
- **Notes:** Evidence in `includes/class-alm-loan-manager.php:89-226`, `includes/class-alm-loan-manager.php:537-640`, `includes/class-alm-loan-manager.php:652-699`, `includes/class-alm-loan-manager.php:1060-1194`, `includes/class-alm-loan-manager.php:1572-1653`, `includes/class-alm-loan-manager.php:1728-1810`.

### [Medium] Break up `frontend-assets.js` and move runtime-injected CSS to static stylesheet
- **Status:** Open
- **Date:** 2026-03-08
- **Category:** Refactoring
- **Description:** `assets/js/frontend-assets.js` currently mixes unrelated responsibilities (filters, forms, modals, request actions, URL message handling) and injects a large CSS block directly into the DOM at runtime. This makes maintenance and debugging harder, reduces cacheability of style rules, and couples presentation concerns to behavior code.
- **Expected behavior:** Split frontend logic into smaller modules (loan forms, request actions, modal utilities, state-change actions, shared AJAX helpers) and move injected CSS from JS to `assets/css/frontend-assets.css`, keeping JS focused on behavior only.
- **Notes:** Evidence in `assets/js/frontend-assets.js:9-1194` (monolithic object) and `assets/js/frontend-assets.js:1199-1446` (dynamic style injection).

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

### [Medium] Active-loan limit check performs full asset scan on each request
- **Status:** Open
- **Date:** 2026-03-08
- **Category:** Performance
- **Description:** `count_active_loans_for_user()` executes a `WP_Query` with `posts_per_page = -1` and meta filter on every loan request submission. This scales poorly as asset volume grows and increases latency of the request endpoint.
- **Expected behavior:** Replace with a bounded/count query (or cached counter) and avoid full ID scans for simple cardinality checks.
- **Notes:** `includes/class-alm-loan-manager.php:764-780`.

### [Medium] Parent kit lookup in state-change flow uses uncached full-scan meta LIKE query
- **Status:** Open
- **Date:** 2026-03-08
- **Category:** Performance
- **Description:** `get_parent_kit_ids()` uses `WP_Query` with `posts_per_page = -1` and `meta_query` `LIKE` on serialized components to find all kits containing a component. During repeated state-change operations this introduces expensive full scans.
- **Expected behavior:** Use a normalized relationship/index (or cache strategy) to avoid repeated serialized `LIKE` scans.
- **Notes:** `includes/class-alm-loan-manager.php:1820-1837`.

### [Low] User autocomplete assets are enqueued on all asset pages, even when unused
- **Status:** Open
- **Date:** 2026-03-08
- **Category:** Performance
- **Description:** `enqueue_frontend_assets()` always enqueues `alm-user-autocomplete` and localizes REST data on asset pages. However, direct assignment UI is operator-only, so most visitors download and parse unused JS.
- **Expected behavior:** Enqueue user-autocomplete assets only when the page context requires them (operator-visible direct assignment or explicit owner-filter UI).
- **Notes:** `includes/class-alm-frontend-manager.php:246-267`.

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
- **Status:** Won't fix
- **Date:** 2026-02-14
- **Category:** Feature
- **Resolution:** By design: an asset remains assigned to the current owner indefinitely until a new loan request is approved and the asset is transferred. Explicit return/check-in is not part of the intended workflow for this association.

### [High] Asset state change (maintenance/retired/restore) from frontend
- **Status:** Done
- **Date:** 2026-03-08
- **Category:** Feature
- **Description:** Operators have no dedicated frontend UI to change asset state to `maintenance` or `retired`. State changes currently require WP admin access. No propagation logic or history tracking exists for these transitions.
- **Expected behavior:** Add a collapsible section in the asset detail page (operator-only) with two actions: set to maintenance / set to retired. Kit propagates state to all components (components stay in kit). A component in a kit gets removed from the kit. Owner is cleared to 0 in all cases. A history row is written (`to_maintenance` / `to_retired`). If asset is `on-loan`, a confirmation warning is shown before proceeding. A "Restore to available" button is shown when asset is in `maintenance` or `retired`; restores state, re-adds component to previous kit(s), writes `to_available` history.
- **Notes:** New history status values: `to_maintenance`, `to_retired`, `to_available`. New post meta: `_alm_removed_from_kit_ids`. Files: `includes/class-alm-loan-manager.php`, `templates/shortcodes/asset-view.php`, `assets/js/frontend-assets.js`, `assets/css/frontend-assets.css`, `assets/css/asset-history-table.css`, `includes/class-alm-frontend-manager.php`, `plugin-config.php`.

### [High] Integration test suite for core AJAX and workflow state transitions
- **Status:** Open
- **Date:** 2026-03-08
- **Category:** Feature
- **Description:** The plugin currently lacks a complete integration test suite covering real WordPress bootstrap, real DB operations, custom ALM tables, and end-to-end AJAX handlers for loan workflows. This leaves critical state transitions under-tested (`request`, `approve`, `reject`, `direct assign`, `change state`) and increases regression risk.
- **Expected behavior:** Implement the integration testing strategy defined in `AGENTS/TESTS_INTRODUCTION.md` using `PHPUnit + WP_UnitTestCase`, including fixture/factory helpers and prioritized coverage of core scenarios (T1-T17), with initial focus on blocking/security paths and main happy paths.
- **Notes:** Priority is explicitly marked as highest among test initiatives in `AGENTS/TESTS_INTRODUCTION.md` (phases 1-4).

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

### [Low] Incremental unit test coverage for extracted domain logic
- **Status:** Open
- **Date:** 2026-03-08
- **Category:** Feature
- **Description:** Unit test coverage is currently missing for pure business rules (state transition guards, permission policies, payload validation). Without targeted unit tests, refactors on isolated logic are slower and less safe.
- **Expected behavior:** Introduce unit tests incrementally, following `AGENTS/TESTS_INTRODUCTION.md`: extract small pure functions/methods from WP-coupled code and test them with `Brain Monkey`, without large rewrites.
- **Notes:** Planned after integration baseline stability (phase 5), with micro-refactors only.

### [Medium] Functional E2E test suite for critical user journeys
- **Status:** Open
- **Date:** 2026-03-08
- **Category:** Feature
- **Description:** There is no browser-level functional verification of end-user workflows across roles (`alm_member`, `alm_operator`). Integration tests alone cannot catch all UI/runtime integration failures.
- **Expected behavior:** Add a compact `Playwright` E2E suite covering only critical narratives from `AGENTS/TESTS_INTRODUCTION.md` (about 5-6 scenarios), including at least concurrent request cancellation, kit conflict rollback, and kit retirement propagation.
- **Notes:** Low-priority and optional phase after integration/unit consolidation (phase 6).

---

## Documentation

### [High] Role-based UAT checklist for core lending flows
- **Status:** Open
- **Date:** 2026-02-14
- **Category:** Documentation
- **Description:** There is no explicit user acceptance test checklist/report that validates core workflows for `alm_member` and `alm_operator` across happy paths and error paths.
- **Expected behavior:** Create and maintain a concise UAT checklist with pass/fail evidence for core flows (request, approve, reject, return/closure, permission checks, error handling) before opening broader user testing.
- **Notes:** Go/No-Go prerequisite for test phase rollout.
