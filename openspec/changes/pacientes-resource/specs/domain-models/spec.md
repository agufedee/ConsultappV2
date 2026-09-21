# Delta for Domain Models

## MODIFIED Requirements

### Requirement: Paciente Model

The system SHALL provide a `Paciente` model in `app/Models/Paciente.php` with: `$fillable` for all non-ID columns, `$casts` mapping `sexo` to `string` and `fecha_nacimiento`/`fecha_alta` to `date`, a `nombre_completo` accessor returning `"Nombre Apellido"`, the `SoftDeletes` trait, and an `edad` accessor computing full years from `fecha_nacimiento`. Calling `->delete()` MUST soft-delete: the row remains with `deleted_at` set, and related consultas and objetivos rows remain in the database.
(Previously: no SoftDeletes; delete() removed the row and relied on FK cascades; no edad accessor.)

#### Scenario: Model exists and is usable

- GIVEN the Paciente model is defined
- WHEN `Paciente::factory()->create()` is called
- THEN a Paciente record is created in the database

#### Scenario: nombre_completo accessor

- GIVEN a paciente with nombre='María' and apellido='González'
- WHEN `$paciente->nombre_completo` is accessed
- THEN it returns 'María González'

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
(Previously: deleting a paciente via `->delete()` removed its consultas from the database.)

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