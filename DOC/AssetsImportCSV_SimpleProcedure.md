# Assets CSV Import - Simple Complete Procedure (MVP)

This document explains the first simple but complete version of ALM assets import.
It is split into two parts:

1. What a user needs to do (prepare file + run import).
2. What the import procedure does internally.

## Part 1 - User Steps

### 1) Prepare the CSV file

- Use a `.csv` file with `;` (semicolon) as delimiter.
- Keep the header exactly as shown below (same order, same names):

`Title;Structure;Type;State;Level;External_Code;Description;Manufacturer;Model;Wp_Status;Kit_Component_Titles`

- Use one row per asset.
- In this MVP, import behaves as `create_only`.
- Existing assets are never updated.
- Existing assets are skipped.

### 2) Fill required and optional columns

- Required columns are: `Title`, `Structure`, `Type`, `State`, `Level`, `Manufacturer`, `Model`.
- Optional columns are: `External_Code`, `Description`, `Wp_Status`, `Kit_Component_Titles`.

### 3) Use allowed values

- `Structure`: `component` or `kit`.
- `State`: `available`, `maintenance`, `retired`.
- `Wp_Status`: `publish` or `draft` (if empty, default is `publish`).
- `Type` and `Level`: must be existing taxonomy slugs in ALM.

### 4) Kit components format

- `Kit_Component_Titles` is used only for `Structure=kit`.
- Multiple component titles must be separated with `|`.
- Example: `10mm Eyepiece|UHC Filter 1.25`

### 5) How to include semicolons in Description

- If a field contains `;`, wrap that field in double quotes.
- Example: `"Plossl 10mm eyepiece; multi-coated optics"`.

### 6) Run the import from WordPress admin

- Go to `ALM > Tools > Import > Assets`.
- Upload the CSV file.
- Choose run mode: `dry_run` to simulate, `execute` to write changes.
- Start the import.

### 7) Read results

- Review summary counters: processed, created, skipped, errors.
- Check row-by-row log for details.
- Download and inspect the error CSV if errors are present.

## Part 2 - Internal Import Workflow

### 1) Authorization and request validation

- Only users with allowed capability can run assets import (admin/operator for this feature).
- Nonce is validated before processing.
- Input run mode is normalized (`dry_run` or `execute`).

### 2) Upload validation

- The uploaded file is validated before parsing.
- Checks include: upload integrity, `.csv` extension, MIME validation, max size `1MB`.

### 3) CSV parsing and header contract

- File is read with `SplFileObject::fgetcsv`.
- Delimiter is `;`.
- Header must match exactly:
`Title;Structure;Type;State;Level;External_Code;Description;Manufacturer;Model;Wp_Status;Kit_Component_Titles`

### 4) Row normalization and validation

- Empty rows are skipped.
- Each non-empty row must have exactly 11 columns.
- Required columns are validated after sanitization.
- Taxonomy slugs are validated against existing terms.
- State/status values are validated against allowed lists.

### 5) Create-only behavior for existing assets

- The key is `Title` (case-insensitive lookup inside `almgr_asset`, `draft|publish`).
- If a title already exists, the row is `skipped` and no update is performed.

### 6) Two-pass import for kits

- Pass 1 creates valid non-kit rows and queues kit rows.
- Pass 2 (kits only) splits `Kit_Component_Titles` by `|` and resolves each component by slug (`sanitize_title` from title).
- Blocking errors on kit rows are: component not found, ambiguous component slug, kit self-reference.

### 7) Write behavior by run mode

- `dry_run`: no database writes, only simulation report.
- `execute`: writes valid rows only; invalid rows remain isolated and logged as errors.

### 8) Reporting and completion

- The procedure stores an import report (counts + logs + errors).
- User is redirected back to `Tools > Import > Assets`.
- The report is rendered in the UI, including downloadable CSV errors.
