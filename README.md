# WordPress Asset Lending Manager

Asset Lending Manager is a WordPress plugin that helps organizations manage shared assets and internal lending workflows.

Members can browse available assets and submit loan requests, while operators and administrators can manage assignments and loan history.

The plugin follows WordPress coding standards, uses a modular architecture, and is designed to be simple, extensible, and future-proof.

---

## Features

- Asset and kit management (kits cannot contain other kits)
- Frontend asset browsing with filters
- Loan request workflow (submit, approve, reject)
- Direct assignment by operator/admin (with mandatory reason)
- Automatic cancellation of concurrent pending requests after assignment
- Email notifications for requests and assignment outcomes
- Loan history tracking
- Role-based permissions (`alm_member`, `alm_operator`)
- Translation-ready

---

## Loan Workflow

1. A member browses the available assets.
2. A loan request is submitted for a selected asset.
3. Notification emails are sent to the requester and, when applicable, to the current owner.
4. The current owner can approve or reject the request.
5. On approval, ownership is transferred and asset state is updated to on-loan.
6. Operators/admins can directly assign non-retired assets at any time.
7. All decisions and assignments are recorded in loan history.

For detailed role/action and notification schemas, see:
- `DOC/SchemaPermessiPerRuolo.md`
- `DOC/SchemaAzioniSwimlane.md`
- `DOC/SchemaNotificheEmail.md`

---

## Installation

1. Upload the `asset-lending-manager` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure **Advanced Custom Fields (ACF)** is installed and active.
4. Add one or both shortcodes to a page:
   - `[alm_asset_list]`
   - `[alm_asset_view]`
5. Optionally configure email sender/system constants in `plugin-config.php`:
   - `ALM_EMAIL_FROM_NAME`
   - `ALM_EMAIL_FROM_ADDRESS`
   - `ALM_EMAIL_SYSTEM_ADDRESS`

Note: `ALM_Settings_Manager` exists in code, but a full settings UI is not currently exposed in wp-admin.

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
