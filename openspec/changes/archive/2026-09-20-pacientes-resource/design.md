# Design: Pacientes Resource (first Filament CRUD)

## Technical Approach

Generate-then-customize on `feature/pacientes-resource` (current branch): run the Filament v5.8.2 resource generator with embedded schemas, then hand-tune form/table/infolist, model (SoftDeletes + `edad` accessor), an additive soft-deletes migration, and a single Pest resource test file. Verified against installed vendor (Filament 5.8.2, Laravel 13.26.1, Livewire 4.4.5, Carbon 3.13.2). Maps to specs `paciente-resource`, `domain-models`, `database-migrations`.

## Architecture Decisions

### Decision: Generator command and file layout

| Option | Tradeoff | Decision |
|---|---|---|
| `--simple` | 1 Manage page + modals; no canonical View URL; weaker home for Sprint 3 RelationManagers | Rejected |
| Full resource, default | 8 files (separate Schemas/) → ~480-600 lines vs 400 budget | Rejected |
| **Full resource + `--embed-schemas --embed-table`** | **5 files, View page included, schemas inline** | **Chosen** |

Exact command:
```bash
php artisan make:filament-resource Paciente --panel=consultapp --view \
  --record-title-attribute=nombre --embed-schemas --embed-table
```
Do NOT pass `--simple` or `--soft-deletes` (the latter adds trashed-filter/restore UI — out of scope). Generator (verified in `MakeResourceCommand.php`) emits 5 files; `app/Filament/` does not exist yet (first resource):

| File | Content |
|---|---|
| `app/Filament/Resources/PacienteResource.php` | Resource + embedded `form()`/`table()`/`infolist()` (~250-300 lines) |
| `app/Filament/Resources/PacienteResource/Pages/ListPacientes.php` | List page |
| `app/Filament/Resources/PacienteResource/Pages/CreatePaciente.php` | Create page |
| `app/Filament/Resources/PacienteResource/Pages/EditPaciente.php` | Edit page |
| `app/Filament/Resources/PacienteResource/Pages/ViewPaciente.php` | View page |

`--record-title-attribute=nombre` generates `protected static ?string $recordTitleAttribute = 'nombre';`. Route slug auto-derives to `pacientes` → `/consultapp/pacientes`.

### Decision: DNI validation wiring (`unique()` + `ignoreRecord` + trashed rows)

| Option | Tradeoff | Decision |
|---|---|---|
| `scopedUnique()` | Applies model scopes → soft-deleted DNIs would pass → violates spec "Deleted DNI stays blocked" | Rejected |
| **`->nullable()->unique()`** | **Raw `Rule::unique` DB count, ignores current record on Edit, counts trashed rows** | **Chosen** |

Exact wiring (verified in `CanBeValidated.php` + Filament docs): `unique()` registers a closure rule — on Create, `Rule::unique('pacientes', 'dni')` with no ignore; on Edit, because `shouldUniqueValidationIgnoreRecordByDefault()` is `true` and the form is bound to an Eloquent record, it becomes `Rule::unique('pacientes', 'dni')->ignore($record->getOriginal('id'), 'pacientes.id')`. Laravel's `Unique` rule runs a presence-verifier count against the **table directly** (`ValidatesAttributes::validateUnique` → `getCount`), never the Eloquent builder — so the `SoftDeletingScope` is NOT applied and a trashed row with the same DNI fails validation. This is the desired blocking semantics; do NOT add `withoutTrashed()`/`scopedUnique` anywhere. Empty input: Livewire's update route runs under the `web` group (verified `HandleRequests.php` line 30), so `ConvertEmptyStringsToNull` turns `''` into `null`; `->nullable()` marks the field optional. NULLs never trip the SQLite unique index.

### Decision: Soft deletes migration

| Option | Tradeoff | Decision |
|---|---|---|
| Drop columns / cascade | Destructive; loses history | Rejected |
| **Additive `softDeletes()` + explicit index** | **Row kept, `deleted_at` set, consultas/objetivos untouched (cascades never fire — no DELETE issued)** | **Chosen** |

Verified: Laravel 13 `Blueprint::softDeletes()` adds a nullable timestamp but **no index** — add `$table->index('deleted_at')` explicitly. Soft-delete visibility (list + global search) comes from the model's `SoftDeletingScope`, not from the generator flag.

### Decision: `edad` accessor semantics

| Option | Tradeoff | Decision |
|---|---|---|
| Manual diff/floor | Reimplements Carbon | Rejected |
| **`fn () => $this->fecha_nacimiento?->age`** | **Carbon `->age` = integer full years, anniversary-based (verified by tinker: 30y−1d→29, exactly 30y→30); `?->` yields null when `fecha_nacimiento` null (cast `date`)** | **Chosen** |

### Decision: Global search

| Option | Tradeoff | Decision |
|---|---|---|
| Default (recordTitleAttribute only) | Searches just `nombre` | Rejected |
| `nombre_completo` accessor | Accessor not SQL-searchable → query break | Rejected |
| **Override `getGloballySearchableAttributes(): ['nombre','apellido','dni']`** | **Real columns; `recordTitleAttribute('nombre')` stays** | **Chosen** |

### Decision: Spanish labels and navigation

`modelLabel` `'Paciente'`, `pluralModelLabel` `'Pacientes'`, `navigationIcon` `'heroicon-o-user-group'` (heroicons 2.7.0 installed). No `navigationLabel`, no navigation group — keep simple; becomes the convention for later resources.

## Data Flow

```
ListPacientes ── searchable/sortable TextColumns on real columns
   │
Create/Edit ── fillForm → validate (unique w/ ignoreRecord) → persist
   │              └ dni '' → ConvertEmptyStringsToNull → NULL
Delete (List/Edit header DeleteAction) ── $record->delete() → SoftDeletes sets deleted_at
   │              └ cascades idle (no DELETE); consultas/objetivos intact
ViewPaciente ── infolist TextEntry::make('edad') → $record->edad accessor
Global search ── getGloballySearchableAttributes → WHERE on nombre/apellido/dni (trashed excluded by scope)
```

## File Changes

| File | Action | Description |
|---|---|---|
| `app/Filament/Resources/PacienteResource.php` | Create | Resource: embedded form/table/infolist, labels, global search |
| `app/Filament/Resources/PacienteResource/Pages/{List,Create,Edit,View}Paciente.php` | Create | 4 pages (generator output) |
| `database/migrations/2026_09_20_000001_add_soft_deletes_to_pacientes.php` | Create | `softDeletes()` + `index('deleted_at')`; down drops both |
| `app/Models/Paciente.php` | Modify | `use SoftDeletes;` + `getEdadAttribute(): ?int` |
| `tests/Feature/Filament/PacienteResourceTest.php` | Create | Resource tests (~130-180 lines) |

## Form / Table / Infolist Contract

```php
// Form (embedded in Resource::form())
TextInput::make('nombre')->required()->maxLength(100);      // column length 100
TextInput::make('apellido')->required()->maxLength(100);
TextInput::make('dni')->nullable()->unique()->maxLength(20); // wiring in Decision 2
DatePicker::make('fecha_nacimiento');
Select::make('sexo')->options(['masculino' => 'Masculino', 'femenino' => 'Femenino', 'otro' => 'Otro']);
TextInput::make('telefono')->maxLength(50);
TextInput::make('email')->email()->maxLength(100);
Textarea::make('antecedentes');
DatePicker::make('fecha_alta');
// Wrap in Section::make()->schema([...])->columns(2)  // verified: Section::columns() exists
```
All fields are fillable (`#[Fillable]` already covers every column). `fecha_alta` is NOT form-required — DB default `CURRENT_DATE` covers it. No `->numeric()` on dni (string(20) column, es_AR data).

```php
// Table: TextColumn::make('nombre')->searchable()->sortable(),
//   apellido searchable()+sortable(), dni searchable(), sexo, fecha_alta->date('d/m/Y')
//   sortable(); $table->defaultSort('apellido')  // verified CanSortRecords
// toggleable(): sexo + fecha_alta (secondary info); primary 3 stay fixed
```

```php
// Infolist (ViewPaciente): Section::make('Datos personales')->schema([
//   TextEntry::make('nombre'), TextEntry::make('apellido'), TextEntry::make('dni'),
//   TextEntry::make('sexo'), TextEntry::make('fecha_nacimiento')->date('d/m/Y'),
//   TextEntry::make('edad')->placeholder('—'),   // resolves getEdadAttribute(); placeholder when null
//   TextEntry::make('telefono'), TextEntry::make('email'),
//   TextEntry::make('antecedentes')->columnSpanFull(), TextEntry::make('fecha_alta')->date('d/m/Y'),
// ]);
```

## Testing Strategy

`tests/Feature/Filament/PacienteResourceTest.php`, `uses(RefreshDatabase::class)` per file, SQLite `:memory:` (phpunit.xml), `it()` style (matches `ConsultappPanelTest.php`), `use Livewire\Livewire;`. Mixins verified registered (`Testable::mixin` in Forms/Tables service providers).

| Layer | What to Test | Approach |
|---|---|---|
| List | renders records | `livewire(ListPacientes::class)->assertCanSeeTableRecords([...])` |
| List | search by nombre / apellido / dni | `->searchTable('María')` / `'González'` / `'30123456'` → `assertCanSeeTableRecords` / `assertDontSeeTableRecords` |
| Create | success | `fillForm([...])->call('create')->assertHasNoFormErrors()` + DB row |
| Create | missing nombre | `->assertHasFormErrors(['nombre' => 'required'])` |
| Create | duplicate DNI | `->assertHasFormErrors(['dni' => 'unique'])` |
| Create | empty DNI persists null | assert `$paciente->fresh()->dni` is null |
| Edit | same-DNI save OK | `livewire(EditPaciente::class, ['record' => $p->getRouteKey()])->fillForm(['dni' => same])->call('save')->assertHasNoFormErrors()` |
| Edit | another record's DNI | `->assertHasFormErrors(['dni' => 'unique'])` |
| Soft delete | delete hides row; deleted DNI still blocked | delete → `assertDontSeeTableRecords`; re-create with same DNI → create rejected |
| Soft delete | search excludes deleted | search term only matches active row |
| Model | `edad` (30y / null) | domain tests in `tests/Feature/Models/PacienteTest.php` additions or inline |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. (Artisan generation is a one-time dev step, not app runtime behavior.)

## Migration / Rollout

Additive, non-destructive. `php artisan migrate` adds `deleted_at` (nullable, indexed) — existing rows get NULL (become active). Rollback: `php artisan migrate:rollback --step=1` drops only `deleted_at` + its index. No feature flags, no data migration. Verification: `php artisan test --compact`, then `vendor/bin/pint --dirty --format agent`. Line-budget forecast: ~540-640 added lines → **400-line budget risk: High** — tasks phase must resolve delivery (chained PRs / size exception) per session delivery strategy.

## Open Questions

- None — vendor and docs verification closed all spec risks.