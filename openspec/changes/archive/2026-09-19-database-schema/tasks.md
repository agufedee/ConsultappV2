# Tasks: Database Schema

## Overview

Break the database-schema change into 17 atomic implementation tasks across 7 groups. Total estimated lines: ~280-350.

---

## Group 1: Git Setup

### Task 1.1 — Create feature branch ✅

- **Action:** `git checkout -b feature/database-schema develop`
- **Dependencies:** None
- **Estimated lines:** 0 (command only)
- **Verification:** `git branch --show-current` → `feature/database-schema`

---

## Group 2: Migrations

### Task 2.1 — create_pacientes_table

- **File:** `database/migrations/2026_09_19_000001_create_pacientes_table.php`
- **Dependencies:** None
- **Estimated lines:** ~25
- **Spec ref:** `specs/database-migrations/spec.md` → Requirement: Pacientes Table Migration
- **Key details:**
  - Columns: id, nombre(100), apellido(100), dni(20 nullable unique), fecha_nacimiento(date nullable), sexo(20 nullable), telefono(50 nullable), email(100 nullable), antecedentes(text nullable), fecha_alta(date not null default today), timestamps
  - Use anonymous migration class (`return new class extends Migration`)
  - `fecha_alta` default: `$table->date('fecha_alta')->default(DB::raw('CURRENT_DATE'))`
- **Verification:** `php artisan migrate:fresh` completes without errors; `sqlite3 database/database.sqlite ".schema pacientes"` shows correct columns

### Task 2.2 — create_objetivos_table

- **File:** `database/migrations/2026_09_19_000002_create_objetivos_table.php`
- **Dependencies:** Task 2.1 (FK → pacientes)
- **Estimated lines:** ~18
- **Spec ref:** `specs/database-migrations/spec.md` → Requirement: Objetivos Table Migration
- **Key details:**
  - Columns: id, paciente_id(FK constrained cascadeOnDelete), tipo(100), peso_objetivo(5,2 nullable), fecha_objetivo(date nullable), estado(20 default 'activo'), timestamps
- **Verification:** `php artisan migrate:fresh` completes; delete a paciente → objetivos cascade

### Task 2.3 — create_consultas_table

- **File:** `database/migrations/2026_09_19_000003_create_consultas_table.php`
- **Dependencies:** Task 2.1 (FK → pacientes)
- **Estimated lines:** ~28
- **Spec ref:** `specs/database-migrations/spec.md` → Requirement: Consultas Table Migration
- **Key details:**
  - Columns: id, paciente_id(FK constrained cascadeOnDelete), fecha(date), motivo(30), peso(5,2), altura(5,2), imc(4,2 nullable), circunferencia_cintura(5,2 nullable), circunferencia_cadera(5,2 nullable), porcentaje_grasa(4,2 nullable), pliegues_cutaneos(json nullable), observaciones(text nullable), proximo_control(date nullable), timestamps
- **Verification:** `php artisan migrate:fresh` completes; FK ordering correct

### Task 2.4 — create_planes_alimentarios_table

- **File:** `database/migrations/2026_09_19_000004_create_planes_alimentarios_table.php`
- **Dependencies:** Task 2.3 (FK → consultas)
- **Estimated lines:** ~18
- **Spec ref:** `specs/database-migrations/spec.md` → Requirement: Planes Alimentarios Table Migration
- **Key details:**
  - Columns: id, consulta_id(FK constrained cascadeOnDelete), objetivo_calorico(unsignedSmallInteger nullable), descripcion(text nullable), archivo_adjunto(255 nullable), vigente_desde(date), vigente_hasta(date nullable), timestamps
- **Verification:** `php artisan migrate:fresh` completes; all 4 tables present

---

## Group 3: Models

### Task 3.1 — Paciente model

- **File:** `app/Models/Paciente.php`
- **Dependencies:** Task 2.1, 2.2 (tables exist for relationship resolution)
- **Estimated lines:** ~35
- **Spec ref:** `specs/domain-models/spec.md` → Requirement: Paciente Model
- **Key details:**
  - Follow `User.php` convention: `#[Fillable([...])]` attribute, `HasFactory` trait, `casts()` method
  - Relationships: `consultas(): HasMany(Consulta)`, `objetivos(): HasMany(Objetivo)`
  - Accessor: `getNombreCompletoAttribute(): string` → `trim("{$this->nombre} {$this->apellido}")`
  - Casts: `fecha_nacimiento => 'date'`, `fecha_alta => 'date'`
  - **Note:** `sexo` does NOT need a cast — it's already a string in DB, PHP treats it as string natively
- **Verification:** `php artisan tinker --execute 'echo App\Models\Paciente::class;'`

### Task 3.2 — Consulta model

- **File:** `app/Models/Consulta.php`
- **Dependencies:** Task 2.3, Task 3.1 (Paciente exists for belongsTo)
- **Estimated lines:** ~30
- **Spec ref:** `specs/domain-models/spec.md` → Requirement: Consulta Model
- **Key details:**
  - `#[Fillable([...])]` attribute
  - Relationships: `paciente(): BelongsTo(Paciente)`, `planAlimentario(): HasOne(PlanAlimentario)`
  - Casts: `fecha => 'date'`, `imc => 'float'`, `pliegues_cutaneos => 'array'`, `proximo_control => 'date'`
- **Verification:** `php artisan tinker --execute 'echo App\Models\Consulta::class;'`

### Task 3.3 — PlanAlimentario model

- **File:** `app/Models/PlanAlimentario.php`
- **Dependencies:** Task 2.4, Task 3.2 (Consulta exists for belongsTo)
- **Estimated lines:** ~25
- **Spec ref:** `specs/domain-models/spec.md` → Requirement: PlanAlimentario Model
- **Key details:**
  - `#[Fillable([...])]` attribute
  - Relationship: `consulta(): BelongsTo(Consulta)`
  - Casts: `objetivo_calorico => 'integer'`, `vigente_desde => 'date'`, `vigente_hasta => 'date'`
- **Verification:** `php artisan tinker --execute 'echo App\Models\PlanAlimentario::class;'`

### Task 3.4 — Objetivo model

- **File:** `app/Models/Objetivo.php`
- **Dependencies:** Task 2.2, Task 3.1 (Paciente exists for belongsTo)
- **Estimated lines:** ~25
- **Spec ref:** `specs/domain-models/spec.md` → Requirement: Objetivo Model
- **Key details:**
  - `#[Fillable([...])]` attribute
  - Relationship: `paciente(): BelongsTo(Paciente)`
  - Casts: `peso_objetivo => 'float'`, `fecha_objetivo => 'date'`
- **Verification:** `php artisan tinker --execute 'echo App\Models\Objetivo::class;'`

---

## Group 4: Factories

### Task 4.1 — PacienteFactory

- **File:** `database/factories/PacienteFactory.php`
- **Dependencies:** Task 3.1
- **Estimated lines:** ~30
- **Spec ref:** `specs/test-factories/spec.md` → Requirement: Paciente Factory
- **Key details:**
  - Use `fake('es_AR')` for firstName/lastName
  - `dni`: `fake()->unique()->numerify('########')` (8-digit string)
  - `telefono`: `fake('es_AR')->mobileNumber()`
  - `email`: `fake()->unique()->safeEmail()`
  - `antecedentes`: `fake('es_AR')->sentence(6)`
  - `fecha_alta`: `Carbon::today()`
  - `sexo`: `fake()->randomElement(['masculino', 'femenino', 'otro'])`
- **Verification:** `php artisan tinker --execute 'Paciente::factory()->create(); echo "OK";'`

### Task 4.2 — ConsultaFactory

- **File:** `database/factories/ConsultaFactory.php`
- **Dependencies:** Task 3.2, Task 4.1 (PacienteFactory for association)
- **Estimated lines:** ~35
- **Spec ref:** `specs/test-factories/spec.md` → Requirement: Consulta Factory
- **Key details:**
  - `paciente_id`: auto via `Paciente::factory()`
  - `peso`: `fake()->randomFloat(2, 45, 150)`
  - `altura`: `fake()->randomFloat(2, 140, 200)`
  - `imc`: computed: `round($peso / ($altura / 100) ** 2, 2)`
  - `motivo`: `fake()->randomElement(['primera_consulta', 'control', 'derivacion'])`
  - Optional fields: `circunferencia_cintura`, `circunferencia_cadera`, `porcentaje_grasa`, `pliegues_cutaneos` with `->optional()` or inline logic
  - **Important:** Compute `imc` from peso/altura in the factory closure, not from random values
- **Verification:** `php artisan tinker --execute '$c = Consulta::factory()->create(); echo "IMC: {$c->imc}";'`

### Task 4.3 — PlanAlimentarioFactory

- **File:** `database/factories/PlanAlimentarioFactory.php`
- **Dependencies:** Task 3.3, Task 4.2 (ConsultaFactory)
- **Estimated lines:** ~25
- **Spec ref:** `specs/test-factories/spec.md` → Requirement: PlanAlimentario Factory
- **Key details:**
  - `consulta_id`: auto via `Consulta::factory()`
  - `objetivo_calorico`: `fake()->randomElement([1200, 1500, 1800, 2000, 2200, 2500, 2800, 3000])`
  - `descripcion`: `fake('es_AR')->sentence(10)`
  - `vigente_desde`: `Carbon::today()`
  - `vigente_hasta`: `fake()->optional(0.7)->dateTimeBetween('+1 month', '+6 months')`
- **Verification:** `php artisan tinker --execute 'PlanAlimentario::factory()->create(); echo "OK";'`

### Task 4.4 — ObjetivoFactory

- **File:** `database/factories/ObjetivoFactory.php`
- **Dependencies:** Task 3.4, Task 4.1 (PacienteFactory)
- **Estimated lines:** ~22
- **Spec ref:** `specs/test-factories/spec.md` → Requirement: Objetivo Factory
- **Key details:**
  - `paciente_id`: auto via `Paciente::factory()`
  - `tipo`: `fake()->randomElement(['Descenso de peso', 'Hipertrofia', 'Control de glucemia', 'Mejora de composición corporal', 'Control tensional'])`
  - `peso_objetivo`: `fake()->randomFloat(2, 50, 120)`
  - `fecha_objetivo`: `fake()->optional(0.8)->dateTimeBetween('+1 month', '+1 year')`
  - `estado`: `'activo'`
- **Verification:** `php artisan tinker --execute 'Objetivo::factory()->create(); echo "OK";'`

---

## Group 5: Seeder

### Task 5.1 — Rewrite DatabaseSeeder

- **File:** `database/seeders/DatabaseSeeder.php`
- **Dependencies:** Tasks 4.1-4.4 (all factories)
- **Estimated lines:** ~35
- **Spec ref:** `specs/test-factories/spec.md` → Requirement: Seeder with Realistic Data
- **Key details:**
  - Keep existing `WithoutModelEvents` trait and `User::factory()` call
  - Add: create 5-10 pacientes via `Paciente::factory()->count(rand(5, 10))->create()`
  - Loop pacientes: create 2-4 consultas each, 50% of consultas get a PlanAlimentario, 1-2 objetivos per paciente
  - Use `Consulta::factory()->forPaciente()->create(...)` pattern
  - Ensure relationships are queryable: `Paciente::with('consultas.planAlimentario', 'objetivos')->get()`
- **Verification:** `php artisan migrate:fresh --seed` runs without errors; `php artisan tinker --execute 'echo Paciente::count();'` returns ≥5

---

## Group 6: Tests

### Task 6.1 — PacienteTest

- **File:** `tests/Feature/Models/PacienteTest.php`
- **Dependencies:** Tasks 3.1, 4.1
- **Estimated lines:** ~50
- **Spec ref:** `specs/domain-models/spec.md` + `specs/test-factories/spec.md`
- **Test cases:**
  1. `test_paciente_can_be_created_via_factory` — factory creates, assert exists
  2. `test_nombre_completo_accessor_returns_concatenated_name` — create with known nombre/apellido, assert accessor
  3. `test_paciente_has_many_consultas` — create paciente + 3 consultas, assert `consultas->count() === 3`
  4. `test_paciente_has_many_objetivos` — create paciente + 2 objetivos, assert `objetivos->count() === 2`
  5. `test_cascade_delete_removes_consultas_and_objetivos` — create related data, delete paciente, assert children gone
  6. `test_factory_overrides_work` — create with custom attributes, assert values
- **Verification:** `php artisan test --compact --filter=PacienteTest`

### Task 6.2 — ConsultaTest

- **File:** `tests/Feature/Models/ConsultaTest.php`
- **Dependencies:** Tasks 3.2, 4.2
- **Estimated lines:** ~40
- **Test cases:**
  1. `test_consulta_can_be_created_via_factory` — factory creates, assert exists
  2. `test_consulta_belongs_to_paciente` — create with associated paciente, assert `paciente` relationship
  3. `test_consulta_has_one_plan_alimentario` — create with plan, assert `planAlimentario` relationship
  4. `test_cascade_delete_via_paciente` — delete paciente, assert consultas removed
  5. `test_imc_is_stored_as_float` — create with known peso/altura, assert imc is float
- **Verification:** `php artisan test --compact --filter=ConsultaTest`

### Task 6.3 — PlanAlimentarioTest

- **File:** `tests/Feature/Models/PlanAlimentarioTest.php`
- **Dependencies:** Tasks 3.3, 4.3
- **Estimated lines:** ~30
- **Test cases:**
  1. `test_plan_alimentario_can_be_created_via_factory` — factory creates, assert exists
  2. `test_plan_alimentario_belongs_to_consulta` — create with associated consulta, assert relationship
  3. `test_cascade_delete_via_consulta` — delete consulta, assert plan removed
- **Verification:** `php artisan test --compact --filter=PlanAlimentarioTest`

### Task 6.4 — ObjetivoTest

- **File:** `tests/Feature/Models/ObjetivoTest.php`
- **Dependencies:** Tasks 3.4, 4.4
- **Estimated lines:** ~25
- **Test cases:**
  1. `test_objetivo_can_be_created_via_factory` — factory creates, assert exists
  2. `test_objetivo_belongs_to_paciente` — create with associated paciente, assert relationship
  3. `test_cascade_delete_via_paciente` — delete paciente, assert objetivo removed
- **Verification:** `php artisan test --compact --filter=ObjetivoTest`

---

## Group 7: Verify & Commit

### Task 7.1 — Full verification suite

- **Action:** Run all checks in sequence
- **Dependencies:** All previous tasks
- **Steps:**
  1. `php artisan migrate:fresh --seed` — all tables created, seeder runs
  2. `php artisan test --compact` — all tests pass
  3. `vendor/bin/pint --dirty --format agent` — code formatted
- **Verification:** All 3 commands exit 0 with no errors

### Task 7.2 — Commit and merge

- **Action:**
  1. `git add -A`
  2. `git commit -m "feat(domain): add foundational database schema with models, factories, and tests"`
  3. `git checkout develop && git merge feature/database-schema`
- **Dependencies:** Task 7.1
- **Verification:** `git log --oneline -5` shows the commit; `php artisan migrate:fresh --seed` still works on develop

---

## Dependency Graph

```
Group 1 (Git) ─────────────────────────────────────────────┐
                                                           │
Group 2 (Migrations)                                       │
  2.1 pacientes ──┬──► 2.2 objetivos                       │
  │               └──► 2.3 consultas ──► 2.4 planes        │
  │                                    │                   │
Group 3 (Models)                       │                   │
  3.1 Paciente ──┬──► 3.2 Consulta ──► 3.3 PlanAlimentario│
  │              └──► 3.4 Objetivo                         │
  │                                                        │
Group 4 (Factories)                                        │
  4.1 PacienteFactory ──┬──► 4.2 ConsultaFactory ──► 4.3 PA│
  │                     └──► 4.4 ObjetivoFactory           │
  │                                                        │
Group 5 (Seeder) ◄── 4.1-4.4                               │
  │                                                        │
Group 6 (Tests) ◄── 3.x + 4.x                             │
  6.1 PacienteTest ◄── 3.1, 4.1                            │
  6.2 ConsultaTest ◄── 3.2, 4.2                            │
  6.3 PlanAlimentarioTest ◄── 3.3, 4.3                     │
  6.4 ObjetivoTest ◄── 3.4, 4.4                            │
  │                                                        │
Group 7 (Verify) ◄── All                                   │
  7.1 Full verification                                     │
  7.2 Commit & merge ◄── 7.1                                │
```

---

## Summary

| Group | Tasks | Files | Est. Lines |
|-------|-------|-------|------------|
| Git Setup | 1 | 0 | 0 |
| Migrations | 4 | 4 | ~90 |
| Models | 4 | 4 | ~115 |
| Factories | 4 | 4 | ~112 |
| Seeder | 1 | 1 | ~35 |
| Tests | 4 | 4 | ~145 |
| Verify & Commit | 2 | 0 | 0 |
| **Total** | **17** | **17** | **~497** |
