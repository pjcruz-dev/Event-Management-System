# CONTRIBUTING.md

## Workflow With Cursor

1. Paste `00_MASTER_PROMPT.md` in full at the top of the message.
2. Paste the target phase file directly below it, same message.
3. Let Cursor generate that phase only — do not let it reach ahead into
   later phases even if it seems convenient.
4. Review the diff. Run the tests/Definition-of-Done checklist at the
   bottom of the phase file.
5. Commit. One commit (or a small PR) per phase, so a regression is easy
   to bisect to a single phase.
6. Move to the next phase only after the current one is fully green.

## Branching

- `main` — always deployable.
- `phase/N-short-name` — one branch per phase (e.g. `phase/5-registration`).
  Merge to `main` only after the phase's Definition of Done is met.
- Hotfixes branch from `main` directly, never from an in-progress phase
  branch.

## Commit Messages

Conventional-commit style, scoped to phase where useful:

```
feat(phase-5): add ticket type CRUD and dynamic form builder
fix(phase-6): verify PayMongo webhook signature before processing
docs(phase-3): add ER diagram to DATABASE_GUIDELINES
test(phase-5): add oversell race-condition test
```

## PR Checklist (per phase)

- [ ] Definition of Done items from the phase file are all checked or
      explicitly logged in `ROADMAP.md` with a reason.
- [ ] `php artisan test` (backend) green.
- [ ] `npm run type-check && npm run lint` (frontend) green.
- [ ] Every file created/modified is listed in the PR description.
- [ ] No `// TODO`, no placeholder/stub methods, no hardcoded config.
- [ ] Tenant isolation re-checked if the phase touched a tenant-owned
      model (query as one seeded org, confirm you never see another
      org's rows).

## Running Locally

```
docker-compose up -d        # MySQL, Redis, app containers
cp .env.example .env        # backend
php artisan key:generate
php artisan migrate:fresh --seed
npm install && npm run dev  # frontend
```

Full env var reference: `04_Deployment/ENVIRONMENT.md`.

## Who Reviews What

Every phase touches both backend and frontend; review both halves
together rather than splitting the PR, since Phase files are written as
one full-stack unit of work.
