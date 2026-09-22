# Delta for Paciente Resource

## ADDED Requirements

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