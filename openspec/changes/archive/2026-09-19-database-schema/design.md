# Design: Database Schema

## Technical Approach

Create the foundational data layer for ConsultApp: 4 migrations, 4 Eloquent models, 4 factories, 1 seeder, and Pest tests. Follows the SDD 4.2 dictionary exactly. Uses SQLite-compatible string casts for enums. Follows existing codebase conventions (PHP 8 attributes for fillable, `casts()` method, anonymous migration classes).

## Architecture Decisions

### Decision: Enum Storage Strategy

**Choice**: VARCHAR columns with string casts (not native ENUM)
**Alternatives considered**: Native ENUM (MySQL), Integer backed enums
**Rationale**: SQLite (dev/tests) doesn't support native ENUM. String casts work everywhere. Matches SDD 4.2 which specifies ENUM values as strings.

### Decision: Model Attribute Pattern

**Choice**: PHP 8 `#[Fillable]` attribute + `casts()` method
**Alternatives considered**: Traditional `$fillable` array property
**Rationale**: Follows existing `User.php` convention exactly. The project uses PHP 8 constructor property promotion and attribute-based configuration.

### Decision: Migration Naming

**Choice**: Timestamped filenames with descriptive names (e.g., `2026_09_19_000001_create_pacientes_table.php`)
**Alternatives considered**: Sequential numbering (`001_`, `002_`)
**Rationale**: Laravel convention. Timestamps guarantee ordering. FK dependencies resolved by creation order: pacientes → consultas → planes_alimentarios, pacientes → objetivos.

### Decision: Test Database

**Choice**: SQLite in-memory (`:memory:`) via phpunit.xml config
**Alternatives considered**: Separate test database file
**Rationale**: Already configured in `phpunit.xml`. Faster, no cleanup needed. `RefreshDatabase` trait handles schema creation per test.

## Data Flow

```
Migrations (Schema)
    │
    ├── pacientes ──→ consultas ──→ planes_alimentarios
    │       │
    │       └──────→ objetivos
    │
Models (Eloquent)
    │
    ├── Paciente ──→ hasMany: Consulta, Objetivo
    ├── Consulta ──→ belongsTo: Paciente, hasOne: PlanAlimentario
    ├── PlanAlimentario ──→ belongsTo: Consulta
    └── Objetivo ──→ belongsTo: Paciente
    │
Factories (Faker es_AR)
    │
    └── Generate realistic Argentine data for all 4 models
    │
Seeder
    │
    └── DatabaseSeeder → 5-10 pacientes, 2-4 consultas each, planes + objetivos
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/2026_09_19_000001_create_pacientes_table.php` | Create | Pacientes table with DNI unique index |
| `database/migrations/2026_09_19_000002_create_objetivos_table.php` | Create | Objetivos table, FK → pacientes |
| `database/migrations/2026_09_19_000003_create_consultas_table.php` | Create | Consultas table, FK → pacientes |
| `database/migrations/2026_09_19_000004_create_planes_alimentarios_table.php` | Create | Planes table, FK → consultas |
| `app/Models/Paciente.php` | Create | Model with relationships + nombre_completo accessor |
| `app/Models/Consulta.php` | Create | Model with relationships + JSON cast |
| `app/Models/PlanAlimentario.php` | Create | Model with belongsTo Consulta |
| `app/Models/Objetivo.php` | Create | Model with belongsTo Paciente |
| `database/factories/PacienteFactory.php` | Create | es_AR Faker, DNI 8-digit unique |
| `database/factories/ConsultaFactory.php` | Create | peso/altura ranges, computed IMC |
| `database/factories/PlanAlimentarioFactory.php` | Create | 1200-3000 kcal range |
| `database/factories/ObjetivoFactory.php` | Create | tipo realistic, estado default activo |
| `database/seeders/DatabaseSeeder.php` | Modify | Rewrite with domain data |
| `tests/Feature/Models/PacienteTest.php` | Create | Relationships, accessor, factory tests |
| `tests/Feature/Models/ConsultaTest.php` | Create | Relationships, factory tests |
| `tests/Feature/Models/PlanAlimentarioTest.php` | Create | belongsTo, factory tests |
| `tests/Feature/Models/ObjetivoTest.php` | Create | belongsTo, factory tests |

## Migration Specifications

### create_pacientes_table

```php
Schema::create('pacientes', function (Blueprint $table) {
    $table->id();
    $table->string('nombre', 100);
    $table->string('apellido', 100);
    $table->string('dni', 20)->nullable()->unique();
    $table->date('fecha_nacimiento')->nullable();
    $table->string('sexo', 20)->nullable(); // enum: masculino, femenino, otro
    $table->string('telefono', 50)->nullable();
    $table->string('email', 100)->nullable();
    $table->text('antecedentes')->nullable();
    $table->date('fecha_alta');
    $table->timestamps();
});
```

### create_objetivos_table

```php
Schema::create('objetivos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->string('tipo', 100);
    $table->decimal('peso_objetivo', 5, 2)->nullable();
    $table->date('fecha_objetivo')->nullable();
    $table->string('estado', 20)->default('activo'); // enum: activo, cumplido, abandonado
    $table->timestamps();
});
```

### create_consultas_table

```php
Schema::create('consultas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('fecha');
    $table->string('motivo', 30); // enum: primera_consulta, control, derivacion
    $table->decimal('peso', 5, 2);
    $table->decimal('altura', 5, 2);
    $table->decimal('imc', 4, 2)->nullable();
    $table->decimal('circunferencia_cintura', 5, 2)->nullable();
    $table->decimal('circunferencia_cadera', 5, 2)->nullable();
    $table->decimal('porcentaje_grasa', 4, 2)->nullable();
    $table->json('pliegues_cutaneos')->nullable();
    $table->text('observaciones')->nullable();
    $table->date('proximo_control')->nullable();
    $table->timestamps();
});
```

### create_planes_alimentarios_table

```php
Schema::create('planes_alimentarios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('consulta_id')->constrained('consultas')->cascadeOnDelete();
    $table->unsignedSmallInteger('objetivo_calorico')->nullable();
    $table->text('descripcion')->nullable();
    $table->string('archivo_adjunto', 255)->nullable();
    $table->date('vigente_desde');
    $table->date('vigente_hasta')->nullable();
    $table->timestamps();
});
```

## Model Specifications

All models follow the existing `User.php` pattern: `#[Fillable]` attribute, `casts()` method, `HasFactory` trait.

### Paciente

```php
#[Fillable(['nombre', 'apellido', 'dni', 'fecha_nacimiento', 'sexo', 'telefono', 'email', 'antecedentes', 'fecha_alta'])]
class Paciente extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_alta' => 'date',
        ];
    }

    public function consultas(): HasMany { return $this->hasMany(Consulta::class); }
    public function objetivos(): HasMany { return $this->hasMany(Objetivo::class); }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }
}
```

### Consulta

```php
#[Fillable(['paciente_id', 'fecha', 'motivo', 'peso', 'altura', 'imc', 'circunferencia_cintura', 'circunferencia_cadera', 'porcentaje_grasa', 'pliegues_cutaneos', 'observaciones', 'proximo_control'])]
class Consulta extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'imc' => 'float',
            'pliegues_cutaneos' => 'array',
            'proximo_control' => 'date',
        ];
    }

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function planAlimentario(): HasOne { return $this->hasOne(PlanAlimentario::class); }
}
```

### PlanAlimentario

```php
#[Fillable(['consulta_id', 'objetivo_calorico', 'descripcion', 'archivo_adjunto', 'vigente_desde', 'vigente_hasta'])]
class PlanAlimentario extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'objetivo_calorico' => 'integer',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function consulta(): BelongsTo { return $this->belongsTo(Consulta::class); }
}
```

### Objetivo

```php
#[Fillable(['paciente_id', 'tipo', 'peso_objetivo', 'fecha_objetivo', 'estado'])]
class Objetivo extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'peso_objetivo' => 'float',
            'fecha_objetivo' => 'date',
        ];
    }

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
}
```

## Factory Specifications

All factories use `Faker\Provider\es_AR` for Argentine-locale data. Use global `fake()` function (project convention from UserFactory).

### PacienteFactory

- `nombre`: `fake('es_AR')->firstName()`
- `apellido`: `fake('es_AR')->lastName()`
- `dni`: `fake()->unique()->numerify('########')` (8-digit string, unique)
- `telefono`: `fake('es_AR')->mobileNumber()` (format: +54 XX XXXX-XXXX)
- `email`: `fake()->unique()->safeEmail()`
- `antecedentes`: `fake('es_AR')->sentence(6)`
- `fecha_alta`: `Carbon::today()`

### ConsultaFactory

- `paciente_id`: `Paciente::factory()` (auto-created)
- `fecha`: `fake()->dateTimeBetween('-1 year', 'now')`
- `motivo`: `fake()->randomElement(['primera_consulta', 'control', 'derivacion'])`
- `peso`: `fake()->randomFloat(2, 45, 150)`
- `altura`: `fake()->randomFloat(2, 140, 200)`
- `imc`: computed from peso/altura: `round($peso / ($altura / 100) ** 2, 2)`
- `circunferencia_cintura`: `fake()->optional(0.6)->randomFloat(2, 60, 120)`
- `circunferencia_cadera`: `fake()->optional(0.6)->randomFloat(2, 70, 130)`
- `porcentaje_grasa`: `fake()->optional(0.5)->randomFloat(2, 10, 45)`
- `pliegues_cutaneos`: `fake()->optional(0.4)->passthrough(['subescapular' => rand(10, 40), 'triceps' => rand(8, 30)])`

### PlanAlimentarioFactory

- `consulta_id`: `Consulta::factory()`
- `objetivo_calorico`: `fake()->randomElement([1200, 1500, 1800, 2000, 2200, 2500, 2800, 3000])`
- `descripcion`: `fake('es_AR')->sentence(10)`
- `vigente_desde`: `Carbon::today()`
- `vigente_hasta`: `fake()->optional(0.7)->dateTimeBetween('+1 month', '+6 months')`

### ObjetivoFactory

- `paciente_id`: `Paciente::factory()`
- `tipo`: `fake()->randomElement(['Descenso de peso', 'Hipertrofia', 'Control de glucemia', 'Mejora de composición corporal', 'Control tensional'])`
- `peso_objetivo`: `fake()->randomFloat(2, 50, 120)`
- `fecha_objetivo`: `fake()->optional(0.8)->dateTimeBetween('+1 month', '+1 year')`
- `estado`: `'activo'` (default)

## Seeder Design

`DatabaseSeeder` creates a realistic dataset:

1. **5-10 Pacientes** via factory
2. **2-4 Consultas per paciente** via `Consulta::factory()->count(rand(2, 4))->forPaciente()->create(['paciente_id' => $paciente->id])`
3. **50% of consultas get a PlanAlimentario** via `PlanAlimentario::factory()->forConsulta()->create(['consulta_id' => $consulta->id])`
4. **1-2 Objetivos per paciente** via `Objetivo::factory()->count(rand(1, 2))->forPaciente()->create(['paciente_id' => $paciente->id])`

Uses `WithoutModelEvents` trait (already in existing seeder) to prevent event dispatching during seed.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Model exists, factory creates valid record | `Model::factory()->create()` + assert exists |
| Integration | Relationships resolve correctly | Create parent + children, assert relationship loads |
| Integration | Cascade deletes work | Delete parent, assert children removed |
| Integration | Accessor returns correct format | Create paciente, assert `nombre_completo` |
| Integration | Factory overrides work | Create with custom attributes, assert values |

All tests use `RefreshDatabase` trait. Tests in `tests/Feature/Models/` directory.

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

No data migration needed — greenfield project. `php artisan migrate:fresh --seed` recreates everything.

## Open Questions

- None — all specs are complete and unambiguous.
