# PROJECT.md

## Project Overview
We are developing a WordPress plugin called asset-lending-manager for the lending of assets (kits and simple components).

It is a plugin (ALM – Asset Lending Manager) that implements features allowing members of a non-profit astronomy association (AAGG) to track the association’s instruments (telescopes, eyepieces, mounts, charts, etc.), books, and magazines.

These objects (resources) are assigned on loan to association members, who manage and maintain them until they are requested by another member, who then takes them over.

The managed objects are generically called “resources” (assets) and are of two types:
- Components: simple items such as eyepieces, mounts, filters, books, etc.
- Kits: a collection of components such as telescopes equipped with eyepieces and mounts, book collections, etc.

A kit cannot contain other kits.
The roles defined in the system are: member and operator.

## Main System Features

  1. Association members have a WordPress site account with the role “member” (alm_member) or “operator” (alm_operator).
  2. alm_member users can view the list of assets and asset detail pages; request the loan of an asset; accept or reject a loan request for an asset currently assigned to them; browse the asset list with filters by free text, structure, type, state, and level.
  3. alm_operator users can do everything alm_member users can, plus: create assets; edit and manage assets; cancel loan requests for all users; approve loan requests for all users; directly assign an asset to a member; change the state of an asset; manage plugin configuration parameters.
  4. The loan request and approval workflow takes place between members and operators.
  5. In the AAGG WordPress front-end, there will be a section that allows viewing the association’s assets. Only members will be able to request asset assignment; anonymous users will not.
  6. From the WordPress back office, system operators can add, remove, review, and edit all technical records of assets (components and kits).
  7. There is an asset list that members can browse using filters (e.g., type, kit, name, etc.).
  8. Each asset has a descriptive detail page (fields to be defined; it will certainly include: id, name, description, photo, technical sheet, external code, internal code, maintenance status, kit, state, etc.).
  9. Kits of kits cannot be created.
  10. From the asset detail page, a member or an operator can request a loan.
  11. A loan request sends three emails: to the requester, the current assignee, and a system email address.
  12. The current assignee can approve or deny the loan request. This action triggers notification emails as in step 11.
  13. The requester and the assignee agree offline on the asset handover details.
  14. Once the handover has taken place, the previous assignee or the system administrator updates the current assignee of the asset. This operation triggers email notifications as in step 
  15. The system stores the complete assignment history.
  16. The operator can view the full assignment history for all devices.
  17. A member can only view history entries that involve them as requester or assignee.


## Documentation

The DOC folder exists but is not yet populated. Official documentation will be added there as the project matures.


## Changelog

The project maintains a version history in `CHANGELOG.md` (currently at v0.1.0). Active issue tracking is in `AGENTS/ISSUES_TODO.md`.


## License

See the `LICENSE` file in the project root for licensing information.

