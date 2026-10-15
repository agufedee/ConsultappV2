# Exploration: escritorio-operativo

status: success
executive_summary: The `/consultapp` dashboard can become a small operational desk without creating a second dietary-plan workflow. The smallest coherent MVP is a dashboard widget that shows the oldest pending requests, a separate payment-pending count, and three resource-based shortcuts; the existing queue remains the source of truth for lifecycle actions.
artifacts:
  - openspec/changes/escritorio-operativo/exploration.md
  - Engram topic `sdd/escritorio-operativo/explore`
next_recommended: sdd-propose
risks: Query duplication, unbounded queue rendering, Filament widget API details, and the current absence of domain-specific authorization policies require explicit treatment in proposal/design.
skill_resolution: paths-injected — sdd-explore, laravel-best-practices, and pest-testing were loaded before investigation.

## Current State

- `ConsultAppPanelProvider` configures the authenticated `consultapp` panel at `/consultapp`, discovers resources, pages, and widgets, and explicitly registers `Dashboard` and `AccountWidget`. There is no operational dashboard widget today.
- `DietaryPlanQueue` is the existing authenticated operational surface at `/consultapp/planes-alimentarios`. Its table eager-loads `consulta.paciente`, orders by `created_at` and `id` ascending, displays lifecycle state, and owns delivery, payment-pending, and download actions.
- The queue currently queries all `PlanAlimentario` rows. It does not expose a reusable query/scope, so a dashboard implementation must extract the shared ordering/filter boundary rather than copy the inline query.
- `PlanAlimentarioStatus` already defines exactly `pending`, `delivered`, and `payment_pending`, with the user-facing label `Falta de pago` for the latter. `PlanAlimentario` casts the enum and enforces delivery-date invariants in its model save hook.
- `PacienteResource` exposes searchable `nombre`, `apellido`, and `dni` fields plus `index` and `create` URLs. `ConsultaResource` exposes `index` and `create` URLs but intentionally does not register navigation. Existing pages use Filament resource routes rather than hand-built URLs.
- `PatientWeightEvolutionChart` is the only custom widget and is explicitly undiscovered, so it establishes no dashboard-widget layout convention. Existing tests use Pest, `RefreshDatabase`, authenticated users, and Livewire component assertions.
- Current installed versions are Laravel 13.26.1, Filament 5.8.2, Pest 5.1.1, and PHP compatible with the repository's `^8.3` constraint. The OpenSpec project context documents Filament v5 and Pest feature testing.

## Affected Areas

- `app/Providers/Filament/ConsultAppPanelProvider.php` — register the operational widget explicitly or rely on widget discovery; explicit registration is safer if the widget should only belong to this panel.
- `app/Filament/Widgets/` — add the dashboard widget (or the smallest set of native Filament widgets) for pending plans, the payment-pending count, and shortcuts.
- `app/Filament/Pages/DietaryPlanQueue.php` — consume the shared queue query/order boundary so the dashboard cannot drift from the full queue.
- `app/Models/PlanAlimentario.php` or a narrowly scoped query class — provide reusable `pending` and deterministic operational ordering behavior without moving UI actions into the model.
- `app/Filament/Resources/Pacientes/PacienteResource.php` and `app/Filament/Resources/Consultas/ConsultaResource.php` — reuse `getUrl('index'|'create')` for shortcuts; no new resource routes are needed.
- `tests/Feature/Filament/` — add focused dashboard/widget coverage alongside the existing `DietaryPlanQueueTest.php`, `PacienteResourceTest.php`, and `ConsultaResourceTest.php` conventions.
- `openspec/specs/filament-panel/spec.md` and/or a new dashboard domain delta during `sdd-spec` — document the operational desk contract without changing the archived dietary-plan queue semantics.

## Approaches

### 1. One custom operational dashboard widget with a shared query boundary — Recommended

Create one dashboard widget/view that renders the oldest pending rows, a distinct `payment_pending` statistic, and three action links. Extract the deterministic queue ordering into a reusable scope/query method and use it from both the widget and `DietaryPlanQueue`; compute the payment count with a database `count()` query and eager-load `consulta.paciente` for displayed rows.

- Pros: smallest dashboard surface; one registration point; keeps the full queue and dashboard aligned; shortcuts reuse existing resource routes; no new domain workflow.
- Cons: custom view markup and Filament widget API details need focused tests; the product must choose a display limit or pagination policy.
- Effort: Low/Medium.

### 2. Compose native Filament widgets: table widget, stats widget, and actions widget

Use Filament-native widget types for the pending table, payment-pending stat, and shortcuts, each with its own registration and tests.

- Pros: follows Filament primitives; table pagination, columns, and stats semantics are explicit.
- Cons: more classes and layout coordination; query/filter behavior can be duplicated across widgets unless the shared query boundary is still extracted; likely larger review surface.
- Effort: Medium.

### 3. Embed the existing `DietaryPlanQueue` table on the dashboard

Reuse the page's table directly or turn it into a shared table builder used by both surfaces.

- Pros: maximum visual and behavioral reuse.
- Cons: the existing page is a full lifecycle page, not a dashboard summary; extracting its table can couple navigation/actions to the dashboard and makes the separate payment count and shortcuts awkward. It risks turning a small MVP into a page/widget abstraction refactor.
- Effort: Medium/High.

## Recommendation

Use approach 1. Add a single read-oriented operational widget, keep delivery/payment/download actions on `DietaryPlanQueue`, and link the dashboard list to that page rather than adding duplicate row actions. The widget should:

1. Filter strictly to `PlanAlimentarioStatus::Pending`.
2. Order by `created_at ASC`, then `id ASC`, using the same reusable boundary as the full queue.
3. Eager-load `consulta.paciente`; render patient, consultation context, and request age/date.
4. Show `PlanAlimentarioStatus::PaymentPending` through a separate count, never mixed into the pending list.
5. Link the pending list to `DietaryPlanQueue` and use `ConsultaResource::getUrl('create')`, `PacienteResource::getUrl('create')`, and `PacienteResource::getUrl('index')` for shortcuts.
6. Treat “search patients” as navigation to the existing searchable patient list. Do not introduce URL-encoded table state or a new search flow in the MVP.

For an operational dashboard, show the oldest bounded slice (recommended: 10 rows) and provide a clear link to the full queue. If the product explicitly requires every pending row on the dashboard, use pagination instead of loading an unbounded collection. This is the main product choice to confirm in the proposal; the data contract itself is unambiguous.

The shared query should remain application-facing and testable. A model scope is the smallest fit if it only expresses status/order; a dedicated query object is unnecessary unless future dashboard filters or multiple queue projections appear. Do not move delivery/payment transitions into the widget.

## Product Decisions and Edge Cases

- `pending` and `payment_pending` are separate operational concepts. The pending list MUST exclude delivered and payment-pending rows; the payment counter MUST count only `payment_pending` rows.
- Ordering MUST be deterministic: oldest `created_at` first, with ascending `id` as the tie-breaker. This matches the archived queue contract.
- A missing patient/consultation relationship should not be silently hidden. The proposal should choose fail-closed rendering or an explicit fallback label; the current foreign-key relationship makes this mainly a defensive edge case.
- Empty states should be explicit: no pending plans and zero payment-pending plans should still leave shortcuts available.
- The dashboard must not expose lifecycle mutation actions, download paths, or public URLs. Users should follow the authenticated queue link for those actions.
- The current panel authenticates users but has no domain policy layer. For this MVP, authorization is the same authenticated Filament-panel boundary already protecting the queue and resources; introducing role-based visibility is out of scope unless a policy requirement emerges.
- Soft-deleted patients are excluded by the normal Eloquent relationship. The dashboard should not bypass that behavior with raw joins.
- Query efficiency matters because the dashboard loads on every panel visit: use `count()` for payment-pending, select only required columns where practical, eager-load displayed relations, and avoid per-row relationship queries.
- Filament discovery versus explicit widget registration is a configuration choice. Explicit registration in `ConsultAppPanelProvider` reduces accidental exposure to other panels and makes the dashboard contract visible.

## Recommended Pest Scenarios

Add a focused `tests/Feature/Filament/OperationalDeskTest.php` (name can be refined during tasks) using the existing authenticated Livewire pattern:

1. Unauthenticated dashboard access redirects to panel authentication.
2. Authenticated dashboard renders the operational widget and all three shortcut links.
3. Pending rows are visible in oldest-first order, including the `created_at`/`id` tie-breaker.
4. Delivered and payment-pending rows are absent from the pending list.
5. The payment-pending count is separate and counts only `payment_pending` records.
6. Empty pending/payment-pending states render a stable empty state and preserve shortcuts.
7. The pending row links to the existing dietary-plan queue rather than exposing duplicate lifecycle actions.
8. The patient-search shortcut points to the existing patient index, whose existing tests continue to prove search by name, surname, and DNI.
9. A focused query test or Livewire assertion proves patient/consultation context is rendered without introducing N+1 behavior; avoid brittle exact query-count assertions unless the project already standardizes them.

Existing `DietaryPlanQueueTest.php` remains the authority for queue lifecycle behavior and should not be duplicated in dashboard tests.

## Risks

- Copying the queue's inline query into a widget would allow ordering or state filters to diverge; extract and reuse the shared query boundary.
- Rendering an unbounded pending collection can make every dashboard visit expensive; cap or paginate the dashboard projection.
- Filament v5 widget/table APIs can differ between minor versions; implementation must verify the installed 5.8.2 API before coding and keep the custom surface narrow.
- A dashboard-only count or list could accidentally include soft-deleted patient contexts or trigger N+1 queries if relationships are not eager-loaded.
- A “search patients” shortcut cannot reliably pre-open Filament's table search state without coupling to internal Livewire state; linking to the existing searchable list is safer.
- There are no role-specific policies. If staff roles are introduced later, widget visibility and queue authorization must be reviewed together.
- The dashboard changes the primary landing experience, so labels, empty-state copy, and whether ten rows is sufficient are product usability decisions not fully specified by the request.

## Ready for Proposal

Yes. The technical direction is sufficiently constrained for `sdd-propose`. The proposal should record the bounded-list versus full-list choice, the shared query boundary, the authenticated-panel authorization assumption, and the explicit exclusion of duplicate lifecycle actions. No implementation code has been changed.
