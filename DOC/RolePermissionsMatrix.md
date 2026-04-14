# Role Permissions Matrix - Asset Lending Manager

This document summarizes the operations available for each plugin role.

## Roles used by the system
* Administrator
* Visitor (anonymous user)
* **Member**: `almgr_member`
* **Operator**: `almgr_operator`

---

## Role x operation matrix

| Operation | Visitor | Member (`almgr_member`) | Operator (`almgr_operator`) | Administrator |
|---|---|---|---|---|
| View asset list | Yes | Yes | Yes | Yes |
| View asset detail | Yes | Yes | Yes | Yes |
| Filter by "only my assets" | No | Yes | No | No |
| Filter by specific owner | No | No | Yes | Yes |
| Create/edit assets | No | No | Yes | Yes |
| Manage taxonomies/states/levels | No | No | Yes | Yes |
| Submit loan request | No | Yes | Yes | Yes |
| Approve/reject request | No | Yes (only if current owner) | Yes | Yes |
| View asset loan requests | No | Yes (only if current owner) | Yes (with approve/reject actions) | Yes (with approve/reject actions) |
| Direct assignment | No | No | Yes | Yes |
| Change asset state (-> maintenance / -> retired) from frontend | No | No | Yes | Yes |
| Force return asset (-> available from on-loan) from frontend | No | No | Yes | Yes |
| Restore asset to available (from maintenance/retired) from frontend | No | No | Yes | Yes |
| View loan history (current UI) | No | No | Yes | Yes |

---

## Summary by role

- Operator -> Create/edit assets; manage taxonomies; submit loan requests; manage loan requests (approve/reject allowed); direct assignment; change asset state (-> maintenance / -> retired) from frontend with required location; force return (-> available from on-loan) with required location and borrower notification; restore asset to available (from maintenance/retired) from frontend with required location.
- Member -> Submit loan request; approve/reject only if current owner.
- Administrator -> Same operational scope as operator, including approve/reject even when not current owner. Plus standard WordPress administrator privileges.

---

## Swimlane (ASCII)

Mermaid version: `DOC/RoleActionsSwimlane.md`.

```text
VISITOR
  [View asset list] -> [View asset detail]

MEMBER (almgr_member)
  [Browse list and detail]
    -> [Submit loan request]
    -> <Current owner?>
         |-- Yes --> [Approve or reject request]
         `-- No --> [Wait for outcome]

OPERATOR / ADMINISTRATOR
  [Create or edit asset]
    -> [Manage taxonomies]
    -> [View loan requests]
    -> [Approve or reject request]
    -> [Direct assignment (asset not retired/maintenance)]
    -> [Change state: -> maintenance / -> retired] + required location
         `-> [Restore to available from maintenance/retired] + required location
    -> [Force return: on-loan -> available] + required location
         (closes active loan, notifies borrower)

CROSS-LANE LINKS
  Member: [Submit loan request] -----------> Operator: [View loan requests]
  Member/Owner: [Approve/reject]     \
  Operator: [Direct assignment]       +---> [History updated]
  Operator: [Change/Restore state]   /
  Operator: [Force return]           /
```

---

## Important notes

- Approve/reject actions are allowed for members only if they are the current owner of the asset. Operators and administrators (capability `almgr_edit_asset`) can approve/reject even when they are not current owners.
- Direct assignment is allowed only for users with capability `almgr_edit_asset` (operator/administrator).
- In the current UI, loan history is shown only to operator/administrator.

---

*Last update: 2026-04-14 (rev 1)*
