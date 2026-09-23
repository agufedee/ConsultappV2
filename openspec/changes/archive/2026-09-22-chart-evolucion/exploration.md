# Exploration: chart-evolucion

### Current State

The repository's next backlog item is `feature/chart-evolucion`, explicitly defined in `consultapp_sdd.md` as a Chart.js-backed patient-view widget for chronological **weight and BMI (IMC)**. `Consulta` already stores `fecha`, `peso`, and nullable `imc`; `Paciente` already exposes the `hasMany` consultas relationship. `PacienteResource` mounts `ConsultasRelationManager`, and `ViewPaciente` is the existing patient detail page, but it currently has no widgets. The panel discovers widgets from `app/Filament/Widgets`.

Filament 5.8.2 is installed and provides `Filament\Widgets\ChartWidget`, whose `getData()` returns Chart.js-compatible datasets and labels. The application has no direct `chart.js` NPM dependency and no existing chart widget, so the implementation should use Filament's bundled chart integration rather than introduce a new dependency. Filament resource pages support `getHeaderWidgets()`, and a widget rendered on a record page can receive the current record through a public `$record` property.

Issue #9 is explicitly deferred. Its body-composition fields (fat mass percentage/kg and muscle mass) and meal-plan checkbox must not be added to this change. The chart scope is therefore limited to existing consultation data: weight and IMC over consultation dates. The separate `feature/planes-alimentarios` backlog item is also not part of this change.

### Affected Areas

- `app/Filament/Resources/Pacientes/Pages/ViewPaciente.php` — register the evolution widget on the patient detail page, most naturally through `getHeaderWidgets()`.
- `app/Filament/Widgets/` — add the patient-scoped `ChartWidget`; query the owning patient's consultations, order by `fecha` ascending, and map dates to labels plus `peso` and `imc` datasets.
- `app/Models/Paciente.php` — existing `consultas()` relationship is the data boundary; no model change appears necessary.
- `app/Models/Consulta.php` — existing date cast and IMC float cast provide the values needed by the chart; no model change appears necessary.
- `app/Filament/Resources/Pacientes/PacienteResource.php` — existing `ViewPaciente` route and relation-manager composition are the integration context; no resource schema change appears necessary.
- `tests/Feature/Filament/` — add focused coverage for widget rendering, chronological labels/data, patient isolation, and the empty-consultation state, following current Pest and Livewire conventions.
- `package.json` — inspected for chart dependencies; no change is currently justified because Filament supplies the chart integration.

### Approaches

1. **Single patient-scoped line ChartWidget with two datasets** — Render weight and IMC as two chronological datasets on the patient view, using the widget's `$record` and the existing `consultas` relationship.
   - Pros: Directly matches the backlog; one compact patient-view widget; no migration, model, or frontend dependency work; uses native Filament 5 APIs.
   - Cons: Weight (kg) and IMC (kg/m²) have different units/scales, so one axis can visually compress one series unless chart options or a clearly documented shared scale are used.
   - Effort: Medium

2. **Two patient-scoped line ChartWidgets** — Render separate weight and IMC charts, each with its own scale and heading.
   - Pros: Correct visual scale and units; simpler chart data per widget.
   - Cons: More vertical space and duplicated widget/query/test plumbing; less aligned with the singular “Widget Chart.js” backlog wording.
   - Effort: Medium

3. **Custom Blade/JavaScript chart implementation** — Add a bespoke view and direct Chart.js asset/dependency handling.
   - Pros: Maximum control over axes, tooltips, and layout.
   - Cons: Duplicates Filament functionality, introduces frontend dependency/build risk, and creates a second chart integration pattern without evidence that the project needs it.
   - Effort: High

### Recommendation

Use a single patient-scoped Filament `ChartWidget` registered on `ViewPaciente`, with two datasets (`Peso` and `IMC`) and labels ordered chronologically from `Consulta::fecha`. Keep the implementation read-only and derive all data from the owning `Paciente` relationship. Prefer native Filament chart options for distinguishable colors and, if needed during design, a dual-axis configuration; do not add direct Chart.js or body-composition fields. Define an explicit empty-data behavior so a patient with no consultations renders safely rather than failing or issuing an unbounded query.

The proposal phase should decide the exact chart-scale presentation (shared axis versus dual axis) and the widget placement (header versus footer), then specify the observable data contract and tests. The implementation should preserve patient isolation and avoid N+1 behavior by querying only the current patient's consultations with the required columns.

### Risks

- Weight and IMC use different units; a shared y-axis may be misleading unless dual axes or clear labeling are used.
- Nullable IMC values must be represented safely in Chart.js data without breaking the dataset or implying a measured value.
- The widget must receive the current `ViewPaciente` record correctly; an unscoped query could expose another patient's clinical data.
- Patients may have zero consultations, so the empty state requires explicit handling.
- `consultapp_sdd.md` and the current `Consulta` schema contain broader anthropometric fields, but Issue #9 body-composition work is deferred and must remain out of scope.

### Ready for Proposal

Yes. The repository provides the required patient/consultation relationships and stored metrics, and Filament 5.8.2 provides the chart primitive. The proposal should constrain the change to a read-only patient evolution chart for existing `fecha`, `peso`, and `imc` data, explicitly excluding Issue #9 and `feature/planes-alimentarios`.
