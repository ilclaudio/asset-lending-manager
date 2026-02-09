# CLAUDE.md

## Full Project Context
Read `AGENTS.md` for complete project overview, architecture, data model, hooks, modules, implementation status, known bugs, and current gaps.

## Quick Reference

### What is this?
WordPress plugin (Asset Lending Manager) for a non-profit astronomy association to track and lend instruments, books, and magazines.

### Commands
- `composer lint` — Check code style.
- `composer lint:fix` — Auto-fix code style.
- `composer test:unit` — Run unit tests.
- `composer test:integration` — Run integration tests.
- `composer test:all` — Run all tests.

### Code Style
- Act as an expert PHP and WordPress developer.
- Use **tabs** for indentation, not spaces.
- Comments in **English**, at the beginning of each file and before every function, ending with a period.
- WordPress naming conventions for classes, files, variables, constants, and functions.
- Align assignments as required by WP coding standards.
- Text domain: `asset-lending-manager`.
- Package: `@package AssetLendingManager`.

### Key Priorities
- No bugs, no vulnerabilities (maximum security).
- Responsive pages (mobile-first).
- Accessibility (very important).
- WordPress best practices compliance.
- Simple, readable, modular code.

### Critical Bugs to Be Aware Of
- DB tables are never created on activation (`ALM_Plugin_Manager::activate()` missing `ALM_Installer::create_tables()` call).
- `uninstall.php` references undefined variables and will crash.
- Autocomplete REST endpoint is open to anonymous users (permission/nonce checks commented out).
- See `AGENTS.md` "Known Bugs" section for full details.

### Collaboration
- When in doubt, ask before writing code and propose alternatives.
- Always suggest the next step to quickly achieve the goal.
- Suggest refactorings when appropriate.
- Be concise but precise.
