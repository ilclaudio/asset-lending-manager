# Users CSV Export - Simple Procedure

This document describes the current ALM users export flow.
It has two parts:

1. User steps.
2. Internal workflow.

## Part 1 - User Steps

### 1) Permissions

- Users CSV export is available to administrators and operators.

### 2) Run export from WordPress admin

- Go to `ALM > Tools > Export > Users`.
- Click `Export Users CSV`.
- The browser downloads a CSV file.

### 3) Output contract

- Format: `.csv`
- Delimiter: `;`
- Fixed header:

`Username;Email;First_Name;Last_Name;Role`

### 4) Export scope

- Only users with ALM roles are exported:
  - `almgr_member`
  - `almgr_operator`
- If a user has both roles, exported role is `operator`.

## Part 2 - Internal Workflow

### 1) Authorization and request validation

- Endpoint checks user capability (administrator or operator).
- Nonce is required before streaming.

### 2) Response setup

- Headers force file download.
- Content type: `text/csv; charset=utf-8`.
- File name includes UTC timestamp.
- UTF-8 BOM is written for spreadsheet compatibility.

### 3) Data query and batching

- Users are fetched in batches (200 per page) via `WP_User_Query`.
- Query scope is limited to ALM roles.

### 4) Row mapping

- `Username` -> `user_login`
- `Email` -> `user_email`
- `First_Name` -> user meta `first_name`
- `Last_Name` -> user meta `last_name`
- `Role` -> `member` or `operator` (operator precedence)

### 5) CSV safety

- Cell values are sanitized.
- Formula-injection guard is applied:
  - values starting with `=`, `+`, `-`, `@`
  - or starting with tab/newline
  are prefixed with `'`.

### 6) Completion

- Rows are written with `fputcsv` using `;`.
- Stream ends immediately after writing.

