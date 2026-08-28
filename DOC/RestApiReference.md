# REST API Reference — Asset Lending Manager

The plugin exposes a read-only JSON REST API under the WordPress REST API namespace
`almgr/v1`. All endpoints are registered via `register_rest_route()` on `rest_api_init`.

---

## Base URL

```
https://your-site.example.com/wp-json/almgr/v1/
```

---

## Global Enable/Disable Switch

The entire API can be toggled from **ALM > Settings > REST API**.
When disabled, all endpoints return HTTP 503 with a JSON error body:

```json
{ "code": "almgr_rest_disabled", "message": "REST API is disabled." }
```

Setting key: `rest_api.enabled` (boolean, default `true`).

---

## Authentication

The plugin delegates authentication entirely to WordPress core. Three methods work:

| Method | How to use |
|---|---|
| **Session cookie + REST nonce** | Standard for logged-in browser sessions. Pass the nonce in the `X-WP-Nonce` header (value from `wp_create_nonce('wp_rest')`). |
| **Application Passwords** | HTTP Basic Auth with `username:application_password`. Recommended for scripts and external integrations. Create in **Users > Profile > Application Passwords**. |
| **No auth (public endpoints)** | Asset autocomplete is publicly accessible when configured. All other endpoints require authentication. |

---

## Endpoints

### GET /assets

Returns a paginated list of published assets.

**Capability required:** `almgr_view_assets`

**Query parameters:**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 10 | Results per page |
| `search` | string | — | Full-text search on asset title |
| `structure` | string | — | Filter by structure slug (`component`, `kit`) |
| `type` | string | — | Filter by type taxonomy slug |
| `state` | string | — | Filter by state taxonomy slug |

**Response:** JSON array of asset objects. Each object includes `id`, `title`,
`permalink`, taxonomy slugs (`structure`, `type`, `state`, `level`), and ACF fields.
Fields restricted to operators (`cost`, `data_acquisto`, `notes`) are omitted for
users without `almgr_edit_asset`. Response includes standard WP REST pagination
headers (`X-WP-Total`, `X-WP-TotalPages`).

---

### GET /assets/{id}

Returns the detail of a single published asset.

**Capability required:** `almgr_view_asset`

**Path parameter:** `id` — asset post ID (integer).

**Response:** Single asset object with full ACF fields. Operator-only fields
(`cost`, `data_acquisto`, `notes`, loan history entries) are included only for
users with `almgr_edit_asset`. ACF response keys use the unprefixed form
(e.g. `manufacturer`, not `almgr_manufacturer`) for API stability.

---

### GET /members

Returns a paginated list of users with ALM roles, including active loan count.

**Capability required:** `almgr_edit_asset` (operator or administrator)

**Query parameters:**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 10 | Results per page |

**Response:** JSON array of user objects. Each object includes `id`, `display_name`,
`email`, `roles` (ALM roles only), `active_loan_count`.

---

### GET /members/{member_id}/assets

Returns assets currently on loan to a specific member.

**Capability required:** `almgr_edit_asset`

**Path parameter:** `member_id` — WordPress user ID (integer).

**Response:** JSON array of asset objects currently assigned to the member
(`_almgr_current_owner = member_id`).

---

### GET /me/loan-requests

Returns the loan requests created by the authenticated user.

**Capability required:** `almgr_view_asset`

**Query parameters:**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | integer | 1 | Page number (minimum 1) |
| `per_page` | integer | 20 | Results per page (1–100) |
| `status` | string | — | Optional status filter |

**Response:** An object containing the paginated shared request result:

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

---

### GET /assets/{id}/loan-requests

Returns the loan requests visible for an asset. Members can access this
resource only when they are the current owner; operators and administrators
may access it according to `almgr_view_asset` and the shared asset-request
visibility policy.

**Capability required:** `almgr_view_asset`, plus asset-request visibility.

**Path parameter:** `id` — asset post ID (integer).

**Query parameters:** The same `page`, `per_page`, and `status` parameters as
`GET /me/loan-requests`.

**Response:** The same paginated object with `data`, `total`, `page`, and
`pages`. Request items use the same fields shown above.

---

### POST /assets/autocomplete

Asset search endpoint for the frontend autocomplete widget.

**Capability required:** none by default (public); configurable via
**ALM > Settings > Autocomplete > Public assets endpoint**.
When public access is disabled, requires a logged-in user with `almgr_view_assets`.

**Request body (JSON):**

| Field | Type | Required | Description |
|---|---|---|---|
| `term` | string | Yes | Search string (minimum length configurable; default 2 characters) |

**Response:** JSON array of matching asset suggestions. Each item includes
`id`, `title`, `description` (truncated), `structure`, `type`, `permalink`.
Maximum results configurable via `autocomplete.max_results` setting (default 10).

---

### POST /users/autocomplete

User search endpoint for the direct assignment autocomplete widget.

**Capability required:** `almgr_edit_asset` (operator or administrator)

**Request body (JSON):**

| Field | Type | Required | Description |
|---|---|---|---|
| `term` | string | Yes | Search string (matched against username and display name) |

**Response:** JSON array of user suggestions. Each item includes `id`,
`display_name`, `user_login`.

---

## Error Responses

All endpoints return standard WordPress REST error format on failure:

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
| `almgr_rest_disabled` | 503 | API disabled from settings |
| `rest_forbidden` | 403 | Insufficient capability |
| `rest_not_found` | 404 | Asset or member not found |
| `almgr_invalid_term` | 400 | Autocomplete term too short or invalid |

---

## Example: Application Password request

```bash
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
     https://your-site.example.com/wp-json/almgr/v1/assets?per_page=5
```

---

*Last update: 2026-08-28 (rev 3)*
