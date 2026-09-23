# Archive Report: Optional Patient Weight Evolution Chart

## Change Summary

| Field | Value |
|-------|-------|
| Change | chart-evolucion |
| Archived | 2026-09-22 |
| Mode | hybrid (Engram + OpenSpec) |
| Verdict at close | PASS WITH WARNINGS |
| Requirements | 2/2 |
| Scenarios | 7/7 |
| Tests at close | 78 passed / 0 failed / 0 skipped (243 assertions); focused 5 passed / 17 assertions |
| Tasks | 8/8 complete |
| Branch | `develop` (feature files uncommitted in working tree) |
| HEAD | `c4de614` (chore(sdd): archive consultas-modulo change) |

## Final State (facts at close)

Per the orchestrator's final-state account (highest-authority source for post-snapshot events; corroborated by `verify-report.md` obs #73, `apply-progress.md` obs #70, and the repository):

1. **Verdict PASS WITH WARNINGS** at close — 2/2 requirements, 7/7 scenarios compliant. The `verify-report` YAML header carries the granular `verdict: pass` (blockers 0, critical_findings 0); the prose verdict is PASS WITH WARNINGS. Both refer to the same pass state; the warning is informational only.
2. **Only WARNING**: a non-blocking PHP notice — `The use statement with non-compound name 'ReflectionMethod' has no effect` in `tests/Feature/Filament/PatientWeightEvolutionChartTest.php:10`. It does not affect runtime correctness. The maintainer explicitly decided to archive with this warning; no application or test code was modified during archive.
3. **All 8 tasks complete** — archived `tasks.md` verified at archive time with 0 unchecked implementation tasks.
4. **Tests at close**: focused suite 5 passed / 17 assertions (exit 0, output hash `sha256:3b8bd555d4bc6ceba4d4dabf8e3d308da58c836a9d77bf262e8695a93ab84ada`); full suite 78 passed / 243 assertions (exit 0, output hash `sha256:1924b8073a2c7387d96c5438daee700744c599adc6a3d9d9bbd2bbf14e3fa9bd`). Pint passed (`vendor/bin/pint --dirty --format agent`).
5. **No commit created for the implementation** — the working tree holds the uncommitted feature files (widget, `ViewPaciente` modifications, footer Blade view, focused test) and this change's SDD artifacts. The orchestrator coordinates delivery.

Note on `apply-progress`/`verify-report` timestamps: both reflect the state at their write time and match the close state; no work occurred after verification. The Engram tasks mirror (obs #69) is a pre-apply snapshot (19:28) still showing unchecked boxes — the filesystem `tasks.md` is the authoritative task artifact for this hybrid-mode change and is fully checked; see Engram Traceability.

## What Was Done

Delivered an optional, read-only, patient-scoped weight evolution chart for the `consultapp` panel, per the proposal's capability split (`patient-weight-evolution` new, `paciente-resource` modified).

### Key Deliverables

1. `app/Filament/Widgets/PatientWeightEvolutionChart.php` — typed `Paciente $record` extending native Filament 5.8.2 `ChartWidget`; `getType()` line; `getData()` maps `consultas()->select(['fecha', 'peso'])->orderBy('fecha')->get()` to date labels and one `Weight (kg)` dataset; native tooltip payload. Excluded from Filament global widget discovery; nested-mounted only by the patient footer.
2. `app/Filament/Resources/Pacientes/Pages/ViewPaciente.php` (+ 3 lines of page state/integration) — transient `public bool $showWeightChart = false`, page-local `hasConsultations` state computed outside Blade (`consultas()->exists()`), `toggleWeightChart(): void`, and `getFooter()` returning the dedicated footer view; existing edit action preserved.
3. `resources/views/filament/resources/pacientes/pages/view-paciente-footer.blade.php` — accessible show/hide control and conditional nested widget mount (only after choice and only when consultations exist); no inline script or query.
4. `tests/Feature/Filament/PatientWeightEvolutionChartTest.php` — Pest/Livewire coverage for chronological order, patient isolation, one-series/tooltip payload, default hidden state, show/hide toggle, fresh-page reset, footer-after-content placement, and no-consultation omission.

No migrations, model changes, dependencies, persistence, or direct `chart.js` usage were introduced (scope restrictions respected; IMC/combined series explicitly out of scope).

## Verification Evidence

Source: `verify-report.md` (obs #73), schema `gentle-ai.verify-result/v1`, verdict `pass` (granular) / PASS WITH WARNINGS (prose), blockers 0, critical_findings 0, requirements 2/2, scenarios 7/7, evidence hash `sha256:4154690643af6e1c843f03369cf9d947ac1ac7fa96d3a59e6ac557b46b4202fb`.

- **Build**: `php -l` on the three changed PHP files — passed, exit 0; only the non-blocking `use ReflectionMethod` warning in the test file.
- **Tests**: focused 5 passed / 17 assertions; full suite 78 passed / 243 assertions, exit 0.
- **Compliance**: 7/7 scenarios compliant across 2/2 requirements (the three Paciente-resource scenarios overlap the same verified behaviors); no FAILING, no UNTESTED, no PARTIAL.
- **TDD evidence**: apply-progress (obs #70) TDD Cycle Evidence table — RED→GREEN per task; 5/6 checks passed, safety-net check informational (correctly N/A for new files).

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| patient-weight-evolution | Created | Full new spec (2 requirements, 7 scenarios) — mechanical copy of the delta via shell (no prior main spec), verified byte-identical |
| paciente-resource | Updated | 1 ADDED requirement (`Optional Weight Chart in View Footer`, 3 scenarios); all other requirements untouched |

Main specs now reflecting the new baseline:
- `openspec/specs/patient-weight-evolution/spec.md`
- `openspec/specs/paciente-resource/spec.md`

### Merge Notes (decisions taken during sync, for audit)

- `patient-weight-evolution` is a brand-new capability whose delta spec is a FULL spec (Purpose + Requirements, no ADDED wrapper) — copied verbatim with `cp`/`diff -r`/`mv`, matching the `consulta-resource` precedent. No delta annotations to strip.
- For `paciente-resource`, the delta's single `## ADDED Requirements` block was appended as the new requirement at the END of the main spec's Requirements section (after `Consultas Relation Manager`, the last requirement), consistent with the consultas-modulo precedent. The delta-only annotations (`# Delta for Paciente Resource`, `## ADDED Requirements` wrapper) were stripped from the merged baseline; they remain verbatim in the archived delta file.
- **No requirement was MODIFIED, REMOVED, or RENAMED** by either delta — zero destructive delta, so the config.yaml archive rule ("Warn before merging destructive deltas") was not triggered.
- The appended requirement block was verified byte-identical to the delta's requirement block (`diff` on the block: empty; the merged tail needed only an EOF-newline normalization).

## Unchanged / Dropped Requirements

None. All requirements delivered by this change are ADDED (or new-domain) requirements; nothing was modified, dropped, or renamed.

## Archive Contents

- proposal.md ✅
- exploration.md ✅ (was part of the change folder; carried into the archive with the whole-folder move)
- specs/patient-weight-evolution/spec.md ✅
- specs/paciente-resource/spec.md ✅
- design.md ✅
- tasks.md ✅ (8/8 tasks complete — verified: 0 unchecked implementation tasks)
- apply-progress.md ✅
- verify-report.md ✅
- archive-report.md ✅ (this file, additive — excluded from the mechanical diff readback)

Verbatim `diff -r` readback (Mechanical Copy Contract): the `patient-weight-evolution` spec-copy step and the folder-move step (recursive snapshot vs. archived tree) both produced **empty** diffs (`SPEC_COPY_DIFF_EMPTY_OK`, `MOVE_DIFF_EMPTY_OK`) — byte-identical archival, no truncation or alteration. The folder move used the `mv` fallback because `git mv` refused the fully-untracked source folder (git treats it as empty from the index perspective); the mandatory recursive-snapshot `diff -r` readback still passed empty before the fallback path was used.

## Issues and Suggestions Carried Forward

| Severity | Issue | Status |
|----------|-------|--------|
| WARNING (W-1) | PHP: `use ReflectionMethod` has no effect at `tests/Feature/Filament/PatientWeightEvolutionChartTest.php:10` | Carried to archive by explicit maintainer decision — non-blocking test-file notice; clean-up deferred (would be a small test-only follow-up) |
| SUGGESTION (S-1) | Keep the native tooltip contract covered by the payload test if future chart configuration changes are made | Not acted on; no action required at close |

## Engram Traceability

Source of truth is the OpenSpec filesystem; Engram holds mirrors of the phase artifacts. Observation IDs for the `chart-evolucion` change consulted during archive:

| Artifact | Engram obs ID |
|----------|---------------|
| explore | #63 |
| proposal | #64 |
| decision (limit chart to optional weight view) | #65 |
| spec | #66 |
| design | #67 |
| tasks | #69 (stale pre-apply mirror — see note) |
| apply-progress | #70 |
| verify-report | #73 |
| dispatcher-block discovery | #74 |

Traceability notes:
- Obs #69 (Engram tasks mirror, written 19:28 before apply completed at ~19:48) still shows unchecked boxes. The archived filesystem `tasks.md` — the authoritative task artifact in hybrid mode — is fully checked (8/8), corroborated by apply-progress (obs #70, "all work-unit tasks complete"), verify-report (obs #73, 8/8 complete), and the orchestrator's final-state account. The Engram mirror was NOT modified per the archive instruction not to alter task artifacts; the discrepancy is recorded here for traceability.
- Obs #74 recorded that the native SDD dispatcher still reported `nextRecommended: verify` / `archive: blocked` after PASS WITH WARNINGS acceptance (no CLI reconciliation command exists). Archive was relaunched explicitly by the orchestrator with all archive gates satisfied (tasks complete, no CRITICAL, review gate structurally absent); it proceeded under ordinary repository policy. No review transaction/ledger/receipt topics exist for this change.

This archive report is persisted to Engram as topic `sdd/chart-evolucion/archive-report` (project `consultappv2`).

## Git State at Close

- Branch: `develop`
- HEAD: `c4de614` — chore(sdd): archive consultas-modulo change
- Working tree at archive start: uncommitted feature files (`app/Filament/Widgets/`, modified `ViewPaciente.php`, footer view, focused test) + this change's SDD artifacts. The archive phase made the expected changes only: baseline spec syncs (paciente-resource merge, patient-weight-evolution creation) + the change-folder move to the archive.
- No commit created by the archive phase (per execution instructions); the orchestrator will commit and push the implementation and the `chore(sdd): archive chart-evolucion change` artifact.

## SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived. Specs are synced to the new baseline — the optional weight-evolution chart is part of the main OpenSpec specs (`patient-weight-evolution` domain created, `paciente-resource` extended). Ready for the next change.