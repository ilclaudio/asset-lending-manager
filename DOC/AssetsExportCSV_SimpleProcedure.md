# Assets CSV Export - Simple Complete Procedure (MVP)

This document defines the simple but complete ALM assets export behavior.
It is split into two parts:

1. What a user needs to do (run export and consume the file).
2. What the export procedure does internally.

## Part 1 - User Steps

### 1) Permissions

- Assets CSV export is available to administrators and operators.

### 2) Run the export from WordPress admin

- Go to `ALM > Tools > Export > Assets`.
- Click `Export Assets CSV`.
- The browser downloads the generated CSV file.

### 3) Output format and file shape

- Output format is `.csv`.
- Delimiter is semicolon (`;`).
- Header is fixed and matches import contract:

`Title;Structure;Type;State;Level;External_Code;Description;Manufacturer;Model;Wp_Status;Kit_Component_Titles`

### 4) Which assets are exported

- Scope is only published assets (`post_status=publish`).
- Draft assets are not included in this MVP export.

### 5) How fields are populated

- `Title`: asset post title.
- `Structure`: structure taxonomy slug.
- `Type`: type taxonomy slug.
- `State`: state taxonomy slug.
- `Level`: level taxonomy slug.
- `External_Code`: asset meta `external_code`.
- `Description`: asset post content.
- `Manufacturer`: asset meta `manufacturer`.
- `Model`: asset meta `model`.
- `Wp_Status`: always `publish` (because export scope is publish only).
- `Kit_Component_Titles`: for kits, component titles separated with `|`; for non-kit assets, empty value.

### 6) Open in spreadsheet applications

- The CSV is compatible with semicolon-based imports.
- If Excel does not split columns automatically, import with delimiter `;` and UTF-8 encoding.

## Part 2 - Internal Export Workflow

### 1) Authorization and request validation

- The export endpoint checks user capability (admin/operator).
- Nonce validation is required before streaming output.

### 2) Response setup

- Response headers force file download.
- Content type is `text/csv; charset=utf-8`.
- File name follows export naming pattern with timestamp.
- UTF-8 BOM is written for spreadsheet compatibility.

### 3) Fixed header write

- The procedure writes exactly one fixed header row:

`Title;Structure;Type;State;Level;External_Code;Description;Manufacturer;Model;Wp_Status;Kit_Component_Titles`

### 4) Dataset query

- Query `almgr_asset` posts.
- Filter by `post_status=publish`.
- Iterate in batches for predictable memory usage.

### 5) Row mapping

- For each asset, the procedure reads taxonomy slugs (`structure`, `type`, `state`, `level`), meta values (`external_code`, `manufacturer`, `model`), `post_title`, `post_content`, and sets `Wp_Status` to `publish`.

### 6) Kit component serialization

- If asset structure is `kit`, resolve related components.
- Build `Kit_Component_Titles` using component titles.
- Join titles with `|`.
- If asset is not a kit, keep this field empty.

### 7) CSV safety

- Every cell is sanitized before output.
- Formula-injection guard is applied: values starting with `=`, `+`, `-`, `@` (or tab/newline prefixes) are prefixed with `'`.

### 8) Stream completion

- Each mapped row is written with `fputcsv` using `;`.
- Stream is closed and response ends immediately.
