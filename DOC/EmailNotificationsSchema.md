# Email Notifications Schema - Asset Lending Manager

This document describes when notification emails are sent, who receives them,
which template is used, and under which conditions.

Emails are sent via `wp_mail()` by the `ALMGR_Notification_Manager` class.
Sender and copy recipients are read from runtime settings:
`email.from_name`, `email.from_address`, `email.system_email`.
Event notifications are controlled by:
`notifications.enabled`, `notifications.loan_request`,
`notifications.loan_decision`, `notifications.loan_confirmation`,
and policy `notifications.loan_request_operator_mode` (`never`, `no_owner`, `always`).
Current fallbacks: site name (`get_bloginfo('name')`) and admin email (`get_bloginfo('admin_email')`) when sender fields are empty.

---

## Summary table

| # | Event | Actor | Recipients | Subject template | Special conditions |
|---|---|---|---|---|---|
| 1 | Loan request submitted | Authenticated user with request permission (typically member) | Requester | `request_to_requester` | Always |
| 1 | Loan request submitted | Authenticated user with request permission (typically member) | Current owner | `request_to_owner` | Only if asset is already assigned |
| 1 | Loan request submitted | Authenticated user with request permission (typically member) | System address | `request_to_owner` | Only if `email.system_email` is configured |
| 1 | Loan request submitted | Authenticated user with request permission (typically member) | All operators (`almgr_operator`) | `request_to_owner` | Only if operator policy is `always`, or `no_owner` with unassigned asset |
| 2 | Loan request approved | Current owner, operator, or administrator | Requester | `approved` | Always |
| 3 | Loan request rejected | Current owner, operator, or administrator | Requester | `rejected` | Always |
| 4 | Loan request automatically canceled | System | Requester (of canceled request) | `canceled` | One email per canceled concurrent request |
| 5 | Direct assignment | Operator or administrator | New owner | `direct_assign` | Always |
| 5 | Direct assignment | Operator or administrator | Previous owner | `direct_assign_to_prev_owner` | Only if previous owner existed and is different from assignee |
| 5 | Direct assignment | Operator or administrator | System address | `direct_assign_to_prev_owner` | Only if `email.system_email` is configured |
| 6 | Force return (on-loan -> available) | Operator or administrator | User who had the loan | `force_return` | Only if `notifications.enabled=true` and `notifications.loan_request=true` |

---

### System copy email

- **Recipient:** `email.system_email`
- **Condition:** sent only if configured
- **Loan request event:** uses `request_to_owner`
- **Direct assignment event:** uses `direct_assign_to_prev_owner`
- **Direct assignment note:** if there was no previous owner, placeholder `{PREV_OWNER_NAME}` is empty

### Recipient deduplication (loan request event)

For "Loan request submitted", recipients are deduplicated by normalized email address.
If the same address appears as requester, owner, `system_email`, or operator,
only one email is sent to that address.

---

## Available placeholders by template

| Placeholder | Available in | Meaning |
|---|---|---|
| `{ASSET_TITLE}` | all | Asset post title |
| `{ASSET_URL}` | all | Frontend asset URL |
| `{REQUESTER_NAME}` | request_*, approved, rejected, canceled | Requester display name |
| `{REQUEST_MESSAGE}` | request_to_owner | Optional requester message |
| `{REJECTION_MESSAGE}` | rejected | Rejection reason |
| `{ASSIGNEE_NAME}` | direct_assign, direct_assign_to_prev_owner | New owner display name |
| `{ACTOR_NAME}` | direct_assign, direct_assign_to_prev_owner | User who performed direct assignment |
| `{REASON}` | direct_assign, direct_assign_to_prev_owner | Direct assignment reason |
| `{PREV_OWNER_NAME}` | direct_assign_to_prev_owner | Previous owner display name |
| `{BORROWER_NAME}` | force_return | User who had the loan |
| `{ACTOR_NAME}` | force_return | User (operator/administrator) who performed force return |
| `{NOTES}` | force_return | Optional operator notes (`-` if empty) |

---

## Sender and notification settings

Runtime settings in `almgr_settings`:

| Setting | Purpose |
|---|---|
| `email.from_name` | Sender display name (fallback: site name) |
| `email.from_address` | Sender email address (fallback: site admin email) |
| `email.system_email` | Optional system/operator copy address |
| `notifications.enabled` | Global master switch for notifications |
| `notifications.loan_request` | Enable/disable loan request related notifications |
| `notifications.loan_decision` | Enable/disable approval/rejection notifications |
| `notifications.loan_confirmation` | Enable/disable direct assignment notifications |
| `notifications.loan_request_operator_mode` | Operator recipient policy for loan requests: `never`, `no_owner`, `always` |

Note: in the current flow, `ALMGR_Notification_Manager` uses runtime settings; `ALMGR_EMAIL_*` constants in `plugin-config.php` are not read directly by the send logic.
Note: `force_return` currently uses `notifications.loan_request` in addition to master `notifications.enabled`.

---

*Last update: 2026-04-14 (rev 1)*
