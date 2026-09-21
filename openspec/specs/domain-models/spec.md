# Domain Models Specification

## Purpose

Define four Eloquent models — Paciente, Consulta, PlanAlimentario, Objetivo — with correct relationships, casts, fillable attributes, and the `nombre_completo` accessor on Paciente.

## Requirements

### Requirement: Paciente Model

The system SHALL provide a `Paciente` model in `app/Models/Paciente.php` with: `$fillable` for all non-ID columns, `$casts` mapping `sexo` to `string` and `fecha_nacimiento`/`fecha_alta` to `date`, a `nombre_completo` accessor returning `"Nombre Apellido"`, the `SoftDeletes` trait, and an `edad` accessor computing full years from `fecha_nacimiento`. Calling `->delete()` MUST soft-delete: the row remains with `deleted_at` set, and related consultas and objetivos rows remain in the database.

#### Scenario: Model exists and is usable

- GIVEN the Paciente model is defined
- WHEN `Paciente::factory()->create()` is called
- THEN a Paciente record is created in the database

#### Scenario: nombre_completo accessor

- GIVEN a paciente with nombre='María' and apellido='González'
- WHEN `$paciente->nombre_completo` is accessed
- THEN it returns `'María González'`

#### Scenario: Has many consultas

- GIVEN a paciente with 3 consultas
- WHEN `$paciente->consultas` is loaded
- THEN the collection contains 3 Consulta models

#### Scenario: Has many objetivos

- GIVEN a paciente with 2 objetivos
- WHEN `$paciente->objetivos` is loaded
- THEN the collection contains 2 Objetivo models

#### Scenario: edad accessor computes age

- GIVEN a paciente with fecha_nacimiento 30 years before today
- WHEN `$paciente->edad` is accessed
- THEN it returns 30

#### Scenario: edad accessor with missing birth date

- GIVEN a paciente without fecha_nacimiento
- WHEN `$paciente->edad` is accessed
- THEN it returns null

#### Scenario: delete() soft-deletes

- GIVEN a paciente with 2 consultas and 2 objetivos
- WHEN `$paciente->delete()` is called
- THEN the paciente row remains with `deleted_at` set
- AND the consultas and objetivos rows remain in the database

### Requirement: Consulta Model

The system SHALL provide a `Consulta` model with: `$fillable` for all non-ID columns, `$casts` mapping `motivo` to `string`, `imc` to `float`, `pliegues_cutaneos` to `array`, and date columns to `date`.

#### Scenario: Model exists and is usable

- WHEN `Consulta::factory()->create()` is called
- THEN a Consulta record is created in the database

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

### Requirement: PlanAlimentario Model

The system SHALL provide a `PlanAlimentario` model with: `$fillable` for all non-ID columns, `$casts` mapping `objetivo_calorico` to `integer` and date columns to `date`.

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

### Requirement: Objetivo Model

The system SHALL provide an `Objetivo` model with: `$fillable` for all non-ID columns, `$casts` mapping `estado` to `string` and date columns to `date`.

#### Scenario: Model exists and is usable

- WHEN `Objetivo::factory()->create()` is called
- THEN an Objetivo record is created in the database

#### Scenario: Belongs to Paciente

- GIVEN an objetivo linked to paciente_id=3
- WHEN `$objetivo->paciente` is loaded
- THEN it returns the Paciente with id=3

### Requirement: Enum String Casts for SQLite Compatibility

The system SHALL cast all enum columns (`sexo`, `motivo`, `estado`) as `string` rather than native enum types, ensuring SQLite compatibility in dev and test environments.

#### Scenario: Sexo stored as string

- GIVEN a paciente with sexo='femenino'
- WHEN the record is retrieved from SQLite
- THEN sexo is a plain string value 'femenino'

#### Scenario: Motivo stored as string

- WHEN a consulta with motivo='control' is saved to SQLite
- THEN motivo is stored and retrieved as the string 'control'

## Edge Cases and Failure Modes

| Case | Expected Behavior |
|------|-------------------|
| SQLite does not support native ENUM | All enum columns cast as string in PHP |
| Pliegues_cutaneos JSON | Cast to array; stored as JSON string in DB |
| nombre_completo with extra spaces | trim() ensures no leading/trailing spaces |
| Deleting Paciente | Soft delete sets `deleted_at`; consultas and objetivos remain in the database |
