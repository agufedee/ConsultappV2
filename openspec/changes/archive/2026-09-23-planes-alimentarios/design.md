# Design: Dietary Plan Request Queue

## Technical Approach

Keep `ConsultaResource` as the consultation-facing source of truth and add a dedicated authenticated Filament `Page` with a table for the operational queue. The page queries `PlanAlimentario` with `paciente` and `consulta`, orders by `created_at`, then `id`, and exposes status/delivery editing and the consultation-scoped download action. A small application service synchronizes the checkbox, request lifecycle, and attachment so create/update behavior is identical from the resource and queue page.

## Architecture Decisions

| Decision | Choice | Alternatives rejected and rationale |
|---|---|---|
| Queue surface | Custom Filament `DietaryPlanQueue` page implementing the existing table pattern; no navigation for `ConsultaResource` is changed. | A `PlanAlimentarioResource` duplicates the consultation form and implies independent CRUD; a relation manager is patient-scoped, not a global work queue. |
| Cardinality | Preserve `Consulta::planAlimentario(): HasOne`; add a unique `planes_alimentarios.consulta_id` index. Existing duplicate legacy rows are a migration blocker and must be reported, never silently discarded. | Redesigning to history/version records contradicts the first-slice contract. |
| Lifecycle boundary | `PlanAlimentarioStatus` backed enum plus model save invariant: only three statuses; `fecha_entrega` is required for `delivered` and cleared otherwise. The service creates one `pending` row, updates an existing row, or removes the row when `requiere_plan` is disabled. | UI-only validation is insufficient for factories, queue actions, and direct model writes. |
| Download/storage | Add `local_private` (`local`, non-served root, private visibility). A named authenticated web route accepts consultation/request identity, resolves the related plan, checks the stored path exists, and returns `response()->download(...)`; it never accepts a path or creates a URL. | Filament-only callbacks and signed/public URLs add ambiguity and do not provide a reusable fail-closed boundary. |

## Data Flow

`ConsultaResource form` → `PlanAlimentarioRequestService` → `DB transaction + PlanAlimentario` → `local_private`

For replacement: validate first, store the generated new file, persist its path, register old-file deletion after commit, then report success. On storage/database failure, delete the newly stored object and retain the old database path/object. Unmarking removes the request and its referenced private object without exposing a separate delete action.

## File Changes

| File | Action | Description |
|---|---|---|
| `app/Models/Consulta.php`, `PlanAlimentario.php` | Modify | Add fillable/casts for `requiere_plan`, `estado`, and `fecha_entrega`, status enum/invariant, and retain relationships. |
| `app/Enums/PlanAlimentarioStatus.php` | Create | `Pending`, `Delivered`, `PaymentPending` values and labels (`falta de pago`). |
| `app/Services/PlanAlimentarioRequestService.php` | Create | Atomic sync, generated private storage, replacement cleanup, unmark cleanup. |
| `app/Filament/Resources/Consultas/ConsultaResource.php` | Modify | Requires-plan and nested request fields; conditional delivery date and PDF validation (`mimetypes:application/pdf`, `max:5120`). |
| `app/Filament/Pages/DietaryPlanQueue.php` | Create | Patient-oriented deterministic table and lifecycle actions; no delete action. |
| `app/Http/Controllers/DownloadPlanAlimentarioController.php`, `routes/web.php` | Create/modify | Authenticated consultation-scoped attachment download, missing-file 404. |
| `config/filesystems.php` | Modify | Dedicated non-served `local_private` disk. |
| `database/migrations/*_add_dietary_plan_request_lifecycle.php` | Create | Add `requiere_plan`, `estado`, `fecha_entrega`, status/order index, and unique consultation constraint; reversible without changing the original migrations. |
| `tests/Feature/{Models,Filament}/`, `tests/Feature/PlanAlimentarioDownloadTest.php` | Create/modify | Lifecycle, queue order/authentication, upload validation, download, replacement, rollback, and legacy-cardinality tests. |

## Interfaces / Contracts

`sync(Consulta $consulta, array $validatedData): void` owns request creation/update/removal. The download route uses only `{consulta}/plan-alimentario/download`; authorization is authentication plus relationship resolution. No client filename or storage path is persisted.

## Testing Strategy

Use focused Pest feature tests with `RefreshDatabase`, `Storage::fake('local_private')`, `UploadedFile`, and Livewire assertions. Cover defaults, unique constraint, all status transitions, conditional `fecha_entrega` clearing, marked/unmarked consultation behavior, deterministic ties, relation-manager reuse, 5 MB/content rejection before replacement, successful replacement cleanup, unauthenticated/cross-consultation/missing-file denial, attachment disposition, and no public URL.

## Threat Matrix

N/A — no shell, subprocess, VCS, executable-file classification, or process-integration boundary; the HTTP download route is covered by authentication and scoped-record tests.

## Migration / Rollout

The additive migration must fail before adding the unique index if deployed data contains duplicate `consulta_id` rows, with an actionable duplicate report. Resolve legacy duplicates as a data-migration decision before rerunning; do not silently delete historical records. Fresh SQLite and production-compatible schema tests are required.

## Open Questions

None for this first slice. Validity fields remain stored but are not part of queue lifecycle decisions.

**Decision needed before apply:** No.
**Chained PRs recommended:** No.
**400-line budget risk:** Medium (estimated 300–390 authored lines including tests); split only if download/storage tests or Filament action wiring expands beyond the budget.
