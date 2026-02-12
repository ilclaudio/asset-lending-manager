# WISH LIST
Last update: 2026-02-12

This file collects feature ideas and improvements that are desirable but not yet approved as actionable issues.

## How to use this file

- Store exploratory ideas and medium/long-term opportunities.
- Keep entries concise and focused on user value.
- Move an item to `ISSUES_TODO.md` only when approved and ready for planning/implementation.

## Wish Template

```markdown
### [PRIORITY] Short feature title
- **Status:** Idea | Under Evaluation | Planned
- **Date:** YYYY-MM-DD
- **Expected value:** Why this matters
- **Impact area:** Users | Editors | Administrators | Developers
- **Technical notes:** Optional implementation hints
```

---

### [Low] CSV export of assets, loans, and loan requests
- **Status:** Idea
- **Date:** 2026-02-12
- **Expected value:** Allows operators to analyze data offline and share reports with the association board.
- **Impact area:** Operators | Administrators
- **Technical notes:** Could leverage `fputcsv()` with a custom admin page or AJAX endpoint. Consider exporting assets, active loans, and loan request history as separate CSV files or tabs.

### [Low] REST API for external application integration
- **Status:** Idea
- **Date:** 2026-02-12
- **Expected value:** Enables third-party tools (mobile apps, dashboards) to interact with assets and loan workflows programmatically.
- **Impact area:** Developers | Administrators
- **Technical notes:** Build on WordPress REST API infrastructure. Requires authentication (Application Passwords or JWT) and capability checks. Endpoints for assets CRUD, loan requests, and loan status updates.