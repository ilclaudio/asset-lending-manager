# Unit Tests — Setup Guide

Unit tests cover pure helper methods with no WordPress or database dependency.
They run in a few seconds and require no special environment beyond PHP and Composer.

---

## Prerequisites

- **PHP 8.x** available in the system PATH — verify with `php -v`
- **Composer** installed — verify with `composer --version`

---

## First-time setup

From the plugin root, install PHP dependencies:

```bash
composer install
```

This installs PHPUnit and the other dev tools into `vendor/`. You only need to
do this once (and again after a `composer.json` change).

---

## Running the tests

```bash
composer test:unit
```

Expected output:

```
PHPUnit 9.6.x by Sebastian Bergmann and contributors.

.........................................           41 / 41 (100%)

OK (41 tests, N assertions)
```

The test count grows as new tests are added. If the suite passes, all dots are
green. A failure prints `F` in place of a dot and reports the assertion that failed.

---

## What is covered

Unit tests verify pure business logic that has no WordPress or database dependency:

- CSV import/export helpers — normalization, sanitization, formula-injection prevention
- Loan plan builder formatting helpers
- Email template placeholder inventory
- Settings service API (in-memory only, no real DB option store)
- Autocomplete term validation
- REST API pagination clamping
- Notification routing decision logic

For the full list of test methods with one-line descriptions, see
[`DEV/TODO/TODO_UnitTests.md`](../../DEV/TODO/TODO_UnitTests.md).

---

## Pre-commit hook

Unit tests run automatically as a pre-commit hook.
A failing suite **blocks the commit** until the issue is resolved.
This ensures the unit test suite always passes on the main branch.
