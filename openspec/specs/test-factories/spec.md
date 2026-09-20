# Test Factories Specification

## Purpose

Provide realistic factories for all four domain models using Argentine-locale Faker data, enabling consistent test data generation across the application.

## Requirements

### Requirement: Paciente Factory

The system SHALL provide a `PacienteFactory` that generates realistic Argentine paciente data: first/last names from `es_AR` locale, DNI as 8-digit string, Argentine phone format, valid email, realistic antecedentes text, and `fecha_alta` defaulting to today.

#### Scenario: Factory creates valid model

- WHEN `Paciente::factory()->create()` is called
- THEN a Paciente record exists in the database
- AND `nombre` is a non-empty string
- AND `apellido` is a non-empty string
- AND `dni` is an 8-digit string

#### Scenario: Factory overrides work

- WHEN `Paciente::factory()->create(['nombre' => 'Test'])` is called
- THEN the created paciente has nombre='Test'

#### Scenario: Multiple pacientes are unique

- WHEN 10 pacientes are created via factory
- THEN all 10 have distinct DNI values

### Requirement: Consulta Factory

The system SHALL provide a `ConsultaFactory` that generates: peso between 45.00-150.00 kg, altura between 140.00-200.00 cm, a random `motivo` from the enum values, computed `imc` derived from peso/altura, and optional measurement fields.

#### Scenario: Factory creates valid model

- WHEN `Consulta::factory()->create()` is called
- THEN a Consulta record exists
- AND `peso` is between 45.00 and 150.00
- AND `altura` is between 140.00 and 200.00
- AND `motivo` is one of: primera_consulta, control, derivacion

#### Scenario: IMC is computed correctly

- GIVEN a consulta with peso=80.00 and altura=180.00
- WHEN imc is calculated
- THEN imc equals 24.69 (80 / (1.80^2))

#### Scenario: Factory with associated paciente

- WHEN `Consulta::factory()->forPaciente()->create()` is called
- THEN the consulta is linked to a valid Paciente

### Requirement: PlanAlimentario Factory

The system SHALL provide a `PlanAlimentarioFactory` that generates: `objetivo_calorico` between 1200-3000 kcal, a realistic `descripcion` of dietary guidelines, and `vigente_desde` defaulting to today.

#### Scenario: Factory creates valid model

- WHEN `PlanAlimentario::factory()->create()` is called
- THEN a PlanAlimentario record exists
- AND `objetivo_calorico` is between 1200 and 3000
- AND `descripcion` is a non-empty string
- AND `vigente_desde` is today's date

#### Scenario: Factory with associated consulta

- WHEN `PlanAlimentario::factory()->forConsulta()->create()` is called
- THEN the plan is linked to a valid Consulta

### Requirement: Objetivo Factory

The system SHALL provide an `ObjetivoFactory` that generates: a realistic `tipo` (e.g., "Descenso de peso", "Hipertrofia"), `peso_objetivo` between 50.00-120.00 kg, and `estado` defaulting to 'activo'.

#### Scenario: Factory creates valid model

- WHEN `Objetivo::factory()->create()` is called
- THEN an Objetivo record exists
- AND `tipo` is a non-empty string
- AND `peso_objetivo` is between 50.00 and 120.00
- AND `estado` is one of: activo, cumplido, abandonado

#### Scenario: Factory with associated paciente

- WHEN `Objetivo::factory()->forPaciente()->create()` is called
- THEN the objetivo is linked to a valid Paciente

### Requirement: Seeder with Realistic Data

The system SHALL provide a `DatabaseSeeder` that creates 5-10 pacientes, each with 2-4 consultas, some consultas with planes_alimentarios, and some pacientes with objetivos.

#### Scenario: Seeder runs without error

- WHEN `php artisan migrate:fresh --seed` runs
- THEN no errors are thrown
- AND at least 5 pacientes exist in the database
- AND each paciente has at least 2 consultas

#### Scenario: Seeder data is queryable

- GIVEN the seeder has run
- WHEN `Paciente::with('consultas.planAlimentario', 'objetivos')->get()` is called
- THEN all relationships are properly loaded
- AND at least one plan_alimentario exists across all consultas
- AND at least one objetivo exists across all pacientes

## Edge Cases and Failure Modes

| Case | Expected Behavior |
|------|-------------------|
| es_AR Faker unavailable | Fallback to default locale with Argentine-style data |
| Factory association missing | Factory auto-creates parent if `for()` not called |
| peso/altura out of range | Factory constrains to realistic nutritional ranges |
| DNI collision at scale | Unique constraint on DNI prevents duplicates |
