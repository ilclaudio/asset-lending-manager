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
3. if your workflow supports it, use a symlink/junction so the site points to
   your active working copy.

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

## Step 6 — Set the E2E base URL

The Playwright config reads the site URL from the environment variable
`ALMGR_E2E_BASE_URL`.

If the variable is not set, the current fallback is:

```text
https://alm-e2e.local
```

If your local site uses a different URL, you must set the environment variable
explicitly before running the suite.

### PowerShell

```powershell
$env:ALMGR_E2E_BASE_URL = "https://alm-e2e.local"
composer test:e2e
```

### Bash

```bash
ALMGR_E2E_BASE_URL="https://alm-e2e.local" composer test:e2e
```

If the site URL is different, replace it with the real LocalWP domain.

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

## Database policy

For Phase 3, tests should be read-only whenever possible.

That means:

- opening pages;
- checking that responses are successful;
- checking that expected containers or text render;
- avoiding writes to options, posts, users, loan requests, or custom tables.

With this initial policy, the database does not need cleanup after each run,
because the current smoke tests should not modify site state.

If future E2E tests start creating or updating data, the suite will need an
explicit reset strategy before it can be considered reliable. Typical options:

- recreate the `alm-e2e` site from a clean snapshot;
- clone `alm-site` into a fresh `alm-e2e` instance before a run;
- seed known fixtures before each run;
- add teardown/reset helpers for mutated entities.

Until then, keep the E2E lane intentionally small and non-destructive.

---

## How it works internally

1. `composer test:e2e` delegates to `npm run test:e2e`.
2. npm runs Playwright using the repository `package.json`.
3. Playwright loads `playwright.config.js`.
4. The config points tests to `tests/e2e/specs/`.
5. The browser uses `ALMGR_E2E_BASE_URL` or the fallback URL.
6. The suite opens real frontend pages of the `alm-e2e` site and evaluates the response.

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
