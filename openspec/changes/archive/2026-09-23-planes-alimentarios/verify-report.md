```yaml
schema: gentle-ai.verify-result/v1
evidence_revision: sha256:3774739d8175db2e624969a069759b5d8e0a832cf41ae6f2b5dcff6dc64ed365
verdict: pass
blockers: 0
critical_findings: 0
requirements: 12/12
scenarios: 36/36
test_command: php artisan test --compact
test_exit_code: 0
test_output_hash: sha256:0f8355c9e143a18329359d0dcdd5d534a0234353067aab5dcadeda4129a6aa78
build_command: vendor/bin/pint --dirty --format agent
build_exit_code: 0
build_output_hash: sha256:cd1a94fc2cf6a965b86e1a4809d6c7fb9148b1ee374e1010ed2ac96ff4876ec2
```

## Verification Report

**Change**: planes-alimentarios
**Version**: N/A (delta specs, 5 files — ADDED/MODIFIED)
**Mode**: Strict TDD
**Verdict**: PASS

### Completeness
| Metric | Value |
|--------|-------|
| Core implementation tasks (1.1–3.2) | 8 complete / 8 total |
| Finalization tasks (4.1, 4.2) | 2 executed by this verify phase (focused + full suite + Pint + duplicate-blocker/scenario confirmation + evidence documented here) |
| Tasks incomplete | 0 |

### Build & Tests Execution
**Build (Pint)**: ✅ Passed — `vendor/bin/pint --dirty --format agent` exit 0, `{"tool":"pint","result":"passed"}`, no files modified.

**Focused tests** (change-related files): ✅ 86 passed / 86 (312 assertions), exit 0
```text
Command: php artisan test --compact tests/Feature/Models/PlanAlimentarioTest.php tests/Feature/Models/ConsultaTest.php tests/Feature/Services/PlanAlimentarioRequestServiceTest.php tests/Feature/Database/PlanAlimentarioRequestLifecycleMigrationTest.php tests/Feature/Filament/ConsultaResourceTest.php tests/Feature/Filament/ConsultasRelationManagerTest.php tests/Feature/Filament/DietaryPlanQueueTest.php tests/Feature/PlanAlimentarioDownloadTest.php
Output hash: sha256:5bc5606099308e31fae7eb64de8f982f78f42272407319f13eb52f86eaaaad0c
```

**Full suite**: ✅ 131 passed / 131 (441 assertions), exit 0
```text
Command: php artisan test --compact
Output hash: sha256:0f8355c9e143a18329359d0dcdd5d534a0234353067aab5dcadeda4129a6aa78
Result: {"tool":"pest","result":"passed","tests":131,"passed":131,"assertions":441}
```
Matches the apply-phase snapshot (131/441 GREEN) — no regression between apply and verify.

**Coverage**: ➖ Not available — no Xdebug/PCOV detected in the environment. Coverage analysis skipped (informational, not blocking).

### Spec Compliance Matrix
| Requirement | Scenario | Test | Result |
|-------------|----------|------|--------|
| Intentional Request Creation and Queue | Marked consultation enters queue | `PlanAlimentarioRequestServiceTest > creates a pending request when the consultation is marked`; `ConsultaResourceTest > creates a pending plan alimentario request when the requires-plan control is enabled` | ✅ COMPLIANT |
| Intentional Request Creation and Queue | Unmarked consultation creates nothing | `PlanAlimentarioRequestServiceTest > creates nothing for an unmarked consultation`; `ConsultaResourceTest > leaves no request when the requires-plan control stays disabled` | ✅ COMPLIANT |
| Intentional Request Creation and Queue | Queue order is deterministic | `DietaryPlanQueueTest > orders requests by creation time then identifier ascending` (two ties asserted in order) | ✅ COMPLIANT |
| Request Lifecycle | New request is pending | `PlanAlimentarioRequestServiceTest > creates a pending request...`; `PlanAlimentarioTest > factory defaults a new request to pending without a delivery date` | ✅ COMPLIANT |
| Request Lifecycle | Delivered requires actual date | `PlanAlimentarioRequestServiceTest > updates the existing request in place`; `PlanAlimentarioTest > a delivered request persists with its delivery date`; `DietaryPlanQueueTest > delivers a request with an actual delivery date` | ✅ COMPLIANT |
| Request Lifecycle | Invalid delivered transition is rejected | `PlanAlimentarioRequestServiceTest > requires a delivery date when delivering through the service`; `ConsultaResourceTest > requires a delivery date when the status is delivered`; `DietaryPlanQueueTest > rejects delivering without an actual delivery date` | ✅ COMPLIANT |
| Request Lifecycle | Payment pending is non-blocking | `DietaryPlanQueueTest > moves a delivered request to payment pending without billing`; `PlanAlimentarioTest > delivery date is cleared when the request leaves delivered` | ✅ COMPLIANT |
| Optional Private PDF | Valid PDF is private and downloadable | `PlanAlimentarioDownloadTest > downloads the attachment for an authenticated user with attachment disposition`; `uses generated paths, never the client filename`; `exposes no public url for the private disk` | ✅ COMPLIANT |
| Optional Private PDF | Invalid PDF is rejected | `PlanAlimentarioDownloadTest > rejects a non-pdf replacement before touching the existing file`; `rejects an oversized replacement before touching the existing file`; `ConsultaResourceTest > rejects a non-pdf attachment before persisting it`; `rejects an oversized attachment without replacing the existing one` | ✅ COMPLIANT |
| Optional Private PDF | Replacement cleans up old object | `PlanAlimentarioDownloadTest > deletes the replaced object only after a successful replacement` | ✅ COMPLIANT |
| Optional Private PDF | Unauthorized or missing file is denied | `PlanAlimentarioDownloadTest > requires a panel user to download an attachment`; `returns 404 when the stored object is missing`; `returns 404 when the consultation has no request`; `denies an attachment download across consultations` | ✅ COMPLIANT |
| First-Slice Lifecycle Boundary | Existing request is updated | `PlanAlimentarioRequestServiceTest > updates the existing request in place` (count stays 1); `ConsultaResourceTest > updates the existing request in place when editing a marked consultation` | ✅ COMPLIANT |
| Requires-Plan Control | Relation manager uses the control | `ConsultasRelationManagerTest > applies the requires-plan control from the relation manager create action` (same contract, no duplicated schema — `delegates the table configuration to the ConsultaResource`) | ✅ COMPLIANT |
| Requires-Plan Control | Delivery date is conditional | `ConsultaResourceTest > clears the delivery date when the status leaves delivered`; `requires a delivery date when the status is delivered`; model invariant clears on non-delivered | ✅ COMPLIANT |
| Requires-Plan Control | PDF validation is enforced in the form | `ConsultaResourceTest > rejects a non-pdf attachment before persisting it`; `rejects an oversized attachment without replacing the existing one` | ✅ COMPLIANT |
| Consulta Queue Context | Queue identifies patient and consultation | `DietaryPlanQueueTest > shows the patient and consultation context for each pending request` (Ana Gomez + Control); `orders requests by creation time then identifier ascending` | ✅ COMPLIANT |
| Consulta Model | Existing Consulta behavior remains usable | `ConsultaTest > consulta can be created via factory`; `consulta belongs to paciente` | ✅ COMPLIANT |
| Consulta Model | Belongs to Paciente | `ConsultaTest > consulta belongs to paciente` | ✅ COMPLIANT |
| Consulta Model | Has one plan alimentario | `ConsultaTest > consulta has one plan alimentario` | ✅ COMPLIANT |
| Consulta Model | Soft delete preserves consultas | `ConsultaTest > soft delete preserves consultas` | ✅ COMPLIANT |
| Consulta Model | Unmarked Consulta has no request | `ConsultaTest > an unmarked consulta has no plan alimentario request` | ✅ COMPLIANT |
| PlanAlimentario Model | Pending request casts correctly | `PlanAlimentarioTest > factory defaults a new request to pending without a delivery date` | ✅ COMPLIANT |
| PlanAlimentario Model | Model exists and is usable | `PlanAlimentarioTest > plan alimentario can be created via factory` | ✅ COMPLIANT |
| PlanAlimentario Model | Belongs to Consulta | `PlanAlimentarioTest > plan alimentario belongs to consulta` | ✅ COMPLIANT |
| PlanAlimentario Model | Cascade delete via relationship | `PlanAlimentarioTest > cascade delete via consulta` (FK `cascadeOnDelete` inherited from immutable original migration) | ✅ COMPLIANT |
| PlanAlimentario Model | Invalid lifecycle state is rejected | `PlanAlimentarioTest > a delivered request without a delivery date is rejected` (LogicException, zero rows persisted) | ✅ COMPLIANT |
| Planes Alimentarios Table Migration | Lifecycle columns exist | `PlanAlimentarioRequestLifecycleMigrationTest > adds the lifecycle columns with working database defaults` (`requiere_plan`=0, `estado`='pending', `fecha_entrega`=null) | ✅ COMPLIANT |
| Planes Alimentarios Table Migration | Table exists with correct columns | `PlanAlimentarioRequestLifecycleMigrationTest > keeps every existing planes_alimentarios column when the lifecycle migration runs`; `applies the lifecycle migration on a fresh database` | ✅ COMPLIANT |
| Planes Alimentarios Table Migration | FK cascade on consulta delete | `PlanAlimentarioTest > cascade delete via consulta` (mechanism proven; original migration immutable) | ✅ COMPLIANT |
| Planes Alimentarios Table Migration | One active request is enforced | `PlanAlimentarioRequestLifecycleMigrationTest > rejects a second active request for the same consulta` (QueryException, count stays 1); `PlanAlimentarioTest > a second active request for the same consulta is rejected` | ✅ COMPLIANT |
| Planes Alimentarios Table Migration | Rollback is focused | `PlanAlimentarioRequestLifecycleMigrationTest > rolls back only the lifecycle schema and preserves existing data` (columns/index removed, consulta and plan rows preserved) | ✅ COMPLIANT |
| Queue Ordering Indexes | Pending queue query is indexable | `PlanAlimentarioRequestLifecycleMigrationTest` rollback reverse-drops the index; `DietaryPlanQueueTest > orders requests by creation time then identifier ascending` exercises the query; migration adds `index(['estado','created_at'])` (direct index-existence assertion missing — SUGGESTION) | ✅ COMPLIANT |
| Authenticated Dietary Plan Queue | Unauthenticated queue access is blocked | `DietaryPlanQueueTest > redirects unauthenticated visitors to panel authentication` | ✅ COMPLIANT |
| Authenticated Dietary Plan Queue | Authenticated staff works queue | `DietaryPlanQueueTest > shows the patient and consultation context...`; `delivers a request with an actual delivery date`; `moves a delivered request to payment pending without billing`; `provides no manual deletion action` | ✅ COMPLIANT |
| Consultation-Scoped PDF Download Authorization | Cross-consultation access is denied | `PlanAlimentarioDownloadTest > denies an attachment download across consultations` (404, no bytes; plan resolved only through the requested consulta) | ✅ COMPLIANT |
| Consultation-Scoped PDF Download Authorization | Download is an attachment | `PlanAlimentarioDownloadTest > downloads the attachment for an authenticated user with attachment disposition` (`assertDownload`, `Content-Type: application/pdf`, private disk has no `url`/`serve`) | ✅ COMPLIANT |

**Compliance summary**: 36/36 scenarios compliant, 12/12 requirements complete.

### Correctness (Static Evidence)
| Requirement | Status | Notes |
|------------|--------|-------|
| Intentional Request Creation and Queue | ✅ Implemented | `sync()` creates one row only when `requiere_plan`; queue eager-loads `consulta.paciente`, orders `created_at ASC, id ASC` |
| Request Lifecycle | ✅ Implemented | Backed enum restricted to 3 values; `Delivered` requires `fecha_entrega` else `LogicException` (model `saving` invariant); non-delivered clears it; payment_pending non-blocking; badge label `Falta de pago` |
| Optional Private PDF | ✅ Implemented | `local_private` disk (local, non-served, no `url`/`serve`); ULID-generated paths (Filament default); form validation `acceptedFileTypes(application/pdf)` + `maxSize(5120)`; after-commit old-object cleanup; rollback branch deletes the newly stored object; unmark removes stored object |
| First-Slice Lifecycle Boundary | ✅ Implemented | Unique `consulta_id` index; `HasOne` preserved; no history semantics |
| Requires-Plan Control | ✅ Implemented | `Toggle requiere_plan` live + conditional 'Plan alimentario' Section; `CreateConsulta::afterCreate` / `EditConsulta::afterSave` / `ConsultasRelationManager CreateAction::after` all delegate to `sync()`; `mutateFormDataBeforeFill` prefills plan fields |
| Consulta Queue Context | ✅ Implemented | Queue page slug `planes-alimentarios`; patient name, consultation motive, status badge, delivery date, attachment availability columns; deterministic order |
| Consulta Model | ✅ Implemented | `requiere_plan` boolean cast + fillable; relationships intact |
| PlanAlimentario Model | ✅ Implemented | status/delivery casts; save invariant; descriptive/calorie fields retained |
| Planes Alimentarios Table Migration | ✅ Implemented | Additive reversible migration; duplicate-`consulta_id` preflight aborts with actionable IDs before adding the unique index; never deletes legacy data; original migrations untouched |
| Queue Ordering Indexes | ✅ Implemented | `(estado, created_at)` index added; no unrelated constraints changed |
| Authenticated Dietary Plan Queue | ✅ Implemented | Page discovered in `consultapp` panel behind `authMiddleware(Authenticate)`; deliver + markPaymentPending actions; no delete/portal/billing/email actions |
| Consultation-Scoped PDF Download Authorization | ✅ Implemented | Named route `{consulta}/plan-alimentario/download` + `FilamentAuthenticate`; record-only resolution; 404 on no-plan/blank-path/missing-object; `StreamedResponse` with attachment disposition; never accepts a path/URL |

### Coherence (Design)
| Decision | Followed? | Notes |
|----------|-----------|-------|
| Custom `DietaryPlanQueue` page; no `ConsultaResource` navigation change | ✅ Yes | `shouldRegisterNavigation = false` on the resource; queue is a discovered panel page |
| Preserve `Consulta::planAlimentario(): HasOne` + unique `consulta_id` index; duplicates reported, never silently discarded | ✅ Yes | Migration preflight throws `RuntimeException` listing duplicate consulta IDs and aborts |
| Backed enum + model save invariant + service `sync()` create/update/unmark | ✅ Yes | Invariant in `booted()`; `sync()` in a transaction |
| `local_private` non-served disk + authenticated record-resolving download route | ✅ Yes | No `url`/`serve` on the disk; route resolves `Consulta` only |
| Replacement: validate → store new → persist → delete old after commit; on failure delete new, keep old | ✅ Yes | `DB::afterCommit` old-file cleanup; catch-branch deletes newly stored file on `LogicException` |
| Unmark removes request row + its private object without a delete UI | ✅ Yes | `sync()` deletes row + after-commit file removal; queue exposes no delete action |

Deviations (verified in-scope):
- Queue `download` recordAction added — design.md explicitly requires the consultation-scoped download action on the queue page; specs require the "download" scenarios. Not out of scope.
- Controller returns `StreamedResponse` (Laravel 13 `FilesystemAdapter::download()` signature), not `BinaryFileResponse`. Version-correct.

### Out-of-Scope Verification (proposal)
| Constraint | Status | Evidence |
|------------|--------|----------|
| No manual deletion UI | ✅ | `DietaryPlanQueueTest > provides no manual deletion action`; queue actions are deliver/markPaymentPending/download only |
| No portal / public URLs | ✅ | Private disk has no `url`/`serve` (test `exposes no public url for the private disk`); only authenticated route exists |
| No email | ✅ | No mail imports/calls in changed files (source inspection) |
| No billing | ✅ | `DietaryPlanQueueTest > moves a delivered request to payment pending without billing`; no billing code paths |
| No auto-generation | ✅ | Request only created on mark via `sync()`; no schedulers/jobs/observers auto-create |
| Unmarked consultations create no request | ✅ | Service + form + model tests (see matrix rows 2, 21) |

### Migration Preflight & Legacy Data
| Check | Status | Evidence |
|-------|--------|----------|
| Preflight reports duplicate `consulta_id` rows with actionable IDs | ✅ | `PlanAlimentarioRequestLifecycleMigrationTest > aborts with an actionable duplicate report before enforcing the unique index` — `RuntimeException` message contains the consulta ID |
| Aborts rather than silently deleting | ✅ | Same test: `planes_alimentarios` count stays 2; `requiere_plan` column absent; unique index absent after the failed migrate |
| Legacy rows not destroyed | ✅ | Same test (2 rows preserved) + `rolls back only the lifecycle schema and preserves existing data` (consulta + plan rows preserved through focused rollback) |

### TDD Compliance
| Check | Result | Details |
|-------|--------|---------|
| TDD Evidence reported | ✅ | `TDD Cycle Evidence` table present in apply-progress (RED/GREEN/REFACTOR per task group) |
| All tasks have tests | ✅ | 8/8 core task groups map to persisted test files (verified on disk: 8 change-related test files) |
| RED confirmed (tests exist) | ✅ | 8/8 test files exist and were read in full during verification |
| GREEN confirmed (tests pass) | ✅ | Focused rerun: 86/86 (312 assertions); full suite 131/131 (441 assertions) — matches apply snapshot |
| Triangulation adequate | ✅ | 10 model tests (2 behaviors with duplicates rejected twice), 6 service tests, 7 migration tests (incl. 2 duplicate-rejection paths), 7 plan-form tests, 12 download/storage tests, 9 queue tests — multiple distinct expected values per behavior |
| Safety Net for modified files | ✅ | Apply-progress documents baseline runs per phase (78/243 → full 131/441); `WidenImcMigrationTest` modification explained (new migration invalidated `--step=1`, rollback made dynamic) — legitimate, verified on disk |

**TDD Compliance**: 6/6 checks passed

### Test Layer Distribution
| Layer | Tests | Files | Tools |
|-------|-------|-------|-------|
| Unit | 0 | 0 | — |
| Integration (Feature/Livewire/HTTP) | 86 change-related (131 suite) | 8 change-related | Pest 5 + Laravel + Livewire 4 |
| E2E | 0 | 0 | not installed (out of scope for this change) |
| **Total** | **86 change-related / 131 full suite** | **8** | |

Critical business logic is covered at feature/integration level (Livewire mount + real HTTP + real Storage fake), which exceeds unit-level guarantees — no SUGGESTION needed.

### Changed File Coverage
Coverage analysis skipped — no coverage tool detected (no Xdebug/PCOV in `php -m`; not a failure, informational only).

### Assertion Quality
| File | Line | Assertion | Issue | Severity |
|------|------|-----------|-------|----------|
| `tests/Feature/PlanAlimentarioDownloadTest.php` | 120 | `expect(Storage::disk('local_private')->exists('plan/new.pdf'))->toBeFalse()` | The new file is never physically stored in this test, so the assertion is near-trivial for that object; the meaningful assertions (old path retained, old file exists) are real | SUGGESTION |
| `tests/Feature/Filament/ConsultasRelationManagerTest.php` | 160 | Reflection reading private `relatedResource`/`relationship` properties | Implementation-detail coupling, but it asserts the spec's no-duplicated-schema reuse contract; acceptable | SUGGESTION |

Banned patterns audit: no tautologies, no ghost loops, no orphan empty-checks (every count-0 assertion has a companion count-1 test), no smoke-only tests, no mock-heavy files (0 mocks, 312 assertions). **Assertion quality**: ✅ 0 CRITICAL, 0 WARNING, 2 SUGGESTION.

### Quality Metrics
**Linter**: ✅ No errors — `vendor/bin/pint --dirty --format agent` exit 0, `result: passed`, zero files modified.
**Type Checker**: ➖ Not available (no PHPStan/Psalm in dev dependencies).

### Issues Found
**CRITICAL**: None.
**WARNING**: None.
**SUGGESTION**:
1. `PlanAlimentarioDownloadTest.php` line ~109: strengthen `retains the existing object when the database write fails` by physically storing `plan/new.pdf` before `sync()` so the catch-branch cleanup deletes a real object (currently asserts absence of a file that was never stored).
2. `PlanAlimentarioRequestLifecycleMigrationTest.php`: add a direct `Schema::hasIndex('planes_alimentarios', 'planes_alimentarios_estado_created_at_index')` assertion to close the small gap in the Queue Ordering Indexes scenario (index existence is currently proven only via its drop on rollback).
3. Apply-progress TDD evidence table lacks TRIANGULATE and SAFETY NET columns; independent verification confirms both, but future phases should record them.

### Verdict
PASS — all 12/12 requirements and 36/36 scenarios compliant with passing runtime tests; full suite GREEN (131/441) matching the apply snapshot; Pint clean; migration duplicate preflight proven to abort with actionable IDs while preserving legacy rows; no blockers, no critical findings, warnings, or design deviations.