# Database Migrations Specification

## Purpose

Create the foundational database schema for ConsultApp's four domain tables: `pacientes`, `consultas`, `planes_alimentarios`, and `objetivos`. All columns, types, constraints, foreign keys, and indexes match the SDD 4.2 dictionary exactly.

## Requirements

### Requirement: Pacientes Table Migration

The system SHALL create a `pacientes` table with the following schema: `id` (bigint unsigned, auto-increment PK), `nombre` (varchar 100, not null), `apellido` (varchar 100, not null), `dni` (varchar 20, nullable, unique index), `fecha_nacimiento` (date, nullable), `sexo` (enum values stored as string: masculino, femenino, otro — nullable), `telefono` (varchar 50, nullable), `email` (varchar 100, nullable), `antecedentes` (text, nullable), `fecha_alta` (date, not null, default today), and `timestamps`.

#### Scenario: Table exists with correct columns

- GIVEN a fresh migration
- WHEN `php artisan migrate:fresh` runs
- THEN the `pacientes` table exists
- AND it contains columns: id, nombre, apellido, dni, fecha_nacimiento, sexo, telefono, email, antecedentes, fecha_alta, created_at, updated_at

#### Scenario: DNI unique index enforced

- GIVEN two pacientes exist with different DNIs
- WHEN a third paciente is inserted with a duplicate DNI
- THEN the database rejects the insert with a unique constraint violation

#### Scenario: fecha_alta defaults to today

- WHEN a paciente is inserted without specifying fecha_alta
- THEN fecha_alta is set to the current date

### Requirement: Consultas Table Migration

The system SHALL create a `consultas` table with: `id` (bigint unsigned, PK), `paciente_id` (bigint unsigned, FK → pacientes.id, ON DELETE CASCADE), `fecha` (date, not null), `motivo` (enum string: primera_consulta, control, derivacion — not null), `peso` (decimal 5,2, not null), `altura` (decimal 5,2, not null), `imc` (decimal 4,2, nullable), `circunferencia_cintura` (decimal 5,2, nullable), `circunferencia_cadera` (decimal 5,2, nullable), `porcentaje_grasa` (decimal 4,2, nullable), `pliegues_cutaneos` (json, nullable), `observaciones` (text, nullable), `proximo_control` (date, nullable), and `timestamps`.

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

### Requirement: Planes Alimentarios Table Migration

The system SHALL preserve the existing `planes_alimentarios` columns and foreign-key cascade, SHALL add a non-null `requiere_plan` boolean to `consultas` with a false default, and SHALL add to `planes_alimentarios` a constrained status with default `pending`, nullable delivery date, and private generated attachment path. The schema MUST enforce one active request per Consulta and support deterministic queue ordering. Existing deployed migrations MUST remain immutable; changes MUST use a reversible additive migration where safe.

#### Scenario: Lifecycle columns exist

- WHEN migrations run from a clean database
- THEN the request status defaults to `pending` and delivery date and attachment path accept null

#### Scenario: Table exists with correct columns

- WHEN `php artisan migrate:fresh` runs
- THEN the `planes_alimentarios` table retains its existing columns and the new lifecycle columns exist

#### Scenario: FK cascade on consulta delete

- GIVEN a consulta with 2 planes_alimentarios
- WHEN the consulta is deleted
- THEN both plans are automatically deleted

#### Scenario: One active request is enforced

- GIVEN a Consulta already has its active PlanAlimentario request
- WHEN a second active request for that Consulta is inserted
- THEN the database rejects the duplicate

#### Scenario: Rollback is focused

- GIVEN the additive lifecycle migration has run
- WHEN it is rolled back
- THEN only the added lifecycle schema is removed and existing consultation/plan data is not silently deleted

### Requirement: Queue Ordering Indexes

The migration set MUST provide indexes sufficient for filtering by status and ordering by request creation time and identifier without changing unrelated domain constraints.

#### Scenario: Pending queue query is indexable

- GIVEN pending requests exist
- WHEN the queue filters pending status and orders deterministically
- THEN the schema exposes status and ordering columns needed by that query

### Requirement: Objetivos Table Migration

The system SHALL create a `objetivos` table with: `id` (bigint unsigned, PK), `paciente_id` (bigint unsigned, FK → pacientes.id, ON DELETE CASCADE), `tipo` (varchar 100, not null), `peso_objetivo` (decimal 5,2, nullable), `fecha_objetivo` (date, nullable), `estado` (enum string: activo, cumplido, abandonado — not null, default 'activo'), and `timestamps`.

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

### Requirement: Widen IMC Column Migration

The system MUST provide an additive migration `widen_imc_on_consultas` changing the `consultas.imc` column from DECIMAL(4,2) to DECIMAL(5,2). The migration MUST NOT alter any other column, constraint, foreign key, or table, and MUST NOT change the ConsultaFactory IMC formula. Rollback MUST restore `imc` to DECIMAL(4,2).

#### Scenario: Migration widens the imc column

- GIVEN a consultas table with imc DECIMAL(4,2)
- WHEN `php artisan migrate` runs
- THEN `imc` is DECIMAL(5,2)

#### Scenario: Existing data preserved

- GIVEN consultas rows exist with imc values
- WHEN the migration runs
- THEN all imc values remain unchanged

#### Scenario: Extreme IMC persists without error

- GIVEN the migration has run
- WHEN a consulta is saved with peso 300.00 and altura 100.00
- THEN imc 300.00 is stored without a database error

#### Scenario: Rollback restores the previous width

- GIVEN the migration has run
- WHEN `php artisan migrate:rollback` runs
- THEN `imc` returns to DECIMAL(4,2) and no other column is changed

### Requirement: Migration Ordering

The system SHALL create migrations in dependency order: `pacientes` and `objetivos` first (no FK dependencies), then `consultas` (depends on pacientes), then `planes_alimentarios` (depends on consultas).

#### Scenario: Migrations run in order

- WHEN `php artisan migrate:fresh` runs
- THEN all 4 tables are created without FK resolution errors
- AND the `consultas` table is created after `pacientes`
- AND `planes_alimentarios` is created after `consultas`

## Edge Cases and Failure Modes

| Case | Expected Behavior |
|------|-------------------|
| SQLite does not support native ENUM | All enum columns use varchar/string storage with PHP cast |
| Duplicate DNI on insert | Database rejects with unique constraint violation |
| Delete paciente with consultas and objetivos | Soft delete sets `deleted_at`; rows remain in the database; the FK ON DELETE CASCADE clause is unchanged at the database level |
| NULL optional fields | All nullable columns accept NULL without error |
