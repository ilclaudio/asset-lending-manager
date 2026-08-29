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
| Filter by warehouse | No | Yes | Yes | Yes |
| Filter by specific owner | No | No | Yes | Yes |
| Create/edit assets | No | No | Yes | Yes |
| Manage taxonomies/states/levels | No | No | Yes | Yes |
| Submit loan request | No | Yes | Yes | Yes |
| Approve/reject request | No | Yes (only if current owner) | Yes | Yes |
| View asset loan requests | No | Yes (only if current owner) | Yes (with approve/reject actions) | Yes (with approve/reject actions) |
| Direct assignment | No | No | Yes | Yes |
| Change asset state (-> maintenance / -> retired) from frontend | No | No | Yes | Yes |
| Force return asset (-> available from on-loan) from frontend | No | No | Yes | Yes |
| Cooperative return asset (-> available from on-loan) from frontend | No | Optional, only for current owner | Yes | Yes |
| Restore asset to available (from maintenance/retired) from frontend | No | No | Yes | Yes |
| View loan history (current UI) | No | No | Yes | Yes |

---

## Summary by role

- Operator -> Create/edit assets; manage taxonomies; submit and manage loan requests; direct assignment; state changes, force return, and cooperative return; restore assets; warehouse filtering. Operator state changes require location; cooperative returns leave location unchanged.
- Member -> Submit loan request; approve/reject only if current owner; cooperative return only when enabled and only for assets currently held; warehouse filtering.
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

  [View loan requests] (from asset detail page)
    -> [Approve or reject request]  (kit: partial transfer -> excluded notice)
    -> [Direct assignment]          (kit: partial transfer -> excluded notice)

  [Asset detail page]
    -> [Change state: -> maintenance / -> retired] + required location
         (kit: partial propagation -> excluded notice)
         `-> [Restore to available] + required location
               (kit: partial propagation -> excluded notice)
    -> [Force return: on-loan -> available] + required location
         (kit: partial propagation -> excluded notice)
         (closes active loan, notifies borrower)
    -> [Cooperative return: on-loan -> available] + warehouse displayed; location unchanged
         (kit: partial propagation -> excluded notice)
         (notifies operators; members only when enabled and current owner)

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
- Cooperative return is allowed for operators and, when `workflow.member_return_enabled` is enabled, for the current-owner member.
- In the current UI, loan history is shown only to operator/administrator.
- **Partial kit propagation:** kit loan transfers (approval and direct assignment) and kit state changes (maintenance, retired, force return, restore) apply only to eligible components. Components already in `maintenance` or `retired` state, or assigned to a different user, are excluded and left untouched. After each operation the operator sees a warning notice listing skipped components and the reason for each exclusion.
- A component moved to `maintenance` independently stays in its parent kit(s). A component moved to `retired` independently is removed from its parent kit(s).

---

## See also

- `DOC/KitBehaviorReference.md` — full inclusion/exclusion rules, location behavior, history, and notification semantics for all kit operations.
- `DOC/RoleActionsSwimlane.md` — Mermaid diagram version of this document.

---

*Last update: 2026-08-29 (rev 5)*
