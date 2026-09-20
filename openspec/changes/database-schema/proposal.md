# Proposal: Database Schema

## Intent

The Filament panel is installed and operational, but the application has no data layer — no models, no migrations, no relationships. This change creates the foundational domain schema that every subsequent feature (Pacientes resource, Consultas module, Planes Alimentarios, Chart evolution) depends on. Without it, nothing else can proceed.

## Scope

### In Scope

- 4 migrations: `pacientes`, `consultas`, `planes_alimentarios`, `objetivos`
- 4 Eloquent models with casts, fillable, and relationships
- 4 factories with realistic Argentine-nutrition domain data
- 1 seeder with meaningful test data (3+ patients, consultas, plans, objectives)
- Pest tests covering model relationships, accessor, and factory output

### Out of Scope

- Filament Resources or Relation Managers (next sprint)
- File upload/storage config for `archivo_adjunto` (planes-alimentarios sprint)
- Chart widgets or data visualization (chart-evolucion sprint)
- Validation rules beyond column types (Filament form layer handles this)
- Soft deletes (not in SDD spec)

## Capabilities

### New Capabilities

- `domain-models`: Eloquent models for Paciente, Consulta, PlanAlimentario, Objetivo — with relationships, casts, and the `nombre_completo` accessor on Paciente.
- `database-migrations`: Migrations for all 4 domain tables with correct column types, nullable constraints, FK cascades, and indexes.
- `test-factories`: Realistic factories for all 4 models with Argentine-domain data (DNI format,体重 ranges, motivo enums, estado enums).

### Modified Capabilities

None — this is the first data-layer change.

## Approach

1. **Migrations first** — use `php artisan make:migration` for each table, matching the SDD 4.2 dictionary exactly (column types, nullability, defaults).
2. **Models with relationships** — `Paciente` hasMany Consulta + Objetivo, `Consulta` belongsTo Paciente + hasOne PlanAlimentario, etc. Use string casts for enums (SQLite compatibility). Add `nombre_completo` accessor as `fn () => trim("{$this->nombre} {$this->apellido}")`.
3. **Factories** — each model gets a factory with `Faker\Provider\es_AR` for realistic data (DNI digits, Argentine phone format,体重 45–150 kg, altura 140–200 cm).
4. **Seeder** — `DatabaseSeeder` creates 3 patients with 2–3 consultas each, plans, and objectives.
5. **Tests** — Pest tests for: model exists, factory creates valid model, relationships resolve correctly, accessor returns concatenated name.
6. **Run Pint** — final formatting pass before completion.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `database/migrations/` | New | 4 migration files |
| `app/Models/` | New | 4 model files |
| `database/factories/` | New | 4 factory files |
| `database/seeders/` | Modified | `DatabaseSeeder` rewritten |
| `tests/Feature/Models/` | New | Pest tests for relationships |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Enum columns fail on SQLite dev/tests | Low | Use string column + cast, not native ENUM — matches locked decisions |
| Cascade deletes orphan related data unexpectedly | Low | Test cascade behavior in Pest tests; confirm ON DELETE CASCADE in migrations |
| Factory data unrealistic for Argentine context | Low | Use `es_AR` Faker provider; validate DNI format (8 digits), phone (54- prefix) |
| Migration order matters (pacientes before consultas) | Low | Use timestamps in migration filenames; pacientes + objetivos first, then consultas, then planes_alimentarios |

## Rollback Plan

- `php artisan migrate:fresh` drops all tables (SQLite dev only)
- Delete the 4 migration files, 4 models, 4 factories, seeder changes, and test files
- No production data to protect — greenfield project

## Dependencies

- `feature/setup-filament` (completed, archived) — Filament panel must exist for future integration, but this change has zero Filament dependency
- SQLite configured in `.env` for dev/tests (confirmed in archive report)

## Success Criteria

- [ ] `php artisan migrate:fresh --seed` runs without errors
- [ ] All 4 tables created with correct columns, types, and constraints
- [ ] All FK relationships work (Paciente→Consulta, Consulta→PlanAlimentario, Paciente→Objetivo)
- [ ] Cascade deletes propagate correctly (deleting a Paciente removes its Consultas, Objetivos)
- [ ] `nombre_completo` accessor returns "Nombre Apellido"
- [ ] Factories generate valid models (`Paciente::factory()->create()`)
- [ ] Pest tests pass (`php artisan test --compact`)
- [ ] Pint clean (`vendor/bin/pint --dirty --format agent`)
