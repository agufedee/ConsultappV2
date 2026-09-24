# Proposal: Dietary Plan Request Queue

## Intent

Make dietary plans an operational work queue. Staff intentionally marks that a patient requires a plan, works requests in order, and tracks delivery/payment state.

## Scope

### In Scope
- Optional consultation control; unmarked consultations create no request.
- Patient-oriented queue with patient/consultation context and deterministic ordering.
- Statuses: `pending`, `delivered`, `payment_pending` (falta de pago).
- User-entered delivery date at actual delivery time.
- Optional private PDF: authenticated panel users, attachment download, maximum 5 MB, replacement cleanup.
- Existing descriptive/calorie data where compatible.

### Out of Scope
- Patient portal, external sharing, public URLs, email delivery, billing, or automatic plan generation.
- Manual deletion UI.
- Historical versioning beyond retaining/replacing the optional PDF; lifecycle retention remains unresolved.

## Capabilities

### New Capabilities
- `dietary-plan-request-queue`: Optional requests, queue, statuses, delivery date, and private attachments.

### Modified Capabilities
- `consulta-resource`: Optional requires-plan control and request state.
- `domain-models`: Request status and delivery lifecycle.
- `database-migrations`: Additive request fields and constraints.
- `filament-panel`: Authenticated queue and authorized downloads.

## Approach

Extend the `Consulta`/`PlanAlimentario` boundary and consultation resource context. Add lifecycle data and queue UI; store generated PDF paths on non-served `local_private`. Validate content and size, authorize by authenticated consultation scope, force attachment disposition, and remove the previous object on replacement. Prefer one active request per consultation, but confirm whether future requests are new records or updates.

## Affected Areas

| Area | Impact |
|---|---|
| `app/Filament/Resources/Consultas/` | Request control, queue, statuses, delivery actions |
| `app/Models/`, `database/migrations/` | Lifecycle model and additive schema |
| `config/filesystems.php`, download boundary | Private storage and scoped downloads |
| `tests/Feature/` | Queue, transitions, storage, authorization, cleanup |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| `HasOne` conflicts with future history | High | Confirm cardinality before design. |
| PDF exposure or orphaned files | High | Private disk, scoped authorization, generated paths, cleanup tests. |
| Ambiguous queue order | Med | Specify deterministic ordering. |

## Rollback Plan

Revert queue UI/actions and additive migrations; remove only private objects referenced by this feature. Preserve existing consultation/plan data and disable queue routes before destructive schema removal.

## Proposal Question Round

- One active request per consultation, or recurring requests?
- Is delivery date required exactly for `delivered`?
- Does `payment_pending` block delivery or only flag payment?
- Should validity dates remain now or wait?

## Success Criteria

- [ ] Staff intentionally creates requests and sees a patient-oriented queue.
- [ ] Staff changes approved statuses and records actual delivery date.
- [ ] PDFs are private, authenticated, attachment downloads, ≤5 MB, and replaceable safely.
- [ ] Unmarked consultations create no request.
