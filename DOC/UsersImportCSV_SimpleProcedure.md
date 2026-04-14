# Users CSV Import - Simple Procedure

This document describes the current ALM users import flow.
It has two parts:

1. User steps.
2. Internal workflow.

## Part 1 - User Steps

### 1) Prepare the CSV file

- Use a `.csv` file with `;` as delimiter.
- Keep the header exactly as:

`Username;Email;First_Name;Last_Name;Role`

- Required for every row: `Username`, `Email`, `First_Name`, `Last_Name`, `Role`.
- Allowed `Role` values: `member`, `operator`.

### 2) Run import from WordPress admin

- Go to `ALM > Tools > Import > Users`.
- Upload the CSV file.
- Choose import mode:
  - `create_only`
  - `update_only`
  - `upsert`
- Choose run mode:
  - `dry_run` (simulation)
  - `execute` (write changes)
- Start import.

### 3) Review results

- Check summary counters: processed, created, updated, skipped, errors.
- Check row-by-row log.

## Part 2 - Internal Workflow

### 1) Authorization and request validation

- Only administrators can import users (`manage_options`).
- Nonce is required.
- Invalid modes are normalized to safe defaults (`upsert`, `dry_run`).

### 2) Upload validation

- Checks include upload integrity, `.csv` extension, MIME, and max size `1MB`.

### 3) CSV parsing and header contract

- File is read with `SplFileObject::fgetcsv`.
- Delimiter is `;`.
- Header must match exactly:

`Username;Email;First_Name;Last_Name;Role`

### 4) Row validation

- Empty rows are skipped.
- Each non-empty row must have exactly 5 columns.
- Username and email are validated.
- `First_Name` and `Last_Name` must stay non-empty after sanitization.
- `Role` must be `member` or `operator`.
- Duplicate username/email inside the same file is skipped.

### 5) Create/update behavior

- Existing user matching is based on email/username consistency.
- `create_only`: existing users are skipped.
- `update_only`: missing users are skipped.
- `upsert`: creates missing users and updates existing ones.
- In `dry_run`, no database writes are performed.

### 6) Role handling

- Import role `operator` overrides `member`.
- If a user is already operator and CSV says `member`, operator is kept.

### 7) Reporting and completion

- Import report is stored (logs + errors + counters).
- User is redirected back to `Tools > Import > Users`.

