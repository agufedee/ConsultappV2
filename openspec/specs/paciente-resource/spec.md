# Paciente Resource Specification

## Purpose

Filament v5 CRUD resource for `Paciente` on the `consultapp` panel: list with search, create/edit with optional-unique DNI, detail view with computed age, soft deletes, Spanish labels; sets conventions for later resources.

## Requirements

### Requirement: Resource Registration

The system MUST register a `PacienteResource` on the `consultapp` panel with List, Create, Edit, and View pages for `Paciente`.

#### Scenario: List page renders with all columns

- GIVEN the resource is registered
- WHEN `/consultapp/pacientes` is visited
- THEN the list renders without error, showing columns `nombre`, `apellido`, `dni`, `sexo`, `fecha_alta`

### Requirement: DNI Optional but Unique

`dni` MUST be optional on create and edit; empty input MUST persist as NULL, never `''`. When provided, `dni` MUST be unique: Create MUST reject duplicates, an edit MAY keep its own, and reassigning another record's `dni` MUST be rejected.

#### Scenario: Empty DNI persists NULL

- GIVEN the create form is submitted without DNI
- WHEN saved
- THEN `dni` stores NULL

#### Scenario: Duplicate DNI rejected on create

- GIVEN a paciente with DNI 30123456
- WHEN a new paciente is created with that DNI
- THEN validation fails and no record is created

#### Scenario: Same-DNI edit of the record succeeds

- GIVEN a paciente with DNI 30123456
- WHEN that record is edited keeping its DNI
- THEN the update succeeds

#### Scenario: Another record's DNI rejected on edit

- GIVEN pacientes with DNIs 30123456 and 30123457
- WHEN the first is edited to use 30123457
- THEN validation fails and the first keeps its DNI

### Requirement: Soft Delete Behavior

Deleting a paciente MUST be logical: the row remains with `deleted_at` set, no cascade. Deleted records MUST be hidden from list and global search. A deleted paciente MUST keep its `dni`; re-registration with it MUST be rejected.

#### Scenario: Delete hides the record

- GIVEN a listed paciente
- WHEN delete is confirmed
- THEN `deleted_at` is set and the record leaves the list

#### Scenario: Deleted DNI stays blocked

- GIVEN a deleted paciente with DNI 30123456
- WHEN a new paciente is created with the same DNI
- THEN validation fails

#### Scenario: Search excludes deleted records

- GIVEN a deleted paciente matching the term
- WHEN global search runs
- THEN it is not returned

### Requirement: Computed Age in Detail View

The View page MUST show `edad` computed from `fecha_nacimiento`; the age MUST NOT be persisted.

#### Scenario: Age shown on the view page

- GIVEN a paciente born 30 years before today
- WHEN the View page opens
- THEN it displays age 30

### Requirement: Global Search Attributes

`nombre`, `apellido`, and `dni` MUST be globally searchable. The record title MUST be `nombre`. The `nombre_completo` accessor MUST NOT be a searchable attribute or record title.

#### Scenario: Search matches nombre

- GIVEN a paciente named "María González"
- WHEN searching "María"
- THEN the paciente is returned

#### Scenario: Search matches apellido

- GIVEN a paciente named "María González"
- WHEN searching "González"
- THEN the paciente is returned

#### Scenario: Search matches dni

- GIVEN a paciente with DNI 30123456
- WHEN searching "30123456"
- THEN the paciente is returned

### Requirement: Form Schema

The form MUST require `nombre` and `apellido`. It SHALL include DatePicker `fecha_nacimiento`, Select `sexo` (masculino/femenino/otro), `telefono`, `email` (validated when provided), Textarea `antecedentes`, DatePicker `fecha_alta`.

#### Scenario: Create succeeds

- GIVEN the form is filled with required and optional fields
- WHEN submitted
- THEN a paciente is persisted

#### Scenario: Missing nombre rejected

- GIVEN the form is submitted without nombre
- THEN validation fails

### Requirement: Spanish Labels

The resource MUST use `modelLabel` "Paciente" and `pluralModelLabel` "Pacientes".

#### Scenario: Plural heading shown

- GIVEN the list page is open
- WHEN the heading is inspected
- THEN it reads "Pacientes"

### Requirement: Consultas Relation Manager

The system MUST mount a `ConsultasRelationManager` on `PacienteResource::getRelations()`, exposing consultas as a relation tab on the View page. The relation manager MUST delegate its form to `ConsultaResource` via related-resource delegation (the form is defined once in the resource), MUST assign `paciente_id` from the owning paciente when a consulta is created from the tab, and MUST show a table with `fecha`, `motivo`, `peso`, `altura`, and `imc`. The manager MAY display a badge with the consulta count.

#### Scenario: Relation tab visible on the view page

- GIVEN a paciente View page is open
- WHEN inspected
- THEN a "Consultas" relation tab is present

#### Scenario: New consulta bound to the owning paciente

- GIVEN the Consultas relation tab on a paciente
- WHEN a consulta is created from the tab
- THEN the consulta persists with `paciente_id` matching the owner

#### Scenario: Table lists only the owner's consultas

- GIVEN a paciente with 3 consultas
- WHEN the relation table loads
- THEN it shows exactly those 3 consultas and no other paciente's

#### Scenario: Create modal shows the resource form

- GIVEN the relation manager create modal is open
- WHEN the form is inspected
- THEN it renders the ConsultaResource reactive form (peso/altura live → imc readOnly)