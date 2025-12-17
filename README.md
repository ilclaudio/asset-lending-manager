# WordPress Asset Lending Manager

Asset Lending Manager is a WordPress plugin that helps organizations manage shared assets and internal lending workflows.

Members can browse available assets, submit loan requests, and track assignments, while administrators manage devices, users, and loan history through the WordPress dashboard.

The plugin follows WordPress coding standards, uses a modular architecture, and is designed to be simple, extensible, and future-proof.

---

## Features

- Asset and kit management
- Frontend asset browsing
- Loan request workflow
- Approval or rejection by current assignee
- Loan confirmation after physical handover
- Full loan history tracking
- Role-based permissions
- Translation-ready

---

## Loan Workflow

1. A member browses the available assets.
2. A loan request is submitted for a selected asset.
3. The current assignee is notified of the request.
4. The request can be approved or rejected.
5. The physical handover of the asset is agreed offline.
6. Once completed, the loan is confirmed in the system.
7. All requests and assignments are stored in the loan history.

---

## Installation

1. Upload the `asset-lending-manager` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Configure plugin settings from the WordPress admin area.

## Development

To execute the tests:
```bash
	composer install
	vendor\bin\phpunit --bootstrap tests/bootstrap.php tests/unit
	vendor\bin\phpunit --bootstrap tests/bootstrap-integration.php tests/integration

	# OR
	vendor\bin\phpunit   -c phpunit.xml --verbose
	vendor\bin\phpunit -c phpunit-integration.xml --verbose

	# OR
	composer test:integration
	composer test:unit
	composer test:all
```
