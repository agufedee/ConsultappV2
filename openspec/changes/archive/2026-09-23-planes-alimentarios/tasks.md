# Tasks: Dietary Plan Request Queue

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | 340–390 authored lines (application, tests, migration, config) |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR; keep the three work units as reviewable commits |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|---|---|---|---|---|---|
| 1 | Schema, enum, models, sync service | Single PR | `php artisan test --compact tests/Feature/Models/PlanAlimentarioTest.php tests/Feature/Services/PlanAlimentarioRequestServiceTest.php` | Migration fresh/rollback plus duplicate preflight | Migration, enum, models, service, and their tests |
| 2 | Consultation form and queue workflow | Single PR | `php artisan test --compact tests/Feature/Filament/ConsultaResourceTest.php tests/Feature/Filament/DietaryPlanQueueTest.php` | Authenticated Filament panel: mark, update, unmark | Resource fields, queue page, and workflow tests |
| 3 | Private storage and download boundary | Single PR | `php artisan test --compact tests/Feature/PlanAlimentarioDownloadTest.php` | Authenticated and unauthenticated download scenarios | Disk, route/controller, attachment tests |

## Phase 1: Foundation — `feature/database-schema`

- [x] 1.1 **RED:** Add Pest tests for `requiere_plan`, enum/defaults, delivery invariant, unique/index constraints, cascade, and focused rollback using existing model/factory patterns.
- [x] 1.2 Before adding the unique index, make the additive migration report duplicate `consulta_id` rows with actionable IDs and abort; never delete or merge legacy data. Then add reversible lifecycle columns/indexes and test clean SQLite plus production-compatible schema behavior.
- [x] 1.3 **RED → GREEN:** Add `PlanAlimentarioStatus`, casts/fillable fields, `Consulta` control, and `PlanAlimentario` save invariant; implement `PlanAlimentarioRequestService::sync()` for create/update/unmark transaction boundaries and factory defaults.

## Phase 2: Consultation Workflow — `feature/setup-filament`

- [x] 2.1 **RED:** Add Livewire/Pest scenarios for marked/unmarked consultation, existing-row update, relation-manager reuse, conditional delivery date, invalid PDF rejection, and deterministic queue ties.
- [x] 2.2 **GREEN:** Extend `ConsultaResource` form without duplicating the relation-manager schema; delegate lifecycle/attachment changes to `PlanAlimentarioRequestService::sync()` and preserve all existing consultation fields.
- [x] 2.3 **GREEN:** Create authenticated `App\Filament\Pages\DietaryPlanQueue` with eager-loaded patient/consultation context, `created_at ASC, id ASC` ordering, status/payment-pending actions, delivery-date validation, attachment availability, and no delete action. Verify unauthenticated redirect and no out-of-scope portal/billing/email behavior.

## Phase 3: Private Attachment Boundary — `feature/setup-filament`

- [x] 3.1 **RED:** Add `Storage::fake('local_private')` tests for content/5 MB rejection before replacement, successful replacement cleanup, storage/database rollback retaining the old object, missing-file 404, cross-consultation denial, authentication, attachment disposition, and absence of public URLs.
- [x] 3.2 **GREEN:** Configure non-served `local_private`; implement generated-path storage and after-commit cleanup in `PlanAlimentarioRequestService`; create authenticated `DownloadPlanAlimentarioController` and named `{consulta}/plan-alimentario/download` route that resolves records only.

## Phase 4: Verification and Refactor

- [x] 4.1 Run focused tests, then `php artisan test --compact` and `vendor/bin/pint --dirty --format agent`; confirm every spec scenario and migration duplicate blocker is covered.
- [x] 4.2 Refactor only after GREEN: retain one active row per consultation, no recurring/history semantics, no manual deletion UI, and document runtime/rollback evidence in the apply receipt.
