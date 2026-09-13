# E2E Functional Tests — Setup Guide

These tests use Playwright to exercise the plugin through a real browser against
an actual local WordPress installation.

Unlike the integration suite, this lane does not use `wordpress-develop` or the
WordPress PHPUnit framework. It uses a normal WordPress site with a real
database, real HTTP responses, and the plugin activated exactly as it would be
in day-to-day usage.

For this repository, the dedicated E2E target is the `alm-e2e` site, not the
main `alm-site` installation.

---

## Purpose of this lane

This is the functional test lane of the project.

In practical terms, that means:

- tests run against a real WordPress site;
- the plugin must already be installed and active;
- the browser interacts with frontend pages and real routing;
- the initial smoke tests should stay read-only whenever possible.

At this phase the goal is not broad end-to-end coverage.
The goal is to prove that browser automation is wired correctly and can be run
reliably from the repository.

---

## Prerequisites

- Git
- PHP and Composer available in the shell
- Node.js and npm available in the same shell used to run Playwright
- LocalWP (or another local WordPress environment)
- a dedicated local WordPress site for E2E, named `alm-e2e`

Because Playwright launches a real browser against a real site, a real
WordPress database is required for this lane.

Unlike the integration suite, the E2E suite does not create or reset WordPress
tables automatically. It uses the actual database attached to the `alm-e2e`
site.

---

## Step 1 — Clone the repository

Clone the plugin repository wherever you keep your local development projects.

```bash
git clone https://github.com/ilclaudio/asset-lending-manager.git
```

If you already have the repository locally, just update it:

```bash
git pull
```

---

## Step 2 — Create the `alm-e2e` WordPress site

Create a dedicated local WordPress site for browser testing.

Recommended rule:

- keep `alm-e2e` separate from `alm-site`;
- do not run E2E tests against your main development site;
- keep this site disposable, so it can be recreated if future tests start
  mutating data heavily.

### If you use LocalWP

Create a new site named `alm-e2e`.

Two practical options are acceptable:

- create it from scratch as a clean WordPress installation;
- clone `alm-site` into `alm-e2e` if you want the same initial content,
  configuration, and plugin state.

After creation, make note of:

- the local site URL;
- the site path on disk;
- whether LocalWP assigned a domain like `alm-e2e.local` or something else.

### If you use another local environment

Create any equivalent dedicated local WordPress site and make sure it has:

- a reachable local URL;
- its own database;
- WordPress fully installed;
- admin access.

---

## Step 3 — Put the plugin code inside the `alm-e2e` site

The site must load the plugin code from this repository.

Typical LocalWP path:

```text
C:/Users/<YOUR_USERNAME>/Local Sites/alm-e2e/app/public/wp-content/plugins/asset-lending-manager
```

You can do this in either of these ways:

1. clone the repository directly into the `plugins/` directory of `alm-e2e`;
2. copy your existing working repository into that plugin path;
3. **recommended:** create a symlink/junction so the site points to your
   active working copy (e.g. on Windows: `New-Item -ItemType Junction -Path
   "<alm-e2e>/wp-content/plugins/asset-lending-manager" -Target
   "<this repository>"`).

A physical copy (options 1-2) goes out of sync with every refactor — this
already caused a fatal error on `alm-e2e` once (a class introduced on the
working copy was missing from the stale copy). The junction/symlink in option
3 avoids that entirely: `alm-e2e` always runs exactly what's in this
repository, with zero extra sync step.

The important requirement is simple:
the `alm-e2e` site must be running this repository's code, not a stale copy.

---

## Step 4 — Activate and verify the plugin in `alm-e2e`

Open the `alm-e2e` site admin and make sure:

- the plugin is installed;
- the plugin is activated;
- ACF (Advanced Custom Fields) is also installed and activated — the plugin requires it;
- the site frontend loads without fatal errors;
- at least one asset post is published — the detail-page smoke test navigates from
  the archive to the first available asset and will fail if the list is empty.

Because the first smoke test opens `/asset/`, also verify this once manually:

1. open the admin;
2. go to `Settings > Permalinks`;
3. click `Save Changes` once, even if you do not change anything.

This flushes rewrite rules so the asset archive route is registered correctly.

If `/asset/` still returns `404`, check that:

- the plugin activated successfully;
- the custom post type registration ran;
- permalinks were saved after activation.

---

## Step 5 — Install Node dependencies from the repository root

From the plugin repository root, install the Node dependencies:

```bash
npm install
```

This installs the Playwright test runner declared in `package.json`.

Then install the Playwright browsers:

```bash
npx playwright install
```

If you want to install only the Chromium browser later, that is also possible,
but the generic install command is the simplest starting point.

---

## Step 6 — Set the E2E base URL and site path

Two environment variables are required before running the suite — one for
the browser, one for the database reset:

- `ALMGR_E2E_BASE_URL` — the URL Playwright's browser navigates to. Falls
  back to `https://alm-e2e.local` if unset.
- `ALMGR_E2E_SITE_PATH` — the **filesystem** path to the `alm-e2e` site root
  (the folder containing `wp-config.php`), used by `global-setup.js`/
  `global-teardown.js` to reset the database before and after every run (see
  "Database reset" below). There is no fallback: **this one is required**, and
  the suite fails immediately if it's missing.

### PowerShell

```powershell
$env:ALMGR_E2E_BASE_URL = "https://alm-e2e.local"
$env:ALMGR_E2E_SITE_PATH = "C:\Users\<you>\Local Sites\alm-e2e\app\public"
composer test:e2e
```

### Bash

```bash
ALMGR_E2E_BASE_URL="https://alm-e2e.local" \
ALMGR_E2E_SITE_PATH="/c/Users/<you>/Local Sites/alm-e2e/app/public" \
composer test:e2e
```

Both only apply to the current terminal session. To avoid setting them every
time, add the lines to your PowerShell profile (`$PROFILE`) or set them as
persistent user environment variables in Windows.

If your local site uses a different URL or path, replace them accordingly.
The full list of environment variables the E2E lane understands (including
the ones for `mysql`/`mysqldump` resolution and test-user credentials) is in
"Database reset" → "Required environment variables" below.

---

## Step 7 — Run the tests

Primary project command:

```bash
composer test:e2e
```

Direct npm equivalent:

```bash
npm run test:e2e
```

Optional variants:

```bash
npm run test:e2e:headed   # opens a visible browser window — useful to watch what Playwright does
npm run test:e2e:ui       # opens the Playwright interactive UI — useful to debug a failing test step by step
```

Expected result for the current smoke tests:

```text
Running 5 tests using 1 worker
  ✓ Asset archive smoke test › asset archive page renders without a fatal frontend failure
  ✓ Asset detail smoke test › asset detail page renders without a fatal frontend failure
  ✓ Asset list filter smoke tests › filter by type renders without a fatal frontend failure
  ✓ Asset list filter smoke tests › filter by structure renders without a fatal frontend failure
  ✓ Asset list filter smoke tests › text search renders without a fatal frontend failure
  5 passed
```

---

## Current smoke tests

### archive-smoke

- open `/asset/`;
- verify that the page responds successfully;
- verify that the page body renders;
- verify that no WordPress fatal error is present.

### asset-detail-smoke

- open `/asset/` and verify the asset list renders;
- navigate to the first asset card link;
- verify the detail page responds successfully;
- verify `article.almgr-asset-view` and `h1.almgr-asset-title` are visible;
- verify the title is non-empty;
- verify that no WordPress fatal error is present.

### filters-smoke (3 tests)

- **Filter by type:** navigate to `/asset/?almgr_type=telescope`; verify the type select
  reflects the active filter; verify results section and form render; verify no fatal error.
- **Filter by structure:** navigate to `/asset/?almgr_structure=kit`; same checks on the
  structure select.
- **Text search:** read the first asset title from the archive; navigate to
  `/asset/?s=<first-word>`; verify the search input reflects the term; verify at least one
  result appears; verify no fatal error.

The type and structure filter tests use `telescope` and `kit` as filter values — both are
default taxonomy terms seeded by the plugin on activation and are always present in a
correctly configured `alm-e2e` site.

All tests are intentionally read-only and leave the database unmodified.

---

## Database reset

The current smoke tests are read-only and do not need a reset to pass. Newer
and future specs are not: loan requests, approvals, direct assignment, state
changes, and kit propagation all mutate the database, so the suite resets
`alm-e2e` to a known snapshot before (and after) every run.

Full design rationale: `DEV/TODO/TODO_ResetPlan.md`.

### How the reset works

1. A baseline SQL dump (synthetic users and assets only) is committed at
   `tests/e2e/fixtures/baseline.sql`.
2. `playwright.config.js` registers `tests/e2e/global-setup.js`, which runs
   `wp db import tests/e2e/fixtures/baseline.sql` against `alm-e2e` before the
   suite starts.
3. `tests/e2e/global-teardown.js` repeats the same import after the suite
   finishes, so `alm-e2e` is left in the same known state for manual
   inspection between sessions.
4. No per-test teardown is needed: since every run starts from the same
   snapshot, later tests never see earlier tests' mutations.

### What's in the baseline

- Users: one operator (`e2e-operator`) and two members (`e2e-member-1`,
  `e2e-member-2` — the second exists specifically for the concurrent
  loan-request-cancellation scenario).
- Assets: one available component, one kit with two available components, and
  one additional component already on loan to `e2e-member-1` (so
  return/force-return/reject scenarios don't need to create and approve a
  request first).
- Seeded by `tests/e2e/bin/seed-baseline.php`, run once via
  `wp eval-file tests/e2e/bin/seed-baseline.php --path=<alm-e2e root>` against
  an otherwise-clean `alm-e2e` site, then exported with `wp db export` into
  `tests/e2e/fixtures/baseline.sql`.

### Required environment variables

| Variable | Purpose | Default |
| --- | --- | --- |
| `ALMGR_E2E_SITE_PATH` | Filesystem path to the `alm-e2e` WordPress root (needed by the reset, not just the browser). | none — required |
| `ALMGR_E2E_PHP_BIN` | PHP CLI binary used to run `wp-cli.phar`. | `php` |
| `ALMGR_E2E_MYSQL_BIN_DIR` | Override for the directory containing `mysql`/`mysqldump`. Auto-detected on Windows from Local's `lightning-services` folder; set this explicitly if auto-detection picks the wrong engine version (you have more than one MariaDB/MySQL version installed in Local) or on a non-Windows setup. | auto-detected (Windows) / none |
| `ALMGR_E2E_OPERATOR_USER` / `ALMGR_E2E_OPERATOR_PASS` | Login used by `loginAsOperator()` (`tests/e2e/support/auth.js`). | `e2e-operator` / `e2e-operator-pw` |
| `ALMGR_E2E_MEMBER_USER` / `ALMGR_E2E_MEMBER_PASS` | Login used by `loginAsMember()`. | `e2e-member-1` / `e2e-member-1-pw` |
| `ALMGR_E2E_SKIP_RESET` | Set (to any value) to skip the database reset entirely. Set automatically by `npm run test:e2e:headed`/`test:e2e:ui` — interactive debugging should not re-import the database on every launch. | unset |

Changing the credential env vars only changes who a spec logs in as; it does
not change what's stored in `baseline.sql`. If you need different fixture
credentials, edit `tests/e2e/bin/seed-baseline.php` and regenerate the dump
(see below).

### Regenerating the baseline

Needed whenever a new scenario requires new fixture data (a new asset, a new
user, a changed initial state):

```bash
# 1. Bring alm-e2e's content back to a clean slate (only if it has drifted —
#    ad hoc content from manual testing, leftover mutations from a run that
#    crashed before global-teardown could run, etc.). There is no single
#    command for this: it means removing almgr_asset posts, loan-request
#    table rows, and any non-essential users beyond the three fixture ones,
#    via `wp post delete`, `wp db query`, `wp user delete`, or by recreating
#    the alm-e2e site from scratch.
# 2. Re-run (or update, then re-run) the seed script:
node tests/e2e/bin/ensure-wp-cli.js   # only needed once, idempotent
php tests/e2e/.bin/wp-cli.phar --path="<alm-e2e root>" eval-file tests/e2e/bin/seed-baseline.php

# 3. Export the new baseline:
php tests/e2e/.bin/wp-cli.phar --path="<alm-e2e root>" db export tests/e2e/fixtures/baseline.sql

# 4. Scrub personal/real data before committing — see checklist below.

# 5. Commit the updated tests/e2e/fixtures/baseline.sql.
```

**Before committing a regenerated dump, scrub anything real out of it.**
`alm-e2e` is meant to hold only synthetic data, but a real WordPress install
running on a developer's own machine accumulates real values in a few options
regardless (admin email, third-party plugin settings, background-job logs).
Search the exported `.sql` file for anything identifying before committing —
at minimum:

- `wp option get admin_email` / the site's admin user's real email — replace
  with a synthetic address (e.g. `admin@example.test`) if it isn't already.
- Any third-party plugin storing real credentials in `wp_options` (e.g. an
  SMTP plugin's saved username/password) — delete the option
  (`wp option delete <option_name>`) if the E2E suite doesn't need it, or blank
  out just the sensitive fields (`wp option patch update ...`) if it does.
- `almgr_settings.email.from_address` / `system_email` — replace with
  synthetic addresses via `wp option patch update almgr_settings email from_address "noreply@example.test"`
  (repeat for `system_email`).
- Background-job/log tables that can retain old error messages referencing
  real addresses or domains (e.g. `wp_actionscheduler_*`, or any mail-plugin
  debug-log table) — safe to `TRUNCATE` entirely, they hold no fixture data.

A quick way to check: `grep` the exported file for your own name, domain, or
email address before running step 5.

`mysql`/`mysqldump` must be reachable for steps 2-3: either already on your
`PATH`, or resolved automatically by `tests/e2e/bin/resolve-mysql-bin.js`
(Windows/Local only — run `node tests/e2e/bin/resolve-mysql-bin.js` to see what
it detects), or pointed at explicitly via `ALMGR_E2E_MYSQL_BIN_DIR`.

---

## How it works internally

1. `composer test:e2e` delegates to `npm run test:e2e`.
2. The `pretest:e2e` npm hook runs `tests/e2e/bin/ensure-wp-cli.js`, downloading
   `wp-cli.phar` into `tests/e2e/.bin/` if it isn't already there.
3. npm runs Playwright using the repository `package.json`.
4. Playwright loads `playwright.config.js`, which imports the baseline database
   via `global-setup.js` before the suite and again via `global-teardown.js`
   after it (see "Database reset" above).
5. The config points tests to `tests/e2e/specs/`.
6. The browser uses `ALMGR_E2E_BASE_URL` or the fallback URL.
7. The suite opens real frontend pages of the `alm-e2e` site and evaluates the response.

---

## Common errors

**`net::ERR_NAME_NOT_RESOLVED`**
The URL in `ALMGR_E2E_BASE_URL` does not exist or the `alm-e2e` site has not
been created yet. Verify the actual LocalWP domain and pass it explicitly.

**`404` on `/asset/`**
The plugin rewrite rules were not flushed. Open `Settings > Permalinks` in the
`alm-e2e` admin and click `Save Changes` once.

**`Fatal error` or `There has been a critical error on this website.`**
The site is reachable, but WordPress or the plugin failed during rendering.
Open the same page manually in the browser and inspect the PHP/WordPress logs.

**`ALMGR_E2E_SITE_PATH is not set`**
The database reset (`global-setup.js`/`global-teardown.js`) needs the
filesystem path to the `alm-e2e` WordPress root, separately from
`ALMGR_E2E_BASE_URL` (which is only the browser-facing URL). Set it, e.g.:

```bash
ALMGR_E2E_SITE_PATH="/c/Users/<you>/Local Sites/alm-e2e/app/public" composer test:e2e
```

**`wp db import`/`wp db export` fails, or a connection error mentioning a port**
LocalWP assigns each site its own MySQL/MariaDB port, injected only into the
PHP process Local itself manages — a `php` CLI started from your own shell
does not see it, and `wp-cli.phar` will fail to connect (or some sub-steps of
`db import`/`export` will, while others silently succeed) unless the site's
`wp-config.php` sets `DB_HOST` explicitly with that port:

```php
define( 'DB_HOST', '127.0.0.1:<port>' );
```

Find `<port>` in Local's UI (site → "Database" tab). If Local ever reassigns
the port (typically only when the site is recreated), update this line.

**`wp db import`/`export` fails with `ERROR at line 1: Unknown command '\U'`**
On Windows, the `mysql` client's `source` command (used internally by `wp db
import`) does its own backslash escape processing, so a path like
`C:\Users\...\baseline.sql` breaks (`\U` looks like an unknown client
command). `run-wp-cli.js` normalizes every path it builds to forward slashes
for exactly this reason, so this only bites if you run `wp-cli.phar`
manually with a backslash path — e.g. when following the "Regenerating the
baseline" steps above, prefer `--path="C:/Users/.../alm-e2e/app/public"`
(forward slashes) over the Windows-native backslash form.

**`mysqldump`/`mysql` not found, or `resolve-mysql-bin.js` reports nothing**
LocalWP does not put these on your system `PATH`; they live inside Local's own
per-engine runtime folder
(`%APPDATA%/Local/lightning-services/<mariadb|mysql>-<version>/bin/win32/bin/`
on Windows). `tests/e2e/bin/run-wp-cli.js` resolves this automatically via
`resolve-mysql-bin.js` for every `wp db export`/`import` call. If you have more
than one engine version installed and it picks the wrong one, or you're not on
Windows, set `ALMGR_E2E_MYSQL_BIN_DIR` explicitly.

**`npm` works but `node` is missing**
You may be picking up a Windows npm shim from WSL instead of a Linux Node
installation. Verify both commands in the same shell with:

```bash
which node
which npm
node --version
npm --version
```

**Playwright is installed but browsers are missing**
Run:

```bash
npx playwright install
```

---

## Notes for this environment

- Use `alm-e2e` as the dedicated functional-testing site.
- Do not point E2E tests at `alm-site`.
- Prefer stable URLs and read-only assertions for the first phase.
- Treat the site as disposable once the suite grows beyond smoke coverage.
