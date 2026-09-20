# Tasks: Pacientes Resource (Filament CRUD)

Sprint 2 (consultapp_sdd.md). Branch: `feature/pacientes-resource`. TDD on.

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~540–640 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (domain) → PR 2 (scaffold+list) → PR 3 (form+view) |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units (proposal — user picks strategy)

| Unit | Goal | PR (base) | Test cmd | Harness | Rollback |
|------|------|-----------|----------|---------|----------|
| 1 | Soft-deletes + edad layer | PR 1 (feature branch) | `php artisan test --compact --filter=PacienteTest` | `migrate:fresh`; `migrate:rollback --step=1` | Revert migration + Paciente trait/accessor |
| 2 | Scaffold + list/search/labels | PR 2 (PR 1) | `php artisan test --compact --filter=PacienteResourceTest` | visit /consultapp/pacientes | Delete app/Filament/Resources/PacienteResource* |
| 3 | Form + infolist + CRUD tests | PR 3 (PR 2) | `php artisan test --compact --filter=PacienteResourceTest` | Full suite run | Revert form/infolist in PacienteResource.php |

## Phase 1: Housekeeping

- [ ] 1.1 Add `.codegraph/` to `.gitignore` (`chore`). Done: `git status` clean.

## Phase 2: Domain Foundation (RED→GREEN)

- [ ] 2.1 RED — append `tests/Feature/Models/PacienteTest.php`: `edad`=30 (birth 30y ago); `edad` null when no `fecha_nacimiento`; `delete()` keeps row (`deleted_at` set), consultas/objetivos intact. `--filter=PacienteTest` → fails.
- [ ] 2.2 Create `database/migrations/2026_09_20_000001_add_soft_deletes_to_pacientes.php`: `softDeletes()` + `$table->index('deleted_at')`; `down()` drops index then column. Hook: `migrate:fresh`/`migrate:rollback --step=1`.
- [ ] 2.3 GREEN — `app/Models/Paciente.php`: `use SoftDeletes;` + `getEdadAttribute(): ?int` (`$this->fecha_nacimiento?->age`). Filtered → passing.

## Phase 3: Resource Generation

- [ ] 3.1 Run `php artisan make:filament-resource Paciente --panel=consultapp --view --record-title-attribute=nombre --embed-schemas --embed-table` (no `--simple`/`--soft-deletes`). Done: 5 files, auto-discovered; route /consultapp/pacientes present.

## Phase 4: Table + Global Search (RED→GREEN)

- [ ] 4.1 RED — `php artisan make:test --pest Filament/PacienteResourceTest`; `uses(RefreshDatabase)`, `it()` per ConsultappPanelTest, Livewire;: list renders; `searchTable('María'|'González'|'30123456')` show/hide. Run `--filter=PacienteResourceTest` → failing.
- [ ] 4.2 GREEN — `table()`: nombre/apellido searchable+sortable, dni searchable, sexo+fecha_alta->date('d/m/Y') toggleable, `defaultSort('apellido')`; `getGloballySearchableAttributes(): ['nombre','apellido','dni']`. Filtered → passing.

## Phase 5: Form Schema (RED→GREEN)

- [ ] 5.1 RED — append tests: create OK (`fillForm()->call('create')->assertHasNoFormErrors()` + DB row); missing nombre → `['nombre'=>'required']`; duplicate DNI → `['dni'=>'unique']`; empty DNI persists null; same-DNI edit OK; foreign-DNI edit blocked. Run → failing.
- [ ] 5.2 GREEN — `form()` in `Section::columns(2)`: nombre/apellido required maxLength(100); dni `nullable()->unique()->maxLength(20)` (never `withoutTrashed`/`scopedUnique`); DatePicker fecha_nacimiento; Select sexo (masculino/femenino/otro); telefono maxLength(50); email email() maxLength(100); Textarea antecedentes; DatePicker fecha_alta. Filtered → passing.

## Phase 6: Infolist + Labels (RED→GREEN)

- [ ] 6.1 RED — add tests: View shows `edad` 30; heading "Pacientes". Run → failing.
- [ ] 6.2 GREEN — `infolist()`: Section 'Datos personales' + TextEntries (nombre/apellido/dni/sexo/fecha_nacimiento->date('d/m/Y')/edad `->placeholder('—')`/telefono/email/antecedentes columnSpanFull/fecha_alta); `$modelLabel='Paciente'`, `$pluralModelLabel='Pacientes'`, `$navigationIcon='heroicon-o-user-group'`. Filtered → passing.

## Phase 7: Resource Test Completion

- [ ] 7.1 Append soft-delete cases: delete hides row (`assertDontSeeTableRecords`); deleted DNI blocked; search excludes deleted. Full suite → green.

## Phase 8: Final Verification

- [ ] 8.1 `vendor/bin/pint --dirty --format agent`; `php artisan test --compact`; `php artisan migrate:fresh`. Done: green + clean.