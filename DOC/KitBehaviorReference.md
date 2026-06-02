# Kit Behavior Reference — Asset Lending Manager

This document describes how kits and their components behave during loan transfers,
state changes, and restore operations. It is intended for operators managing assets
and for developers maintaining or extending the plugin.

---

## Overview

A **kit** is an asset of structure `kit` that groups one or more component assets.
A **component** is an asset of structure `component`.

Kit composition is stored in the ACF field `almgr_components` on the kit post.
The plugin does not support nested kits (a kit inside another kit).

---

## Kit Transfer (Loan Approval and Direct Assignment)

When a kit is approved for a loan or directly assigned, the system builds a
**transfer plan** before writing any data. The plan classifies each component as
included or excluded. Only included components receive the new owner and state.

### Inclusion and exclusion rules

| Component state | Component current owner | Decision | Reason code |
|---|---|---|---|
| `available` | none (0) | **Included** | — |
| `on-loan` | same as kit's current owner | **Included** | — |
| `on-loan` | different from kit's current owner | Excluded | `on_loan_to_other_user` |
| `maintenance` | any | Excluded | `maintenance` |
| `retired` | any | Excluded | `retired` |
| unknown/other | any | Excluded | `unknown_state` |

The kit itself is always updated regardless of component outcomes.

### Location behavior

The `almgr_location` field is cleared on the kit and on every included component.
Excluded components retain their current location value.

### History behavior

One history entry (`approved` or `direct_assign`) is written for the kit and for
each included component. Excluded components get no history entry.
The kit history entry appends a plain-text summary of excluded components and their
exclusion reasons.

### Notification behavior

Cancellation notifications (for concurrent pending requests) are sent only for
the kit and included components. Owners of excluded components receive no
notification — their assignment has not changed.

---

## Kit State Changes

When a kit's state is changed by an operator (maintenance, retired, force return,
or restore), the system builds a **state-change plan**. The plan classification
depends on the target state and the kit's current state.

### Target: `maintenance` or `retired`

Applies when an operator moves an available or on-loan kit to maintenance or retired.

| Component state | Component current owner | Decision |
|---|---|---|
| `available` | none (0) | **Included** |
| `on-loan` | same as kit's current owner | **Included** |
| `maintenance` | any | Excluded |
| `retired` | any | Excluded |
| `on-loan` | different from kit's current owner | Excluded |

Included components follow the kit to the new state. Their owner is set to 0.
Excluded components are left untouched.

### Target: `available` — force return (kit was `on-loan`)

Applies when an operator force-returns an on-loan kit to available.

| Component state | Component current owner | Decision |
|---|---|---|
| `on-loan` | same as kit's current owner | **Included** |
| anything else | any | Excluded |

### Target: `available` — restore (kit was `maintenance` or `retired`)

Applies when an operator restores a kit from maintenance or retired to available.

| Component state | Component current owner | Decision |
|---|---|---|
| same state as the kit | none (0) | **Included** |
| any other state | any | Excluded |

This rule ensures that only components that were in the same transition as the kit
are restored. Components that reached their current state independently are not affected.

### Location behavior on state changes

After a state change the operator provides a location value. That value is written
to the kit and to every included component. Excluded components retain their
current location.

---

## Component-Level State Changes (independent of kit)

When a single component changes state without a kit-level propagation:

### Component → `maintenance`

- State is changed to `maintenance`, owner set to 0.
- The component **stays in its parent kit(s)**. Kit composition is not modified.
- `_almgr_removed_from_kit_ids` is **not written**.

### Component → `retired`

- State is changed to `retired`, owner set to 0.
- The component is **removed from all parent kit(s)**.
- The kit IDs are saved in `_almgr_removed_from_kit_ids` on the component post.

### Restore from `maintenance` → `available`

- State-only change: state set to `available`, owner stays 0.
- **No kit re-attach** is performed: the component was never removed from the kit.
- Any stale `_almgr_removed_from_kit_ids` meta from pre-refactor data is deleted defensively.

### Restore from `retired` → `available`

- State set to `available`, owner stays 0.
- The component is **re-added** to all kits listed in `_almgr_removed_from_kit_ids`.
- `_almgr_removed_from_kit_ids` is deleted after re-attach.

---

## Excluded / Skipped Component Notice (Frontend)

After any kit operation that produces excluded or skipped components, the operator
sees a warning notice on the next page load.

Flow:
1. The AJAX response payload includes an `excluded_components` or `skipped_components`
   array with `id`, `title`, `reason_code`, and `reason_label` for each excluded item.
2. Before the page redirect the frontend saves the array to `sessionStorage`
   under the key `almgr_excluded_notice`.
3. On the next page load, `showExcludedNotice()` reads and removes the entry,
   then renders a `.almgr-notice.almgr-notice--warning` block listing each
   excluded component as "Title (reason)".

The notice appears once only. If `sessionStorage` is unavailable (private browsing,
restricted iframe) the notice is silently skipped; the operation itself is not affected.

---

## Summary: `_almgr_removed_from_kit_ids` Meta

| Scenario | Meta written? |
|---|---|
| Component → `maintenance` (standalone) | No |
| Component → `retired` (standalone) | Yes — kit IDs saved |
| Kit → `maintenance`/`retired` (propagation to included components) | No |
| Restore component from `maintenance` | No (meta cleaned up if stale) |
| Restore component from `retired` | Deleted after re-attach |

---

*Last update: 2026-06-02 (rev 1)*
