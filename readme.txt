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

Members can browse available assets and submit loan requests, while operators and administrators can manage assignments and loan history.

The plugin follows WordPress coding standards, uses a modular architecture, and is designed to be simple, extensible, and future-proof.


== Features ==
* Asset and kit management (kits cannot contain other kits)
* Frontend asset browsing with filters
* Loan request workflow (submit, approve, reject)
* Direct assignment by operator/admin (with mandatory reason)
* Automatic cancellation of concurrent pending requests after assignment
* Email notifications for requests and assignment outcomes
* Loan history tracking
* Role-based permissions (alm_member, alm_operator)
* Translation-ready


== Loan Workflow ==
* A member browses the available assets.
* A loan request is submitted for a selected asset.
* Notification emails are sent to the requester and, when applicable, to the current owner.
* The current owner can approve or reject the request.
* On approval, ownership is transferred and asset state is updated to on-loan.
* Operators/admins can directly assign non-retired assets at any time.
* All decisions and assignments are recorded in loan history.


== Installation ==
Upload the asset-lending-manager folder to the /wp-content/plugins/ directory.

Activate the plugin through the “Plugins” menu in WordPress.

Ensure Advanced Custom Fields (ACF) is installed and active.

Add one or both shortcodes to a page:
- [alm_asset_list]
- [alm_asset_view]

Optionally configure email sender/system constants in plugin-config.php:
- ALM_EMAIL_FROM_NAME
- ALM_EMAIL_FROM_ADDRESS
- ALM_EMAIL_SYSTEM_ADDRESS


== Frequently Asked Questions ==
= Does this plugin manage physical delivery of assets? =
No. Asset delivery and handover are handled offline. The plugin tracks requests and assignments only.

= Is there a full plugin settings UI in wp-admin? =
Not yet. The settings manager exists in code, but a complete settings UI is not currently exposed in wp-admin.

= Is the plugin translation-ready? =
Yes. All user-facing strings are prepared for translation using standard WordPress internationalization functions.


== Development ==
Install dependencies:
- composer install

Run lint:
- composer lint
- composer lint:fix

Run tests:
- composer test:unit
- composer test:integration
- composer test:all


== Screenshots ==
1. Asset list frontend view
2. Asset detail page with loan request form
3. Loan management in the admin area


== Changelog ==



== Upgrade Notice ==
