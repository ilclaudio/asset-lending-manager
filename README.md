# WordPress Asset Lending Manager

Asset Lending Manager is an open-source WordPress plugin that helps any organization manage shared physical assets and internal lending workflows.

Designed for clubs, associations, schools, public bodies, libraries, laboratories, makerspaces, and any group that loans equipment or materials to its members.

Members can browse available assets and submit loan requests, while operators and administrators can manage assignments and loan history.

The plugin follows WordPress coding standards, uses a modular architecture, and is designed to be simple, extensible, and future-proof. Born within the [AAGG astronomy association](https://www.astrofilipisani.it/) to manage telescopes and equipment, it is published as a general-purpose tool freely usable by any organization.

---

## Features

- Asset and kit management (kits cannot contain other kits)
- Frontend asset browsing with filters (type, state, structure, search)
- QR code generation and print label from asset detail page
- QR scanner from asset list (camera-based quick lookup)
- Loan request workflow (submit, approve, reject)
- Direct assignment by operator/admin (reason requirement configurable)
- Automatic cancellation of concurrent pending requests after assignment
- Asset state management from frontend: operators can set maintenance, retired, or force-return on-loan assets to available; location field required on every state change
- Email notifications for all loan workflow events (request, approval, rejection, cancellation, direct assignment, forced return)
- Loan history tracking
- Role-based permissions (`alm_member`, `alm_operator`)
- Translation-ready

---

## Requirements

This plugin requires the Advanced Custom Fields plugin (free).
QR features use bundled JavaScript libraries:
- `qrcode-generator` (MIT)
- `jsQR` (Apache-2.0)

---

## Loan Workflow

1. A member browses the available assets.
2. A loan request is submitted for a selected asset.
3. Notification emails are sent to the requester and, when applicable, to the current owner.
4. The current owner can approve or reject the request.
5. On approval, ownership is transferred and asset state is updated to on-loan.
6. Operators/admins can directly assign any asset that is not retired or under maintenance, at any time.
7. Operators can change asset state (→ maintenance, → retired) from the frontend, providing a location and optional notes.
8. Operators can force-return an on-loan asset to available directly from the frontend; this closes the active loan, clears the owner, and notifies the borrower.
9. Assets in maintenance or retired state can be restored to available by operators.
10. All decisions, assignments, and state changes are recorded in loan history.

For detailed role/action and notification schemas, see:
- `DOC/SchemaPermessiPerRuolo.md`
- `DOC/SchemaAzioniSwimlane.md`
- `DOC/SchemaNotificheEmail.md`

---

## Asset State Machine

| From \ To  | available | on-loan | maintenance | retired |
|------------|-----------|---------|-------------|---------|
| **available** | — | ✅ loan approval / direct assign | ✅ operator | ✅ operator |
| **on-loan** | ✅ operator (forced return) | — | ✅ operator | ✅ operator |
| **maintenance** | ✅ operator (restore) | ❌ | — | ❌ |
| **retired** | ✅ operator (restore) | ❌ | ❌ | — |

All operator state transitions require a **location** field (mandatory) and accept optional notes.
Kit state changes propagate to all components.

---

## Installation

1. Upload the `asset-lending-manager` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure **Advanced Custom Fields (ACF)** is installed and active.
4. The plugin works out of the box on both classic and block themes — no shortcodes required for normal use. Asset pages are served automatically via the plugin's built-in templates:
   - `/asset/` — asset catalog with search filters
   - `/asset/asset-name/` — single asset detail page
5. Use the shortcodes only if you need to embed a view inside an existing WordPress page:
   - `[alm_asset_list]` — embeds the asset catalog into any page or post
   - `[alm_asset_view]` — embeds the single asset detail view (not needed on standard asset permalinks)
6. Optionally configure email sender settings in wp-admin under **ALM → Settings**, or via constants in `plugin-config.php`:
   - `ALM_EMAIL_FROM_NAME`
   - `ALM_EMAIL_FROM_ADDRESS`
   - `ALM_EMAIL_SYSTEM_ADDRESS`

Settings UI is available in wp-admin under the ALM menu.

## Uninstall

Uninstalling the plugin via the WordPress admin panel removes:

- Plugin settings (`alm_settings` option)
- Loan request history table (`wp_alm_loan_requests_history`)
- Pending loan requests table (`wp_alm_loan_requests`)
- Custom roles (`alm_member`, `alm_operator`) and their capabilities

**Asset posts (`alm_asset`) and their metadata are intentionally preserved.**
Your inventory data is not deleted on uninstall, so it can be recovered if the plugin is reinstalled later.

---

## Development

Install dependencies:
```bash
composer install
```

Run lint:
```bash
composer lint
composer lint:fix
```

Run tests:
```bash
composer test:unit
composer test:integration
composer test:all
```

### Build distribution ZIP

Run from the repository root (or from `DEV/`):

```bash
bash DEV/SETUP/AIScripts/build-zip.sh
```

Output: `DEV/dist/asset-lending-manager-<version>.zip`

The version is read automatically from the plugin header in `asset-lending-manager.php`.
Dev-only files are stripped from the archive: `tests/`, `composer.json`, `phpcs.xml.dist`,
`phpunit*.xml`, `.gitignore`, `.githooks/`, `.vscode/`, `DEV/`, `DOC/`, `TODO.txt`.
The script prints a full file list and size on completion.
Requires one of: `zip` (Unix), PowerShell, or `python3`.
