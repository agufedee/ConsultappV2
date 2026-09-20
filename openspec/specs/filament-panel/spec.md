# Filament Panel Specification

## Purpose

Filament v5 admin panel installed, configured, and accessible at `/consultapp` with Spanish locale and default authentication. Foundation for all future CRUD features.

## Requirements

### Requirement: Git Repository Initialization

The system MUST be initialized as a Git repository with GitFlow branching model.

#### Scenario: Main branch creation

- GIVEN a new project directory
- WHEN `git init` is executed and an initial commit is made
- THEN a `main` branch exists with at least one conventional commit
- AND the commit message follows `chore: initial commit` format

#### Scenario: Develop branch exists

- GIVEN a git repo with a `main` branch
- WHEN `develop` is created from `main`
- THEN `develop` exists and tracks `main`

#### Scenario: Feature branch exists

- GIVEN a git repo with a `develop` branch
- WHEN `feature/setup-filament` is created from `develop`
- THEN the branch exists and all subsequent changes are committed on it

### Requirement: Filament v5 Installation

The system SHALL install `filament/filament:^5.0` via Composer and run the panel installer.

#### Scenario: Composer require succeeds

- GIVEN a Laravel project with no Filament packages
- WHEN `composer require filament/filament:"^5.0"` is executed
- THEN the command completes without error
- AND `filament/filament` appears in `composer.json` require section

#### Scenario: Panel installer executes

- GIVEN Filament is installed
- WHEN `php artisan filament:install --panels` is executed
- THEN an `AdminPanelProvider` (or equivalent) is created under `app/Providers/Filament/`
- AND the provider is registered in the application

### Requirement: Panel Configuration

The system MUST configure a single Filament panel with the following settings.

#### Scenario: Panel ID is consultapp

- GIVEN the panel provider exists
- WHEN the panel is configured
- THEN the panel ID is `consultapp`

#### Scenario: Panel path is /consultapp

- GIVEN the panel ID is `consultapp`
- WHEN the application boots
- THEN the panel is accessible at the URL path `/consultapp`
- AND unauthenticated requests to `/consultapp` redirect to `/consultapp/login`

#### Scenario: Panel locale is Spanish

- GIVEN the panel is configured
- WHEN the panel renders
- THEN the locale is set to `es`

#### Scenario: Authentication is enabled

- GIVEN the panel is configured
- WHEN a user visits `/consultapp` unauthenticated
- THEN the user is redirected to the login page
- AND the login page renders at `/consultapp/login` with HTTP 200

#### Scenario: Navigation sidebar branding

- GIVEN the panel is configured
- WHEN the panel dashboard loads
- THEN the sidebar displays "ConsultApp" as the application name

### Requirement: Admin User Seeding

The system MUST create a Filament admin user for initial access.

#### Scenario: Admin user exists

- GIVEN the panel is installed and database is migrated
- WHEN `php artisan make:filament-user` is executed with `admin@consultapp.test` / `password`
- THEN a user record exists with that email
- AND the user is assignable to the `consultapp` panel

#### Scenario: Admin user can log in

- GIVEN the admin user exists in the database
- WHEN POST `/consultapp/login` is submitted with valid credentials
- THEN the user is authenticated
- AND redirected to the panel dashboard at `/consultapp`

### Requirement: Pest Test Coverage

The system MUST include Pest feature tests proving panel accessibility.

#### Scenario: Unauthenticated access redirects

- GIVEN the panel is installed
- WHEN a GET request hits `/consultapp`
- THEN the response is a redirect (HTTP 302) to the login page

#### Scenario: Login page renders

- GIVEN the panel is installed
- WHEN a GET request hits `/consultapp/login`
- THEN the response is HTTP 200
- AND the response contains a login form element

#### Scenario: Authenticated dashboard access

- GIVEN an authenticated admin user
- WHEN a GET request hits `/consultapp`
- THEN the response is HTTP 200
- AND the response contains the panel dashboard content

### Requirement: Code Quality

The system SHALL pass Pint formatting with no dirty files.

#### Scenario: Pint runs clean

- GIVEN PHP files have been created or modified during setup
- WHEN `vendor/bin/pint --dirty --format agent` is executed
- THEN all files are formatted to project standards
- AND no dirty files remain after the run

## Edge Cases and Failure Modes

| Case | Expected Behavior |
|------|-------------------|
| SQLite extension missing | Installer fails with clear error; dev-only concern |
| Filament v5 API change from docs | Follow `search-docs` output; panel provider creation may differ |
| Tailwind CSS conflict | Out of scope — Filament manages its own CSS bundles |
| `make:filament-user` prompts for panel | Pass `--panel=consultapp` to avoid interactive prompt |
| Pint reformats vendor files | Use `--dirty` flag to limit scope to project files only |

## Test Strategy

- **Pest feature tests**: 3 tests covering unauthenticated redirect, login page render, authenticated dashboard
- **Manual verification**: `php artisan route:list --path=consultapp` confirms routes registered
- **Pint formatting**: automated in CI and pre-commit
- **No unit tests needed**: this change is infrastructure/installation, not business logic
