# Design: Operational Desk Dashboard

## Technical Approach

Add one Filament v5 `TableWidget` to the existing `consultapp` dashboard. The widget will render a bounded projection of pending `PlanAlimentario` records, a separately calculated payment-pending count, navigation actions, and a date-required delivery action. Queue and widget queries will share model scopes for status filtering and deterministic ordering; both delivery entry points will call one lifecycle service while `PlanAlimentario` remains the final invariant guard.

## Architecture Decisions

| Decision | Choice | Alternatives rejected | Rationale |
|---|---|---|---|
| Dashboard composition | One `TableWidget` with a small wrapper view for count, shortcuts, and queue link | Multiple stats/table widgets; replacing the panel Dashboard page | Satisfies the one-widget scope and preserves Filament discovery and the existing panel shell. |
| Query boundary | Add reusable `pending()` and `orderedForQueue()` scopes to `PlanAlimentario`; use eager loading of `consulta.paciente` | Duplicate closures in queue and widget | Prevents status/order drift while keeping the queue authoritative for the complete list. |
| Delivery boundary | Add a lifecycle service method used by queue and widget | Saving directly in each action; dashboard-only workflow | One transition path supplies the date and status, while the model `saving` hook remains the invariant backstop. |
| Navigation | Generate URLs through Filament resource/page URL helpers for panel `consultapp` | Hard-coded `/consultapp/...` URLs | Preserves panel prefixes and existing authentication behavior. |

## Data Flow

```text
Authenticated /consultapp
  -> OperationalDashboardWidget
     -> pending()->orderedForQueue()->limit(10)->with(consulta.paciente)
     -> paymentPending()->count()
     -> Lifecycle service -> PlanAlimentario save/invariant
```

The queue keeps the complete ordered query and uses the same delivery service. A successful widget action causes the table to refresh; delivered records therefore disappear from the pending projection and payment-pending records never enter it.

## File Changes

| File | Action | Description |
|---|---|---|
| `app/Filament/Widgets/OperationalDashboardWidget.php` | Create | Table widget, bounded pending projection, count, shortcuts, queue link, and delivery action. |
| `resources/views/filament/widgets/operational-dashboard-widget.blade.php` | Create | Widget wrapper for the payment count and navigation content around the table. |
| `app/Services/PlanAlimentarioLifecycleService.php` | Create | Shared delivered transition requiring a non-empty date. |
| `app/Models/PlanAlimentario.php` | Modify | Add status/order scopes and retain the existing date invariant. |
| `app/Filament/Pages/DietaryPlanQueue.php` | Modify | Use shared ordering/scopes and lifecycle service for delivery. |
| `app/Providers/Filament/ConsultAppPanelProvider.php` | Modify | Explicitly register the operational widget on `consultapp`. |
| `tests/Feature/Filament/OperationalDashboardWidgetTest.php` | Create | Access, projection, navigation, empty state, relation rendering, and delivery coverage. |
| `tests/Feature/Services/PlanAlimentarioLifecycleServiceTest.php` | Create | Shared transition and invalid-state immutability coverage. |

## Interfaces / Contracts

- `PlanAlimentario::pending()` and `paymentPending()` return status-filtered builders.
- `PlanAlimentario::orderedForQueue()` applies `created_at ASC`, then `id ASC`.
- The lifecycle service accepts a `PlanAlimentario` and a validated non-empty date, sets `Delivered` and the date, then saves. Invalid input is rejected before mutation; the model invariant remains authoritative.
- Widget links use `ConsultaResource`, `PacienteResource`, and `DietaryPlanQueue` URL helpers with the `consultapp` panel.

## Testing Strategy

| Layer | What to test | Approach |
|---|---|---|
| Unit/feature | Lifecycle transition and model immutability | Pest with `RefreshDatabase`, including missing-date rejection and non-delivered date clearing. |
| Feature | Auth boundary, ten-row deterministic ordering, status separation, eager-loaded context, empty state, links, and valid/invalid delivery | Livewire widget tests as used by existing Filament tests; assert database state and rendered content. |
| Regression | Queue still exposes complete lifecycle operations and shared delivery behavior | Extend the existing queue feature test without duplicating model invariant tests. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary is introduced. Existing panel authentication is reused.

## Migration / Rollout

No migration required. Register the widget after its class and view exist; rollback removes the widget, service calls, scopes, and tests, restoring the prior dashboard and queue behavior.

## Open Questions

None.
