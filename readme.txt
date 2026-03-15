=== Asset Lending Manager ===
Contributors: ilclaudio
Author URI: https://www.astrofilipisani.it/
Author: AAGG
Tags: asset management, loans, library, equipment, organization
Requires at least: 6.0
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free plugin to manage shared physical assets and loan workflows for associations, schools, libraries, and any organization.

== Description ==
Asset Lending Manager is a free, open-source WordPress plugin that helps any organization manage shared physical assets and internal lending workflows.

Designed for clubs, associations, schools, public bodies, libraries, laboratories, makerspaces, and any group that loans equipment or materials to its members.

Members can browse available assets and submit loan requests, while operators and administrators can manage assignments and loan history.

The plugin follows WordPress coding standards, uses a modular architecture, and is designed to be simple, extensible, and future-proof. Born within the AAGG astronomy association to manage telescopes and equipment, it is published as a general-purpose tool freely usable by any organization.


== Features ==
* Asset and kit management (kits cannot contain other kits)
* Frontend asset browsing with filters
* QR code generation and print label from asset detail page
* QR scanner from asset list (camera-based quick lookup)
* Loan request workflow (submit, approve, reject)
* Direct assignment by operator/admin (reason required)
* Automatic cancellation of concurrent pending requests after assignment
* Email notifications for requests and assignment outcomes
* Loan history tracking per asset and per kit component
* Role-based permissions (alm_member, alm_operator)
* Translation-ready


== Requirements ==
This plugin requires the Advanced Custom Fields plugin (free version is sufficient).

QR features use bundled JavaScript libraries:
* qrcode-generator (MIT license)
* jsQR (Apache-2.0 license)


== Loan Workflow ==
* A member browses the available assets.
* A loan request is submitted for a selected asset.
* Notification emails are sent to the requester and, when applicable, to the current owner.
* The current owner can approve or reject the request.
* On approval, ownership is transferred and asset state is updated to on-loan.
* Operators/admins can directly assign non-retired assets at any time.
* All decisions and assignments are recorded in loan history.


== Installation ==
1. Upload the `asset-lending-manager` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Ensure Advanced Custom Fields (ACF) is installed and active.
4. Add one or both shortcodes to a page:
   * `[alm_asset_list]` — displays the browsable asset list with filters
   * `[alm_asset_view]` — displays the asset detail view with loan request form


== Frequently Asked Questions ==

= Who is this plugin for? =
Any organization that manages a shared pool of physical objects: associations, schools, public bodies, libraries, laboratories, makerspaces, sports clubs, and more. The plugin was originally created for an astronomy association (AAGG, Italy) but is designed to be generic and suitable for any context.

= Does this plugin require Advanced Custom Fields? =
Yes. ACF (free version) is required to store and retrieve custom asset fields. The plugin will display an admin notice if ACF is not active.

= Does this plugin manage physical delivery of assets? =
No. Asset delivery and handover are handled offline. The plugin tracks requests and assignments only.

= Is there a settings page in wp-admin? =
Yes. A settings page is available in wp-admin under the ALM menu.

= Is the plugin translation-ready? =
Yes. All user-facing strings are prepared for translation using standard WordPress internationalization functions. A `.pot` file is included.

= What data is removed when the plugin is uninstalled? =
Uninstalling the plugin removes: the plugin settings, the loan request history table, the pending loan requests table, and the custom roles (alm_member, alm_operator). Asset posts and their metadata are intentionally preserved so that your inventory is not lost if you reinstall the plugin later.


== Screenshots ==
1. Asset list frontend view
2. Asset detail page with loan request form
3. Loan management in the admin area


== Changelog ==

= 0.1.0 =
* First public release.
* Asset and kit management with full loan workflow (request, approve, reject, direct assign).
* Role-based access control (alm_member, alm_operator).
* Email notifications for all loan workflow events.
* Loan history tracking, including per-component entries for kit operations.
* Frontend asset browsing with filters, QR code generation, and QR scanner.
* Asset state management (available, on-loan, maintenance, retired) with kit propagation.
* Translation-ready with English and Italian included.
* Settings page in wp-admin.


== Upgrade Notice ==

= 0.1.0 =
First public release.
