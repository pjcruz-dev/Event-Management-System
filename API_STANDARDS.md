# API_STANDARDS.md

> Stub produced in Phase 0. Expanded reference:
> `ai-development-kit/02_Backend/API_STANDARDS.md`

## Versioning

All API routes live under `/api/v1/`. Breaking changes ship as `/api/v2/`
alongside v1 — never as an in-place breaking change.

Implementation: `routes/api.php` with a `v1` prefix group.

## Response Envelope

Every response uses this shape via `App\Support\Responses\ApiResponse`:

```json
{
  "success": true,
  "data": {},
  "message": null,
  "errors": null
}
```

Failure:

```json
{
  "success": false,
  "data": null,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

Frontend mirror: `frontend/src/types/api.ts` (`ApiResponse<T>`, `ApiError`).

## Pagination

Produced via `ApiResponse::paginated()`:

```json
{
  "success": true,
  "data": {
    "items": [],
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

## HTTP Status Codes

| Situation | Code |
|---|---|
| Success (read) | 200 |
| Success (create) | 201 |
| Validation failure | 422 |
| Unauthenticated | 401 |
| Unauthorized | 403 |
| Not found | 404 |
| Conflict | 409 |
| Rate limited | 429 |
| Server error | 500 |

Cross-tenant access attempts return **403** or **404** — never 200 with
another tenant's data.

## Exception Handling

`App\Exceptions\ApiExceptionRenderer` converts validation, authorization,
model-not-found, and unhandled exceptions into the standard envelope for
all `api/*` requests.

## Lifecycle Actions

State changes use dedicated routes, not overloaded PATCH:

```
POST /api/v1/events/{event}/publish
```

Not: `PATCH /api/v1/events/{event}` with a buried `status` field.

## Health Check (Phase 0)

```
GET /api/v1/health
```

Returns `{ "success": true, "data": { "status": "ok", "service": "..." } }`.
