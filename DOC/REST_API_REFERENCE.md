# REST API Reference — Asset Lending Manager

## 1. Overview

Asset Lending Manager exposes a **read-only** JSON REST API under the WordPress REST
API namespace `almgr/v1`. It is intended for external integrations, scripts, and
custom frontends that need to read the asset catalog, member assignments, and loan
request data without going through the plugin's own shortcode-based UI.

The API shares the same domain services used by the web interface and by the
plugin's WordPress Abilities registrations (see `DOC/AI_ABILITIES_REFERENCE.md`):
every endpoint below delegates its query, visibility, and data-shaping logic to a
shared service class, so results are always consistent with what a logged-in user
would see in the frontend.

All endpoints are registered via `register_rest_route()` on `rest_api_init`, in
`includes/class-almgr-rest-manager.php`.

---

## 2. Conventions

### 2.1 Base URL

```
https://your-site.example.com/wp-json/almgr/v1/
```

### 2.2 Global enable/disable switch

The entire API can be toggled from **ALM > Settings > REST API**
(setting key `rest_api.enabled`, boolean, default `true`). When disabled, every
endpoint returns HTTP 503:

```json
{ "code": "almgr_rest_disabled", "message": "REST API is disabled." }
```

### 2.3 Authentication

Authentication is delegated entirely to WordPress core. Three methods work:

| Method | How to use |
|---|---|
| **Session cookie + REST nonce** | Standard for logged-in browser sessions. Pass the nonce in the `X-WP-Nonce` header (value from `wp_create_nonce('wp_rest')`). |
| **Application Passwords** | HTTP Basic Auth with `username:application_password`. Recommended for scripts and external integrations. Create in **Users > Profile > Application Passwords**. |
| **No auth (public endpoints)** | The asset autocomplete endpoint is publicly accessible when configured that way. Every other endpoint requires authentication. |

### 2.4 Capabilities

Every endpoint requires one of these plugin-defined capabilities (in addition to
authentication). This table is the single source of truth; individual endpoints
below only name which capability they require.

| Capability | Grants |
|---|---|
| `almgr_view_assets` | Read access to the asset catalog (list endpoint). |
| `almgr_view_asset` | Read access to a single asset, and to loan-request endpoints, subject to the request-visibility policy in §2.6. |
| `almgr_edit_asset` | Operator-level access: member management endpoints, operator-only asset fields, and the user-search endpoint used by direct assignment. |

Members (`almgr_member` role) hold `almgr_view_assets`/`almgr_view_asset` by
default; operators (`almgr_operator` role) and administrators hold all three.

### 2.5 Pagination

Two pagination conventions coexist, depending on when the endpoint was added.
Each endpoint below states which one it uses.

- **WP standard headers** (`GET /assets`, `GET /members`): query params `page`
  and `per_page`; response body is a plain JSON array; pagination totals are
  returned in the `X-WP-Total` and `X-WP-TotalPages` response headers.
- **Structured envelope** (`GET /me/loan-requests`, `GET /assets/{id}/loan-requests`):
  same `page`/`per_page` query params, plus `status`; response body is an object
  with `data`, `total`, `page`, and `pages` keys (see §3.3).

### 2.6 Request-visibility policy

Loan-request endpoints apply an additional domain rule, on top of the
`almgr_view_asset` capability check: a member may only see requests they are
involved in (as requester, or as the asset's current owner); operators and
administrators may see any request. This rule lives in `ALMGR_Access_Policy`
and is the same rule enforced by the web interface and by the corresponding
Ability — see the architectural note in §1.

### 2.7 Error format

All endpoints return the standard WordPress REST error shape on failure:

```json
{
  "code": "error_code",
  "message": "Human-readable error description.",
  "data": { "status": 403 }
}
```

Common codes:

| Code | HTTP status | Meaning |
|---|---|---|
| `almgr_rest_disabled` | 503 | API disabled from settings (§2.2). |
| `rest_forbidden` | 403 | Insufficient capability, or request-visibility policy denied access. |
| `rest_not_found` | 404 | Asset, member, or request not found. |
| `almgr_invalid_term` | 400 | Autocomplete search term too short or invalid. |

---

## 3. Data models

### 3.1 Asset object

Returned by `GET /assets` (list form) and `GET /assets/{id}` (detail form, which
adds full ACF fields and loan history).

| Field | Type | Notes |
|---|---|---|
| `id` | integer | Post ID. |
| `title` | string | |
| `permalink` | string | |
| `structure` | string | Taxonomy slug: `component` or `kit`. |
| `type` | string | Taxonomy slug (e.g. `telescope`, `book`, ...). |
| `state` | string | Taxonomy slug: `available`, `on-loan`, `maintenance`, `retired`. |
| `level` | string | Taxonomy slug: `basic`, `intermediate`, `advanced`. |
| ACF fields | mixed | Unprefixed keys for API stability (e.g. `manufacturer`, not `almgr_manufacturer`). Full field list: `manufacturer`, `model`, `data_acquisto`, `cost`, `dimensions`, `weight`, `location`, `components`, `user_manual`, `technical_data_sheet`, `serial_number`, `external_code`, `notes`. |
| Operator-only fields | mixed | `cost`, `data_acquisto`, `notes`, and (detail only) loan history entries are omitted entirely for callers without `almgr_edit_asset`, rather than returned empty. |

### 3.2 Member/user object

Two distinct shapes exist for users, depending on the endpoint — do not assume
they are interchangeable.

**Member list item** (`GET /members`):

| Field | Type | Notes |
|---|---|---|
| `id` | integer | WordPress user ID. |
| `display_name` | string | |
| `email` | string | |
| `roles` | array | ALM roles only (`almgr_member`, `almgr_operator`), other WordPress roles are not included. |
| `active_loan_count` | integer | |

**User suggestion** (`POST /users/autocomplete`) — lighter shape, used by the
direct-assignment picker:

| Field | Type | Notes |
|---|---|---|
| `id` | integer | |
| `display_name` | string | |
| `user_login` | string | Not present on the member-list shape above. |

### 3.3 Loan request object

Returned inside the paginated envelope by `GET /me/loan-requests` and
`GET /assets/{id}/loan-requests`:

```json
{
  "data": [
    {
      "id": 42,
      "asset_id": 17,
      "requester_id": 8,
      "owner_id": 0,
      "request_date": "2026-08-28 10:30:00",
      "request_message": "Request message",
      "status": "pending"
    }
  ],
  "total": 1,
  "page": 1,
  "pages": 1
}
```

`status` is one of: `pending`, `approved`, `rejected`, `canceled`.

### 3.4 Asset suggestion (autocomplete)

Returned by `POST /assets/autocomplete`:

| Field | Type | Notes |
|---|---|---|
| `id` | integer | |
| `title` | string | |
| `description` | string | Truncated. |
| `structure` | string | Taxonomy slug. |
| `type` | string | Taxonomy slug. |
| `permalink` | string | |

---

## 4. Endpoints

### 4.1 Assets

#### `GET /assets`

Paginated list of published assets. Pagination: WP standard headers (§2.5).
Capability: `almgr_view_assets`.

**Query parameters:**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 10 | Results per page |
| `search` | string | — | Full-text search on asset title |
| `structure` | string | — | Filter by structure slug (`component`, `kit`) |
| `type` | string | — | Filter by type taxonomy slug |
| `state` | string | — | Filter by state taxonomy slug |

**Response:** JSON array of Asset objects (§3.1, list form).

#### `GET /assets/{id}`

Detail of a single published asset. Capability: `almgr_view_asset`.

**Path parameter:** `id` — asset post ID (integer).

**Response:** Single Asset object (§3.1, detail form).

---

### 4.2 Members

#### `GET /members`

Paginated list of users with ALM roles. Pagination: WP standard headers (§2.5).
Capability: `almgr_edit_asset` (operator or administrator).

**Query parameters:**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 10 | Results per page |

**Response:** JSON array of Member list items (§3.2).

#### `GET /members/{member_id}/assets`

Assets currently on loan to a specific member. Capability: `almgr_edit_asset`.

**Path parameter:** `member_id` — WordPress user ID (integer).

**Response:** JSON array of Asset objects (§3.1) currently assigned to the
member (`_almgr_current_owner = member_id`).

---

### 4.3 Loan requests

#### `GET /me/loan-requests`

Loan requests created by the authenticated user. Pagination: structured
envelope (§2.5). Capability: `almgr_view_asset`.

**Query parameters:**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | integer | 1 | Page number (minimum 1) |
| `per_page` | integer | 20 | Results per page (1–100) |
| `status` | string | — | Optional status filter |

**Response:** Paginated envelope of Loan request objects (§3.3).

#### `GET /assets/{id}/loan-requests`

Loan requests visible for an asset. Pagination: structured envelope (§2.5).
Capability: `almgr_view_asset`, plus the request-visibility policy (§2.6) — a
member sees this only when they are the asset's current owner; operators and
administrators always see it.

**Path parameter:** `id` — asset post ID (integer).

**Query parameters:** Same as `GET /me/loan-requests`.

**Response:** Paginated envelope of Loan request objects (§3.3).

> A single-request detail endpoint (`GET /loan-requests/{id}`) is **not**
> exposed over REST. It is available only as an Ability
> (`almgr/get-loan-request`) — see `DOC/AI_ABILITIES_REFERENCE.md`.

---

### 4.4 Autocomplete

#### `POST /assets/autocomplete`

Asset search endpoint for the frontend autocomplete widget. Capability: none
by default (public); configurable via **ALM > Settings > Autocomplete > Public
assets endpoint**. When public access is disabled, requires a logged-in user
with `almgr_view_assets`.

**Request body (JSON):**

| Field | Type | Required | Description |
|---|---|---|---|
| `term` | string | Yes | Search string (minimum length configurable; default 2 characters) |

**Response:** JSON array of Asset suggestions (§3.4). Maximum results
configurable via `autocomplete.max_results` setting (default 10).

#### `POST /users/autocomplete`

User search endpoint for the direct-assignment autocomplete widget.
Capability: `almgr_edit_asset` (operator or administrator).

**Request body (JSON):**

| Field | Type | Required | Description |
|---|---|---|---|
| `term` | string | Yes | Search string (matched against username and display name) |

**Response:** JSON array of User suggestions (§3.2).

---

## 5. Examples

### 5.1 Application Password (recommended for scripts)

```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
     https://your-site.example.com/wp-json/almgr/v1/assets?per_page=5
```

### 5.2 Session cookie + REST nonce (browser context)

Use this pattern from your own theme/plugin JavaScript, where a REST nonce is
already available (for example via a `wp_localize_script()` value produced with
`wp_create_nonce( 'wp_rest' )` on the server side):

```js
fetch( 'https://your-site.example.com/wp-json/almgr/v1/me/loan-requests', {
  credentials: 'same-origin',
  headers: { 'X-WP-Nonce': myLocalizedNonce },
} )
  .then( ( response ) => response.json() )
  .then( ( body ) => console.log( body.data ) );
```

---

## 6. Versioning & changelog

The `almgr/v1` namespace follows the plugin's own Semantic Versioning policy
(see `DEV/AGENTS/CODING_STANDARDS.md`): a breaking change to any endpoint's
request or response shape requires a MAJOR version bump of the plugin. Additive,
backwards-compatible changes (a new endpoint, a new optional field, a new
optional query parameter) may ship in a MINOR release.

For the history of changes that affected the REST API, see `CHANGELOG.md`.

---

*Last update: 2026-08-28 (rev 1)*
