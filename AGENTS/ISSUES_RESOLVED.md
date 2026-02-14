# ISSUES_RESOLVED
Last update: 2026-02-15

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
