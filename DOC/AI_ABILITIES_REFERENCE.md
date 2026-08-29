# AI Abilities Reference — Asset Lending Manager

## 1. Overview

Asset Lending Manager registers a set of WordPress Abilities under the category
`almgr`, using the WordPress core Abilities API (`wp_register_ability()`).
Abilities are a typed, PHP-callable contract on top of the same shared domain
  services used by the web interface and by the REST API described in
  `DOC/REST_API_REFERENCE.md`: every Ability delegates its query and visibility
  logic to shared services, while common asset projections are shared between
  REST and Abilities, so results remain consistent
with what the same user would see in the frontend or via REST.

The Abilities module is entirely optional and self-disabling: the WordPress
Abilities API itself requires **WordPress 6.9 or later**. On older versions,
`function_exists( 'wp_register_ability' )` is `false`, `ALMGR_Abilities_Manager`
registers nothing, and the rest of the plugin is unaffected — no fatal error,
no degraded behavior elsewhere.

MCP (Model Context Protocol) support is not built into this plugin — Abilities
are the contract; an external, separately maintained project
(`wordpress/mcp-adapter`) is what can bridge them to an MCP client such as
Claude Desktop. See §6.

---

## 2. Conventions

### 2.1 How Abilities are invoked

- **PHP, in-process:** `wp_get_ability( 'almgr/list-assets' )->execute( $input )`.
  This is how the plugin's own code would call an Ability if needed, and how
  any other WordPress plugin running on the same site can call it.
- **REST:** every ability below sets `show_in_rest: true`, so each is also
  reachable at `wp-json/wp-abilities/v1/` (WordPress core's own Abilities REST
  surface). This is a **different namespace** from the plugin's own
  `wp-json/almgr/v1/` REST API — do not confuse the two.
- **MCP:** only reachable through a separate, optional MCP adapter — see §6.

### 2.2 Actor identity

Every Ability that reads or writes data scoped to "the current user" (`list-my-assets`,
`list-my-loan-requests`, `create-loan-request`) always derives the actor from
`get_current_user_id()`. None of them accept a caller-supplied user ID for
"my"-scoped operations — this is a deliberate security decision, not an
oversight, matching the two-level authorization model in
`DEV/TODO/TODO_MCP_AbilitiesImplementationPlan.md`.

### 2.3 Permissions

Every Ability has its own `permission_callback`. Two patterns are used:

- A plain WordPress capability check (`current_user_can( ALMGR_VIEW_ASSET )`,
  etc.) for abilities whose visibility does not depend on which specific
  record is requested.
- A call into the shared `ALMGR_Access_Policy` for abilities whose visibility
  depends on the specific asset or loan request being requested (ownership,
  operator status) — the same policy class used by the REST API for the
  equivalent endpoints.

| Capability | Grants |
|---|---|
| `almgr_view_assets` | Catalog listing. |
| `almgr_view_asset` | Single-record reads and loan-request operations, subject to `ALMGR_Access_Policy` where applicable. |
| `almgr_edit_asset` | Not used directly by any MVP ability (operator-only abilities are out of v1 scope — see §7). |

### 2.4 Public exposure (`abilities.public` / `abilities.actions_public`)

By default, every Ability below is registered with `show_in_rest: true` (always
reachable via `wp-abilities/v1` for an authenticated, capable WordPress user)
but with `meta.public` **not** enabled — meaning external clients such as an
MCP adapter will not surface them to an AI agent unless a site explicitly opts
in.

Two settings control this, under **ALM > Settings > Abilities**:

| Setting | Default | Effect |
|---|---|---|
| `abilities.public` | `false` | Sets `meta.public` (and `meta.mcp.public`, see the note below) to `true` for the seven read-only abilities. |
| `abilities.actions_public` | `false` | Same, but for `almgr/create-loan-request` only. Has no effect unless `abilities.public` is also enabled. |

**Why two flags:** a site may want an AI agent to browse the catalog and loan
history without ever letting it submit a loan request on a user's behalf. The
read/write split mirrors the "read operations" vs. "operations with side
effects" distinction already used in the project's own MCP strategy document.

**`meta.public` vs. `meta.mcp.public`:** WordPress core's Abilities API defines
a single `meta.public` flag — "whether the ability is meant to be available to
clients such as the REST API, MCP, or AI agents" (default `false`). Separately,
`wordpress/mcp-adapter` reads its own `meta.mcp.public` flag, which can veto MCP
exposure specifically even when `meta.public` is `true`. This plugin sets both
flags identically from the same setting, because v1 has no need to expose an
ability generically as "public" while still hiding it specifically from MCP.

### 2.5 Common annotations

Every ability carries `annotations.readonly`, `annotations.destructive`, and
`annotations.idempotent`. All seven read abilities are
`readonly: true, destructive: false, idempotent: true`. `almgr/create-loan-request`
is the only exception: `readonly: false, destructive: false, idempotent: false`
(creating the same request twice is not a no-op).

### 2.6 Pagination

Shared `ALMGR_Pagination` defaults apply everywhere: `page` defaults to `1`,
`per_page` defaults to `20` (maximum `100`). The **response shape is not
uniform** across abilities — document this per-ability rather than assume a
single convention:

- `list-assets`, `list-my-assets`, `get-asset-loan-history` return
  `{ data, total, pages }` — no `page` key.
- `list-my-loan-requests`, `list-asset-loan-requests` return
  `{ data, total, page, pages }` — `page` included.

### 2.7 Errors

Abilities return a `WP_Error` on failure (not a REST-style HTTP status). Common
codes: `almgr_missing_asset_id`, `almgr_asset_not_found`,
`almgr_missing_loan_request_id`, `almgr_loan_request_not_found`,
`almgr_loan_request_forbidden`, `almgr_missing_request_message`,
`almgr_member_assets_forbidden`.

---

## 3. Data models

The Ability response shape is **not** identical field-for-field to the REST
API shape (`DOC/REST_API_REFERENCE.md`, §3), even though both are built from
the same shared domain services. Do not assume parity of exact JSON keys —
only parity of the underlying domain data and visibility rules.

### 3.1 Asset object (Ability projection)

List form (`list-assets`, via `project_asset()`):

| Field | Type |
|---|---|
| `id` | integer |
| `code` | string |
| `title` | string |
| `permalink` | string |
| `thumbnail_url` | string\|null |
| `structure` | array |
| `type` | array |
| `state` | array |
| `level` | array |
| `warehouse` | array |
| `owner_id` | integer |
| `owner_name` | string |
| `owner_username` | string |

Detail form (`get-asset`) adds: `content` (plain text, tags stripped),
`components`, `parent_kits`, `state_label`, `acf_fields` (nested object — not
flattened keys as in the REST API).

### 3.2 Member asset object (`list-my-assets`)

| Field | Type |
|---|---|
| `id` | integer |
| `code` | string |
| `title` | string |
| `structure` | array |
| `type` | array |
| `external_code` | string |
| `location` | string |
| `thumbnail_url` | string\|null |
| `permalink` | string |

### 3.3 History row object (`get-asset-loan-history`)

| Field | Type |
|---|---|
| `status` | string |
| `changed_at` | string (ISO 8601) |
| `changed_by` | integer |
| `changed_by_name` | string |
| `message` | string |

### 3.4 Loan request object

Identical to the REST API's loan request object (§3.3 of
`DOC/REST_API_REFERENCE.md`) — both channels call the same
`ALMGR_Loan_Request_Query_Service::project()` method:

```json
{
  "id": 42,
  "asset_id": 17,
  "requester_id": 8,
  "owner_id": 0,
  "request_date": "2026-08-28 10:30:00",
  "request_message": "Request message",
  "status": "pending"
}
```

---

## 4. Abilities reference

### 4.1 Assets

#### `almgr/list-assets`

Paginated asset catalog. Permission: `almgr_view_assets`.

**Input:** `page`, `per_page`, `search` (string), `state` (taxonomy slug),
`type` (taxonomy slug), `structure` (taxonomy slug), `owner` (integer user ID,
default `0`).

**Output:** `{ data: Asset[] (list form), total, pages }`.

#### `almgr/get-asset`

Single asset detail. Permission: `almgr_view_asset`.

**Input:** `asset_id` (integer, required).

**Output:** Asset object (detail form), or `WP_Error`
(`almgr_missing_asset_id`, `almgr_asset_not_found`).

#### `almgr/get-asset-loan-history`

Visible loan history for one asset. Permission: `almgr_view_asset`. Visibility
narrowing (operators see all entries; members see only entries they are
involved in) happens inside the shared history service itself, not in the
permission callback.

**Input:** `asset_id` (integer, required), `page`, `per_page`.

**Output:** `{ data: history row[] (§3.3), total, pages }`.

### 4.2 My assets

#### `almgr/list-my-assets`

Assets currently held by the authenticated user. Permission: `almgr_view_asset`.
Actor is always `get_current_user_id()` (§2.2).

**Input:** `page`, `per_page`.

**Output:** `{ member_id, data: member asset[] (§3.2), total, pages }`.

### 4.3 Loan requests

#### `almgr/list-my-loan-requests`

Loan requests created by the authenticated user. Permission: `almgr_view_asset`.

**Input:** `status` (string, optional), `page`, `per_page`.
`additionalProperties: false`.

**Output:** `{ data: loan request[] (§3.4), total, page, pages }`.

#### `almgr/get-loan-request`

Detail of one loan request. Permission: dynamic — `ALMGR_Access_Policy::can_view_loan_request()`,
evaluated against the actual fetched request (requester, or the asset's
current owner/operator).

**Input:** `loan_request_id` (integer, required). `additionalProperties: false`.

**Output:** Loan request object (§3.4), or `WP_Error`
(`almgr_missing_loan_request_id`, `almgr_loan_request_not_found`,
`almgr_loan_request_forbidden`).

> This is the one operation with **no REST equivalent** — the REST API does not
> expose a single-request detail endpoint (`DOC/REST_API_REFERENCE.md`, §4.3).

#### `almgr/list-asset-loan-requests`

Loan requests visible for an asset. Permission: `ALMGR_Access_Policy::can_view_asset_requests()`
— asset's current owner, or an operator/administrator.

**Input:** `asset_id` (integer, required), `status` (string, optional), `page`,
`per_page`. `additionalProperties: false`.

**Output:** `{ data: loan request[] (§3.4), total, page, pages }`, or `WP_Error`
(`almgr_missing_asset_id`).

#### `almgr/create-loan-request`

Creates a loan request for the authenticated user. Permission: `almgr_view_asset`.
**Not read-only** — see §2.5. Delegates entirely to
`ALMGR_Loan_Manager::submit_loan_request()`, the same shared workflow used by
the web UI's AJAX handler: same validation, same duplicate-request and
workflow-limit checks, same persisted effect.

**Input:** `asset_id` (integer, required), `request_message` (string,
required). `additionalProperties: false`.

**Output:** The shared workflow's result, or `WP_Error`
(`almgr_missing_asset_id`, `almgr_missing_request_message`, plus whatever
domain error `submit_loan_request()` itself returns — duplicate request,
ineligible asset, workflow limit reached).

---

## 5. Examples

### 5.1 PHP, in-process

```php
$ability = wp_get_ability( 'almgr/get-asset' );
if ( $ability && true === $ability->check_permissions( array( 'asset_id' => 42 ) ) ) {
    $result = $ability->execute( array( 'asset_id' => 42 ) );
}
```

### 5.2 REST (`wp-abilities/v1`)

Only abilities with `meta.public: true` (§2.4) are meant to be reached this way
by an external client; authentication uses the same WordPress mechanisms as
the plugin's own REST API (`DOC/REST_API_REFERENCE.md`, §2.3).

```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
     -X POST "https://your-site.example.com/wp-json/wp-abilities/v1/abilities/almgr/list-assets/run" \
     -H "Content-Type: application/json" \
     -d '{"input": {"per_page": 5}}'
```

Note the route shape: `wp-json/wp-abilities/v1/abilities/{ability-name}/run` (the
`{ability-name}` itself contains a slash, e.g. `almgr/list-assets`), and the
ability's own input parameters must be wrapped inside a top-level `input` key
in the request body — verified against WordPress core's
`WP_REST_Abilities_V1_Run_Controller`.

---

## 6. Using these Abilities from Claude Desktop

Claude Desktop is an MCP **client**. This plugin does not run an MCP server —
it only registers Abilities. To reach them from Claude Desktop, a separate,
optional bridge is required: **`wordpress/mcp-adapter`**
(https://github.com/WordPress/mcp-adapter), installed and configured on the
WordPress site. This is a WordPress-maintained project, not part of Asset
Lending Manager — refer to its own documentation for installation and MCP
server transport configuration.

Once that bridge is in place, whether Claude Desktop can actually see and call
an `almgr/*` ability depends on this plugin's own settings, not just on the
adapter being installed:

1. Enable **ALM > Settings > Abilities > Public read-only abilities**
   (`abilities.public`) to make the seven read-only abilities discoverable.
2. Additionally enable **Public loan-request creation**
   (`abilities.actions_public`) only if you want an AI agent to be able to
   submit loan requests on a user's behalf. Leave it off to keep Claude
   Desktop read-only against this plugin's data.
3. In Claude Desktop's own MCP server configuration, add the server exposed by
   `mcp-adapter` (URL/command as documented by that project).
4. Authentication for the machine-to-machine connection should use standard
   WordPress Application Passwords, consistent with every other remote-access
   method this plugin supports — no custom login flow is introduced for MCP.

**Scope reminder:** an AI agent connected this way can never do more than the
WordPress user it authenticates as could already do through the web UI or the
REST API — the same capability checks and `ALMGR_Access_Policy` rules apply
identically (§2.3).

---

## 7. Scope & versioning

### Out of scope for v1

Explicitly not implemented as abilities yet (see
`DEV/TODO/TODO_MCP_AbilitiesImplementationPlan.md`, "Fuori scope v1"):

- Approving or rejecting a loan request.
- Direct asset assignment.
- Asset state transitions (maintenance/retired/force-return/restore).
- `almgr/list-asset-loan-requests`'s counterpart for an operator browsing an
  arbitrary member's assets (`get_member_assets` as a public ability).
- Any mandatory dependency on `mcp-adapter` — it remains an optional,
  separately installed bridge.

### Versioning

Ability names (`almgr/list-assets`, etc.) and their input/output shapes follow
the plugin's own Semantic Versioning policy
(`DEV/AGENTS/CODING_STANDARDS.md`): a breaking change to any ability's schema
or behavior requires a MAJOR version bump. For implementation status and
session history, see `DEV/TODO/TODO_MCP_AbilitiesImplementationPlan.md`. For
the history of changes that affected the Abilities layer, see `CHANGELOG.md`.

---

*Last update: 2026-08-29 (rev 3) — documented the shared common asset projections
used by REST and Abilities.*
