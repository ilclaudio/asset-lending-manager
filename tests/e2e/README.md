# E2E Tests

E2E tests use Playwright against a real, dedicated WordPress site named `alm-e2e`. They exercise browser-visible workflows and reset the site to a committed synthetic baseline during normal runs.

For shared commands, safety rules, and test-lane selection, see the [test guide](../README.md).

## Dedicated site and working copy

Create a disposable local WordPress site named `alm-e2e`; do not use `alm-site`. Install and activate Asset Lending Manager and ACF.

The site must load this repository's active working copy. Prefer a symlink/junction from its plugin directory to this repository, rather than a physical copy, so browser tests cannot run against stale code. After creating or restoring the site, verify the link and activate the plugin again if needed.

Save permalinks once in the site's admin after activation so `/asset/` is available.

## Install browser dependencies

From the plugin root:

```bash
npm install
npx playwright install
```

Node.js and npm must be available in the same shell that runs Playwright.

## Configure and run

The normal suite resets the E2E database before and after the run. Set the filesystem path to the WordPress root; set the browser URL too when it differs from the default `https://alm-e2e.local`.

### PowerShell

```powershell
$env:ALMGR_E2E_BASE_URL = 'https://alm-e2e.local'
$env:ALMGR_E2E_SITE_PATH = 'C:/Users/<you>/Local Sites/alm-e2e/app/public'
composer test:e2e
```

### Bash

```bash
ALMGR_E2E_BASE_URL='https://alm-e2e.local' \
ALMGR_E2E_SITE_PATH='/c/Users/<you>/Local Sites/alm-e2e/app/public' \
composer test:e2e
```

Useful debug commands:

```bash
npm run test:e2e:headed
npm run test:e2e:ui
```

The debug variants set `ALMGR_E2E_SKIP_RESET`; they can leave mutations in the site and must never target a non-disposable installation.

## Baseline and fixtures

Normal runs import `tests/e2e/fixtures/baseline.sql` through the configured WordPress site. The baseline contains synthetic users and assets only. Extend fixtures through `tests/e2e/bin/seed-baseline.php`, regenerate the SQL dump, and scrub any personal data, real email addresses, SMTP settings, and background-job logs before committing it.

The reset requires `ALMGR_E2E_SITE_PATH`. Optional environment variables are documented in the E2E helper scripts, including overrides for the PHP binary, MySQL binary directory, and synthetic test-user credentials. Do not add real credentials to this document or to the baseline.

## Add and debug specs

Place specs in `tests/e2e/specs/`. Use the shared authentication helpers and assert browser-visible structure or behaviour rather than fragile translated text. Every spec must run from the baseline independently of execution order.

Common setup failures:

- `ALMGR_E2E_SITE_PATH is not set`: supply the filesystem path, not just the browser URL.
- `/asset/` returns 404: save permalinks in the E2E site's admin.
- `mysql` or `mysqldump` cannot be found: set the documented helper override for the MySQL binary directory.
- Playwright browsers are missing: run `npx playwright install`.
