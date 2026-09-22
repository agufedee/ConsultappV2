# Archive Report: Consultas Module

## Change Summary

| Field | Value |
|-------|-------|
| Change | consultas-modulo |
| Archived | 2026-09-22 |
| Mode | hybrid (Engram + OpenSpec) |
| Verdict | PASS |
| Requirements | 10/10 |
| Scenarios | 22/22 |
| Tests at close | 73 passed / 0 failed / 0 skipped (226 assertions) |
| Tasks | 12/12 complete |
| Branch | `develop` (feature fully merged) |
| HEAD | `8694184` (Merge pull request #8 from agufedee/feature/consultas-modulo) |

## Final State (facts at close)

Per the orchestrator's final-state account (highest-authority source for post-snapshot events; corroborated by `verify-report.md` and the repository):

1. **Feature merged to `develop` at `8694184`** — chained delivery #5–#8 all MERGED (2026-09-22T01:17Z UTC, see Delivery References).
2. **Verify PASS at close**: 22/22 scenarios, 10/10 requirements, 73/73 tests (226 assertions), Pint clean, migration harness green.
3. **All 12 tasks complete** in `tasks.md` — verified at archive time, no unchecked implementation tasks.
4. **No CRITICAL, no WARNING** in the terminal verify report (W-1/W-2 closed by the test-only remediation commit `test(consultas): close verify W-1/W-2 warnings`; W-3 recorded informational).
5. Working tree was clean at archive start (`8694184`); the archive phase leaves spec syncs + folder move as uncommitted changes on top (orchestrator commits).

Note on test counts: apply-time snapshot recorded 71 passed / 219 assertions; the verification remediation added 2 tests (W-1 motivo-options test, W-2 driver-aware width test), so the final close figure is **73/73 (226 assertions)** per `verify-report.md` and the launch prompt.

## What Was Done

Sprint 2b of consultapp_sdd.md (§2.3): full consultas CRUD on the `consultapp` panel. Consultas live only inside the paciente detail (hidden navigation), created/edited via the reactive IMC form (weight/height typed live → IMC computed client-side without reload) and a `ConsultasRelationManager` tab on the paciente View page.

### Key Deliverables

1. `database/migrations/2026_09_21_000001_widen_imc_on_consultas.php` — additive `->change()` widening `consultas.imc` DECIMAL(4,2) → DECIMAL(5,2) so the spec's extreme case (300 kg @ 100 cm → 300.00) fits; `down()` restores (4,2); no other column/constraint/table touched; ConsultaFactory formula untouched.
2. `app/Filament/Resources/Consultas/ConsultaResource.php` (+ 4 thin Pages List/Create/Edit/View, embedded schemas per house pattern) — reactive IMC form: `peso` (20–300, step 0.1, kg) + `altura` (100–250, step 0.5, cm), both `live(onBlur: true)->afterStateUpdated($calculateImc)` with `(float)` casts and `altura <= 0 → null` guard; `imc` readOnly suffix kg/m²; `motivo` Select with the 3 SDD values in Spanish (`motivoOptions()` shared with table display); `fecha` required; pliegues Repeater (`defaultItems(0)`, pliegue+mm required) with `mutateDehydratedStateUsing` normalizing emptied `[]` → NULL; `paciente_id` Select hidden on RelationManagers; table fecha desc defaultSort; labels Consulta/Consultas; `$shouldRegisterNavigation = false`; `recordTitleAttribute='motivo'`; global search `[]`.
3. `app/Filament/Resources/Pacientes/RelationManagers/ConsultasRelationManager.php` — `$relatedResource = ConsultaResource::class` (form/infolist/table defined ONCE in the resource, no duplication); own `table()` adding header `CreateAction`; `getDefaultActionUrl(): ?string { return null; }` forces modal create/edit/view; `isReadOnly(): bool { return false; }` (apply-discovered v5 panel default hides create/edit on View pages — scoped override, panel untouched); `getBadge()` shows consulta count or null.
4. `app/Filament/Resources/Pacientes/PacienteResource.php` — `getRelations()` → `[ConsultasRelationManager::class]` (+3 lines, the ONLY existing-code touch).
5. Tests: `tests/Feature/Filament/ConsultaResourceTest.php` (14), `tests/Feature/Filament/ConsultasRelationManagerTest.php` (11), `tests/Feature/Database/WidenImcMigrationTest.php` (5, driver-aware width helper `$assertImcWidth`) — 30 change tests, full suite 73 green.

## Verification Evidence

Source: `verify-report.md` (Engram obs #57 `consultas-modulo verify-report v2 → PASS verdict (W-1/W-2 closed, 22/22)`), `schema: gentle-ai.verify-result/v1`, verdict `pass`, blockers 0, critical_findings 0, requirements 10/10, scenarios 22/22, evidence hash `sha256:e8a41c9ce642122a6dec863915b22dae26e7bf601a419acaf00a87840f88436b`.

- **Build**: `vendor/bin/pint --dirty --format agent` — passed, exit 0.
- **Tests**: `php artisan test --compact` — 73 passed / 0 failed / 0 skipped, 226 assertions, exit 0.
- **Focused change suites** (verifier-run): ConsultaResourceTest 14 passed / 58 assertions; ConsultasRelationManagerTest 11 passed / 39 assertions; WidenImcMigrationTest 5 passed / 24 assertions — 30/30.
- **Migration harness**: WidenImcMigrationTest drives `migrate:fresh` → widener present; extreme 300.00 round-trips; all 15 consultas columns intact; single-step rollback removes only the widener row; driver-aware `$assertImcWidth` asserts literal decimal(5,2)/(4,2) on mysql/mariadb/pgsql and the SQLite `numeric` reflection limit plus behavioral proxies.
- **Compliance**: 22/22 scenarios compliant across 10/10 requirements; no FAILING, no UNTESTED, no PARTIAL.
- **TDD evidence**: per-phase RED→GREEN annotations in `tasks.md` + apply-progress (obs #55) + remediation run (prior FAIL was the RED state for W-1/W-2); 6/6 TDD checks passed.

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| consulta-resource | Created | Full new spec (8 requirements, 14 scenarios) — mechanical copy of the delta (no prior main spec) |
| database-migrations | Updated | 1 ADDED requirement (`Widen IMC Column Migration`, 4 scenarios); all other requirements untouched |
| paciente-resource | Updated | 1 ADDED requirement (`Consultas Relation Manager`, 4 scenarios); all other requirements untouched |

Main specs now reflecting the new baseline:
- `openspec/specs/consulta-resource/spec.md`
- `openspec/specs/database-migrations/spec.md`
- `openspec/specs/paciente-resource/spec.md`

### Merge Notes (decisions taken during sync, for audit)

- Delta-only annotations (`# Delta for ...` titles, `## ADDED Requirements` wrappers) were stripped from the merged baseline; they remain verbatim in the archived delta files. Main specs describe the current baseline, matching the pre-existing convention (no main spec carries such annotations).
- `Widen IMC Column Migration` was placed after `Add Soft Deletes to Pacientes Migration` and before `Migration Ordering` (keeps `Migration Ordering` last; the two additive alter-migrations group at the end of the Requirements section).
- `Consultas Relation Manager` was appended after `Spanish Labels` (last requirement of the paciente-resource spec).
- **No requirement was MODIFIED, REMOVED, or RENAMED** by any delta — all three deltas carry only ADDED blocks, so no existing requirement text was replaced or deleted and no stale baseline rows needed alignment (the baseline `Consultas Table Migration` still describes the original create migration — `imc` DECIMAL(4,2) — consistent with the new widener requirement; same coexistence pattern as `Pacientes Table Migration` + `Add Soft Deletes`).
- config.yaml archive rule ("Warn before merging destructive deltas") not triggered — zero destructive delta.

## Unchanged / Dropped Requirements

None. All requirements delivered by this change are ADDED requirements; nothing was modified, dropped, or renamed (expected per the change's scope).

## Archive Contents

- proposal.md ✅
- specs/consulta-resource/spec.md ✅
- specs/database-migrations/spec.md ✅
- specs/paciente-resource/spec.md ✅
- design.md ✅
- tasks.md ✅ (12/12 tasks complete — verified: no unchecked implementation tasks)
- verify-report.md ✅
- archive-report.md ✅ (this file, additive — excluded from the mechanical diff readback)

Verbatim `diff -r` readback output (Mechanical Copy Contract): the spec-copy step (consulta-resource) and the folder-move step both produced **empty** diffs (`DIFF_EMPTY_OK`) — byte-identical archival, no truncation or alteration.

## Delivery References

| PR | Title | Base -> Head | State | URL |
|----|-------|--------------|-------|-----|
| #5 | feat(consultas): widen imc to DECIMAL(5,2) | develop -> feature/consultas-modulo-01-migration | MERGED (2026-09-22T01:17:30Z) | https://github.com/agufedee/ConsultappV2/pull/5 |
| #6 | feat(consultas): add ConsultaResource with reactive IMC form | feature/consultas-modulo-01-migration -> feature/consultas-modulo-02-resource | MERGED (2026-09-22T01:17:33Z) | https://github.com/agufedee/ConsultappV2/pull/6 |
| #7 | feat(consultas): mount ConsultasRelationManager on pacients | feature/consultas-modulo-02-resource -> feature/consultas-modulo-03-rm | MERGED (2026-09-22T01:17:37Z) | https://github.com/agufedee/ConsultappV2/pull/7 |
| #8 | feat(consultas): Consulta resource and relation manager (tracker) | develop -> feature/consultas-modulo | MERGED (2026-09-22T01:17:42Z) | https://github.com/agufedee/ConsultappV2/pull/8 |

PR states corroborated at archive time via `gh pr view` (all `state: MERGED`). Bedrock commit for the tracker merge: `8694184` on `develop`. The test-only remediation commit (`test(consultas): close verify W-1/W-2 warnings`, `afcba82`) landed directly on the tracker branch, no PR.

## Issues and Suggestions Carried Forward

| Severity | Issue | Status |
|----------|-------|--------|
| SUGGESTION (S-1) | No test asserts the RM rendered table columns by name (fecha/motivo/peso/altura/imc); column presence is framework-guaranteed via configureTable delegation and rows demonstrably render | A column-header assertion would prove it end-to-end at runtime |
| SUGGESTION (S-2) | Driver-aware width assertions (W-2) only exercise the FULL literal-width branch on mysql/pgsql | A CI job on those drivers would make the decimal(5,2)/(4,2) assertions active in CI (SQLite asserts the documented `numeric` reflection limit + behavioral proxies) |
| SUGGESTION (S-3) | Two `assertSee('Consultas')` presence checks (ConsultaResourceTest ~37-38, ConsultasRelationManagerTest ~29/~189) are broad presence matches | Could be tightened to `assertSeeInOrder` scoped to the relation-manager heading |
| INFO (W-3) | RM `table()` code is `$table->headerActions([...])` rather than the design's literal `ConsultaResource::table($table)->headerActions(...)` | Informational text-deviation, behaviorally equivalent (vendor-verified delegation chain); no action needed |

## Engram Traceability

Source of truth is the OpenSpec filesystem; Engram holds mirrors of the phase artifacts. Observation IDs read/consulted during archive:

| Artifact | Engram obs ID |
|----------|---------------|
| explore | #48 |
| proposal | #50 |
| spec | #51 |
| design | #52 |
| tasks | #54 |
| apply-progress | #55 |
| verify-report | #57 |
| PR chain creation decision | #59 |

This archive report is persisted to Engram as topic `sdd/consultas-modulo/archive-report` (project `consultappv2`).

## Git State at Close

- Branch: `develop`
- HEAD: `8694184` — Merge pull request #8 from agufedee/feature/consultas-modulo
- Working tree: clean at archive start; the archive phase made the expected uncommitted changes only: baseline spec syncs + the change-folder move.
- No commit created by the archive phase (per execution instructions); the orchestrator will commit and push the archive (`chore(sdd): archive consultas-modulo change`).

## SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived. Specs are synced to the new baseline (the `consultas-modulo` feature is now part of the main OpenSpec specs, including the new `consulta-resource` domain). Ready for the next change.