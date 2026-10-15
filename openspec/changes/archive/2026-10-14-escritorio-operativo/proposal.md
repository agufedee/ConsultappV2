# Proposal: Operational Desk Dashboard

## Intent

Turn `/consultapp` into an operational desk: show ten pending plans, separate payment-pending work, preserve shortcuts, and allow direct delivery. The existing queue remains the source of truth.

## Scope

### In Scope
- One Filament v5 dashboard widget with `pending` plans ordered by `created_at ASC`, then `id ASC`, limited to 10.
- A separate `payment_pending` count, never mixed into the pending list.
- Existing new-consultation, new-patient, and searchable-patient shortcuts.
- A dashboard delivery action requiring an actual delivery date. It must apply the existing `PlanAlimentario` status/date invariant (`Delivered` requires a date and non-delivered states clear it) and reuse the queue’s lifecycle semantics, not introduce a second workflow.
- A link to the full queue, authoritative for lifecycle and complete-list operations.
- Bounded queries, eager-loaded context, and empty states.

### Out of Scope / Non-goals
- New payment, download, search, patient-facing, public-URL, role-policy, migration, or dependency work.
- A dashboard-only delivery model or status vocabulary.
- Changes to queue semantics or patient search behavior.

## Capabilities

### New Capabilities
- `operational-dashboard`: Pending-plan visibility, separate count, shortcuts, queue navigation, and direct delivery.

### Modified Capabilities
- `filament-panel`: Add the authenticated dashboard contract.
- `dietary-plan-request-queue`: Reuse lifecycle invariant and source-of-truth semantics.

## Approach

Register a widget on `consultapp`. Share its status/order query boundary with `DietaryPlanQueue`. The delivery action requires a date and delegates the queue’s `PlanAlimentario` transition/invariant; failed validation leaves the record unchanged. Reuse resource URLs and panel authentication.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `app/Filament/Widgets/` | New | Dashboard widget and delivery action. |
| `app/Providers/Filament/ConsultAppPanelProvider.php` | Modified | Explicit widget registration. |
| `app/Filament/Pages/DietaryPlanQueue.php`, `app/Models/PlanAlimentario.php` | Modified | Shared query and lifecycle boundary. |
| `tests/Feature/Filament/` | New/Modified | Dashboard and lifecycle coverage. |

## Risks, Rollback, and Success

| Risk | Mitigation |
|---|---|
| Action diverges from queue semantics | Reuse the existing transition/invariant. |
| Query drift or N+1 behavior | Share the query boundary, eager-load relations, and use `count()`. |
| Review scope exceeds 400 lines | Keep one widget; split before apply if forecast is high. |

Rollback removes the widget/action and restores the prior queue query; no migration is required.

- [ ] Dashboard shows the oldest 10 pending plans, separate payment-pending count, shortcuts, and queue link.
- [ ] Delivery requires a non-empty date; invalid submission does not mutate the plan, while valid submission persists `Delivered` with that date through queue semantics.
- [ ] Delivered and payment-pending plans are excluded from the pending list after refresh.
- [ ] Focused Pest coverage proves access, ordering, separation, empty states, links, delivery validation, and relation rendering without duplicating queue lifecycle coverage.
