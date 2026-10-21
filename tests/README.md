# Test Guide

This directory contains three complementary test lanes. Run commands from the plugin root after `composer install`.

## Choose a lane

| Lane | Use it for | Command | Environment |
| --- | --- | --- | --- |
| [Unit](unit/README.md) | Pure logic and small helpers | `composer test:unit` | PHP and Composer |
| [Integration](integration/README.md) | Hooks, persistence, permissions, and workflow behaviour | `composer test:integration` | WordPress Test Suite and a disposable database |
| [E2E](e2e/README.md) | Browser-visible user journeys | `composer test:e2e` | Dedicated `alm-e2e` site, Node.js, and Playwright |

`composer test:all` runs unit and integration tests. It does not run E2E tests.

## Shared prerequisites

Install PHP dependencies once from the plugin root:

```bash
composer install
```

The integration and E2E lanes have additional requirements in their own guides.

## Safety rules

- Integration tests use a database that WordPress Test Suite may create, alter, or drop. Never point them to a production, shared, or everyday site database.
- E2E tests import a committed synthetic baseline into the dedicated `alm-e2e` site before and after normal runs. Never point them to `alm-site`.
- Do not commit credentials, personal data, or unsanitized database dumps.
- A failing test is evidence to investigate, not an instruction to silently change code or the test. Follow `DEV/AGENTS/AI_BEHAVIOR.md`.

## Adding tests

Choose the narrowest lane that exercises the behaviour:

- put isolated PHP tests in `tests/unit/`;
- put tests requiring WordPress, database state, hooks, or capabilities in `tests/integration/`;
- put browser workflows in `tests/e2e/specs/`.

Use descriptive test names and keep each test independent. E2E scenarios must be compatible with the baseline reset; add synthetic fixture data rather than depending on manual site state.

## When a command fails

Record the command, the failing assertion or error, and the observed result. Check the lane-specific guide for setup and troubleshooting. Do not change the test, code, or baseline merely to obtain a green run without deciding whether the failure is a regression, an outdated expectation, or an environment issue.
