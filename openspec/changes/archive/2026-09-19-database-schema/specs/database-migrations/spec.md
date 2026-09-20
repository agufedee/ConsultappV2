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

#### Scenario: FK cascade on paciente delete

- GIVEN a paciente with 3 consultas
- WHEN the paciente is deleted
- THEN all 3 consultas are automatically deleted (cascade)

#### Scenario: motivo enum values stored as strings

- WHEN a consulta is inserted with motivo = 'control'
- THEN the value is stored as a plain string (not a native ENUM type)

### Requirement: Planes Alimentarios Table Migration

The system SHALL create a `planes_alimentarios` table with: `id` (bigint unsigned, PK), `consulta_id` (bigint unsigned, FK → consultas.id, ON DELETE CASCADE), `objetivo_calorico` (smallint unsigned, nullable), `descripcion` (text, nullable), `archivo_adjunto` (varchar 255, nullable), `vigente_desde` (date, not null), `vigente_hasta` (date, nullable), and `timestamps`.

#### Scenario: Table exists with correct columns

- WHEN `php artisan migrate:fresh` runs
- THEN the `planes_alimentarios` table exists with all 8 columns plus timestamps

#### Scenario: FK cascade on consulta delete

- GIVEN a consulta with 2 planes_alimentarios
- WHEN the consulta is deleted
- THEN both planes_alimentarios are automatically deleted

### Requirement: Objetivos Table Migration

The system SHALL create a `objetivos` table with: `id` (bigint unsigned, PK), `paciente_id` (bigint unsigned, FK → pacientes.id, ON DELETE CASCADE), `tipo` (varchar 100, not null), `peso_objetivo` (decimal 5,2, nullable), `fecha_objetivo` (date, nullable), `estado` (enum string: activo, cumplido, abandonado — not null, default 'activo'), and `timestamps`.

#### Scenario: Table exists with correct columns

- WHEN `php artisan migrate:fresh` runs
- THEN the `objetivos` table exists with all 7 columns plus timestamps

#### Scenario: estado defaults to activo

- WHEN an objetivo is inserted without specifying estado
- THEN estado is set to 'activo'

#### Scenario: FK cascade on paciente delete

- GIVEN a paciente with 2 objetivos
- WHEN the paciente is deleted
- THEN both objetivos are automatically deleted

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
| Delete paciente with consultas and objetivos | Cascade removes all related rows in one operation |
| NULL optional fields | All nullable columns accept NULL without error |
