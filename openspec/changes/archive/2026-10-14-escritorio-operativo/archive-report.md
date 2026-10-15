# Archive Report: Operational Desk Dashboard

**Change**: escritorio-operativo
**Archived**: 2026-10-14
**Archive path**: `openspec/changes/archive/2026-10-14-escritorio-operativo/`
**Artifact store**: hybrid (OpenSpec + Engram)
**Delivery strategy**: feature-branch-chain
**Review budget**: 800 changed lines (session); SDD default 400-line guard flagged High and resolved by the 3-slice chain

## Verdict at Close

**PASS WITH WARNINGS** — 8/8 requirements and 17/17 scenarios compliant with covering tests that passed in the terminal verify run; full suite GREEN **158/158 (559 assertions)**; Pint clean; Strict-TDD evidence 6/6 verified; no out-of-scope work.

The verify-report is the terminal snapshot for this change. No work occurred after it was persisted (no commits, no fixes, no later test runs), so its verdict and numbers ARE the final state — not a stale intermediate claim. Source: `verify-report.md` (OpenSpec) and Engram observation `#126`.

- Blockers: 0
- CRITICAL findings: 0
- Verify evidence revision: `sha256:94d00377d670ba8a1aa745ba643a4b016c9c833b430f72764393d1e3f1486af4`

## Requirement / Scenario Counts

| Domain | Delta action | Merged into | Requirements | Scenarios |
|--------|--------------|-------------|--------------|-----------|
| `operational-dashboard` | ADDED (new domain) | `openspec/specs/operational-dashboard/spec.md` | 6 | 11 |
| `filament-panel` | ADDED requirement | `openspec/specs/filament-panel/spec.md` | 1 | 2 |
| `dietary-plan-request-queue` | MODIFIED requirement | `openspec/specs/dietary-plan-request-queue/spec.md` | 1 | 5 |
| **Total** | | | **8** | **17** |

Authoritative verify counts: **8 requirements / 17 scenarios**, all COMPLIANT.

## Test Evidence

- Full suite: `php artisan test --compact` → **158 passed / 158, 559 assertions**, exit 0.
- Focused change-related suite: **43/43 (164 assertions)**
  - `OperationalDashboardWidgetTest` 11/11 (56 assertions)
  - `PlanAlimentarioLifecycleServiceTest` 7/7 (17 assertions)
  - `DietaryPlanQueueTest` 12/12 (68 assertions)
  - `PlanAlimentarioTest` 13/13 (23 assertions)
- Linter: `vendor/bin/pint --dirty --format agent` → exit 0, `{"tool":"pint","result":"passed"}`, zero files modified.

## Full-Suite Warnings

The full suite reports **6 warnings**. Per-file attribution (verify-report): **5 pre-existing** (`PlanAlimentarioTest:8-9`, `PlanAlimentarioRequestServiceTest`, `PlanAlimentarioRequestLifecycleMigrationTest`, `PatientWeightEvolutionChartTest`) and **1 candidate-caused** — a no-effect non-compound `use InvalidArgumentException;` statement in `tests/Feature/Services/PlanAlimentarioLifecycleServiceTest.php:7` (non-namespaced file).

The apply-phase note claimed "6 pre-existing warnings"; the verify phase corrected this to **5 pre-existing + 1 change-introduced**. Zero production impact; all tests pass. This warning is non-critical and did not block archive. The apply-phase claim is a lower-ranked intermediate snapshot; the verify-report attribution is the final account.

## Branch / Commit State

- Branch: `develop`
- Commit state at close: **uncommitted working tree**, no commits, no branches, no PRs created for this change.
- Feature-branch-chain strategy was selected; commits require separate user authorization and were NOT performed. Archive moved only the OpenSpec change folder; implementation files remain in the working tree as authored by apply.

## PR Boundary per Unit

| Unit | Goal | Likely PR | Rollback boundary | Tracked changed lines |
|------|------|-----------|-------------------|-----------------------|
| 1 | Shared scopes + `PlanAlimentarioLifecycleService` | PR 1 | Revert service, three model scopes, and queue call-site changes | 73 |
| 2 | Operational dashboard widget + panel registration | PR 2 | Remove widget/view and panel registration line | 2 |
| 3 | Queue regression + final integration coverage | PR 3 | Revert only the 5 added regression assertions | 28 |

Cumulative native attempt-gate `changed_lines`: **103** — the gate metric counts tracked `git diff --numstat` between begin/finish candidate trees, so untracked new files are excluded. Authored footprint is ~465 lines (5 tracked modified + 5 new untracked), within the **800-line** session review budget via the 3-slice feature-branch-chain.

## Delivery Strategy

`ask-on-risk` resolved to **feature-branch-chain** (chosen at session preflight). Task forecast: `400-line budget risk: High`, `Chained PRs recommended: Yes`. Three work units map to PR 1 → PR 2 → PR 3, PR #1 targeting the feature/tracker branch and later children targeting the previous slice.

## Review Budget

- Session review budget: **800 changed lines**.
- SDD default guard: 400 lines — exceeded by the authored footprint, hence the 3-slice chain.
- Estimated authored lines: 450–600; realized cumulative tracked 103 (gate view) / ~465 authored.

## Scope Confirmation

Out-of-scope check at close: **confirmed none**. No migration, dependency, public-URL, payment/download, or role-policy work was introduced.

| Constraint | Status |
|------------|--------|
| No migration work | ✅ `database/` untouched; no new migrations |
| No dependency changes | ✅ `composer.json`/`composer.lock`/`package.json`/`package-lock.json` untouched |
| No public URLs / routes | ✅ `routes/` untouched; URLs are Filament panel helpers behind `Authenticate` |
| No payment/download changes | ✅ `markPaymentPending` persists status only, no billing |
| No role-policy changes | ✅ `app/Policies/` untouched; panel `authMiddleware` unchanged |
| `payment_pending` non-blocking | ✅ Proven at service level |
| Existing queue transitions preserved | ✅ `DietaryPlanQueueTest` 12/12 green |
| No second delivery workflow | ✅ Widget delegates to the shared service + model invariant |

## Artifact Traceability (Engram observation IDs read)

| Artifact | Engram observation |
|----------|--------------------|
| `sdd/escritorio-operativo/proposal` | #114 |
| `sdd/escritorio-operativo/explore` | #112 |
| `sdd/escritorio-operativo/design` | #117 |
| `sdd/escritorio-operativo/tasks` | #119 |
| `sdd/escritorio-operativo/apply-progress` | #120 |
| `sdd/escritorio-operativo/verify-report` | #126 |
| `sdd/escritorio-operativo/archive-report` | this report (upserted) |

No separate Engram `sdd/escritorio-operativo/spec` observation exists; the delta specs live in OpenSpec under `specs/{domain}/spec.md` and were merged into the main specs. OpenSpec-side artifacts were read directly from `openspec/changes/escritorio-operativo/`.

## Task Completion Gate — Stale-Checkbox Reconciliation

- OpenSpec `tasks.md`: **9/9 tasks checked (`[x]`)** — the hybrid gate artifact, passes cleanly.
- Engram `tasks` observation **#119**: stale, all 9 tasks shown unchecked (`[ ]`), captured at spec time (2026-10-14 19:37:29, before apply) and never updated by `sdd-apply`.

Per the Final-State Authority hierarchy, the orchestrator's launch prompt ("All 9/9 tasks complete") is the most recent account and outranks the stale Engram snapshot; `apply-progress` and `verify-report` independently prove every task complete. Under the explicit orchestrator instruction plus that proof, `sdd-archive` performed the **exceptional mechanical reconciliation**: Engram observation #119 was updated to reflect all 9 tasks complete, matching the OpenSpec `tasks.md`. Reconciliation reason: the persisted Engram tasks audit trail must not retain stale unchecked tasks for completed work. No implementation task was found genuinely incomplete.

## Final-State Handoff

No facts changed after the apply/verify snapshots — no post-verify fixes, no later commits, no resolved blockers. Commit/PR/evidence refs are **N/A** (uncommitted on `develop`, no PRs). The verify-report is the terminal verification record.

Live attempt-gate outcome: objective `sdd-archive` / `archive-with-verify-report`, outcome **passed**, harness disposition **reused**.

## Post-Archive Correction — Payment-Pending Count Removed

After archive, the maintainer judged the header "Falta de pago" counter not useful and removed it from the dashboard view by hand. A follow-up correction aligned the living system with that decision:

- **Requirement removed** from the main spec: `openspec/specs/operational-dashboard/spec.md` no longer carries `### Requirement: Separate Payment-Pending Count`. The dashboard requirement count is now 7 (was 8).
- **Dead data path removed**: `OperationalDashboardWidget::getViewData()` no longer computes `paymentPendingCount`, dropping one `COUNT` query on every dashboard render.
- **Tests realigned**: the widget test now asserts only the surviving invariant — `payment_pending` and `delivered` records never appear in the pending list — plus the native-markup assertion (`fi-btn`).
- **Preserved**: the separation guarantee (line 28, `payment_pending` MUST NOT appear in the pending list) still holds and is still tested; the `payment_pending` status, its `PlanAlimentario::scopePaymentPending()` query, and the dietary-plan queue "Falta de pago" label are unchanged.

This is a **destructive delta**, recorded per `openspec/config.yaml` (`archive: Warn before merging destructive deltas`). The archived delta specs and verify-report remain the historical snapshot of what was verified at archive time; this section is the record of the post-archive removal.

Verification after the correction: `php artisan test --compact` → 159 passed, 560 assertions, 6 pre-existing warnings; Pint clean.
