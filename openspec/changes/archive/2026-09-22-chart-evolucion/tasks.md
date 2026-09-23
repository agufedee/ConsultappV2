# Tasks: Optional Patient Weight Evolution Chart

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 260–340 (application, tests, and SDD task artifact) |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR on `feature/chart-evolucion`; split only if implementation exceeds the budget |
| Delivery strategy | ask-on-risk |
| Chain strategy | feature-branch-chain (cached preference) |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: feature-branch-chain
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Weight chart plus footer toggle | Single PR | `php artisan test --compact tests/Feature/Filament/PatientWeightEvolutionChartTest.php` | `php artisan serve`; inspect two patient View pages | Revert the four feature files and focused test |

## Phase 1: RED Tests / Contracts

- [x] 1.1 Create `tests/Feature/Filament/PatientWeightEvolutionChartTest.php` with Pest/Livewire fixtures and failing assertions for ordered `fecha`/`peso`, one weight dataset, native tooltip payload, and patient isolation.
- [x] 1.2 Add failing assertions for default hidden state, show/hide toggle, fresh-page reset, footer-after-content placement, preserved infolist/relation content, and no-consultation omission.

## Phase 2: Chart Implementation

- [x] 2.1 Create `app/Filament/Widgets/PatientWeightEvolutionChart.php` extending native Filament 5.8.2 `ChartWidget`, accepting typed `Paciente $record` and querying only `$record->consultas()->select(['fecha', 'peso'])->orderBy('fecha')->get()`.
- [x] 2.2 Map cast dates and weights to one `line` dataset labelled `Weight (kg)`, with native basic date/value tooltip behavior; include no IMC, combined series, axes, writes, dependency, or arbitrary identifier input.

## Phase 3: Page/Footer Integration

- [x] 3.1 Modify `app/Filament/Resources/Pacientes/Pages/ViewPaciente.php` with transient `showWeightChart = false`, page-local consultation existence state, `toggleWeightChart(): void`, and `getFooter()` while preserving the edit action.
- [x] 3.2 Create `resources/views/filament/resources/pacientes/pages/view-paciente-footer.blade.php` with an accessible show/hide control and conditional nested widget; mount only after choice and only when consultations exist.

## Phase 4: GREEN / Verification

- [x] 4.1 Make all focused tests pass, then run `vendor/bin/pint --dirty --format agent` and `php artisan test --compact tests/Feature/Filament/PatientWeightEvolutionChartTest.php`.
- [x] 4.2 Verify runtime footer ordering and empty-state omission manually; confirm no migrations, model changes, direct `chart.js`, persistence, or out-of-scope chart behavior were introduced.

## Key Learnings

1. The current Paciente relationship is the required data-isolation boundary for chart queries.
2. Filament native ChartWidget rendering avoids a direct chart.js dependency and custom JavaScript.
