# Delta for Database Migrations

## ADDED Requirements

### Requirement: Add Soft Deletes to Pacientes Migration

The system MUST provide an additive migration `add_soft_deletes_to_pacientes` adding a nullable timestamp column `deleted_at` to `pacientes` and a database index on `deleted_at`. The migration MUST NOT alter any other column, constraint, foreign key, or table. Rollback MUST drop only `deleted_at` and its index.

#### Scenario: Migration adds deleted_at and index

- GIVEN the pacientes table exists without deleted_at
- WHEN `php artisan migrate` runs
- THEN `deleted_at` (nullable timestamp) and its index are added

#### Scenario: Existing rows become active

- GIVEN pacientes rows exist before the migration
- WHEN the migration runs
- THEN every existing row has `deleted_at` NULL

#### Scenario: Rollback is non-destructive

- GIVEN the migration has run
- WHEN `php artisan migrate:rollback` runs
- THEN only `deleted_at` and its index are removed; patient data is untouched

## MODIFIED Requirements

### Requirement: Consultas Table Migration

The system SHALL create a `consultas` table with: `id` (bigint unsigned, PK), `paciente_id` (bigint unsigned, FK → pacientes.id, ON DELETE CASCADE), `fecha` (date, not null), `motivo` (enum string: primera_consulta, control, derivacion — not null), `peso` (decimal 5,2, not null), `altura` (decimal 5,2, not null), `imc` (decimal 4,2, nullable), `circunferencia_cintura` (decimal 5,2, nullable), `circunferencia_cadera` (decimal 5,2, nullable), `porcentaje_grasa` (decimal 4,2, nullable), `pliegues_cutaneos` (json, nullable), `observaciones` (text, nullable), `proximo_control` (date, nullable), and `timestamps`.
(Previously: deleting a paciente cascaded and removed its consultas in the same operation.)

#### Scenario: Table exists with correct columns

- GIVEN a fresh migration
- WHEN `php artisan migrate:fresh` runs
- THEN the `consultas` table exists with all 15 columns plus timestamps

#### Scenario: Soft delete preserves consultas

- GIVEN a paciente with 3 consultas
- WHEN the paciente is soft-deleted through the application
- THEN all 3 consultas remain in the database
- AND the FK ON DELETE CASCADE clause is unchanged at the database level

#### Scenario: motivo enum values stored as strings

- WHEN a consulta is inserted with motivo = 'control'
- THEN the value is stored as a plain string (not a native ENUM type)

### Requirement: Objetivos Table Migration

The system SHALL create a `objetivos` table with: `id` (bigint unsigned, PK), `paciente_id` (bigint unsigned, FK → pacientes.id, ON DELETE CASCADE), `tipo` (varchar 100, not null), `peso_objetivo` (decimal 5,2, nullable), `fecha_objetivo` (date, nullable), `estado` (enum string: activo, cumplido, abandonado — not null, default 'activo'), and `timestamps`.
(Previously: deleting a paciente cascaded and removed its objetivos in the same operation.)

#### Scenario: Table exists with correct columns

- WHEN `php artisan migrate:fresh` runs
- THEN the `objetivos` table exists with all 7 columns plus timestamps

#### Scenario: estado defaults to activo

- WHEN an objetivo is inserted without specifying estado
- THEN estado is set to 'activo'

#### Scenario: Soft delete preserves objetivos

- GIVEN a paciente with 2 objetivos
- WHEN the paciente is soft-deleted through the application
- THEN both objetivos remain in the database
- AND the FK ON DELETE CASCADE clause is unchanged at the database level