# Role Actions Swimlane - Asset Lending Manager

Mermaid diagram version of `DOC/RolePermissionsMatrix.md`.
If your viewer does not render Mermaid, use the ASCII version below.

---

## Mermaid diagram

```mermaid
flowchart TD
  subgraph VIS["Visitor"]
    V1["View asset list"]
    V2["View asset detail"]
    V1 --> V2
  end

  subgraph MEM["Member (almgr_member)"]
    M1["Browse list and detail"]
    M2["Submit loan request"]
    M3{"Current owner?"}
    M4["Approve / reject request"]
    M5["Wait for outcome"]
    M1 --> M2 --> M3
    M3 -->|Yes| M4
    M3 -->|No| M5
  end

  subgraph OPE["Operator / Administrator"]
    O1["Create or edit asset"]
    O2["Manage taxonomies"]
    O3["View loan requests"]
    O4["Approve / reject request"]
    O5["Direct assignment"]
    O6["Change state\nmaintenance / retired\n+ required location"]
    O7["Restore to available\nfrom maintenance / retired\n+ required location"]
    O8["Force return\nfrom on-loan -> available\n+ required location\n(closes loan, notifies borrower)"]
    O1 --> O2
    O2 --> O3
    O3 --> O4
    O3 --> O5
    O3 --> O6
    O6 --> O7
    O3 --> O8
  end

  H1[("History updated")]

  M2 --> O3
  M4 --> H1
  O4 --> H1
  O5 --> H1
  O6 --> H1
  O7 --> H1
  O8 --> H1
```

---

## ASCII version

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
    -> [Direct assignment]
    -> [Change state: maintenance / retired] + required location
         `-> [Restore to available from maintenance/retired] + required location
    -> [Force return: on-loan -> available] + required location
         (closes active loan, notifies borrower)

CROSS-LANE LINKS
  Member: [Submit loan request] ---------> Operator: [View loan requests]
  Member/Owner: [Approve/reject]   \
  Operator: [Direct assignment]     +---> [History updated]
  Operator: [Change/Restore state] /
  Operator: [Force return]         /
```

---

*Last update: 2026-04-14 (rev 1)*
