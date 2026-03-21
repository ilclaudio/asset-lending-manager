=== Asset Lending Manager ===
Contributors: ilclaudio
Author URI: https://www.astrofilipisani.it/
Author: AAGG
Tags: asset management, loans, library, equipment, organization
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free plugin to manage shared physical assets and loan workflows for associations, schools, libraries, and any organization.

== Description ==
Asset Lending Manager is a free, open-source WordPress plugin that helps any organization manage shared physical assets and internal lending workflows.

Designed for clubs, associations, schools, public bodies, libraries, laboratories, makerspaces, and any group that loans equipment or materials to its members.

Members can browse available assets and submit loan requests, while operators and administrators can manage assignments and loan history.

Born within the AAGG astronomy association to manage telescopes and equipment, it is published as a general-purpose tool freely usable by any organization.

**Requires the Advanced Custom Fields (ACF) plugin** (free version) to store and manage asset details.


== Features ==
* Asset and kit management (a kit is a group of items lent together as a set)
* Public browsing page with search and category filters
* QR code generation and printable label from the asset detail page
* QR scanner from the asset list (camera-based quick lookup)
* Loan request workflow: members submit requests, operators approve or reject
* Direct assignment by operators and admins (a reason is always required)
* When an asset is assigned, all other pending requests for it are automatically canceled
* Email notifications for requests and assignment outcomes
* Full loan history for each asset
* Two user roles included: Member (can browse and request loans) and Operator (can manage assignments and history)
* Translation-ready


== Requirements ==
This plugin requires the **Advanced Custom Fields** plugin (free version is sufficient).
You can install it for free from the WordPress plugin directory.


== Loan Workflow ==
* A member browses the available assets.
* A loan request is submitted for a selected asset.
* Notification emails are sent to the requester and, when applicable, to the current owner.
* The current owner can approve or reject the request.
* On approval, the asset is marked as on loan and the new borrower is recorded.
* Operators and admins can also directly assign any available asset without a prior request.
* All decisions and assignments are recorded in loan history.


== Installation ==
1. In your WordPress admin, go to Plugins > Add New > Upload Plugin.
2. Upload the plugin ZIP file and click Install Now, then Activate.
3. Install and activate the **Advanced Custom Fields** (ACF) plugin — the free version is sufficient and available in the WordPress plugin directory.
4. Create a new page and add one or both shortcodes to its content:
   * `[alm_asset_list]` — displays the browsable asset list with search and filters
   * `[alm_asset_view]` — displays the asset detail page with the loan request form


== Frequently Asked Questions ==

= Who is this plugin for? =
Any organization that manages a shared pool of physical objects: associations, schools, public bodies, libraries, laboratories, makerspaces, sports clubs, and more. The plugin was originally created for an astronomy association (AAGG, Italy) but is designed to be generic and suitable for any context.

= Does this plugin require Advanced Custom Fields? =
Yes. ACF (free version) is required to store and retrieve custom asset fields. The plugin will display an admin notice if ACF is not active.

= Does this plugin manage physical delivery of assets? =
No. Asset delivery and handover are handled offline. The plugin tracks requests and assignments only.

= Is there a settings page in wp-admin? =
Yes. Under the **ALM** menu in wp-admin you can configure the email sender, loan rules (maximum active loans per member, message length limits), and other workflow options.

= Is the plugin translation-ready? =
Yes. English and Italian are included out of the box. Other languages can be added using standard WordPress translation tools.

= What data is removed when the plugin is uninstalled? =
Uninstalling the plugin removes the plugin settings, the loan request history, the pending loan requests, and the custom user roles. Your asset inventory (posts and their data) is intentionally preserved so that it is not lost if you reinstall the plugin later.

= What is the difference between an asset and a kit? =
An asset is a single physical item (for example, a telescope, a book, or a camera). A kit is a collection of items that are lent together as a group (for example, a telescope with its eyepieces and carrying case). Managing kits allows you to track all components under a single loan request.

= Can multiple members request the same asset at the same time? =
Yes. Multiple members can submit requests for the same asset simultaneously. When a request is approved or the asset is directly assigned, all other pending requests for that asset are automatically canceled and the requesters are notified by email.

= Do I need a developer to set up this plugin? =
Basic setup only requires installing the plugin, activating ACF, and adding a shortcode to a page — no coding needed. Some advanced customization such as theme integration or user role adjustments may benefit from developer support.


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


== Credits ==

This plugin bundles the following third-party JavaScript libraries:

* **qrcode-generator** by Kazuhiko Arase (http://www.d-project.com/) — MIT License
* **jsQR** by cozmo (https://github.com/cozmo/jsQR) — Apache License 2.0

Both licenses are compatible with GPLv2 or later. License files are included in `assets/js/vendor/`.


== Upgrade Notice ==

= 0.1.0 =
First public release.
