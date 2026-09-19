# Design: Setup Filament v5 Panel

## Technical Approach

Initialize a greenfield Laravel 13 project with Filament v5 as the admin panel foundation. The change follows a strict GitFlow sequence: create the repo, branch from develop, install Filament, configure the panel provider with consultapp ID/path/locale, create the admin user, verify with Pest, and merge back. This is the project's first feature — there are no existing Filament components, routes, or auth to conflict with.

## Architecture Decisions

| Decision | Options Considered | Choice | Rationale |
|----------|-------------------|--------|-----------|
| Panel ID | `admin` (default) vs `consultapp` | `consultapp` | Multi-panel-ready; avoids collision if a second panel is added later |
| Panel path | `/admin` (default) vs `/consultapp` | `/consultapp` | Matches panel ID, clear URL semantics for the nutrition clinic domain |
| Login page | Custom vs vanilla Filament | Vanilla Filament | Proposal locked decision; custom login is deferred to later sprint |
| Locale | `en` (default) vs `es` | `es` | Application serves Argentine nutrition professionals |
| DB for dev | MySQL vs SQLite | SQLite | Already configured in `.env`, zero setup for local dev |
| Tailwind CSS | Shared Vite vs Filament-managed | Filament-managed | Filament v5 bundles its own CSS; no custom views yet, no conflict |
| Panel provider name | `AdminPanelProvider` vs `ConsultAppPanelProvider` | `ConsultAppPanelProvider` | Matches panel ID, clear ownership |

## Implementation Sequence

```
Step  Action                                         Artifact
----  ---------------------------------------------  ----------------------
  1   git init + initial commit on main               .git/
  2   git checkout -b develop                         develop branch
  3   git checkout -b feature/setup-filament          feature branch
  4   composer require filament/filament:"^5.0"       composer.json + vendor/
  5   php artisan migrate                             users table created
  6   php artisan filament:install --panels           app/Providers/Filament/
  7   Rename + configure ConsultAppPanelProvider      AdminPanelProvider.php
  8   php artisan make:filament-user                  users table seed
  9   config/app.php locale -> es                     config/app.php
 10   .env APP_LOCALE -> es                           .env
 11   vendor/bin/pint --dirty --format agent           auto-formatted files
 12   Write Pest test                                 tests/Feature/ConsultappPanelTest.php
 13   git commit: feat: install filament v5 panel      commit
 14   git checkout develop && merge feature branch     develop updated
```

## File Changes

| File | Action | What Changes |
|------|--------|-------------|
| `composer.json` | Modify | Add `filament/filament: ^5.0` to `require` |
| `composer.lock` | Auto | Updated by composer |
| `app/Providers/Filament/ConsultAppPanelProvider.php` | Create | Panel provider created by artisan, then renamed and configured |
| `config/app.php` | Modify | Change locale default from `en` to `es` |
| `.env` | Modify | Change `APP_LOCALE=en` to `APP_LOCALE=es` |
| `tests/Feature/ConsultappPanelTest.php` | Create | Pest tests for panel access and auth |
| `database/database.sqlite` | Verify | Must exist (created by artisan post-create-project-cmd) |
| `bootstrap/providers.php` | Modify | Register ConsultAppPanelProvider |

## AdminPanelProvider Configuration

The artisan command creates `AdminPanelProvider.php`. It will be renamed to `ConsultAppPanelProvider.php` and registered in `bootstrap/providers.php`.

ConsultAppPanelProvider.php (exact code):

```php
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ConsultAppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('consultapp')
            ->path('consultapp')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

bootstrap/providers.php (modified):

```php
<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\ConsultAppPanelProvider;

return [
    AppServiceProvider::class,
    ConsultAppPanelProvider::class,
];
```

config/app.php locale change:

```php
'locale' => env('APP_LOCALE', 'es'),
```

.env locale change:

```
APP_LOCALE=es
```

## Data Flow

```
Browser
   |
   | GET /consultapp
   v
Filament Router (consultapp panel)
   |
   |--> Not authenticated? --> /consultapp/login (GET, POST)
   |                                |
   |                                |--> Valid credentials --> /consultapp (Dashboard)
   |
   |--> Authenticated? --> Dashboard (default page)
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Feature | Unauthenticated access redirects to login | `GET /consultapp` asserts 302 to login |
| Feature | Login page renders | `GET /consultapp/login` asserts 200 + Livewire component |
| Feature | Authenticated dashboard access | Create user, actingAs, `GET /consultapp` asserts 200 |

Pest test code (tests/Feature/ConsultappPanelTest.php):

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/consultapp');

    $response->assertRedirect('/consultapp/login');
});

it('renders the login page', function () {
    $response = $this->get('/consultapp/login');

    $response->assertStatus(200);
    $response->assertSeeLivewire('filament.pages.login');
});

it('allows authenticated users to access the dashboard', function () {
    $user = User::factory()->create([
        'email' => 'admin@consultapp.test',
    ]);

    $this->actingAs($user);

    $response = $this->get('/consultapp');

    $response->assertStatus(200);
});
```

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

No data migration required. Filament publishes its own migration for the `users` table (and `password_reset_tokens`) when `filament:install --panels` runs. This is a dev-only setup; production deployment will use the same steps.

## Rollback Plan

```
1. git checkout develop && git branch -D feature/setup-filament
2. composer remove filament/filament
3. rm -rf app/Providers/Filament/
4. Restore bootstrap/providers.php to original
5. Restore config/app.php locale to 'en'
6. Restore .env APP_LOCALE to 'en'
7. rm tests/Feature/ConsultappPanelTest.php
```

## Risk Mitigation

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| Filament v5 API differs from v4 docs | Medium | Low | Use `search-docs` tool during apply; verify with `composer show filament/filament` |
| Laravel 13 + Filament v5 incompatibility | High | Low | Composer will resolve constraints; check `composer.json` requires `laravel/framework: ^13.17` |
| Tailwind v4 conflict with Filament CSS | Low | Low | Filament bundles its own CSS; no custom views means no conflict yet |
| SQLite missing required extensions | Low | Low | Dev only; Filament uses standard SQL, no extensions needed |
| `make:filament-user` command not available in v5 | Medium | Low | Fallback: manual user creation via Tinker or factory |
| Pint reformats artisan-generated code | Low | High | Expected; run Pint after all artisan commands, before commit |

## Open Questions

- None — all decisions are locked by the proposal and this design.
