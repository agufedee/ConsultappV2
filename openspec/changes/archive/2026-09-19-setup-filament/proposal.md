# Proposal: Setup Filament v5 Panel

## Intent

ConsultAppV2 is a greenfield Laravel project with no git history and no Filament installed. The first sprint requires a working admin panel as the foundation for all future features. This change initializes the repository, installs Filament v5, configures the panel, and proves it works with a Pest test.

## Scope

### In Scope
- Initialize git repo with GitFlow branches (main + develop)
- Create `feature/setup-filament` branch from develop
- Install Filament v5 (`filament/filament:^5.0`)
- Configure panel provider: ID `consultapp`, path `/consultapp`, locale `es`
- Create admin user: `admin@consultapp.test` / `password`
- Verify SQLite works for local dev (already default in .env)
- Run Pint formatter on dirty files
- Write Pest test: panel accessible at `/consultapp`, login works
- Commit: `feat: install filament v5 panel with consultapp config`

### Out of Scope
- Domain models, migrations, or factories (next sprint)
- Tailwind v4 separation from Vite (deferred — Filament manages its own CSS)
- Custom login page (vanilla Filament default)
- Role/permission system
- Any resource or widget creation

## Capabilities

### New Capabilities
- `filament-panel`: Filament v5 admin panel installed, configured, and accessible at `/consultapp` with Spanish locale and default auth

### Modified Capabilities
- None (greenfield project, no existing specs)

## Approach

1. `git init` + create main/develop branches
2. `composer require filament/filament:"^5.0"` + `php artisan filament:install --panels`
3. Rename `AdminPanelProvider` → `ConsultAppPanelProvider`, set ID to `consultapp`, path to `/consultapp`, locale to `es`
4. `php artisan make:filament-user` with locked credentials
5. `vendor/bin/pint --dirty --format agent`
6. Pest test hitting `/consultapp` (expect redirect to login) and `/consultapp/login` (expect 200 + form)
7. Conventional commit on `feature/setup-filament`

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Providers/Filament/` | New | Panel provider file created by artisan |
| `composer.json` | Modified | Filament dependencies added |
| `routes/` | New | Filament registers auth + panel routes |
| `tests/` | New | Pest feature test for panel access |
| `.env` | Verified | SQLite already configured, no change needed |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Filament v5 API differs from docs | Low | Follow `search-docs` output; fall back to installed package docs |
| Tailwind CSS conflict with Vite config | Low | Out of scope — Filament bundles its own CSS, no custom views yet |
| SQLite missing extensions | Low | Dev environment only; test runner uses in-memory SQLite |

## Rollback Plan

1. `git checkout develop && git branch -D feature/setup-filament`
2. `composer remove filament/filament`
3. Delete `app/Providers/Filament/` directory
4. `php artisan migrate:rollback` if any migration was published
5. Remove Pest test file

## Dependencies

None — this is the first change in the project.

## Success Criteria

- [ ] Git repo initialized with main + develop branches
- [ ] `feature/setup-filament` branch exists with all changes committed
- [ ] `composer require filament/filament:"^5.0"` succeeds
- [ ] Panel accessible at `/consultapp` (redirects to login)
- [ ] Login page renders at `/consultapp/login`
- [ ] `admin@consultapp.test` / `password` can log in
- [ ] Panel locale is Spanish (`es`)
- [ ] Pint formatter run with no dirty files remaining
- [ ] Pest test passes: `php artisan test --compact --filter=FilamentPanel`
