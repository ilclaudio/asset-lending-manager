=== Asset Lending Manager ===
Contributors: ioclaudio
Author URI: https://www.claudiobattaglino.it/
Author: IoClaudio
Tags: asset management, loans, library, inventory, organization
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Open-source plugin to manage shared physical assets and loan workflows for associations, schools, libraries, and any organization.

== Description ==
Asset Lending Manager is an open-source WordPress plugin that helps any organization manage shared physical assets and internal lending workflows.

Designed for clubs, associations, schools, public bodies, libraries, laboratories, makerspaces, and any group that loans equipment or materials to its members.

Members can browse available assets and submit loan requests, while operators and administrators can manage assignments and loan history.

Born within an association of amateur astronomers to manage telescopes and equipment, it is published as a general-purpose tool usable by any organization.

**Requires the Advanced Custom Fields (ACF) plugin** (free version) to store and manage asset details.


== Features ==
* Asset and kit management (a kit is a group of items lent together as a set)
* Public browsing page with search, taxonomy filters (including member-only warehouse filtering), QR labels, and QR scanner lookup
* Asset warehouse (`almgr_warehouse`) with automatic Kit to component propagation
* Loan request workflow with approval, rejection, direct assignment, and configurable cancellation of competing pending requests
* Optional contact form to send email messages to the current asset owner
* Email notifications for the main loan workflow events, including cooperative returns to operators
* Frontend asset state management for operators: set maintenance or retired, force-return on-loan assets, and restore assets to available
* Cooperative asset return from the frontend, distinct from operator force-return; optionally available to the current-owner member
* Full loan history, plus a dedicated full asset history page for operators via `[almgr_asset_history]`
* Two user roles included: Member and Operator
* Read-only REST API for external integrations (asset catalog, member assets, loan requests); see DOC/REST_API_REFERENCE.md
* Optional WordPress Abilities for programmatic/AI-agent integrations, available only when running WordPress 6.9 or later (the plugin works normally without it), sharing the same domain logic as the web UI and REST API. MCP itself is not bundled — see DOC/AI_ABILITIES_REFERENCE.md for details and how to connect an external MCP adapter such as Claude Desktop
* Back-office Tools page (ALM → Tools) with Import, Export, and Utilities tabs
* Users and assets CSV import/export, including kit components and their ACF fields in asset CSV files
* Translation-ready


== Requirements ==
Requires the **Advanced Custom Fields** plugin (free version is sufficient):
https://wordpress.org/plugins/advanced-custom-fields/
The plugin registers its ACF field group automatically, so no manual field setup is needed.


== Loan Workflow ==
* A member browses the available assets.
* A loan request is submitted for a selected asset.
* Notification emails are sent to the requester and, when applicable, to the current owner.
* The current owner can approve or reject the request.
* On approval, the asset is marked as on loan and the new borrower is recorded.
* Operators and admins can also directly assign any asset that is not retired or under maintenance, without a prior request (when direct assignment is enabled).
* Operators can force-return an on-loan asset to available, or restore an asset from maintenance or retired back to available.
* Operators can cooperatively return an on-loan asset; members can do so when enabled and only for assets they currently hold.
* All decisions, assignments, and state changes are recorded in loan history.


== Installation ==
1. Install and activate the **Advanced Custom Fields** (ACF) plugin (free version is enough): https://wordpress.org/plugins/advanced-custom-fields/
   The plugin registers its ACF field group automatically. No manual ACF setup is required.
2. In WordPress admin, go to Plugins > Add New > Upload Plugin.
3. Upload the plugin ZIP file, click Install Now, then Activate.

= Classic themes =
1. Asset pages are served automatically — no shortcodes required:
   * `/asset/` — asset catalog with search and filters
   * `/asset/asset-name/` — single asset detail page
2. If `/asset/` returns 404, go to Settings > Permalinks and click Save Changes once.
3. The full asset history page is not automatic. Create a normal WordPress page with the `[almgr_asset_history]` shortcode and assign it in **ALM > Settings > Frontend** as **Asset history page**.

= Block themes =
1. Block themes do not support automatic PHP template overrides. Create three pages manually:
   * Add `[almgr_asset_list]` to a page — this is your asset catalog.
   * Add `[almgr_asset_view]` to a second page — this is your asset detail view.
   * Add `[almgr_asset_history]` to a third page — this is your full asset history page for operators.
2. In **ALM > Settings > Frontend**, set "Asset archive page", "Asset detail page", and "Asset history page" to the pages you just created. This ensures all asset and history links point to the correct pages.

= Common to both =
1. Optionally configure email sender settings in wp-admin under **ALM > Settings**.

== Screenshots ==

1. Frontend asset list with search, taxonomy filters, and QR scanner access.
2. Frontend asset detail page with loan workflow actions, history, and QR code label generation.
3. Back-office direct assignment form for operators and administrators.
4. Frontend advanced search and filter interface.
5. Plugin settings in wp-admin.


== Frequently Asked Questions ==

= Who is this plugin for? =
Any organization that manages a shared pool of physical objects: associations, schools, public bodies, libraries, laboratories, makerspaces, sports clubs, and more.
The plugin was originally created for an association of amateur astronomers but is designed to be generic and suitable for any context.


= Does this plugin require Advanced Custom Fields? =
Yes. ACF (free version) is required to store and retrieve custom asset fields. The plugin will display an admin notice if ACF is not active.

= Does this plugin manage physical delivery of assets? =
No. Asset delivery and handover are handled offline. The plugin tracks requests and assignments only.

= Is there a settings page in wp-admin? =
Yes. Under the **ALM** menu in wp-admin you can configure the email sender, loan rules (maximum active loans per member, message length limits), and other workflow options.

= Which shortcodes are available and when should I use them? =
The plugin provides three shortcodes:
`[almgr_asset_list]` for the asset catalog,
`[almgr_asset_view]` for the single asset detail view,
and `[almgr_asset_history]` for the full asset history page (operator-only).
On classic themes, catalog and detail pages are usually served automatically, so the history shortcode is the one most commonly needed.
On block themes, create and assign all three shortcode pages manually in **ALM > Settings > Frontend**.

= Is the plugin translation-ready? =
Yes. English and Italian are included out of the box. Other languages can be added using standard WordPress translation tools.

= What data is removed when the plugin is uninstalled? =
Uninstalling the plugin removes the plugin settings, the loan request history, the pending loan requests, and the custom user roles.
By default, your asset inventory (posts and their data) is preserved.
If you want to remove all plugin data, define `ALMGR_REMOVE_ALL_DATA` as `true` in `wp-config.php` before uninstalling.

= What is the difference between an asset and a kit? =
An asset is a single physical item (for example, a telescope, a book, or a camera). A kit is a collection of items that are lent together as a group (for example, a telescope with its eyepieces and carrying case). Managing kits allows you to track all components under a single loan request.

= Can multiple members request the same asset at the same time? =
Yes. Multiple members can submit requests for the same asset simultaneously. By default, when a request is approved or the asset is directly assigned, all other pending requests for that asset are automatically canceled (this behavior is configurable), and requesters are notified by email when notifications are enabled.

= Do I need a developer to set up this plugin? =
On classic themes, basic setup usually only requires installing the plugin and activating ACF; asset pages are served automatically, except for the optional full asset history page. On block themes, three shortcode pages must be created manually and assigned in the plugin settings: catalog, asset detail, and full asset history.


== Changelog ==

For full release notes see `CHANGELOG.md`.

= 0.4.0 =
* Added: asset warehouse taxonomy with Kit-to-component propagation and member-only frontend visibility.
* Added: cooperative asset return for operators and, when enabled, the current-owner member.
* Added: optional WordPress Abilities for catalog, history, and loan-request integrations on WordPress 6.9+.
* Changed: REST API and Abilities now use shared domain services for equivalent read operations.

= 0.3.2 =
* Fixed: Installation formatting for classic and block themes.

= 0.3.1 =
* Fixed: checked compatibility with WordPress 7.1; no code changes required.

= 0.3.0 =
* Added: full asset history page for operators via `[almgr_asset_history]`.
* Added: optional contact form to send email messages to the current asset owner.
* Fixed: kit loan approval, direct assignment, and state changes now affect only eligible components; excluded components are skipped safely and reported to the operator.
* Fixed: maintenance/restore kit behavior and ACF write handling are now more robust and consistent.
* Fixed: operator actions (direct assignment, state change, restore) now reject non-published assets.
* Fixed: asset list search field no longer collides with the WordPress reserved search query variable.


== Credits ==

This plugin bundles the following third-party JavaScript libraries:

* **qrcode-generator** by Kazuhiko Arase (http://www.d-project.com/), MIT License
* **jsQR** by cozmo (https://github.com/cozmo/jsQR), Apache License 2.0

Both licenses are compatible with GPLv2 or later. License files are included in `assets/js/vendor/`.


== Upgrade Notice ==

= 0.4.0 =
Adds asset warehouses, cooperative returns, and optional WordPress Abilities. No manual migration is required.

= 0.3.2 =
Documentation-only release. No code or database changes; no action required.

= 0.3.1 =
WordPress 7.1 compatibility check. No code changes, no database changes; no manual intervention required.

= 0.3.0 =
Adds an operator asset history page and optional owner contact messages. Kit operations now skip ineligible components safely. No database changes; no manual intervention required.
