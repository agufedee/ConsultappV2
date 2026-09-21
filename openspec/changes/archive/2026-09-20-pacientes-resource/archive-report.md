# Archive Report: Pacientes Resource

## Change Summary

| Field | Value |
|-------|-------|
| Change | pacientes-resource |
| Archived | 2026-09-20 |
| Mode | hybrid (Engram + OpenSpec) |
| Verdict | PASS |
| Requirements | 12/12 |
| Scenarios | 35/35 |
| Tests at close | 43 passed / 0 failed / 0 skipped (105 assertions) |
| Tasks | 13/13 complete |
| Branch | `feature/pacientes-resource` |
| HEAD | `b269364` (chore(sdd): add pacientes-resource verify report) |

## Final State (facts at close)

Per the orchestrator's final-state account (highest-authority source for post-snapshot events; corroborated against the repository):

1. **Chained delivery published on GitHub** (agufedee/ConsultappV2):
   - PR #1 `feature/pacientes-resource-01-domain` -> `develop` (soft deletes + computed age). Size:exception approved by the maintainer (655 lines: ~568 SDD artifacts + ~87 code). Corroboration at archive time: GitHub reports 648 additions + 7 deletions = 655 changed lines across 12 files; the reviews API records no formal review objects yet, so the approval is recorded as per the orchestrator's account.
   - PR #2 `feature/pacientes-resource-02-list` -> `feature/pacientes-resource-01-domain` (scaffold + searchable list).
   - PR #3 `feature/pacientes-resource-03-form` -> `feature/pacientes-resource-02-list` (form schema, infolist, CRUD tests).
   - PR #4 `feature/pacientes-resource` -> `develop` (DRAFT tracker, no-merge).
2. Working tree clean at `b269364`.
3. All 43/43 tests pass; Pint clean; `migrate:fresh` verified; rollback non-destructive.

PR chain creation decision: Engram obs #42 `pacientes-resource PRs encadenados creados (feature-branch-chain adaptada)` — non-destructive adaptation: the work already existed whole on `feature/pacientes-resource`, so tag branches over the linear history were used instead of resetting the tracker branch.

## What Was Done

First Filament v5 CRUD resource on the `consultapp` panel: full CRUD + detail view for `Paciente`, with optional-but-unique DNI, soft deletes, computed age, global search by nombre/apellido/dni, and Spanish labels. Sets the conventions for later resources. Generated via `make:filament-resource` with `--embed-schemas --embed-table` (`--simple`/`--soft-deletes` explicitly not used).

### Key Deliverables

1. `app/Filament/Resources/Pacientes/PacienteResource.php` (+ 4 pages List/Create/Edit/View) — embedded form/table/infolist, `getGloballySearchableAttributes(['nombre','apellido','dni'])`, `modelLabel`/`pluralModelLabel` Paciente/Pacientes (note: generator v5.8.2 emitted the nested `Resources/Pacientes/*` namespace — see W-1).
2. `database/migrations/2026_09_20_000001_add_soft_deletes_to_pacientes.php` — additive `softDeletes()` + explicit `index('deleted_at')`; rollback drops only those.
3. `app/Models/Paciente.php` — `SoftDeletes` trait + `getEdadAttribute(): ?int` (Carbon `->age`, anniversary-based, null-safe).
4. DNI validation wiring: `nullable()->unique()` using the raw `Rule::unique` DB count — trashed rows still block re-registration (deleted-DNI stays blocked); empty input persists NULL via `ConvertEmptyStringsToNull` (no `withoutTrashed`/`scopedUnique` anywhere).
5. `tests/Feature/Filament/PacienteResourceTest.php` + `tests/Feature/Models/PacienteTest.php` additions — 43/43 suite green.

## Verification Evidence

Source: `verify-report.md` (Engram obs #41 `sdd/pacientes-resource/verify-report`), `schema: gentle-ai.verify-result/v1`, verdict `pass`, blockers 0, critical_findings 0, evidence hash `sha256:bc4853c4ef6f3a460bd06650652e2b8663d970764f4b7bd149667d3cb9ac9bc9`.

- **Build**: `vendor/bin/pint --dirty --format agent` — passed, exit 0.
- **Tests**: `php artisan test --compact` — 43 passed / 0 failed / 0 skipped, 105 assertions, exit 0.
- **Migration harness** (verifier-run): `migrate:fresh` builds 8 migrations clean; schema introspection confirms `deleted_at` nullable + `pacientes_deleted_at_index` (+ pre-existing `pacientes_dni_unique`); `migrate:rollback --step=1` drops only `deleted_at` + index (non-destructive); re-apply clean.
- **Routes**: `route:list --path=pacientes` → 4 routes (`consultapp/pacientes`, `/create`, `/{record}`, `/{record}/edit`).
- **Compliance**: 35/35 scenarios compliant across 12/12 requirements; no FAILING, no UNTESTED.
- **TDD evidence**: RED→GREEN cycles present per phase (apply-progress obs #39), including genuine RED states and triangulation where behavior needed disambiguation.

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| database-migrations | Updated | 1 ADDED requirement (`Add Soft Deletes to Pacientes Migration`, 3 scenarios); 2 MODIFIED requirements (`Consultas Table Migration`, `Objetivos Table Migration` — `FK cascade on paciente delete` scenarios replaced by `Soft delete preserves consultas/objetivos`); other requirements untouched |
| domain-models | Updated | 2 MODIFIED requirements: `Paciente Model` gains `SoftDeletes` trait + `edad` accessor + `delete() soft-deletes` scenario (3 scenarios added); `Consulta Model` cascade scenario replaced by `Soft delete preserves consultas`; other requirements untouched |
| paciente-resource | Created | Full new spec (7 requirements, 15 scenarios) — mechanical copy of the delta (no prior main spec) |

Main specs now reflecting the new baseline:
- `openspec/specs/database-migrations/spec.md`
- `openspec/specs/domain-models/spec.md`
- `openspec/specs/paciente-resource/spec.md`

### Merge Notes (decisions taken during sync, for audit)

- Delta-only annotations (`(Previously: ...)` notes, `## ADDED/MODIFIED Requirements` wrappers) were stripped from the merged baseline; they remain verbatim in the archived delta files. Main specs describe the current baseline, matching the pre-existing convention (no main spec carries such annotations).
- Two stale Edge-Cases rows that contradicted the new baseline were aligned: database-migrations `Delete paciente with consultas and objetivos` and domain-models `Deleting Paciente cascades to Consultas and Objetivos` now state soft-delete semantics. This alignment reflects the archived change in the baseline; it is not a destructive removal.
- New `Add Soft Deletes` requirement was placed after `Objetivos Table Migration` (grouping all table-migration requirements; `Migration Ordering` remains last).
- Verify-report suggestion S-1 (amend spec text about `sexo`/`motivo`/`estado` string casts, which no model actually casts) was **not** applied during archive: the delta's MODIFIED requirement text re-states those casts, and amending the baseline beyond the delta would alter requirements this change did not own. Carried forward as a spec-accuracy item for a future change.

## Archive Contents

- proposal.md ✅
- specs/database-migrations/spec.md ✅
- specs/domain-models/spec.md ✅
- specs/paciente-resource/spec.md ✅
- design.md ✅
- tasks.md ✅ (13/13 tasks complete — verified: no unchecked implementation tasks)
- verify-report.md ✅
- archive-report.md ✅ (this file, additive — excluded from the mechanical diff readback)

Verbatim `diff -r` readback output (Mechanical Copy Contract): both the spec-copy step and the folder-move step produced **empty** diffs (`DIFF_EMPTY_OK`) — byte-identical archival, no truncation or alteration.

## Delivery References

| PR | Title | Base -> Head | State | URL |
|----|-------|--------------|-------|-----|
| #1 | feat(pacientes): soft deletes and computed age | develop -> feature/pacientes-resource-01-domain | OPEN (size:exception approved, 655 lines) | https://github.com/agufedee/ConsultappV2/pull/1 |
| #2 | feat(pacientes): scaffold resource with searchable list | feature/pacientes-resource-01-domain -> feature/pacientes-resource-02-list | OPEN | https://github.com/agufedee/ConsultappV2/pull/2 |
| #3 | feat(pacientes): add form schema, infolist, and CRUD tests | feature/pacientes-resource-02-list -> feature/pacientes-resource-03-form | OPEN | https://github.com/agufedee/ConsultappV2/pull/3 |
| #4 | feat(pacientes): Paciente CRUD resource (tracker) | develop -> feature/pacientes-resource | OPEN (DRAFT tracker, no-merge) | https://github.com/agufedee/ConsultappV2/pull/4 |

PR #1 size-approval note: the orchestrator's final-state account reports maintainer-approved size:exception; GitHub corroborates the line count (648 additions + 7 deletions = 655, 12 files). GitHub's reviews API showed no recorded formal review objects at archive time (2026-09-20) — the exception-approval likely happened outside a formal review entry; recorded here as orchestrator-reported with line count corroborated.

## Issues and Suggestions Carried Forward

| Severity | Issue | Status |
|----------|-------|--------|
| WARNING (W-1) | Resource lives at `app/Filament/Resources/Pacientes/PacienteResource.php` (nested, v5.8.2 generator default) instead of the design's flat path | Non-blocking documented deviation; behavior identical; spec requirement satisfied (routes + tests pass) |
| SUGGESTION (S-1) | Spec text lists `sexo`/`motivo`/`estado` string casts; no model casts them (project convention — PDO returns strings natively) | Pre-existing Sprint-1 inaccuracy, functionally a no-op; consider amending spec text in a future spec-maintenance change |
| SUGGESTION (S-2) | Migration schema scenarios proven via verifier-run harness, not automated assertions | A schema-assertion test could make the migration spec self-evident in CI |
| SUGGESTION (S-3) | Apply-progress said "9 migrations build clean"; actual fresh build runs 8 | Cosmetic count nit in the intermediate snapshot only, no behavioral impact |

## Engram Traceability

Source of truth is the OpenSpec filesystem; Engram holds mirrors of the phase artifacts. Observation IDs read/consulted during archive:

| Artifact | Engram obs ID |
|----------|---------------|
| explore | #33 |
| proposal | #34 |
| spec | #35 |
| design | #36 |
| tasks | #37 |
| session summary (pre-change) | #38 |
| apply-progress | #39 |
| verify-report | #41 |
| PR chain creation decision | #42 |

This archive report is persisted to Engram as topic `sdd/pacientes-resource/archive-report` (project `consultappv2`).

## Git State at Close

- Branch: `feature/pacientes-resource`
- HEAD: `b269364 chore(sdd): add pacientes-resource verify report`
- Working tree: clean
- No commit created by the archive phase (per execution instructions); spec sync + folder move are uncommitted filesystem changes on top of the clean tree.

## SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived. Specs are synced to the new baseline. Ready for the next change.