# AGENTS.md

## Project Overview
We are developing a WordPress plugin called asset-lending-manager for the lending of assets (kits and simple components).

It is a plugin (ALM – Asset Lending Manager) that implements features allowing members of a non-profit astronomy association (AAGG) to track the association’s instruments (telescopes, eyepieces, mounts, charts, etc.), books, and magazines.

These objects (resources) are assigned on loan to association members, who manage and maintain them until they are requested by another member, who then takes them over.

The managed objects are generically called “resources” (assets) and are of two types:

Components: simple items such as eyepieces, mounts, filters, books, etc.

Kits: a collection of components such as telescopes equipped with eyepieces and mounts, book collections, etc.

A kit cannot contain other kits.
The roles defined in the system are: member and operator.



## Architecture Notes
In this codebase, a plugin usable on the WordPress CMS is defined.
The plugin is multilingual; all labels are translated using the text domain: asset-lending-manager.
The package for all files is: @package AssetLendingManager.

Within the plugin, an asset content type (alm_asset) is defined via ACF, which can be of type component or kit.

Taxonomies are also defined to classify the various assets:

	- Structure (alm_structure): kit, component.

	- Type (alm_type): accessory, binoculars, filter, generic, book, mount, ocular, telescope, e.g.

	- State (alm_state): available, maintenance, on-loan, retired.

	- Level (alm_level): advanced, base, intermediate.



## Main system features:
	i) Association members have a WordPress site account with the role “member” (alm_member) or “operator” (alm_operator).

	ii) alm_member users can:

	 - view the list of assets and the asset detail pages,

	- request the loan of an asset,

	 - accept or reject a loan request for an asset currently assigned to them.

	 - The asset list must be paginated and provide advanced filters allowing filtering by: free text, structure, type, state, and level.

	iii) alm_operator users can do everything alm_member users can, plus additional features:

		- create assets,

		 - edit and manage assets,

		- cancel loan requests for all users,

		- approve loan requests for all users,

		- directly assign an asset to a member,

		- change the state of an asset,

		- manage plugin configuration parameters.

	iv) The loan request and approval workflow takes place between members and operators.

	v) In the AAGG WordPress front-end, there will be a section that allows viewing the association’s assets. Only members will be able to request asset assignment; anonymous users will not.

	vi) From the WordPress back office, system operators can add, remove, review, and edit all technical records of assets (components and kits).

	vii) There is an asset list that members can browse using filters (e.g., type, kit, name, etc.).

	viii) Each asset has a descriptive detail page (fields to be defined; it will certainly include: id, name, description, photo, technical sheet, external code, internal code, maintenance status, kit, state, etc.).

	ix) Kits of kits cannot be created.

	x) From the asset detail page, a member or an operator can request a loan.

	xi) A loan request sends three emails: to the requester, the current assignee, and a system email address.

	xii) The current assignee can approve or deny the loan request. This action triggers notification emails as in step xi.

	xiii) The requester and the assignee agree offline on the asset handover details.

	xiv) Once the handover has taken place, the previous assignee or the system administrator updates the current assignee of the asset. This operation triggers email notifications as in step xi.

	xv) The system stores the complete assignment history.

	xvi) The operator can view the full assignment history for all devices.

	xvii) A member can only view history entries that involve them as requester or assignee.



## Possible future extensions:
A) Export of assets, loans, and loan requests to CSV.
B) REST API for managing entities and workflows from external applications.



## System Modules:
 - ALM_Settings_Manager:
 - ALM_Role_Manager:
 - ALM_Asset_Manager:
 - ALM_Loan_Manager:
 - ALM_Notification_Manager:
 - ALM_Frontend_Manager:
 - ALM_Admin_Manager:
 - ALM_Autocomplete_Manager:


## Documentation
This is the file with the [Documentation](https://github.com/ilclaudio/asset-lending-manager/tree/dev/README.md).


## Key Directories
- `admin/`: Templates for the pages of the back-office and code used only in the WordPress back-office.
- `assets/`: The images, the JavaScript code and the css files used by this plugin.
- `includes/`: The main classes of the modules used by this plugin.
- `languages/`: The files with the label translations.
- `SETUP/`: A backup of the ACF fields definition.
- `tests/`: The unit tests and the integration tests.



## Main files:
- `assets-lending-manager.php`: Entry point of the plugin, it creates the singleton ALM_Plugin_Manager that registers and activates all the components of the system.
- `plugin-config.php`: Constants used by the modules of the plugin.
- `phpcs.xml.dist`: The rules used by PHPCS to check the code syntax and the WordPress codig rules.
- `composer.json`: The JSON file that contains the needed libraries and the development tools needed by a developer.
- `LICENSE`: The license file of this product.
- `AGENTS.md`: This file.
- `phpunit.xml`: Entry point for the unit tests included in the "tests/unit" folder.
- `phpunit-integration.xml`: Entry point for the integration tests included in the "tests/integration" folder.


## Project Repository
DEV: 


## Code Style
### Guidelines
Act as an expert PHP and WordPress developer.
Write code in compliance with the official WordPress plugin and theme guidelines.
Explain the code you write and the inner workings of WordPress, describing standard practices as we move forward.
While developing this project, I want to learn everything needed to become an expert developer of WordPress core, themes, and plugins.

Some rules to follow:

  * Use tabs, not spaces, and place comments in English at the beginning of each file and before every function.
  * Comments must end with a period ".".
  * Use WordPress naming conventions for classes, files, variables, constants, and functions.
  * Align the assignments as required by WP reules, e.g.:
```
$tipo_risorsa     = dli_get_post_main_category( $post, RT_TYPE_TAXONOMY );
$archive_page_obj = dli_get_page_by_post_type( TECHNICAL_RESOURCE_POST_TYPE );
$archive_page     = $archive_page_obj ? get_permalink( $archive_page_obj->ID ) : '';
```


Pay particular attention to the following aspects when writing code:
- Absence of bugs.
- Absence of vulnerabilities: maximum security.
- Responsive pages (mobile-first design).
- Page accessibility (this point is very important).
- Compliance with WordPress best practices.
- Simple, readable, and modular code.

In any case, be concise but precise in your responses.

### Collaboration Expectations
When you have doubts about what to do, ask before writing code and propose alternatives.
Always suggest the next step in order to quickly achieve the requested goal.
Suggest code refactorings whenever you consider them appropriate.


## CHANGELOG AND TODO
This is the file with the [ChangeLog and TODO list](https://github.com/ilclaudio/asset-lending-manager/tree/dev/CHANGELOG.md).


## Setup
## Commands
## Testing
## Build / Assets
## Deployment
## Dependencies
## Configuration
## Known Issues
