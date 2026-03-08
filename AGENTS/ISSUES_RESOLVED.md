# ISSUES_RESOLVED
Last update: 2026-03-08 (rev 5)

---

### [High] Kit approval can override maintenance/retired component states
- **Status:** Resolved
- **Date:** 2026-03-04
- **Category:** Bug
- **Description:** During kit transfer, the conflict guard only blocked `on-loan` components with a different owner. Components in `maintenance` or `retired` state were not checked and were forcibly set to `on-loan` during propagation.
- **Resolution date:** 2026-03-08
- **Fix summary:** Extended the conflict guard in `execute_ownership_transfer()` to throw an exception if any kit component is in `maintenance` or `retired` state before the propagation loop runs. Also extracted `$component_title` before the inner checks to avoid redundant `get_the_title()` calls.
- **Notes:** `includes/class-alm-loan-manager.php` (`execute_ownership_transfer`, conflict guard block ~line 976)

---

### [High] Reject flow can write inconsistent history under concurrent processing
- **Status:** Resolved
- **Date:** 2026-02-22
- **Category:** Bug
- **Description:** The reject flow could insert a `rejected` history row before deleting the request and accepted `0 affected rows` as success, allowing stale history under concurrent processing.
- **Resolution date:** 2026-03-05
- **Fix summary:** `reject_loan_request()` now re-reads and locks the target request row inside the transaction (`SELECT ... FOR UPDATE`), validates that status is still `pending`, writes history from the locked row, and requires delete to affect exactly one row. Any mismatch now raises an exception and triggers rollback.
- **Notes:** `includes/class-alm-loan-manager.php` (`reject_loan_request`)

---

### [High] Loan request submission rejects assets that are currently on-loan
- **Status:** Resolved
- **Date:** 2026-03-04
- **Category:** Bug
- **Description:** `ajax_submit_loan_request()` allowed requests only when state was exactly `available`, rejecting valid hand-off requests when the asset was already `on-loan`.
- **Resolution date:** 2026-03-04
- **Fix summary:** Updated `ajax_submit_loan_request()` to treat both `available` and `on-loan` as loanable states. Requests are now rejected only when state is outside the loanable set (for example `maintenance` or `retired`).
- **Notes:** `includes/class-alm-loan-manager.php` (`ajax_submit_loan_request` state guard)

---

### [High] Loan/direct-assign governance settings are not enforced by runtime handlers
- **Status:** Resolved
- **Date:** 2026-03-02
- **Category:** Bug
- **Resolution date:** 2026-03-04
- **Fix summary:** Enforced 6 governance settings in AJAX handlers. In `ajax_submit_loan_request()`: added `loans.approver_policy_for_unowned_assets` check (blocks requests on unowned assets when policy is 'none'), `loans.allow_multiple_requests` check (blocks if user already has a pending request when disabled), and `loans.max_active_per_user` check (blocks if user is at the active loan limit, using new private helper `count_active_loans_for_user()`). In `ajax_direct_assign_asset()`: added `direct_assign.enabled` guard (blocks when feature is disabled), made reason requirement conditional on `direct_assign.require_reason` setting (was hardcoded mandatory), and replaced hardcoded role check with `direct_assign.allowed_target_roles` policy via `array_intersect`. Added two new private helpers: `has_any_pending_request()` and `count_active_loans_for_user()`.
- **Notes:** `includes/class-alm-loan-manager.php`

---

### [High] Loan request submission and approval do not validate asset state
- **Status:** Resolved
- **Date:** 2026-03-02
- **Category:** Bug
- **Description:** `ajax_submit_loan_request()` accepted requests for assets in any state. `approve_loan_request()` did not re-validate state inside the transaction.
- **Resolution date:** 2026-03-04
- **Fix summary:** Added `get_asset_state_slug()` check in `ajax_submit_loan_request()` immediately after the asset existence check: returns `wp_send_json_error` if state is outside the loanable states (`available`, `on-loan`). Added the same check inside the `approve_loan_request()` transaction after the asset existence check: throws an Exception (triggering rollback) if state is `retired` or `maintenance`.
- **Notes:** `includes/class-alm-loan-manager.php` (submit: ~line 133, approve: ~line 984). Reuses existing private helper `get_asset_state_slug()`.

---

### [Medium] Settings values stored but not consumed by runtime modules
- **Status:** Resolved
- **Date:** 2026-02-09
- **Category:** Refactoring
- **Description:** All runtime modules were using hardcoded constants instead of reading from `ALM_Settings_Manager`. Settings UI (10 tabs) was complete but disconnected from runtime.
- **Resolution date:** 2026-03-02
- **Fix summary:** Full wiring completed in two phases. Phase 1 (tabs 1–3, done previously): `ALM_Notification_Manager` reads `email.*`, `notifications.*`, `template.*`; `ALM_Loan_Manager` reads `loans.*` message lengths. Phase 2 (tabs 4–10, this session): `ALM_Frontend_Manager` (new `$settings` constructor injection) reads `frontend.asset_list_per_page`, `frontend.login_redirect_page_id`, `frontend.logout_redirect_page_id`, `autocomplete.min_chars`. `ALM_Autocomplete_Manager` (new `$settings` constructor injection) reads `autocomplete.min_chars`, `autocomplete.max_results`, `autocomplete.description_length`. `ALM_Loan_Manager` reads `workflow.cancel_concurrent_requests_on_assign`, `workflow.cancel_component_requests_when_kit_assigned`, `workflow.automatic_operations_actor_user_id`. `ALM_Asset_Manager::get_asset_code()` reads `asset.code_prefix` via `ALM_Plugin_Manager` singleton (method is `static`). `ALM_Plugin_Manager::init_modules()` updated to pass `$settings` to `ALM_Frontend_Manager` and `ALM_Autocomplete_Manager` constructors. All constants kept as fallback defaults.
- **Notes:** `includes/class-alm-plugin-manager.php`, `includes/class-alm-frontend-manager.php`, `includes/class-alm-autocomplete-manager.php`, `includes/class-alm-loan-manager.php`, `includes/class-alm-asset-manager.php`

---

### [High] Autocomplete hardening settings are saved but not enforced at runtime
- **Status:** Resolved
- **Date:** 2026-03-02
- **Category:** Security
- **Description:** The admin UI persisted `autocomplete.public_assets_endpoint_enabled`, `autocomplete.rate_limit_enabled`, and `autocomplete.rate_limit_per_minute`, but the public REST route remained fully open (`permission_callback => __return_true`) and runtime did not enforce the configured guards.
- **Resolution date:** 2026-03-02
- **Fix summary:** Applied runtime enforcement for `autocomplete.public_assets_endpoint_enabled` in `ALM_Autocomplete_Manager` via dedicated REST permission callback. When public access is disabled, endpoint access is limited to authenticated users with `ALM_VIEW_ASSETS`. Also stopped enqueuing frontend autocomplete assets for users who cannot access the endpoint. Removed unused rate-limit settings end-to-end (`rate_limit_enabled`, `rate_limit_per_minute`) from defaults, settings save handler, and admin settings UI to avoid non-functional controls.
- **Notes:** `includes/class-alm-autocomplete-manager.php`, `includes/class-alm-settings-manager.php`, `includes/class-alm-plugin-manager.php`, `admin/alm-settings-page.php`

---

### [Low] Hardcoded operator user ID for automatic operations
- **Status:** Resolved
- **Date:** 2026-02-12
- **Category:** Refactoring
- **Description:** Automatic operations (e.g. automatic cancellation of concurrent requests) used a hardcoded constant `AUTOMATIC_OPERATIONS_OPERATOR_ID` (value `1`) as the actor user ID, unsafe for installations where user ID 1 is not the designated operator.
- **Resolution date:** 2026-03-02
- **Fix summary:** `ALM_Loan_Manager::cancel_concurrent_requests()` now reads the actor ID from `$this->settings->get( 'workflow.automatic_operations_actor_user_id', self::AUTOMATIC_OPERATIONS_OPERATOR_ID )`. The constant remains as the safe fallback default. Operators can configure the correct user ID in Settings → Workflow tab.
- **Notes:** `includes/class-alm-loan-manager.php`

## Entry Format
```markdown
### [PRIORITY] Short descriptive title
- **Status:** Resolved
- **Date:** YYYY-MM-DD
- **Category:** Security | Bug | Refactoring | CodeStyle | Performance | Accessibility | Feature | Documentation
- **Description:** Original issue summary
- **Resolution date:** YYYY-MM-DD
- **Fix summary:** What changed and why
- **Notes:** Optional commit/PR/doc references
```

---

### [High] Operators cannot approve or reject loan requests for unowned assets
- **Status:** Resolved
- **Date:** 2026-02-22
- **Category:** Bug
- **Description:** `can_user_approve_request()` and `can_user_reject_request()` required the acting user to be the current asset owner. For unowned assets (`owner_id = 0`) this blocked the standard approve/reject flow and left requests stuck in `pending`.
- **Resolution date:** 2026-03-02
- **Fix summary:** Updated permission checks so operators (`ALM_EDIT_ASSET`) can approve/reject any pending request via `user_can( $user_id, ALM_EDIT_ASSET )` in both `can_user_approve_request()` and `can_user_reject_request()`. Updated request table UI condition to show approve/reject buttons to operators as well as current owners.
- **Notes:** `includes/class-alm-loan-manager.php`, `templates/shortcodes/asset-view.php`

---

### [High] Cancellation notifications fired before DB transaction commit
- **Status:** Resolved
- **Date:** 2026-03-02
- **Category:** Bug
- **Description:** `cancel_concurrent_requests()` fired `alm_loan_request_canceled` inside an open DB transaction. If later steps failed and rollback happened, users could receive cancellation notifications for changes that were never committed.
- **Resolution date:** 2026-03-02
- **Fix summary:** Refactored cancellation flow to queue notification payloads during transactional work and dispatch them only after a successful `COMMIT`. `execute_ownership_transfer()` now collects cancellation events, `cancel_concurrent_requests()` only records events, and both `approve_loan_request()` / `direct_assign_asset()` trigger notifications via a post-commit dispatcher.
- **Notes:** `includes/class-alm-loan-manager.php`

---

### [High] Minimum operator settings UI for runtime configuration
- **Status:** Resolved
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Settings structure existed but was not exposed via admin UI.
- **Resolution date:** 2026-02-25
- **Fix summary:** Implemented complete settings admin page (`admin/alm-settings-page.php`) with 10 tabs covering all parameters in `ParametriBackoffice.txt`: Email & Notifications, Email Templates, Loan Rules, Direct Assignment, Workflow, Frontend, Autocomplete & API, Logging & Audit, Asset Identification, Maintenance. All parameters saved via `ALM_Settings_Manager::set_batch()` with correct [A]/[A/O] access control. Runtime wiring of settings into modules is tracked as a separate Refactoring issue.
- **Notes:** `admin/alm-settings-page.php`, `includes/class-alm-settings-manager.php`, `includes/class-alm-plugin-manager.php`

---

### [Low] REST autocomplete handler logs nonce in plaintext
- **Status:** Resolved
- **Date:** 2026-02-14
- **Category:** Security
- **Description:** `handle_autocomplete()` was tracked as writing the received nonce to PHP error log.
- **Resolution date:** 2026-02-22
- **Fix summary:** Re-verified current code path and confirmed no nonce/token logging is present in `handle_autocomplete()`. The issue is no longer reproducible and has been moved out of TODO.
- **Notes:** `includes/class-alm-autocomplete-manager.php:143`

---

### [Medium] Asset detail — file links open in new tab without user warning (target=_blank)
- **Status:** Resolved
- **Date:** 2026-02-21
- **Category:** Accessibility
- **Description:** File download links (`user_manual`, `technical_data_sheet`) used `target="_blank"` without informing the user.
- **Resolution date:** 2026-02-21
- **Fix summary:** Added a screen-reader-only warning `(opens in new tab)` to file links opened with `target="_blank"` in asset detail optional fields.
- **Notes:** `templates/shortcodes/asset-view.php`

### [Low] Asset detail — `aria-required` missing on required textareas
- **Status:** Resolved
- **Date:** 2026-02-21
- **Category:** Accessibility
- **Description:** Required textareas used HTML `required` but not `aria-required="true"`.
- **Resolution date:** 2026-02-21
- **Fix summary:** Added `aria-required="true"` to required textareas in loan request and direct assignment forms.
- **Notes:** `templates/shortcodes/asset-view.php`

---

### [High] Real notification delivery for loan workflow events
- **Status:** Resolved
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Notification logic was placeholder/logging only. No emails were sent to requester, owner, or operators on any loan workflow event.
- **Resolution date:** 2026-02-19
- **Fix summary:** Implemented `ALM_Notification_Manager` with `wp_mail()` for all events: request submitted (to requester + owner + optional system address), approved, rejected, automatically canceled, and direct assign. `ALM_Loan_Manager` stub methods removed; replaced with `do_action()` calls for each event. Sender and template configuration added as constants in `plugin-config.php` (`ALM_EMAIL_FROM_NAME`, `ALM_EMAIL_FROM_ADDRESS`, `ALM_EMAIL_SYSTEM_ADDRESS`, subject/body template constants). Templates are translatable via `__()` at runtime.
- **Notes:** `plugin-config.php`, `includes/class-alm-notification-manager.php`, `includes/class-alm-loan-manager.php`

### [Medium] ALM_Notification_Manager is an empty stub
- **Status:** Resolved
- **Date:** 2026-02-09
- **Category:** Refactoring
- **Description:** Notification manager was non-functional; notification logic lived as private stubs in ALM_Loan_Manager.
- **Resolution date:** 2026-02-19
- **Fix summary:** Fully implemented ALM_Notification_Manager. Loan manager now fires custom WP actions (`alm_loan_request_submitted`, `alm_loan_request_approved`, `alm_loan_request_rejected`, `alm_loan_request_canceled`, `alm_direct_assign`); notification manager hooks into them. Four private stub methods removed from ALM_Loan_Manager.
- **Notes:** `includes/class-alm-notification-manager.php`, `includes/class-alm-loan-manager.php`

---

### [High] Autocomplete assets enqueued on every frontend page
- **Status:** Resolved
- **Date:** 2026-02-18
- **Category:** Performance
- **Description:** `ALM_Autocomplete_Manager::enqueue_assets()` ran on `wp_enqueue_scripts` without page checks, loading autocomplete JS/CSS and localized REST data on all frontend pages.
- **Resolution date:** 2026-02-19
- **Fix summary:** Added private `is_alm_page()` method to `ALM_Autocomplete_Manager` that checks for archive, single, and shortcode pages. Added early return guard at the top of `enqueue_assets()`.
- **Notes:** `includes/class-alm-autocomplete-manager.php`

---

### [High] Asset list rendering has N+1 user queries through wrapper hydration
- **Status:** Resolved
- **Date:** 2026-02-18
- **Category:** Performance
- **Description:** `get_asset_wrapper()` called `get_userdata()` for each asset in the list loop. With N assets this produced N separate user DB queries for owner data.
- **Resolution date:** 2026-02-19
- **Fix summary:** Added `cache_users()` call before the foreach in `render_asset_list_template()`. Owner IDs are collected from the already-cached post meta (pre-loaded by `WP_Query`) and bulk-loaded into the user object cache in a single query. Subsequent `get_userdata()` calls inside the loop are cache hits.
- **Notes:** `includes/class-alm-frontend-manager.php`

---

### [High] Asset detail tables perform repeated user lookups inside loops
- **Status:** Resolved
- **Date:** 2026-02-18
- **Category:** Performance
- **Description:** Loan requests and history tables called `get_userdata()` inside foreach loops for each row, producing repeated lookups as row count grows.
- **Resolution date:** 2026-02-19
- **Fix summary:** Added user ID prefetch in `asset-view.php` for both loan requests and history sections. The template now collects unique user IDs, primes cache with `cache_users()`, builds a user ID to display-name map once, and renders rows from the preloaded dictionary instead of calling `get_userdata()` per row.
- **Notes:** `templates/shortcodes/asset-view.php`

---

### [High] Loan tables miss composite indexes for real query patterns
- **Status:** Resolved
- **Date:** 2026-02-18
- **Category:** Performance
- **Description:** Runtime queries filter by multiple columns and sort by date (`asset_id + status + request_date`, `asset_id + changed_at`) but table schemas defined only single-column indexes.
- **Resolution date:** 2026-02-19
- **Fix summary:** Added composite indexes to installer table schemas: `asset_status_request_date`, `requester_request_date`, `requester_status_request_date` on `alm_loan_requests` and `asset_changed_at` on `alm_loan_requests_history`; also added `changed_by` index. Updated installer flow to always run `dbDelta()` so existing tables receive index migrations instead of being skipped when already present.
- **Notes:** `includes/class-alm-installer.php`

---

### [Low] Taxonomy filter values not validated as term slugs
- **Status:** Resolved
- **Date:** 2026-02-18
- **Category:** Bug
- **Description:** Filter values read from `$_GET['alm_structure']`, `$_GET['alm_type']`, `$_GET['alm_state']`, `$_GET['alm_level']` were sanitized with `sanitize_text_field()` but not constrained to slug format.
- **Resolution date:** 2026-02-19
- **Fix summary:** Replaced `sanitize_text_field()` with `sanitize_title()` for all taxonomy filter query args so values are normalized as slugs before building `tax_query`.
- **Notes:** `includes/class-alm-frontend-manager.php`

### [Low] Missing explicit relation in multi-filter tax_query
- **Status:** Resolved
- **Date:** 2026-02-18
- **Category:** Refactoring
- **Description:** `render_asset_list_template()` relied on implicit `AND` behavior in `tax_query` without declaring it explicitly.
- **Resolution date:** 2026-02-19
- **Fix summary:** Added explicit `'relation' => 'AND'` when building taxonomy filters to self-document intent and keep query semantics explicit.
- **Notes:** `includes/class-alm-frontend-manager.php`

---

### [Low] Plugin main admin page title is not rendered
- **Status:** Resolved
- **Date:** 2026-02-14
- **Category:** Bug
- **Description:** Template called `esc_html__()` without `echo`, so the `<h2>` heading was empty.
- **Resolution date:** 2026-02-19
- **Fix summary:** Replaced `esc_html__()` with `esc_html_e()` in `admin/plugin-main-page.php:7`.
- **Notes:** `admin/plugin-main-page.php`

---

### [Low] assets_count assigned inside foreach loop
- **Status:** Resolved
- **Date:** 2026-02-18
- **Category:** Bug
- **Description:** In `render_asset_list_template()`, `$assets_count = (int) $query->found_posts` was assigned inside the `foreach` loop, causing a redundant reassignment on every iteration.
- **Resolution date:** 2026-02-18
- **Fix summary:** Moved `$assets_count` assignment outside the `foreach`, immediately after the `have_posts()` check.
- **Notes:** `includes/class-alm-frontend-manager.php`

### [Low] jQuery declared as script dependency but vanilla JS policy applies
- **Status:** Resolved
- **Date:** 2026-02-15
- **Category:** Refactoring
- **Description:** `wp_enqueue_script()` calls for `alm-frontend-assets`, `alm-admin-assets`, and `alm-asset-autocomplete` declared `array( 'jquery' )` as dependency despite vanilla JS implementation.
- **Resolution date:** 2026-02-18
- **Fix summary:** Removed `jquery` dependency from all three script enqueues and refactored `assets/js/admin-assets.js` to vanilla JavaScript.
- **Notes:** `includes/class-alm-frontend-manager.php`, `includes/class-alm-admin-manager.php`, `includes/class-alm-autocomplete-manager.php`, `assets/js/admin-assets.js`; commits `46e6454`, `e0d1a57`.

---

### [Medium] Refactoring: duplicated ownership transfer logic in approve and direct_assign
- **Status:** Resolved
- **Date:** 2026-02-17
- **Category:** Refactoring
- **Description:** `approve_loan_request()` and `direct_assign_asset()` shared ~40 lines of identical ownership transfer logic (set owner, set state, kit propagation, cancel concurrent requests).
- **Resolution date:** 2026-02-17
- **Fix summary:** Extracted shared logic into private method `execute_ownership_transfer()`. Two callers differ only in `$exclude_request_id` (loan ID vs 0) and `$check_component_conflicts` (true for approve, false for direct_assign).
- **Notes:** `includes/class-alm-loan-manager.php`

### [Medium] Bug: approve_loan_request() blocked hand-off between members
- **Status:** Resolved
- **Date:** 2026-02-17
- **Category:** Bug
- **Description:** When an asset was `on-loan`, the approval flow threw "Asset is already on loan" even when the current owner (the borrower) was approving a request from another member, which is a legitimate hand-off operation.
- **Resolution date:** 2026-02-17
- **Fix summary:** Removed the blanket `on-loan` block from `approve_loan_request()` — `can_user_approve_request()` already guarantees the approver is the current owner. Also refined the kit component conflict check in `execute_ownership_transfer()` to only block when a component is on-loan to a user *different* from the current kit owner, allowing kit hand-offs without false positives.
- **Notes:** `includes/class-alm-loan-manager.php`

### [Low] Bug: wrong column labels and missing status in loan history table
- **Status:** Resolved
- **Date:** 2026-02-17
- **Category:** Bug
- **Description:** History table columns were labeled "Requester" (showing the recipient/assignee) and "New Owner" (showing the operator who acted). The `direct_assign` status was also missing from the labels map, displaying the raw slug instead of a human-readable label.
- **Resolution date:** 2026-02-17
- **Fix summary:** Renamed headers to "Recipient" and "Changed by" to match actual data semantics. Added `direct_assign => 'Direct assignment'` to status labels. Renamed CSS class `.alm-history-new-owner` to `.alm-history-changed-by`.
- **Notes:** `templates/shortcodes/asset-view.php`, `assets/css/asset-history-table.css`

---

### [High] Direct assignment by operator to member/operator
- **Status:** Resolved
- **Date:** 2026-02-14
- **Category:** Feature
- **Description:** Operators needed to assign assets directly to a target user without passing through the loan request flow.
- **Resolution date:** 2026-02-17
- **Fix summary:** Added `wp_ajax_alm_direct_assign_asset` AJAX handler in `ALM_Loan_Manager` with full transactional support (owner update, state transition, kit propagation, concurrent request cancellation, audit history). Added protected REST endpoint `POST /wp-json/alm/v1/users/autocomplete` in `ALM_Autocomplete_Manager`. Added frontend section VIII in `asset-view.php` (operator-only collapsible form with user autocomplete). Added `alm-user-autocomplete.js` with keyboard-accessible autocomplete widget. New history status value `direct_assign` with dedicated CSS badge.
- **Notes:** `includes/class-alm-loan-manager.php`, `includes/class-alm-autocomplete-manager.php`, `includes/class-alm-frontend-manager.php`, `templates/shortcodes/asset-view.php`, `assets/js/frontend-assets.js`, `assets/js/alm-user-autocomplete.js`, `assets/css/asset-history-table.css`.

---

### [High] Missing fail-fast capability checks in approve/reject AJAX handlers
- **Status:** Resolved
- **Date:** 2026-02-12
- **Category:** Security
- **Description:** Approve/reject handlers validated nonce but did not perform an immediate capability check at entry point.
- **Resolution date:** 2026-02-14
- **Fix summary:** Added fail-fast `current_user_can( ALM_VIEW_ASSET )` checks right after nonce validation in both AJAX handlers.
- **Notes:** `includes/class-alm-loan-manager.php`

### [Medium] Missing CSRF nonce field in loan request form template
- **Status:** Resolved
- **Date:** 2026-02-12
- **Category:** Security
- **Description:** Loan request form did not render `wp_nonce_field()` and relied only on localized JS nonce.
- **Resolution date:** 2026-02-14
- **Fix summary:** Added `wp_nonce_field( 'alm_loan_request_nonce', 'nonce' )` in form template and updated JS to send nonce from form with fallback.
- **Notes:** `templates/shortcodes/asset-view.php`, `assets/js/frontend-assets.js`

### [Critical] uninstall.php has multiple fatal errors
- **Status:** Resolved
- **Date:** 2026-02-09
- **Category:** Bug
- **Description:** Uninstall script used undefined symbols and had disabled table cleanup.
- **Resolution date:** 2026-02-14
- **Fix summary:** Reworked uninstall bootstrap, removed undefined vars, removed ALM capabilities/roles safely, dropped ALM tables, and cleaned plugin option.
- **Notes:** `uninstall.php`

### [Critical] Undefined variable in asset detail state lookup
- **Status:** Resolved
- **Date:** 2026-02-12
- **Category:** Bug
- **Description:** State taxonomy lookup used `$asset_id` instead of `$alm_asset_id`.
- **Resolution date:** 2026-02-14
- **Fix summary:** Updated state terms lookup to use `$alm_asset_id`, restoring correct state rendering in asset detail.
- **Notes:** `templates/shortcodes/asset-view.php`

### [High] XSS risk: unescaped output in templates
- **Status:** Resolved
- **Date:** 2026-02-12
- **Category:** Bug
- **Description:** Multiple dynamic outputs in frontend templates were printed without explicit escaping/sanitization.
- **Resolution date:** 2026-02-14
- **Fix summary:** Added context-safe output handling (`wp_kses_post`, `esc_html`, `esc_attr`, `sanitize_text_field`) for dynamic image/content/message fields in asset view/list templates.
- **Notes:** `templates/shortcodes/asset-view.php`, `templates/shortcodes/asset-list.php`

### [High] Incorrect escaping helper usage in templates
- **Status:** Resolved
- **Date:** 2026-02-12
- **Category:** CodeStyle
- **Description:** Templates used `esc_attr_e()` in text nodes and `echo` with escaping helpers not aligned to output context.
- **Resolution date:** 2026-02-14
- **Fix summary:** Replaced text-node labels with `esc_html_e()` and switched dynamic text outputs to context-correct HTML escaping.
- **Notes:** `templates/shortcodes/asset-view.php`, `templates/shortcodes/asset-list.php`

### [Medium] Race condition in loan approval transaction
- **Status:** Resolved
- **Date:** 2026-02-12
- **Category:** Bug
- **Description:** Approval could use stale request data under concurrent operations.
- **Resolution date:** 2026-02-14
- **Fix summary:** Added transaction-scoped lock and re-read (`SELECT ... FOR UPDATE`) for target request and related asset requests before state transition.
- **Notes:** `includes/class-alm-loan-manager.php`

### [Medium] Missing wp_unslash() on $_GET['asset'] before sanitization
- **Status:** Resolved
- **Date:** 2026-02-15
- **Category:** Bug
- **Description:** `$_GET['asset']` passed to `sanitize_title()` without `wp_unslash()` first, inconsistent with the rest of the file.
- **Resolution date:** 2026-02-15
- **Fix summary:** Added `wp_unslash()` wrapper before `sanitize_title()` in `get_asset_id_from_context()`.
- **Notes:** `includes/class-alm-frontend-manager.php`, line 309.

### [Medium] Null pointer risks in loan notification methods
- **Status:** Resolved
- **Date:** 2026-02-12
- **Category:** Bug
- **Description:** `get_userdata()` results dereferenced without null checks in `log_email_notification()`.
- **Resolution date:** 2026-02-15
- **Fix summary:** Added early return with error log when requester is not found; wrapped owner notification in null check.
- **Notes:** `includes/class-alm-loan-manager.php`
