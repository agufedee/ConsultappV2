# Archive Report: Dietary Plan Request Queue (planes-alimentarios)

## Change Summary

| Field | Value |
|-------|-------|
| Change | planes-alimentarios |
| Archived | 2026-09-23 |
| Mode | hybrid (OpenSpec + Engram) |
| Verdict at close | PASS |
| Requirements | 12/12 |
| Scenarios | 36/36 |
| Tests at close | 131 passed / 131 (441 assertions), exit 0; focused 86 passed / 86 (312 assertions); Pint clean |
| Tasks | 10/10 complete (8 core + 2 finalization) |
| Branch | `develop` (feature files uncommitted in working tree) |
| HEAD | `e72b680` (Merge pull request #10 from agufedee/feature/chart-evolucion) |
| Review gate | Structurally absent (no review policy/ledger/receipt artifacts exist) — archived under ordinary repository policy |

## Final State (facts at close)

Per the orchestrator's final-state account (most recent account of the change; corroborated by the persisted `verify-report.md`, Engram #90 validation, Engram #89 apply-progress, and the repository):

1. **Verdict PASS at close** — 12/12 requirements, 36/36 scenarios compliant. `verify-report.md` carries `verdict: pass`, `blockers: 0`, `critical_findings: 0`, `evidence_revision: sha256:3774739d8175db2e624969a069759b5d8e0a832cf41ae6f2b5dcff6dc64ed365`. The `gentle-ai sdd-verify-validate` replay (Engram #90) confirmed `{"valid":true,"verdict":"pass"}`.
2. **Full suite GREEN at close**: 131 passed / 131 (441 assertions), exit 0, output hash `sha256:0f8355c9e143a18329359d0dcdd5d534a0234353067aab5dcadeda4129a6aa78` — matches the apply-phase snapshot (no regression). Focused change-related rerun: 86 passed / 86 (312 assertions), exit 0.
3. **Pint clean**: `vendor/bin/pint --dirty --format agent` exit 0, `result: passed`, zero files modified.
4. **All 10 tasks complete at close**: 8 core implementation tasks (1.1–3.2) plus the 2 finalization tasks (4.1, 4.2), which were executed BY the verify phase (focused + full suite + Pint + duplicate-blocker/scenario confirmation + runtime/rollback evidence documented in the verify report). See Task Completion Gate note below.
5. **Issues**: 0 CRITICAL, 0 WARNING, 3 non-blocking SUGGESTIONS (none acted on; see Issues and Suggestions Carried Forward).
6. **No commit or PR exists for the implementation** — feature files and this change's SDD artifacts are uncommitted in the working tree. The orchestrator coordinates delivery after archive.

Note on `apply-progress`/`verify-report` timestamps: `verify-report.md` was written at 20:27 and matches the close state; no work occurred after verification. No `apply-progress.md` file exists in the change folder — apply progress for this change lives only in Engram (obs #89); the OpenSpec status correctly reports `applyProgress: missing` while the work itself is proven complete by tasks.md and verify-report.md.

## What Was Done

Delivered a dietary-plan request queue (`planes-alimentarios`) per the proposal: staff mark a `Consulta` with a requires-plan control, creating one active `PlanAlimentario` request (pending), track its lifecycle (pending → delivered with actual date → payment_pending), optionally attach a private PDF served through a consultation-scoped authenticated download route, with queue visibility in the `consultapp` Filament panel.

### Key Deliverables

1. **Schema/migration** — additive reversible migration `2026_09_23_000001_add_dietary_plan_request_lifecycle.php`: non-null `requiere_plan` boolean on `consultas` (default false), constrained `estado` (default `pending`), nullable `fecha_entrega`, nullable attachment path, unique `consulta_id` index (one active request), `(estado, created_at)` ordering index; duplicate-`consulta_id` preflight aborts with actionable IDs before enforcing the index and never deletes legacy data; original migrations immutable (legacy data preserved on focused rollback).
2. **Models/enum/service** — `PlanAlimentarioStatus` backed enum (`pending|delivered|payment_pending` + `label()`/`options()`, Spanish label `Falta de pago`), `Consulta`/`PlanAlimentario` casts/fillable + `requiere_plan`, `PlanAlimentario` save invariant (delivered requires `fecha_entrega` else `LogicException`; non-delivered clears it), `PlanAlimentarioRequestService::sync()` create/update/unmark in a transaction, factory defaults.
3. **Filament workflow** — `ConsultaResource` form: live Toggle `requiere_plan` + conditional 'Plan alimentario' Section (estado Select, conditional delivery DatePicker, PDF-only 5 MB FileUpload on `local_private`); Create/Edit pages and `ConsultasRelationManager` CreateAction delegate to `sync()` (no duplicated form schema); `DietaryPlanQueue` panel page (slug `planes-alimentarios`, eager-loaded `consulta.paciente`, deterministic `created_at ASC, id ASC` ordering, deliver + markPaymentPending + download recordActions, no delete).
4. **Private attachment boundary** — `local_private` disk (local, non-served, no `url`/`serve`), ULID-generated storage paths (never client filename), validate → store new → persist → delete old after commit (`DB::afterCommit`), rollback branch deletes the newly stored object and retains the old one, unmark removes the row + object; `DownloadPlanAlimentarioController` on named route `{consulta}/plan-alimentario/download` behind Filament auth, record-only resolution (404 on no request / blank path / missing object), `StreamedResponse` with attachment disposition.

No data deletion, no history/recurring semantics, no public URLs, no email, no billing, no auto-generation were introduced (scope restrictions respected).

## Verification Evidence

Source: `verify-report.md` (filesystem, validated by Engram obs #90), schema `gentle-ai.verify-result/v1`, verdict `pass`, blockers 0, critical_findings 0, requirements 12/12, scenarios 36/36, evidence hash `sha256:3774739d8175db2e624969a069759b5d8e0a832cf41ae6f2b5dcff6dc64ed365`.

- **Build**: Pint passed — exit 0, `{"tool":"pint","result":"passed"}`, no files modified.
- **Focused tests**: 86 passed / 86 (312 assertions), exit 0 (8 change-related test files).
- **Full suite**: 131 passed / 131 (441 assertions), exit 0 — matches the apply-phase snapshot; no regression between apply and verify.
- **Compliance**: 36/36 scenarios compliant across 12/12 requirements; no FAILING, no UNTESTED, no PARTIAL.
- **TDD**: 6/6 checks passed (RED confirmed via 8/8 persisted test files; GREEN via focused + full reruns; triangulation and safety-net adequate).
- **Quality**: Pint lint pass; type checker not available (no PHPStan/Psalm in dev deps — informational); coverage tooling not available (no Xdebug/PCOV — informational, not blocking).

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| dietary-plan-request-queue | Created | Full new spec (4 requirements, 11 scenarios) — mechanical copy of the delta via shell (no prior main spec), verified byte-identical (`SPEC_COPY_DIFF_EMPTY_OK`) |
| consulta-resource | Updated | 2 ADDED requirements (`Requires-Plan Control` 3 scenarios, `Consulta Queue Context` 1 scenario); 8 existing requirements untouched |
| database-migrations | Updated | 1 MODIFIED requirement replaced in place (`Planes Alimentarios Table Migration`, 2 → 5 scenarios) + 1 new requirement added (`Queue Ordering Indexes`, 1 scenario); 6 other requirements untouched |
| domain-models | Updated | 2 MODIFIED requirements replaced in place (`Consulta Model` 4 → 5 scenarios, `PlanAlimentario Model` 3 → 5 scenarios); 3 other requirements untouched |
| filament-panel | Updated | 2 ADDED requirements (`Authenticated Dietary Plan Queue` 2 scenarios, `Consultation-Scoped PDF Download Authorization` 2 scenarios); 6 existing requirements untouched |

Main specs now reflecting the new baseline:
- `openspec/specs/dietary-plan-request-queue/spec.md`
- `openspec/specs/consulta-resource/spec.md`
- `openspec/specs/database-migrations/spec.md`
- `openspec/specs/domain-models/spec.md`
- `openspec/specs/filament-panel/spec.md`

### Merge Notes (decisions taken during sync, for audit)

- `dietary-plan-request-queue` is a brand-new capability whose delta spec is a FULL spec (Purpose + Requirements, no ADDED wrapper) — copied verbatim with `cp`/`diff -r`/`mv`, matching the `patient-weight-evolution` precedent. No delta annotations to strip.
- For ADDED-requirement domains (`consulta-resource`, `filament-panel`), each delta's `## ADDED Requirements` block was appended at the END of the main spec's Requirements section (after the last existing requirement, before any Edge Cases section), consistent with the consultas-modulo and chart-evolucion precedents.
- For MODIFIED-requirement domains (`database-migrations`, `domain-models`), each matching requirement block was replaced IN PLACE with the delta's full updated requirement, preserving every scenario not touched by the delta.
- **`Queue Ordering Indexes` (database-migrations)**: the delta marks it MODIFIED, but no such requirement existed in the baseline main spec (it is introduced by this change). Merge behavior: inserted as a NEW requirement immediately after the replaced `Planes Alimentarios Table Migration` (the delta lists them consecutively and both concern the same migration set, mirroring how consultas-modulo placed `Widen IMC Column Migration` next to its sibling). Recorded here for audit.
- Delta-only annotations were stripped from merged baselines and remain verbatim in the archived delta files: `# Delta for ...` titles, `## ADDED Requirements` / `## MODIFIED Requirements` wrappers, and the `(Previously: ...)` parentheticals inside MODIFIED requirements (they describe the delta's change, not the current baseline). Scenario formatting in the appended/replaced blocks was normalized to the main-spec convention (blank line after `#### Scenario:` header and between bullets) — content otherwise byte-faithful to the delta blocks.
- **No requirement was REMOVED or RENAMED** by any delta. Zero destructive delta, so the config.yaml archive rule ("Warn before merging destructive deltas") was not triggered.

## Unchanged / Dropped Requirements

None. All requirements delivered by this change are ADDED or MODIFIED-superset; nothing was removed or renamed. Every requirement not mentioned in the deltas was preserved (verified: consulta-resource 8/8, database-migrations 6/6 besides the two touched, domain-models 3/3 besides the two touched, filament-panel 6/6).

## Task Completion Gate — Exceptional Checkbox Reconciliation

Archival performed the sanctioned stale-checkbox repair for tasks 4.1 and 4.2, which remained `- [ ]` in `tasks.md` although their work is complete. Exact reconciliation reason (recorded per skill policy):

- **Orchestrator instruction**: the launch prompt's final-state facts state "All 8 implementation tasks across Phases 1–3 are `[x]`; Phase 4 verification/refactor was completed by the verify phase (PASS)" — an explicit instruction to reconcile stale checkboxes.
- **Proof from higher-ranked sources**: `verify-report.md` (the terminal record) states "Finalization tasks (4.1, 4.2) = 2 executed by this verify phase (focused + full suite + Pint + duplicate-blocker/scenario confirmation + evidence documented here)" and "Tasks incomplete: 0". Engram #89 (apply-progress) records "Phase 4 (4.1 run full suite+Pint — DONE as evidence above; 4.2 refactor + apply receipt) → verify phase". Engram #90 validates the PASS report.
- **Action**: 4.1 and 4.2 were marked `[x]`; the archived `tasks.md` is now 10/10 checked with 0 unchecked. No other task unchecked at any point (Phases 1–3 were already `[x]`).

Per the skill, implementation-task checkboxes are apply's responsibility and archive only performs this exceptional mechanical reconciliation when the orchestrator instructs it and apply-progress/verify-report prove completion — both conditions held here.

## Archive Contents

- proposal.md ✅
- exploration.md ✅ (was part of the change folder; carried into the archive with the whole-folder move)
- specs/consulta-resource/spec.md ✅
- specs/database-migrations/spec.md ✅
- specs/dietary-plan-request-queue/spec.md ✅
- specs/domain-models/spec.md ✅
- specs/filament-panel/spec.md ✅
- design.md ✅
- tasks.md ✅ (10/10 tasks complete — verified: 0 unchecked implementation tasks after reconciliation)
- verify-report.md ✅
- archive-report.md ✅ (this file, additive — excluded from the mechanical diff readback)

Verbatim `diff -r` readback (Mechanical Copy Contract):

1. **Spec copy** (dietary-plan-request-queue delta → main spec): `diff -r` empty → `SPEC_COPY_OK: diff empty (byte-identical copy verified)`.
2. **Folder move** (recursive snapshot vs. archived tree): `git mv` refused the fully-untracked source folder ("directorio de fuente está vacío" — git treats it as empty from the index perspective, same as chart-evolucion) → `mv` fallback used; mandatory recursive-snapshot `diff -r` empty → `MOVE_DIFF_EMPTY_OK: archived tree is byte-identical to the pre-move recursive snapshot`.

Both readbacks passed EMPTY — byte-identical archival, no truncation or alteration. No application or test code was modified during archive.

## Issues and Suggestions Carried Forward

| Severity | Issue | Status |
|----------|-------|--------|
| SUGGESTION (S-1) | `PlanAlimentarioDownloadTest.php` ~line 109: strengthen `retains the existing object when the database write fails` by physically storing `plan/new.pdf` before `sync()` so the catch-branch cleanup deletes a real object (the meaningful assertions — old path retained, old file exists — are real) | Not acted on; non-blocking, no action required at close |
| SUGGESTION (S-2) | `PlanAlimentarioRequestLifecycleMigrationTest.php`: add a direct `Schema::hasIndex('planes_alimentarios', 'planes_alimentarios_estado_created_at_index')` assertion to close the small gap in the Queue Ordering Indexes scenario (index existence currently proven only via its drop on rollback) | Not acted on; non-blocking, no action required at close |
| SUGGESTION (S-3) | Apply-progress TDD evidence table lacks TRIANGULATE and SAFETY NET columns; independent verification confirms both | Future phases should record them |

0 CRITICAL, 0 WARNING — nothing blocking archived.

## Engram Traceability

Source of truth is the OpenSpec filesystem; Engram holds mirrors of the phase artifacts. Observation IDs for the `planes-alimentarios` change consulted during archive:

| Artifact | Engram obs ID | Source used for archive |
|----------|---------------|--------------------------|
| apply-progress | #89 | Evidence for Phase 4 reconciliation (full content read) |
| verify-report | #90 | Validator replay proof `{"valid":true,"verdict":"pass"}` (full content read) |

Proposal/specs/design/tasks were read from the filesystem (OpenSpec is authoritative in hybrid mode); their Engram mirrors exist under `sdd/planes-alimentarios/{proposal,spec,design,tasks}` but were not re-fetched — the filesystem artifacts and the two observations above cover the change. The native SDD dispatcher still reports `nextRecommended: apply` / `archive: blocked` purely from the stale `tasks.md` checkbox state and missing `applyProgress` artifact (no CLI reconciliation command exists); archive was relaunched explicitly by the orchestrator with all archive gates satisfied (tasks reconciled with proof, verify PASS, no CRITICAL, review gate structurally absent) and proceeded under ordinary repository policy. No review transaction/ledger/receipt topics exist for this change.

This archive report is persisted to Engram as topic `sdd/planes-alimentarios/archive-report` (project `consultappv2`).

## Git State at Close

- Branch: `develop`
- HEAD: `e72b680` — Merge pull request #10 from agufedee/feature/chart-evolucion
- Working tree at archive start: uncommitted feature files (enums, service, controller, queue page, migration, resource/form edits, factory/model edits, 3 new + modified test files) + this change's SDD artifacts. The archive phase made the expected changes only: baseline spec syncs (4 merges + 1 mechanical copy) + tasks.md checkbox reconciliation (4.1/4.2) + the change-folder move to the archive.
- No commit created by the archive phase (per execution instructions); the orchestrator will commit and deliver the implementation and the `chore(sdd): archive planes-alimentarios change` artifact.

## SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived. Specs are synced to the new baseline — the dietary-plan request queue is part of the main OpenSpec specs (`dietary-plan-request-queue` domain created; `consulta-resource`, `database-migrations`, `domain-models`, `filament-panel` extended). Ready for the next change.