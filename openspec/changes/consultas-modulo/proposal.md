# Proposal: Consultas Module (reactive IMC form + ConsultasRelationManager)

## Intent

Sprint 2b of consultapp_sdd.md (§2.3): full consultas CRUD so nutritionists can register each clinical session — motivo, weight/height with **reactive IMC** calculated live while typing (no server round-trip per keystroke, SDD §1.2 #2), body measurements, pliegues, observations, and next-control date. Target users: nutritionists recording consultas from the paciente detail. Product outcome: a nutritionist opens a paciente, creates/edits consultas without leaving the detail page, and the IMC appears automatically.

## Scope

### In Scope
1. **IMC overflow fix**: new additive migration widening `consultas.imc` DECIMAL(4,2) → DECIMAL(5,2) (deviation from §4.2, documented).
2. **Navigation**: `protected static bool $shouldRegisterNavigation = false;` — consultas live only inside the paciente detail, no standalone nav entry.
3. **CRUD scope**: full `ConsultaResource` (List/Create/Edit/View) + `ConsultasRelationManager` on PacienteResource view; reactive form written ONCE in the resource, reused by the RM via related-resource delegation.
4. **Motivo**: Select with the 3 SDD values (`primera_consulta`/`control`/`derivacion`), Spanish labels.
5. **Fields**: all editable — peso, altura, imc (readOnly, computed), motivo, fecha, circunferencias, porcentaje_grasa, pliegues (repeatable JSON list), observaciones, proximo_control.

### Non-goals
- **chart-evolucion is OUT** (SDD §2.3 treats it as its own feature branch `feature/chart-evolucion`; no chart here).
- **planes-alimentarios is a future change** (`feature/planes-alimentarios`); `getRelations()` on ConsultaResource stays empty for now.
- No model changes, no composer/panel config changes, no seeders.

### Current-state gap
`consultas` table, Consulta model, factory, and relations already exist (imc factory formula matches §5.2). Missing: any Filament surface — consultas are invisible in the UI today. PacienteResource::getRelations() is an empty `//` placeholder.

## Capabilities

### New Capabilities
- `consulta-resource`: ConsultaResource CRUD — reactive IMC form (peso/altura live → imc readOnly), motivo Select, narrative table, hidden nav, repeated via ConsultasRelationManager on the paciente view

### Modified Capabilities
- `database-migrations`: new `widen_imc_on_consultas` migration — `imc` DECIMAL(5,2); rollback restores (4,2)
- `paciente-resource`: getRelations() mounts ConsultasRelationManager; View page gains the consultas relation tab

## Approach

Product-level: generate `ConsultaResource` (`make:filament-resource --panel=consultapp --view`), hand-normalize to v5 namespaces per house pattern, then build the reactive form per SDD §5.2: `peso` numeric step(0.1) suffix kg / `altura` numeric step(0.5) suffix cm, both `live(onBlur: true)` with `afterStateUpdated(calculateImc)`; `imc` readOnly suffix kg/m². Validation spirit: peso required 20–300, altura required 100–250 (ranges chosen so even extremes fit DECIMAL(5,2)). Generate RM via `--related-resource` so the form is reused, not duplicated. Table defaultSort fecha desc. Pest tests mirroring PacienteResourceTest + RM test (ownerRecord mount).

## Implications & Impact

| Area | Impact | Description |
|------|--------|-------------|
| app/Filament/Resources/Consultas/ConsultaResource.php | New | Form + table + infolist (~140 lines) |
| app/Filament/Resources/Consultas/Pages/* | New | 4 pages (~60–80 lines) |
| app/Filament/Resources/Pacientes/RelationManagers/ConsultasRelationManager.php | New | relationship consultas, reuses resource form (~50–70) |
| app/Filament/Resources/Pacientes/PacienteResource.php | Modified | getRelations() +2–6 lines (only existing-code touch) |
| database/migrations/*_widen_imc_on_consultas_table.php | New | DECIMAL(5,2) (~20–30 lines) |
| tests/Feature/Filament/ConsultaResourceTest.php | New | (~150–200) |
| tests/Feature/Filament/ConsultasRelationManagerTest.php | New | (~80–120) |

**~500–650 authored lines** → over the 400-line review budget; sdd-tasks should plan chained PRs (slice 1: migration + resource; slice 2: RM + PacienteResource + tests). Existing model/factory/panel tests unaffected (RM mount doesn't touch list/create/edit).

## Edge Cases & Tradeoffs

- **Overflow**: 300kg @ 100cm → IMC 300.00; DECIMAL(5,2) absorbs it (decision 1). 
- **Edit-time recompute**: stored imc shows until peso/altura change; `afterStateUpdated` fires on edit too — no blur required.
- **DNI-style empty normalization**: not relevant — no `unique()` fields in consultas; nullable columns are genuinely nullable.
- **pliegues UX**: repeatable list (Repeater, pliegue + mm), JSON via existing `array` cast.
- **Hidden nav**: List page reachable only via RM "view all" — accepted per decision 2.

## Open Questions

None blocking — the five product decisions are bound above. Residual detail (table columns/badge, Repeater layout) is design-phase.

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| ~500–650 lines vs 400 budget | High | Chained PR slices forecast in sdd-tasks |
| Generator emits v3-era namespaces | Med | Hand-normalize to house v5 pattern (verified in PacienteResource) |
| imc drift between UI and stored value on edit | Low | readOnly + recompute on any peso/altura change, Livewire-tested |

## Rollback Plan

- `php artisan migrate:rollback` restores DECIMAL(4,2) (widening is additive, non-destructive).
- Delete `Consultas/` resource tree + RM; revert `getRelations()` to `[]`.
- Isolated on `feature/consultas-modulo`; revert = revert branch commits.

## Dependencies

- `feature/consultas-modulo` branch (from develop, pacientes-resource merged)
- Filament v5.8.2 consultapp panel; consultapp_sdd.md §4.2/§5.2; archived pacientes-resource change (RM slot)

## Success Criteria

- [ ] `php artisan test --compact` green; Pint clean
- [ ] New consulta created from paciente View (RM) persists with computed imc
- [ ] IMC updates reactively in create and edit without reload
- [ ] Extreme combo (300kg @ 100cm) saves without DB error
- [ ] No "Consultas" entry in the panel sidebar