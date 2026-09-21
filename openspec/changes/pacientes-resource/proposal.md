# Proposal: Pacientes Resource (first Filament CRUD)

## Intent

First Filament v5 resource on the `consultapp` panel: full CRUD + detail view, with optional-but-unique DNI, soft deletes, computed age, global search by nombre/apellido/dni. Aligns with Sprint 2 of consultapp_sdd.md; sets conventions for later resources.

## Scope

### In Scope
- PacienteResource (embedded form/table/infolist) + 4 pages via `make:filament-resource Paciente --panel=consultapp --view --embed-schemas --embed-table`
- Form: required nombre/apellido; nullable unique dni (empty persists NULL); DatePicker fecha_nacimiento; Select sexo; telefono; email; Textarea antecedentes; DatePicker fecha_alta
- Global search on columns nombre/apellido/dni (never nombre_completo accessor)
- Detail view: Infolist Section + TextEntries incl. computed age
- Soft deletes: migration (nullable deleted_at) + SoftDeletes trait; list hides deleted, history persists
- $modelLabel/$pluralModelLabel Spanish labels
- Pest tests: tests/Feature/Filament/PacienteResourceTest.php

### Out of Scope
- Consultas module / ConsultasRelationManager (Sprint 2b); planes/objetivos resources
- Charts/widgets, exports, custom authorization, IMC reactive forms

## Capabilities

### New Capabilities
- `paciente-resource`: Filament CRUD for Paciente — list + search, create/edit with DNI validation, detail view with computed age, soft-delete behavior

### Modified Capabilities
- `domain-models`: Paciente gains SoftDeletes + edad accessor; delete stops cascading
- `database-migrations`: new add_soft_deletes_to_pacientes migration (deleted_at + index); delete scenarios updated

## Approach

Generate, then customize: nullable-unique dni input, getGloballySearchableAttributes(['nombre','apellido','dni']), recordTitleAttribute('nombre'), edad accessor (Carbon diff) as TextEntry; soft-deletes migration first. Tests: list/search, create, duplicate-DNI rejection, edit-same-DNI, soft-delete hiding.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| app/Filament/Resources/PacienteResource.php | New | Resource, embedded schemas (~250-300 lines) |
| app/Filament/Resources/PacienteResource/Pages/* | New | 4 pages |
| database/migrations/*_add_soft_deletes_to_pacientes.php | New | deleted_at + index |
| app/Models/Paciente.php | Modified | SoftDeletes + edad accessor |
| tests/Feature/Filament/PacienteResourceTest.php | New | Resource tests (~130-180 lines) |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| ~480-600 lines vs 400-line review budget | High | --embed-schemas/--embed-table (5 files); PR strategy deferred to tasks phase |
| Deleted rows keep DNI, blocking re-registration | Med | Accepted semantics; document in specs |
| Empty-string DNI trips SQLite unique index | Med | ->nullable() persists empty as NULL |
| First resource; generator layout may differ from v5 docs | Med | Re-verify generated layout at apply time |
| nombre_completo in search/record-title -> SQL break | Low | Search real columns only |

## Rollback Plan

- `php artisan migrate:rollback` drops only deleted_at (additive, non-destructive)
- Delete the PacienteResource/ tree (no other code depends on it); remove trait + accessor from model
- Isolated on feature/pacientes-resource; revert = revert branch commits

## Dependencies

- feature/pacientes-resource branch (exists off develop)
- Filament v5.8.2 consultapp panel (installed)

## Success Criteria

- [ ] php artisan test --compact passes
- [ ] /consultapp/pacientes lists, creates, edits, views, soft-deletes
- [ ] Duplicate DNI rejected on create; same-DNI edit succeeds; empty DNI persists NULL
- [ ] Deleted hidden; global search matches all three columns
- [ ] Detail view shows computed age
- [ ] vendor/bin/pint --dirty --format agent clean