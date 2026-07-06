# Cursor Phase Prompts — Enterprise Event SaaS

27 phase prompts (0–27) for building the platform incrementally in Cursor,
plus one master prompt used at the top of every phase.

**Core launch path:** Phases 0–11. **Extended phases:** 12–22 (prompts ready;
optional — see `ai-development-kit/01_Project_Overview/ROADMAP.md` for
sequencing guidance). **Event Page Builder hardening:** Phases 23–27 (run
after Phase 4; best started after Phase 23's dependencies 8 and 10 are ✅).

## How to use these

1. Open `00_MASTER_PROMPT.md`. Paste its full contents into Cursor first.
2. Open the phase file you're working on (e.g.
   `PHASE_0_ARCHITECTURE.md`). Paste its contents directly below the
   master prompt in the same message.
3. Let Cursor generate that phase only. Review the diff, run the tests /
   definition-of-done checks listed at the bottom of the phase file,
   commit.
4. Move to the next phase. Do not skip ahead within 0–11 — each phase
   explicitly depends on models/services/middleware created in earlier
   phases. For 12–22, respect each file's own "Depends on" line.

Reference docs live in `/ai-development-kit/`. Cursor Rules in
`.cursor/rules/*.mdc` apply automatically during coding — no need to paste
them into the chat.

## Files — Core (0–11)

| File | Phase | Depends on |
|---|---|---|
| `00_MASTER_PROMPT.md` | — | — |
| `PHASE_0_ARCHITECTURE.md` | 0 | — |
| `PHASE_1_AUTH_ORGANIZATIONS.md` | 1 | 0 |
| `PHASE_2_MULTI_TENANCY.md` | 2 | 0, 1 |
| `PHASE_3_DATABASE_FOUNDATION.md` | 3 | 0, 1, 2 |
| `PHASE_4_EVENT_MANAGEMENT.md` | 4 | 0–3 |
| `PHASE_5_REGISTRATION_TICKETING.md` | 5 | 0–4 |
| `PHASE_6_PAYMENTS.md` | 6 | 0–5 |
| `PHASE_7_QR_CHECKIN.md` | 7 | 0–6 |
| `PHASE_8_CONFERENCE_MODULE.md` | 8 | 0–7 |
| `PHASE_9_ORGANIZER_DASHBOARD.md` | 9 | 0–8 |
| `PHASE_10_PUBLIC_WEBSITE.md` | 10 | 0, 4 (generally 0–9) |
| `PHASE_11_PRODUCTION_READINESS.md` | 11 | all prior phases |

## Files — Extended (12–22)

Not required for a core product launch. Sequence by business need — see
`ROADMAP.md`.

| File | Phase | Depends on |
|---|---|---|
| `PHASE_12_AI_ENTERPRISE.md` | 12 | 0–11 |
| `PHASE_13_BILLING_SUBSCRIPTIONS.md` | 13 | 6, 11 |
| `PHASE_14_SUPER_ADMIN.md` | 14 | 2, 3 (13 optional) |
| `PHASE_15_CUSTOM_DOMAINS.md` | 15 | 4, 11 |
| `PHASE_16_I18N_MULTICURRENCY.md` | 16 | 4, 5, 9 |
| `PHASE_17_MARKETING_SITE.md` | 17 | 1 (13 optional) |
| `PHASE_18_LEGAL_COMPLIANCE.md` | 18 | 1, 6, 13 |
| `PHASE_19_PERFORMANCE_TESTING.md` | 19 | 11 |
| `PHASE_20_OBSERVABILITY.md` | 20 | 11 |
| `PHASE_21_MOBILE_PWA.md` | 21 | 7, 10 |
| `PHASE_22_RSVP_GUEST_MANAGEMENT.md` | 22 | 5, 7, 10 (11 recommended) |

## Files — Event Page Builder (23–27)

Hardens the Phase 4 theme/landing builder: preview fidelity, assets,
draft sharing, richer blocks, templates. Run **in order** 23 → 27.

| File | Phase | Depends on |
|---|---|---|
| `PHASE_23_EVENT_PAGE_BUILDER_FIDELITY.md` | 23 | 4, 8, 10 |
| `PHASE_24_EVENT_PAGE_BUILDER_THEME_UX.md` | 24 | 23 |
| `PHASE_25_EVENT_PAGE_BUILDER_PREVIEW_EXPERIENCE.md` | 25 | 23, 24 |
| `PHASE_26_EVENT_PAGE_BUILDER_BLOCKS.md` | 26 | 23–25, 8 (exhibitors) |
| `PHASE_27_EVENT_PAGE_BUILDER_TEMPLATES_A11Y.md` | 27 | 23–26 |

## Notes

- **Cursor Rules** — `.cursor/rules/` contains five rule files
  (`000-project-context`, backend, frontend, multi-tenancy, testing). They
  mirror the master prompt and dev-kit standards and apply by file glob.
- **Reference docs** — `/ai-development-kit/` is the authoritative doc set
  (architecture, API standards, tenancy, security, frontend conventions,
  deployment). Phase prompts may extend it as features land; keep docs in
  sync when code changes behavior.
- **Status tracking** — flip phase status in
  `ai-development-kit/01_Project_Overview/ROADMAP.md` as each phase
  completes.
