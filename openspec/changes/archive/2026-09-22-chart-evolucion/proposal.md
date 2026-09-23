# Proposal: Optional Patient Weight Evolution Chart

## Intent

Give nutritionists an optional, read-only view of a patient’s historical weight. Show consultation weights chronologically with a basic date-and-weight tooltip. This supersedes exploration recommendations for IMC, combined series, and header placement.

## Scope

### In Scope
- Add one patient-scoped Filament 5.8.2 `ChartWidget` using existing `Consulta::fecha` and `peso` data.
- Render it in the `ViewPaciente` footer, below primary patient information and relation content.
- Make visibility user-controlled; the smallest default is a local page UI toggle/collapse, not a new persisted preference.
- Omit the widget entirely when the patient has no consultations.
- Add focused Pest coverage for ordering, patient isolation, visibility, and the empty state.

### Out of Scope
- IMC, dual axes, combined charts, or any IMC series/decision.
- Issue #9 body-composition fields, meal-plan checkbox, and `feature/planes-alimentarios`.
- Direct `chart.js` dependency, migrations, consultation model changes, or write operations.

## Capabilities

### New Capabilities
- `patient-weight-evolution`: Optional patient-scoped chronological weight chart with safe empty-state behavior.

### Modified Capabilities
- `paciente-resource`: The patient View page gains an optional footer widget after existing patient and relation content.

## Approach

Use Filament’s native `ChartWidget`, pass the current record, query its `consultas` relationship, select required fields, order by `fecha` ascending, and map labels/data without N+1 queries. Use native tooltip configuration.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Filament/Widgets/` | New | Patient-scoped weight widget |
| `app/Filament/Resources/Pacientes/Pages/ViewPaciente.php` | Modified | Footer registration/visibility |
| `tests/Feature/Filament/` | New | Widget behavior and patient isolation |
| `openspec/specs/` | New/Modified | Patient view and chart contracts |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Visibility semantics are ambiguous | Medium | Decide local toggle versus justified persistence before design; do not silently add schema. |
| Record scope leaks data | Low | Query exclusively through the current `Paciente` relationship and test two patients. |

## Rollback Plan

Remove the registration, widget, and focused tests. No schema or dependency rollback is expected.

## Dependencies

- Existing `Paciente`–`Consulta` relationship and Filament 5.8.2 native chart integration.

## Success Criteria

- [ ] A user can choose to show or hide the weight chart on a patient View page.
- [ ] Visible data contains every consultation weight in ascending date order with a date/weight tooltip.
- [ ] Patients without consultations render no chart widget; unrelated patients’ data never appears.

## Proposal question round

The next phase should confirm whether “optional” means a per-page local toggle/collapse (smallest, no persistence) or an existing persisted preference (more durable, broader scope). No other product question blocks specification.
