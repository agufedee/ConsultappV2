# Tasks: Operational Desk Dashboard

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 450–600 authored lines |
| Session review budget (800 lines) | Within budget, but above the SDD 400-line guard |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1: lifecycle/query foundation → PR 2: dashboard widget and panel wiring → PR 3: focused regression coverage |
| Delivery strategy | ask-on-risk |
| Chain strategy | feature-branch-chain |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|---|---|---|---|---|---|
| 1 | Shared scopes and lifecycle transition | PR 1 | `php artisan test --compact --filter=PlanAlimentarioLifecycleServiceTest` | `php artisan test --compact --filter=PlanAlimentarioTest` | Revert service, model scopes, and queue call-site changes |
| 2 | Operational dashboard and registration | PR 2 | `php artisan test --compact --filter=OperationalDashboardWidgetTest` | Authenticated `/consultapp` browser scenario | Remove widget/view and panel registration |
| 3 | Queue regression and final integration | PR 3 | `php artisan test --compact --filter='DietaryPlanQueueTest|OperationalDashboardWidgetTest'` | `php artisan test --compact` | Revert only regression assertions and integration adjustments |

## Phase 1: Foundation (Sprint 3)

- [x] 1.1 RED: Add Pest cases for `pending()`, `paymentPending()`, deterministic `orderedForQueue()`, delivered-date rejection, non-delivered date clearing, and non-blocking `payment_pending` behavior.
- [x] 1.2 Add `PlanAlimentario` scopes and `PlanAlimentarioLifecycleService`; preserve every existing queue status transition and keep the model saving invariant authoritative.
- [x] 1.3 Update `DietaryPlanQueue.php` to use shared scopes/service without changing complete-list behavior or existing transitions.

## Phase 2: Dashboard Implementation (Sprint 3)

- [x] 2.1 RED: Add dashboard feature cases for auth boundary, ten-row ordering, eager-loaded patient/consultation context, status separation, empty state, links, and invalid/valid delivery.
- [x] 2.2 Create `OperationalDashboardWidget.php` and its Blade wrapper with bounded pending data, separate payment count, shortcuts, queue link, and required-date delivery action.
- [x] 2.3 Register the widget in `ConsultAppPanelProvider.php` using the existing `consultapp` panel authentication and URL helpers.

## Phase 3: Verification and Regression (Sprint 4)

- [x] 3.1 Complete `OperationalDashboardWidgetTest.php` and `PlanAlimentarioLifecycleServiceTest.php`; prove invalid submissions do not mutate records.
- [x] 3.2 Extend `DietaryPlanQueueTest.php` for shared delivery behavior and verify delivered/payment-pending records remain excluded from the pending projection.
- [x] 3.3 Run focused Pest tests, full `php artisan test --compact`, and Pint; confirm no migration, dependency, public URL, payment, or role-policy work was introduced.

Threat-matrix rows are all N/A in the design; no additional threat RED tests are required.
