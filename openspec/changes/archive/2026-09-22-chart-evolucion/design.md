# Design: Optional Patient Weight Evolution Chart

## Technical Approach

Use one application widget, `PatientWeightEvolutionChart`, extending Filament 5.8.2 `ChartWidget`. `ViewPaciente` owns a page-local boolean (`showWeightChart`, default `false`) and exposes a footer Blade view containing the show/hide control. The footer mounts the chart only after the user chooses to show it and only when the patient has consultations. This satisfies footer placement and default omission without persistence, migration, or a second chart implementation.

## Architecture Decisions

| Decision | Choice | Alternatives rejected | Rationale |
|---|---|---|---|
| Scope boundary | Query `$this->record->consultas()` inside the widget | Global `Consulta` query filtered manually; eager-loading all consultations | The relationship is the authorization/data-isolation boundary and returns only the current patient’s records. |
| Visibility | `ViewPaciente::$showWeightChart = false`, toggled by a page action method | Session/database preference; always-registering the widget | State is Livewire page state, so reload/navigation resets it and no schema change is introduced. |
| Footer composition | Override `ViewPaciente::getFooter()` and render a small page view with a nested native widget | Header widget; custom Chart.js view; render hook | Filament 5.8.2 explicitly supports custom page footers, and this allows a footer-local control while retaining native `ChartWidget` rendering. |
| Chart data | `select(['fecha', 'peso'])->orderBy('fecha')->get()` mapped to labels and one dataset | IMC/combined data; unscoped collection | The query is narrow, deterministic, one-series, and exactly matches the specification. |

## Data Flow

`ViewPaciente::mount()` → record + `consultas()->exists()` → footer control → `toggleWeightChart()` → nested `PatientWeightEvolutionChart(record)` → relationship query → `ChartWidget::mount()` checksum → native Filament chart and tooltip.

The widget returns `line` data with date labels formatted from the cast `fecha` date and one `peso` dataset labelled `Weight (kg)`. Native Chart.js tooltip behavior therefore displays the date label and dataset value; no direct Chart.js dependency, custom JavaScript, or `RawJs` callback is needed.

## File Changes

| File | Action | Description |
|---|---|---|
| `app/Filament/Widgets/PatientWeightEvolutionChart.php` | Create | Typed `Paciente $record`; `getType()`, `getData()`, heading, one weight dataset, and relationship-scoped ordered query. |
| `app/Filament/Resources/Pacientes/Pages/ViewPaciente.php` | Modify | Add page-local visibility state, consultation-existence check, toggle method, and `getFooter()` view. Preserve existing header action. |
| `resources/views/filament/resources/pacientes/pages/view-paciente-footer.blade.php` | Create | Footer-local show/hide button and conditional nested widget; no query or inline script. |
| `tests/Feature/Filament/PatientWeightEvolutionChartTest.php` | Create | Pest/Livewire coverage for order, isolation, toggle/reset behavior, footer placement, and no-consultation omission. |

## Interfaces / Contracts

```php
// Page state is transient Livewire state.
public bool $showWeightChart = false;

public function toggleWeightChart(): void;

// Widget receives the current resource record through its public property.
public Paciente $record;
```

The page must compute `hasConsultations` outside Blade (using `consultas()->exists()`). The widget must never accept an arbitrary patient identifier from request input; its record is supplied by the current page.

## Testing Strategy

| Layer | What to test | Approach |
|---|---|---|
| Unit/component | Ordered labels/data and exactly one weight dataset | Instantiate the widget with a patient and inspect its rendered chart payload/data contract. |
| Feature | Patient isolation, default hidden state, show/hide, reset on a fresh page, footer ordering | Pest with `RefreshDatabase`, factories, and `Livewire::test(ViewPaciente::class)`; use two patients and dated consultations. |
| Feature | Empty state | Load a patient without consultations and assert no chart heading, toggle, or widget markup is present. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

No migration, dependency, model, or persisted preference is required. Rollback is removal of the widget, footer view, page additions, and focused tests. Existing patient infolist and `Consultas` relation manager remain unchanged.

## Open Questions

- [ ] Confirm the product wording for the footer control label during implementation; behavior is fully specified as page-local show/hide.
- [ ] Verify the project’s preferred chart heading/weight unit wording if existing UI copy establishes a stricter convention.
