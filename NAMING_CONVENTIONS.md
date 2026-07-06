# NAMING_CONVENTIONS.md

Cross-stack naming rules for the Event Management SaaS platform. Database
naming has additional detail in `DATABASE_NAMING_CONVENTION.md`.

## PHP / Laravel

| Kind | Convention | Example |
|---|---|---|
| Classes | PascalCase | `PublishEventAction`, `EventResource` |
| Methods / variables | camelCase | `execute()`, `$organizationId` |
| Constants | UPPER_SNAKE_CASE | `DEFAULT_HOLD_MINUTES` |
| DB tables | snake_case, plural | `ticket_types` |
| DB columns | snake_case | `organization_id`, `published_at` |
| API route segments | kebab-case | `/api/v1/ticket-types` |
| Config keys | snake_case | `queue.names.emails` |
| Log channels | dot-separated | `security.log`, `payments.log` |

## TypeScript / React / Next.js

| Kind | Convention | Example |
|---|---|---|
| Components | PascalCase | `ThemeToggle`, `EventCard` |
| Hooks | camelCase, `use` prefix | `useAuthStore`, `useEvents` |
| Types / interfaces | PascalCase | `ApiResponse`, `EventSummary` |
| Variables / functions | camelCase | `apiClient`, `buildQueryKey` |
| Route folders | kebab-case | `(dashboard)/ticket-types/` |
| Component files | PascalCase.tsx | `ThemeToggle.tsx` |
| Utility files | kebab-case or camelCase | `api-client.ts`, `utils.ts` |
| Zustand stores | `use<Name>Store` | `useAuthStore` |

## API

| Kind | Convention | Example |
|---|---|---|
| Base path | `/api/v1/` | `/api/v1/events` |
| Resource collections | plural kebab-case | `/api/v1/ticket-types` |
| Lifecycle actions | verb sub-resource | `POST /api/v1/events/{id}/publish` |
| JSON envelope keys | snake_case | `current_page`, `per_page` |

## Services vs Actions

- **Service** (`BaseService::execute()`) — multi-step business workflows.
- **Action** (`BaseAction::handle()`) — single, focused command (publish,
  refund, check-in scan).

## File Placement

See `FOLDER_STRUCTURE.md` for which folder each artifact type belongs in.
