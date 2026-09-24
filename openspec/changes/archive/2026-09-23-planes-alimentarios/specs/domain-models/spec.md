# Delta for Domain Models

## MODIFIED Requirements

### Requirement: Consulta Model

The system SHALL provide a `Consulta` model with its existing fillable attributes, casts, `paciente()` relationship, and `planAlimentario()` relationship. It MUST persist a boolean `requiere_plan` control. Enabling it MUST allow the single active request to exist; disabling it MUST leave no active request.
(Previously: Consulta provided clinical fields and a HasOne PlanAlimentario relationship without request lifecycle state.)

#### Scenario: Existing Consulta behavior remains usable
- WHEN `Consulta::factory()->create()` is called
- THEN a Consulta record is created and its patient relationship remains available

#### Scenario: Belongs to Paciente
- GIVEN a consulta linked to paciente_id=1
- WHEN `$consulta->paciente` is loaded
- THEN it returns the Paciente with id=1

#### Scenario: Has one plan alimentario
- GIVEN a consulta with a plan_alimentario
- WHEN `$consulta->planAlimentario` is loaded
- THEN it returns the PlanAlimentario record

#### Scenario: Soft delete preserves consultas
- GIVEN a paciente with 2 consultas
- WHEN the paciente is soft-deleted via `->delete()`
- THEN both consultas remain in the database

#### Scenario: Unmarked Consulta has no request
- GIVEN a Consulta with requires-plan disabled
- WHEN its plan relationship is loaded
- THEN no PlanAlimentario request is returned

### Requirement: PlanAlimentario Model

The system SHALL provide a PlanAlimentario model with existing descriptive/calorie fields and date casts, plus request status, delivery date, and optional private attachment path. Status values MUST be restricted to `pending`, `delivered`, and `payment_pending`; delivery date MUST be null unless status is `delivered`.
(Previously: PlanAlimentario contained descriptive, calorie, attachment, and validity fields only.)

#### Scenario: Pending request casts correctly
- GIVEN a PlanAlimentario request is created without delivery
- WHEN it is retrieved
- THEN status is `pending` and delivery date is null

#### Scenario: Model exists and is usable
- WHEN `PlanAlimentario::factory()->create()` is called
- THEN a PlanAlimentario record is created in the database

#### Scenario: Belongs to Consulta
- GIVEN a plan linked to consulta_id=5
- WHEN `$plan->consulta` is loaded
- THEN it returns the Consulta with id=5

#### Scenario: Cascade delete via relationship
- GIVEN a consulta with 1 plan_alimentario
- WHEN the consulta is deleted
- THEN the plan_alimentario is removed from the database

#### Scenario: Invalid lifecycle state is rejected
- GIVEN a request with status `delivered`
- WHEN it is saved without a delivery date
- THEN validation prevents persistence
