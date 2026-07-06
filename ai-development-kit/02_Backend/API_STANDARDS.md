# API_STANDARDS.md

## Versioning

All routes under `/api/v1/...`. Breaking changes ship as `/api/v2/...`
alongside v1, never as an in-place breaking change to v1.

## Response Envelope

Every response — success or failure — uses this shape, produced by
`ApiResponse` (`app/Support/Responses/ApiResponse.php`), never a raw
model or raw array:

```json
{
  "success": true,
  "data": { },
  "message": "Optional human-readable message",
  "errors": null
}
```

Failure:

```json
{
  "success": false,
  "data": null,
  "message": "The given data was invalid.",
  "errors": { "email": ["The email field is required."] }
}
```

## Pagination

```json
{
  "success": true,
  "data": {
    "items": [ ],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 143,
      "last_page": 8
    }
  },
  "message": null,
  "errors": null
}
```

Produced via `ApiResponse::paginated()` — never hand-roll a different
pagination shape in an individual controller.

## HTTP Status Codes

| Situation | Code |
|---|---|
| Success (read) | 200 |
| Success (create) | 201 |
| Success (no body) | 204 |
| Validation failure | 422 |
| Unauthenticated | 401 |
| Unauthorized (authenticated, wrong tenant/role) | 403 |
| Not found (or hidden cross-tenant) | 404 |
| Conflict (e.g. duplicate check-in) | 409 |
| Rate limited | 429 |
| Unhandled server error | 500 |

Cross-tenant access attempts return 403 or 404 (never a 200 with someone
else's data, never a distinguishing error that leaks whether the
resource exists in another tenant).

## Errors

Global exception handler (Phase 0) converts validation errors,
`ModelNotFoundException`, authorization failures, and unhandled
exceptions into the standard envelope with the correct status code —
individual controllers never hand-catch these.

## Lifecycle Actions Are Dedicated Endpoints

State-changing actions get their own route, not an overloaded PATCH:
`POST /events/{event}/publish`, not `PATCH /events/{event}` with a status
field buried in the body. This applies platform-wide (publish/archive/
duplicate, refund, check-in scan, etc.).

## Public vs Authenticated Endpoints

Public endpoints (event discovery, public registration, public agenda)
are unauthenticated-friendly but still tenant-scoped correctly to the
resource's owning organization — "public" means no login required, not
"tenant scoping is skipped."

## Rate Limiting

Per-endpoint-class limits (see `SECURITY.md`): stricter on auth and
public registration, separate limits for webhook endpoints, generous
limits on authenticated dashboard reads.
