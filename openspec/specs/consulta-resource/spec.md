# Consulta Resource Specification

## Purpose

Filament v5 CRUD resource for `Consulta` on the `consultapp` panel: reactive IMC form (peso/altura live → imc readOnly computed), motivo Select, full editable anthropometric fields, hidden navigation, and Spanish labels. The resource is the single source of truth for the consulta form, reused by `ConsultasRelationManager` on the paciente view.

## Requirements

### Requirement: Resource Registration and Navigation

The system MUST register a `ConsultaResource` on the `consultapp` panel with List, Create, Edit, and View pages for `Consulta`. The resource MUST set `$shouldRegisterNavigation = false`, so no "Consultas" entry appears in the panel sidebar; the resource SHALL remain reachable through the ConsultasRelationManager on the paciente View page.

#### Scenario: No sidebar entry

- GIVEN the panel sidebar is rendered
- WHEN inspected
- THEN there is no "Consultas" navigation item

### Requirement: Reactive IMC Calculation

`peso` and `altura` MUST be live inputs (`onBlur`) whose state updates trigger recalculation via `afterStateUpdated`; `imc` MUST be a read-only computed field. The IMC MUST be calculated as `imc = peso / (altura/100)^2` and rounded to two decimals. The IMC MUST recompute whenever `peso` or `altura` changes, in both Create and Edit, without a page reload; `imc` MUST NOT be directly editable.

#### Scenario: IMC computed on create

- GIVEN the create form
- WHEN peso is set to 82.40 and altura to 174.00
- THEN imc displays 27.22

#### Scenario: IMC recomputed on edit

- GIVEN an existing consulta with peso 70.00 and altura 170.00
- WHEN peso is changed to 80.00
- THEN imc updates from 24.22 to 27.68 without a reload

#### Scenario: Extreme combination fits

- GIVEN the create form
- WHEN peso is set to 300.00 and altura to 100.00
- THEN imc displays 300.00 and persists without a database error

### Requirement: Motivo Select

`motivo` MUST be a Select offering exactly the values `primera_consulta`, `control`, and `derivacion`, displayed with Spanish labels, and MUST be required.

#### Scenario: Options rendered in Spanish

- GIVEN the form
- WHEN the motivo Select is opened
- THEN it shows "Primera consulta", "Control", and "Derivación"

#### Scenario: Missing motivo rejected

- GIVEN the form is submitted without motivo
- THEN validation fails

### Requirement: Form Schema

The form MUST include: `fecha` (required DatePicker), `peso` (required, numeric 20–300, step 0.1, suffix kg), `altura` (required, numeric 100–250, step 0.5, suffix cm), `imc` (readOnly, suffix kg/m²), `circunferencia_cintura`, `circunferencia_cadera`, `porcentaje_grasa`, `pliegues_cutaneos`, `observaciones` (Textarea), and `proximo_control` (DatePicker). All fields except `imc` MUST be editable; optional columns MUST persist NULL when left empty.

#### Scenario: Create succeeds

- GIVEN the form is filled with required and optional fields
- WHEN submitted
- THEN a consulta is persisted with the computed imc

#### Scenario: Out-of-range peso rejected

- GIVEN the form is submitted with peso 15.00
- THEN validation fails

#### Scenario: Out-of-range altura rejected

- GIVEN the form is submitted with altura 260.00
- THEN validation fails

#### Scenario: Missing fecha rejected

- GIVEN the form is submitted without fecha
- THEN validation fails

### Requirement: Repeatable Pliegues

`pliegues_cutaneos` MUST be edited through a repeatable list (pliegue name + millimeters) and MUST persist as a JSON array via the existing `array` cast; an empty list MUST persist NULL.

#### Scenario: Pliegues stored as JSON

- GIVEN three pliegues are added to the repeater
- WHEN the consulta is saved
- THEN `pliegues_cutaneos` stores a JSON array of three entries

### Requirement: Table Columns and Sorting

The table MUST show columns `fecha`, `motivo`, `peso`, `altura`, and `imc`, with `fecha` as the default sort column in descending order.

#### Scenario: Newest consulta first

- GIVEN two consultas dated 2026-09-01 and 2026-09-21
- WHEN the table loads
- THEN the 2026-09-21 consulta appears first

### Requirement: Spanish Labels

The resource MUST use `modelLabel` "Consulta" and `pluralModelLabel` "Consultas".

#### Scenario: Plural heading shown

- GIVEN the list page is open
- WHEN the heading is inspected
- THEN it reads "Consultas"

### Requirement: Form Reuse by Relation Manager

The consulta form and infolist MUST be defined once in `ConsultaResource` and MUST be reusable by `ConsultasRelationManager` through related-resource delegation; the relation manager MUST NOT duplicate the form schema.

#### Scenario: Create modal reuses the resource form

- GIVEN the ConsultasRelationManager create modal on a paciente
- WHEN the form is inspected
- THEN it renders the resource's reactive form (peso/altura live, imc readOnly)