# Tasks: Consultas Módulo (reactive IMC + ConsultasRelationManager)

Sprint 2. Branch: `feature/consultas-modulo`. TDD on.

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~553 + ~35 test ≈ 590 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (migration ≈40) → PR 2 (resource+form ≈496 w/ tests) → PR 3 (RM ≈230 w/ tests) |
| Delivery strategy | ask-on-risk |
| Chain strategy | feature-branch-chain |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units (PR3=PR2=PR1 → feature-branch-chain)

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Widen imc migration + test | PR 1 | `php artisan test --compact --filter=WidenImcMigrationTest` | migrate:fresh; rollback --step=1 | Revert migration |
| 2 | ConsultaResource + Pages + tests | PR 2 | `php artisan test --compact --filter=ConsultaResourceTest` | /consultapp/consultas CRUD | Revert migration; delete Consultas/ |
| 3 | RM + PacienteResource::getRelations + test | PR 3 | `php artisan test --compact --filter=ConsultasRelationManagerTest` | /consultapp/pacientes/{id} tab; modal | Delete RM file; revert getRelations() |

Commits: S1 `feat(migrations): widen imc` + `feat(consultas): add ConsultaResource`; S2 `feat(consultas): mount ConsultasRelationManager` + `chore(sdd): mark consultas-modulo slice 2 tasks complete`.

## Phase 1: Migration (RED→GREEN)

- [x] 1.1 RED — `make:test --pest WidenImcMigrationTest` → `tests/Feature/Database/WidenImcMigrationTest.php`: `Schema::getColumns('consultas')` imc DECIMAL(5,2); rollback restores (4,2). → fails. NOTE: SQLite type = bare `decimal`; fallback migrate:fresh + extreme persists.
- [x] 1.2 GREEN — `database/migrations/2026_09_21_000001_widen_imc_on_consultas.php`: `$table->decimal('imc',5,2)->nullable()->change()`; down (4,2). Factory untouched. → green.

## Phase 2: Scaffold + v5 Normalization

- [x] 2.1 Generate: `make:filament-resource Consulta --panel=consultapp --view --record-title-attribute=motivo --embed-schemas --embed-table --resource-namespace=App\\Filament\\Resources\\Consultas` → 5 files, slug consultas.
- [x] 2.2 Normalize v5: `Filament\Schemas\Components\Section`; `Filament\Actions\*`; `recordActions()/toolbarActions()`; `form/infolist(Schema $schema): Schema`; embedded schemas (thin Pages); `$shouldRegisterNavigation=false`; labels; global search `[]`; `$recordTitleAttribute='motivo'`.

## Phase 3: Reactive Form + Table (RED→GREEN)

- [x] 3.1 RED — `make:test --pest Filament/ConsultaResourceTest` (RefreshDatabase, admin): list; create 82.40/174.00→imc 27.22; extreme 300/100→300.00; edit `->set('data.peso',80.00)`→`assertFormSet(['imc'=>27.68])` (no blur; fallback fillForm); motivo/fecha required; peso 15/altura 260 rejected; pliegues JSON + empty→NULL (incl. add-remove-all `[]`); nav hidden. → fails. NOTE (apply): `->set('data.peso', 80.00)` fired `afterStateUpdated` fine — fallback unused.
- [x] 3.2 GREEN — form: `peso` (20–300, 0.1, kg) + `altura` (100–250, 0.5, cm) `->live(onBlur:true)->afterStateUpdated($calculateImc)`; `imc` `->readOnly()->suffix('kg/m²')`; `use Filament\Schemas\Components\Utilities\{Get,Set}`, `(float)` casts, altura<=0 → null, else `round($peso/($altura/100)**2, 2)`.
- [x] 3.3 GREEN — form: `fecha` DatePicker required; `motivo` Select required (motivoOptions: Primera consulta/Control/Derivación); `pliegues_cutaneos` Repeater (pliegue+mm required) `->defaultItems(0)` columns(2) + `->mutateDehydratedStateUsing(static fn (Repeater $c, ?array $s): ?array => empty($d = $c->dehydrateItems($s)) ? null : $d)` (cf. `[]`→NULL); cintura/cadera/grasa; observaciones; proximo_control DatePicker; `paciente_id` Select relation `->getOptionLabelFromRecordUsing(nombre_completo)` + `->searchable(['nombre','apellido'])` + `->hidden(fn(HasSchemas $lw) => $lw instanceof RelationManager)`. API-verified deltas: (a) `mutateDehydratedState()` is the RUNNER (takes `$state`), the SETTER is `mutateDehydratedStateUsing()` — calling the former with a closure passes it as state and v5 evaluate() invokes it with 0 args (ArgumentCountError); (b) v5 Repeater::setUp already registers `mutateDehydratedStateUsing(Repeater $component, ?array $state)` = `dehydrateItems` — must CHAIN it, not drop it; (c) v5 Repeater `defaultItems(1)` — an empty default row fails required(), so `->defaultItems(0)`.
- [x] 3.4 GREEN — table: fecha `->date('d/m/Y')` sortable, motivo `formatStateUsing(motivoOptions())`, peso, altura, imc; `->defaultSort('fecha','desc')`; `->recordActions([ViewAction, EditAction])`; `->toolbarActions([BulkActionGroup([DeleteBulkAction])])`. infolist: Section + TextEntries. ⚠️ `assertDatabaseHas` sees `fecha` as `Y-m-d H:i:s` (Eloquent `date` cast) — assert `'2026-09-21 00:00:00'`. → green.

## Phase 4: RM Integration (RED→GREEN)

- [x] 4.1 RED — `make:test --pest Filament/ConsultasRelationManagerTest`: `Livewire::test(RM::class, ['ownerRecord'=>$paciente,'pageClass'=>ViewPaciente::class])` → assertOk; only owner's rows (assertCanSee/assertDontSee); create `callAction(TestAction::make(CreateAction::class)->table(), [...])` → `assertDatabaseHas('consultas',['paciente_id'=>$paciente->id])`. → fails.
- [x] 4.2 GREEN — `Pacientes/RelationManagers/ConsultasRelationManager.php`: `$relationship='consultas'`; `$relatedResource=ConsultaResource::class` (delegated); `table()` = `ConsultaResource::table($table)->headerActions([CreateAction::make()])`; `getDefaultActionUrl(): ?string { return null; }` (modal create/edit/view); `getBadge()` count. NOTE (apply): v5 Panel DEFAULT `hasReadOnlyRelationManagersOnResourceViewPagesByDefault=true` hides create/edit on View pages → override `isReadOnly(): bool { return false; }` on the RM (scoped; panel untouched).
- [x] 4.3 GREEN — `PacienteResource::getRelations()` → `[ConsultasRelationManager::class]` (+3, only existing-code touch). Untouched: PacienteResourceTest + model/panel tests. → green.

## Phase 5: Verification

- [x] 5.1 Pint `--dirty --format agent`; `php artisan test --compact`; `migrate:fresh` clean. NOTE (apply): full suite 71 passed / 219 assertions; `migrate:fresh` skipped — SQLite `:memory:` test harness already migrates per test; dev DB seeded data untouched.