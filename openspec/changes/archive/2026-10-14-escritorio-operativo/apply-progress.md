# Apply Progress: Operational Desk Dashboard

## Implementation Progress

**Change**: escritorio-operativo
**Mode**: Strict TDD
**Batch**: Unit 1 — Phase 1: Foundation (Sprint 3) — PR 1 slice; Unit 2 — Phase 2: Dashboard Implementation (Sprint 3) — PR 2 slice; Unit 3 — Phase 3: Verification and Regression (Sprint 4) — PR 3 slice

### Completed Tasks

- [x] 1.1 RED: Add Pest cases for `pending()`, `paymentPending()`, deterministic `orderedForQueue()`, delivered-date rejection, non-delivered date clearing, and non-blocking `payment_pending` behavior.
- [x] 1.2 Add `PlanAlimentario` scopes and `PlanAlimentarioLifecycleService`; preserve every existing queue status transition and keep the model saving invariant authoritative.
- [x] 1.3 Update `DietaryPlanQueue.php` to use shared scopes/service without changing complete-list behavior or existing transitions.
- [x] 2.1 RED: Add dashboard feature cases for auth boundary, ten-row ordering, eager-loaded patient/consultation context, status separation, empty state, links, and invalid/valid delivery.
- [x] 2.2 Create `OperationalDashboardWidget.php` and its Blade wrapper with bounded pending data, separate payment count, shortcuts, queue link, and required-date delivery action.
- [x] 2.3 Register the widget in `ConsultAppPanelProvider.php` using the existing `consultapp` panel authentication and URL helpers.
- [x] 3.1 Complete `OperationalDashboardWidgetTest.php` and `PlanAlimentarioLifecycleServiceTest.php`; prove invalid submissions do not mutate records.
- [x] 3.2 Extend `DietaryPlanQueueTest.php` for shared delivery behavior and verify delivered/payment-pending records remain excluded from the pending projection.
- [x] 3.3 Run focused Pest tests, full `php artisan test --compact`, and Pint; confirm no migration, dependency, public URL, payment, or role-policy work was introduced.

### Files Changed

| File | Action | What Was Done |
|------|--------|---------------|
| `app/Models/PlanAlimentario.php` | Modified | Added `pending()`, `paymentPending()`, and `orderedForQueue()` scopes; the `saving` invariant is untouched. |
| `app/Services/PlanAlimentarioLifecycleService.php` | Created | Shared `deliver()` (requires non-empty date, rejects before mutation) and `markPaymentPending()` transitions. |
| `app/Filament/Pages/DietaryPlanQueue.php` | Modified | Query now uses `orderedForQueue()`; `deliver` and `markPaymentPending` actions delegate to the lifecycle service. |
| `tests/Feature/Models/PlanAlimentarioTest.php` | Modified | Added scope coverage for `pending()`, `paymentPending()`, `orderedForQueue()`. |
| `tests/Feature/Services/PlanAlimentarioLifecycleServiceTest.php` | Created/Extended | Lifecycle transition, missing/blank date rejection, date clearing, non-blocking payment pending; Unit 3 added in-memory immutability on rejection and payment-pending→delivered delivery. |
| `app/Filament/Widgets/OperationalDashboardWidget.php` | Created | Bounded pending-queue table widget (10 rows, eager-loaded context), separate payment-pending count, shortcuts, queue link, and required-date delivery action delegating to the lifecycle service. |
| `resources/views/filament/widgets/operational-dashboard-widget.blade.php` | Created | Widget wrapper view rendering the pending table plus the shortcut row, payment count, and empty state. |
| `app/Providers/Filament/ConsultAppPanelProvider.php` | Modified | Registered `OperationalDashboardWidget` on the `consultapp` panel alongside `AccountWidget`. |
| `tests/Feature/Filament/OperationalDashboardWidgetTest.php` | Created/Extended | Auth boundary, ten-row ordering, eager-loaded context, status separation, empty state, shortcut/queue links, and required-date delivery coverage; Unit 3 added blank-date rejection with full-attribute immutability proof. |
| `tests/Feature/Filament/DietaryPlanQueueTest.php` | Extended (Unit 3) | Blank-date rejection with unchanged record; queue transitions keep delivered/payment-pending out of the shared pending projection while the queue complete list stays authoritative. |

### TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 1.1 | `tests/Feature/Models/PlanAlimentarioTest.php` | Feature | ✅ 10/10 | ✅ Written | ✅ Passed 13/13 | ✅ 3 scope cases | ✅ Clean |
| 1.1 | `tests/Feature/Services/PlanAlimentarioLifecycleServiceTest.php` | Feature | N/A (new) | ✅ Written | ✅ Passed 5/5 | ✅ 5 cases (happy + null + blank + date clear + payment pending) | ✅ Clean |
| 1.2 | same as 1.1 | Feature | ✅ 10/10 | ✅ Written | ✅ Passed | ✅ Multi-case | ✅ Clean |
| 1.3 | `tests/Feature/Filament/DietaryPlanQueueTest.php` | Feature | ✅ 10/10 | ➖ (behavior-preserving refactor of existing action bodies) | ✅ Passed 10/10 | ✅ Existing approval suite | ➖ None needed |
| 2.1 | `tests/Feature/Filament/OperationalDashboardWidgetTest.php` | Feature | ✅ 85/85 | ✅ Written (8 errors, class missing) | ✅ Passed 10/10 / 47 assertions | ✅ 10 cases (auth, ordering, eager-load, status split, empty, links, invalid/valid delivery) | ✅ Clean |
| 2.2 | same as 2.1 | Feature | ✅ 85/85 | ✅ Written | ✅ Passed | ✅ Multi-case | ✅ `PENDING_LIMIT` narrowed to `private const`; Pint `types_spaces` fix |
| 2.3 | `tests/Feature/ConsultappPanelTest.php` | Feature | ✅ 85/85 | ➖ (registration only) | ✅ Passed | ✅ Panel suite | ➖ None needed |
| 3.1 | `tests/Feature/Filament/OperationalDashboardWidgetTest.php` + `tests/Feature/Services/PlanAlimentarioLifecycleServiceTest.php` | Feature | ✅ 25/25 (105 assertions) | ✅ Written (3 new cases: widget blank-date full-attribute immutability; lifecycle in-memory immutability on rejection; payment-pending→delivered non-blocking delivery) | ✅ Passed 30/30 (141 assertions) — approval-style: documents behavior the Units 1–2 production code already implements | ✅ Immutability triangulated at both widget (validation layer) and service (domain layer) boundaries; payment non-blocking triangulated with a different source status | ➖ None needed |
| 3.2 | `tests/Feature/Filament/DietaryPlanQueueTest.php` | Feature | ✅ 25/25 (105 assertions) | ✅ Written (2 new cases: blank-date rejection; queue transitions exclude delivered/payment-pending from shared `pending()` projection while complete list stays authoritative) | ✅ Passed 30/30 (141 assertions) | ✅ Two distinct transitions (deliver + markPaymentPending) both asserted against the projection | ➖ None needed |
| 3.3 | full suite + Pint | Feature | ✅ 30/30 focused | ➖ (verification-only task, no new production code) | ✅ Full suite 158/158 (559 assertions); Pint passed on dirty files | ➖ Not applicable (no production logic) | ➖ None needed |

### Test Summary

- **Total tests written**: 23 (Unit 1: 3 scope + 5 lifecycle; Unit 2: 10 dashboard; Unit 3: 5 regression — 1 widget + 2 lifecycle + 2 queue)
- **Total tests passing**: 23 new + full suite 158/158 green (559 assertions, 6 pre-existing warnings unrelated to this change)
- **Layers used**: Feature (23)
- **Approval tests** (refactoring): `DietaryPlanQueueTest` (10 tests) used as approval suite before/after rewiring to the shared service; Unit 3 added 2 more approval-style regression cases
- **Pure functions created**: 0 (domain service + Eloquent scopes + Filament widget; behavior is DB/panel-bound)

### Work Unit Evidence

| Evidence | Value |
|---|---|
| Focused test command and exact result | `php artisan test --compact --filter=PlanAlimentarioLifecycleServiceTest` → 5 passed / 12 assertions; `php artisan test --compact --filter=PlanAlimentarioTest` → 13 passed / 23 assertions |
| Runtime harness command/scenario and exact result | `php artisan test --compact --filter=DietaryPlanQueueTest` → 10 passed / 46 assertions (proves both existing transitions and complete-list behavior preserved through the shared service) |
| Rollback boundary | Revert `app/Services/PlanAlimentarioLifecycleService.php`, the three scopes in `app/Models/PlanAlimentario.php`, and the `DietaryPlanQueue.php` query/action bodies. No migration, schema, or cross-unit work is involved. |

### Unit 2 — Work Unit Evidence

| Evidence | Value |
|---|---|
| Focused test command and exact result | `php artisan test --compact --filter=OperationalDashboardWidgetTest` → 10 passed / 47 assertions |
| Runtime harness command/scenario and exact result | `php artisan test --compact --filter=DietaryPlanQueueTest\|PlanAlimentarioTest\|PlanAlimentarioLifecycleServiceTest\|ConsultappPanelTest\|PatientWeightEvolutionChartTest\|PacienteResourceTest\|ConsultaResourceTest\|PlanAlimentarioUploadTest\|PlanAlimentarioDownloadTest` → 85 passed / 296 assertions (no regression in queue, lifecycle, or panel behavior) |
| Rollback boundary | Remove `OperationalDashboardWidget.php`, its Blade view, the test file, and the widget registration line in `ConsultAppPanelProvider.php`. Reuses Unit 1 scopes/service and touches no migration, schema, or queue transition. |

### Unit 3 — Work Unit Evidence

| Evidence | Value |
|---|---|
| Focused test command and exact result | `php artisan test --compact --filter='OperationalDashboardWidgetTest\|PlanAlimentarioLifecycleServiceTest\|DietaryPlanQueueTest'` → 30 passed / 141 assertions (baseline before extension was 25 passed / 105 assertions) |
| Runtime harness command/scenario and exact result | `php artisan test --compact` → 158 passed / 559 assertions (6 pre-existing warnings); `vendor/bin/pint --dirty --format agent` → passed |
| Rollback boundary | Revert only the 5 new test cases added in Unit 3 (`OperationalDashboardWidgetTest.php` blank-date case; `PlanAlimentarioLifecycleServiceTest.php` in-memory immutability + payment-pending delivery cases; `DietaryPlanQueueTest.php` blank-date + projection cases). No production code, migration, dependency, route, payment, or policy file is touched by Unit 3. |

### Deviations from Design

None — implementation matches design. Note: the queue/consumer uses `app(PlanAlimentarioLifecycleService::class)` inside the Filament action closure, matching the existing project convention (`EditConsulta::afterSave` uses `app(PlanAlimentarioRequestService::class)`) rather than constructor injection, to stay consistent with the codebase.

Unit 3 note: the 5 new cases are approval-style regression tests — Units 1–2 production code already implements the behavior, so RED produced tests that document current reality and passed on first execution (the strict-tdd approval-testing path for behavior-preserving verification tasks). No production code was changed in Unit 3.

### Issues Found

None.

### Remaining Tasks

- [x] 2.1 RED: dashboard feature cases (auth boundary, ten-row ordering, eager-loaded context, status separation, empty state, links, delivery).
- [x] 2.2 Create `OperationalDashboardWidget.php` and its Blade wrapper.
- [x] 2.3 Register the widget in `ConsultAppPanelProvider.php`.
- [x] 3.1–3.3 Verification and regression (Unit 3 PR slice).

### Workload / PR Boundary

- Mode: chained PR slice (feature-branch-chain)
- Current work unit: Unit 3 — Verification and Regression (Sprint 4)
- Boundary: starts from Unit 2's registered dashboard widget and ends with completed regression coverage in the three test files plus full-suite/Pint verification. No production code changes in Unit 3; no verification of scope violations found.
- Estimated review budget impact: Unit 1 ~180 authored lines; Unit 2 ~227 authored lines; Unit 3 ~60 authored test lines (widget 12 + lifecycle 20 + queue 28). Cumulative ~467 authored lines, within the 800-line review budget via the three-slice chain.
- Native attempt gate result: Unit 1 `changed_lines` 73, Unit 2 `changed_lines` 2, Unit 3 `changed_lines` 28 (tracked diff on `DietaryPlanQueueTest.php`) — cumulative 103 tracked lines, far under the 200-line attempt cap. The gate metric derives from tracked `git diff --numstat` between begin/finish candidate trees, so untracked new files are not counted; only tracked modifications register (Unit 3's sole tracked edit is the queue test extension).

### Status

9/9 tasks complete. Units 1, 2, and 3 implemented and green. Ready for verify.
