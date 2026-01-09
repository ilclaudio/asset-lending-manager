=== Asset Lending Manager ===
Contributors: ilclaudio
Plugin link: https://github.com/ilclaudio/asset-lending-manager
Author URI: https://www.astrofilipisani.it/
Author: AAGG
Tags: asset management, lending, inventory, loans
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Asset Lending Manager is a WordPress plugin that helps organizations manage shared assets and internal lending workflows.

Members can browse available assets, submit loan requests, and track assignments, while administrators manage assets, users, and loan history through the WordPress dashboard.

The plugin follows WordPress coding standards, uses a modular architecture, and is designed to be simple, extensible, and future-proof.


== Features ==
* Asset and kit management
* Frontend asset browsing
* Loan request workflow
* Approval or rejection by current assignee
* Loan confirmation after physical handover
* Full loan history tracking
* Role-based permissions
* Translation-ready


== Loan Workflow ==
* A member browses the available assets.
* A loan request is submitted for a selected asset.
* The current assignee is notified of the request.
* The request can be approved or rejected.
* The physical handover of the asset is agreed offline.
* Once completed, the loan is confirmed in the system.
* All requests and assignments are stored in the loan history.


== Installation ==
Upload the asset-lending-manager folder to the /wp-content/plugins/ directory.

Activate the plugin through the “Plugins” menu in WordPress.

Configure plugin settings from the WordPress admin area.


== Frequently Asked Questions ==
= Does this plugin manage physical delivery of assets? =
No. Asset delivery and handover are handled offline. The plugin tracks requests and assignments only.
= Is the plugin translation-ready? =
Yes. All user-facing strings are prepared for translation using standard WordPress internationalization functions.


== Development ==
To execute the tests:
- composer install
- vendor\bin\phpunit --bootstrap tests/bootstrap.php --verbose tests


== Screenshots ==
1. Asset list frontend view
2. Asset detail page with loan request form
3. Loan management in the admin area


== Changelog ==



== Upgrade Notice ==
