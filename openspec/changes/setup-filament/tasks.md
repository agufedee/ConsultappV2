# Tasks: Setup Filament v5 Panel

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~150 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Full setup (git + Filament + config + tests) | Single PR | `php artisan test --compact --filter=ConsultappPanel` | All tasks in one branch | `git checkout develop && git branch -D feature/setup-filament` |

## Phase 1: Git Initialization

- [ ] 1.1 Run `git init` in project root
- [ ] 1.2 Stage all files: `git add .` and create initial commit: `git commit -m "chore: initial commit"`
- [ ] 1.3 Create develop branch: `git checkout -b develop`
- [ ] 1.4 Create feature branch: `git checkout -b feature/setup-filament`

**Commands:**
```bash
git init
git add .
git commit -m "chore: initial commit"
git checkout -b develop
git checkout -b feature/setup-filament
```

**Files:** `.git/` (new)
**Estimated lines:** 0 (git operations only)
**Dependencies:** None
**Verification:** `git branch` shows `main`, `develop`, `feature/setup-filament`; `git log --oneline` shows initial commit

## Phase 2: Filament Installation

- [ ] 2.1 Install Filament: `composer require filament/filament:"^5.0"`
- [ ] 2.2 Run database migration: `php artisan migrate --force`
- [ ] 2.3 Install panel provider: `php artisan filament:install --panels`

**Commands:**
```bash
composer require filament/filament:"^5.0"
php artisan migrate --force
php artisan filament:install --panels
```

**Files:** `composer.json` (modified), `composer.lock` (auto), `app/Providers/Filament/AdminPanelProvider.php` (created)
**Estimated lines:** ~10 (composer.json diff)
**Dependencies:** Phase 1 (git branch exists)
**Verification:** `composer show filament/filament` shows v5.x; `ls app/Providers/Filament/` shows AdminPanelProvider.php

## Phase 3: Panel Configuration

- [ ] 3.1 Rename provider: `mv app/Providers/Filament/AdminPanelProvider.php app/Providers/Filament/ConsultAppPanelProvider.php`
- [ ] 3.2 Update class name inside file: `AdminPanelProvider` → `ConsultAppPanelProvider`
- [ ] 3.3 Configure panel: set `->id('consultapp')`, `->path('consultapp')`, `->login()`, `->colors(['primary' => Color::Amber])`
- [ ] 3.4 Update `bootstrap/providers.php`: import `ConsultAppPanelProvider` and register it
- [ ] 3.5 Update `config/app.php`: change locale default from `'en'` to `'es'`
- [ ] 3.6 Update `.env`: change `APP_LOCALE=en` to `APP_LOCALE=es`

**Files:**
- `app/Providers/Filament/ConsultAppPanelProvider.php` (renamed + modified)
- `bootstrap/providers.php` (modified)
- `config/app.php` (modified — locale line)
- `.env` (modified — APP_LOCALE)

**Estimated lines:** ~40 (panel provider ~50 lines, config changes ~5 lines each)
**Dependencies:** Phase 2 (artisan created AdminPanelProvider)
**Verification:** `grep -n "consultapp" app/Providers/Filament/ConsultAppPanelProvider.php` shows ID and path; `php artisan route:list --path=consultapp` shows routes

## Phase 4: User Seeding

- [ ] 4.1 Create admin user: `php artisan make:filament-user --panel=consultapp`

**Commands:**
```bash
php artisan make:filament-user --panel=consultapp
```
(Interactive: enter `admin@consultapp.test`, `password`, confirm)

**Files:** None (database only)
**Estimated lines:** 0
**Dependencies:** Phase 3 (panel configured), Phase 2 (migration run)
**Verification:** `php artisan tinker --execute 'App\Models\User::where("email", "admin@consultapp.test")->count()'` returns `1`

## Phase 5: Testing

- [ ] 5.1 Create test file `tests/Feature/ConsultappPanelTest.php` with three Pest tests:
  - Unauthenticated redirect (GET /consultapp → 302 to /consultapp/login)
  - Login page renders (GET /consultapp/login → 200 + Livewire component)
  - Authenticated dashboard access (actingAs → GET /consultapp → 200)
- [ ] 5.2 Run tests: `php artisan test --compact --filter=ConsultappPanel`

**Files:** `tests/Feature/ConsultappPanelTest.php` (new, ~35 lines)
**Estimated lines:** ~35
**Dependencies:** Phase 3 (panel accessible), Phase 4 (user exists for auth test)
**Verification:** All 3 tests pass with green output

## Phase 6: Code Quality & Commit

- [ ] 6.1 Run Pint: `vendor/bin/pint --dirty --format agent`
- [ ] 6.2 Verify no dirty files: `vendor/bin/pint --dirty --test`
- [ ] 6.3 Stage all changes: `git add .`
- [ ] 6.4 Commit: `git commit -m "feat: install filament v5 panel with consultapp config"`
- [ ] 6.5 Merge to develop: `git checkout develop && git merge --no-ff feature/setup-filament`

**Commands:**
```bash
vendor/bin/pint --dirty --format agent
vendor/bin/pint --dirty --test
git add .
git commit -m "feat: install filament v5 panel with consultapp config"
git checkout develop && git merge --no-ff feature/setup-filament
```

**Files:** Any files Pint reformats
**Estimated lines:** ~5 (Pint reformatting)
**Dependencies:** Phase 5 (tests pass)
**Verification:** `git log --oneline -3` shows commit on develop; `vendor/bin/pint --dirty --test` exits clean
