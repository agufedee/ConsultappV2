# Design: Consultas Module (reactive IMC form + ConsultasRelationManager)

## Technical Approach

Generate-then-customize on `feature/consultas-modulo`: `make:filament-resource Consulta --panel=consultapp --view --embed-schemas --embed-table --resource-namespace=App\Filament\Resources\Consultas` creates the resource + 4 pages, then hand-normalize to v5 namespaces and build the reactive IMC form (SDD §5.2). A small additive migration widens `consultas.imc` to DECIMAL(5,2) so the spec's extreme case (300kg @ 100cm → 300.00) fits. `ConsultasRelationManager` reuses the resource form via v5 related-resource delegation (`static::$relatedResource`), with its own `table()` adding a modal CreateAction. Only existing-code touch: `PacienteResource::getRelations()`. All Filament APIs below verified against installed vendor (5.8.2) this session, not assumed.

## Architecture Decisions

### Decision: Migration `widen_imc_on_consultas`

| Option | Tradeoff | Decision |
|---|---|---|
| Keep DECIMAL(4,2), clamp ranges so IMC ≤ 99.99 | Contradicts spec scenario 300/100 → 300.00 | Rejected |
| **Additive `->change()` to DECIMAL(5,2)** | **Non-destructive; SQLite `change()` handled by Laravel table-rebuild; only column touched** | **Chosen** |

`database/migrations/2026_09_21_000001_widen_imc_on_consultas.php` (prefix matches `2026_09_20_000001_*` precedent, alter-migrations carry no `_table` suffix): `up()` → `$table->decimal('imc', 5, 2)->nullable()->change();` inside `Schema::table('consultas', ...)`; `down()` restores `decimal('imc', 4, 2)->nullable()->change();`. No index, no other column/constraint touched. Factory IMC formula untouched.

### Decision: Generator command and file layout

| Option | Tradeoff | Decision |
|---|---|---|
| `--simple` | Modals-only; no View page; breaks RM-first pattern | Rejected |
| **Full resource + `--embed-schemas --embed-table --view`** | **5 files; schemas inline in resource (house pattern); View included** | **Chosen** |

`php artisan make:filament-resource Consulta --panel=consultapp --view --record-title-attribute=motivo --embed-schemas --embed-table --resource-namespace=App\\Filament\\Resources\\Consultas` → `app/Filament/Resources/Consultas/ConsultaResource.php` + `Pages/{List,Create,Edit,View}Consulta.php`. Route slug `consultas`; panel auto-discovers the new folder. Nav hidden via `protected static bool $shouldRegisterNavigation = false;`.

### Decision: Reactive IMC wiring (`calculateImc`)

`peso`/`altura` → `->numeric()` ranges (20–300 step 0.1 kg; 100–250 step 0.5 cm) `->live(onBlur: true)->afterStateUpdated($calculateImc)`; `imc` → `->readOnly()->suffix('kg/m²')`, still dehydrated so the computed value persists. Closure MUST cast to float (model has no casts on peso/altura → strings from DB) and guard null/zero altura:

```php
use Filament\Schemas\Components\Utilities\{Get, Set}; // v5 namespace (verified in vendor)

$calculateImc = function (Set $set, Get $get): void {
    $peso = (float) $get('peso');       // DB returns strings — cast is mandatory
    $altura = (float) $get('altura');

    if ($altura <= 0) {                 // null/zero guard
        $set('imc', null);

        return;
    }

    $set('imc', round($peso / ($altura / 100) ** 2, 2));
};
```

Fires on any peso/altura change in Create AND Edit (afterStateUpdated runs on state update; onBlur only gates the browser event). Standalone-create path: `Select::make('paciente_id')->relationship('paciente', 'nombre_completo')->required()->preload()` — `->hidden(fn (HasSchemas $livewire): bool => $livewire instanceof RelationManager)` (hidden ⇒ not dehydrated, verified `HasState::isDehydrated`), so the RM modal auto-binds the owner and never lets the user re-point the consulta, while the standalone page stays functional.

### Decision: RM delegation — modals inside the paciente View

| Option | Tradeoff | Decision |
|---|---|---|
| Bare generated RM (`--related-resource` only) | Generator emits NO `table()` (inherited) → **no create button**; `getDefaultActionUrl` resolves resource page URLs → rows/header navigate OUT of the paciente page, breaking the spec "create modal" scenario | Rejected |
| **`$relatedResource` + own `table()` + null `getDefaultActionUrl`** | **Form/infolist auto-delegated (defined once); explicit header CreateAction; `getDefaultActionUrl(): ?string { return null; }` (verified `RelationManager::getDefaultActionUrl` + `CanOpenUrl::getUrl` fallback) forces modal create/edit/view** | **Chosen** |

```php
protected static string $relationship = 'consultas';
protected static string $relatedResource = ConsultaResource::class; // form/infolist/table delegation

public static function table(Table $table): Table
{
    return ConsultaResource::table($table)->headerActions([CreateAction::make()]);
}

public function getDefaultActionUrl(Action $action): ?string { return null; }

public static function getBadge(Model $ownerRecord, string $pageClass): ?string
{
    $count = $ownerRecord->consultas()->count();

    return $count > 0 ? (string) $count : null;
}
```

Verified chain: `InteractsWithRelationshipTable::form()/infolist()` call `static::getRelatedResource()::form($schema)` (no duplication); `makeTable()` applies `relatedResource::configureTable` + `relationship(fn () => $this->getRelationship())` (owner-scoped query). Create submit runs `$this->getRelationship()->create($data)` → Eloquent HasMany auto-sets `paciente_id`.

### Decision: Table, labels, global search, pliegues

| Topic | Decision |
|---|---|
| Table columns | `fecha` (date d/m/Y, sortable), `motivo` (`formatStateUsing` via shared `motivoOptions()`), `peso`, `altura`, `imc`; `->defaultSort('fecha', 'desc')` (verified `defaultSort($column, $direction)` in v5); `->recordActions([ViewAction, EditAction])`, `->toolbarActions([BulkActionGroup([DeleteBulkAction])])` per house |
| Labels | `modelLabel='Consulta'`, `pluralModelLabel='Consultas'`; `motivoOptions()`: `primera_consulta`→"Primera consulta", `control`→"Control", `derivacion`→"Derivación" (Select options + table display share one source) |
| Global search | `getGloballySearchableAttributes(): []` — nav hidden, no standalone surface; `recordTitleAttribute='motivo'` (real column) for modal headers |
| Pliegues | `Repeater::make('pliegues_cutaneos')->schema([TextInput pliegue required, TextInput mm required numeric])->columns(2)`; no `->default()` → untouched state null → NULL persists via `array` cast (spec: empty list → NULL). Edge: add-then-remove-all rows persist `[]` (not NULL) — normalize with `->mutateDehydratedState(fn ($state) => empty($state) ? null : $state)` on the Repeater; task-level note for sdd-tasks |
| Infolist | Section with TextEntry fecha/motivo/peso/altura/imc/medidas/observaciones/proximo_control, `->date('d/m/Y')` + `->placeholder('—')` per house |

## Data Flow

```
PacienteView ── getRelations() → ConsultasRelationManager (tab, badge N)
   ├─ table: owner-scoped consultas (fecha desc) ── rows → edit/view modals (delegated form/infolist)
   └─ CreateAction modal ── delegated ConsultaResource::form (reactive IMC)
        └─ submit → relationship->create(data) → paciente_id auto-set
Create/EditConsulta (standalone, nav hidden):
   fillForm → validate (peso/altura ranges, motivo/fecha/paciente required, imc readOnly skipped) → persist computed imc
peso/altura live(onBlur) → afterStateUpdated(calculateImc) → imc readOnly state → dehydrated into save payload
```

## File Changes

| File | Action | Est. lines |
|---|---|---|
| `database/migrations/2026_09_21_000001_widen_imc_on_consultas.php` | Create | ~20 |
| `app/Filament/Resources/Consultas/ConsultaResource.php` | Create | ~155 |
| `app/Filament/Resources/Consultas/Pages/{List,Create,Edit,View}Consulta.php` | Create | ~60 (4×15) |
| `app/Filament/Resources/Pacientes/RelationManagers/ConsultasRelationManager.php` | Create | ~60 |
| `app/Filament/Resources/Pacientes/PacienteResource.php` | Modify | +3 (`getRelations()` → `[ConsultasRelationManager::class]`) — only existing-code touch |
| `tests/Feature/Filament/ConsultaResourceTest.php` | Create | ~165 |
| `tests/Feature/Filament/ConsultasRelationManagerTest.php` | Create | ~90 |

## v3→v5 Normalization Checklist (generator output)

1. `Filament\Schemas\Components\Section` — NOT `Filament\Forms\Components\Section`.
2. `Filament\Actions\*` (`CreateAction`, `EditAction`, `ViewAction`, `DeleteAction`, `BulkActionGroup`, `DeleteBulkAction`) — NOT `Filament\Tables\Actions\*`.
3. Table API `->recordActions([...])` + `->toolbarActions([...])` — v3 `->actions()`/`->bulkActions()` gone.
4. Resource signature `form/infolist(Schema $schema): Schema` with `Filament\Schemas\Schema`; RM inherits delegation (no schema methods).
5. Embedded schemas stay in the resource (one file); Pages are thin classes (house precedent).
6. `live(onBlur: true)` + `afterStateUpdated(Set|Get)` with `Filament\Schemas\Components\Utilities\{Get, Set}`.
7. `->defaultSort('fecha', 'desc')` direction arg exists in v5.
8. Inline Select options (motivo) — no DB enums (sexo precedent).
9. Repeater from `Filament\Forms\Components\Repeater`.
10. Plural Spanish resource folder `App\Filament\Resources\Consultas\` + `Pages\` (Pacientes precedent).

## Testing Strategy

`uses(RefreshDatabase::class)`, `beforeEach` actingAs admin factory, Livewire mixins — mirrors PacienteResourceTest.

| Layer | What to Test | Approach |
|---|---|---|
| Feature — ConsultaResourceTest | list renders; create 82.40/174.00 → imc 27.22 persisted; edit recompute `->set('data.peso', 80.00)` → `->assertFormSet(['imc' => 27.68])` (array arg per v5 signature, no blur); extreme 300/100 → 300.00 saved; motivo required; peso <20 / altura >250 rejected; fecha required; pliegues JSON stored; empty-pliegues → NULL (includes add-then-remove-all → `[]` normalized to NULL by `mutateDehydratedState` — task carries forward); hidden nav `expect(ConsultaResource::$shouldRegisterNavigation)->toBeFalse()` | `Livewire::test(List/Create/EditConsulta::class)` + `fillForm`/`assertHasFormErrors`/`assertDatabaseHas` |
| Feature — ConsultasRelationManagerTest | mounts with `['ownerRecord' => $paciente, 'pageClass' => ViewPaciente::class]` → `assertOk()`; table shows only owner's 3 consultas (`assertCanSeeTableRecords`/`assertDontSeeTableRecords` with second paciente); create via `->callAction(TestAction::make(CreateAction::class)->table(), [...])` → `assertDatabaseHas('consultas', ['paciente_id' => $paciente->id])` (docs-shipped pattern) | `Livewire::test(ConsultasRelationManager::class, ...)` |

Untouched: PacienteResourceTest, Model tests, ConsultappPanelTest (RM mount does not touch list/create/edit).

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary (Artisan generation is a one-time dev step, not runtime behavior).

## Migration / Rollout

Additive, non-destructive: `php artisan migrate` widens imc in place; existing rows unchanged (values ≤ 99.99 fit (5,2)). Rollback: `php artisan migrate:rollback --step=1` restores (4,2). No feature flags, no data migration. Verification: `php artisan test --compact`, then `vendor/bin/pint --dirty --format agent`.

## Line Budget & Delivery Slices

Forecast ~550–600 authored lines → **400-line review budget: High**. Recommend 2 chained PR slices (decision needed in tasks):

| Slice | Contents | Est. |
|---|---|---|
| 1 — Resource | migration + ConsultaResource + 4 Pages + ConsultaResourceTest | ~400 (trim test scenarios to keep ≤400) |
| 2 — Integration | ConsultasRelationManager + PacienteResource::getRelations + ConsultasRelationManagerTest | ~153 |

`Decision needed before apply: Yes` · `Chained PRs recommended: Yes`.

## Open Questions

- None blocking. Verification note: if `set('data.peso', ...)` does not fire `afterStateUpdated` for `onBlur` fields in the Livewire test, fall back to `fillForm()` for the edit-recompute scenario (same assertion) — apply phase must confirm with the RED test.