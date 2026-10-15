# Delta for Filament Panel

## ADDED Requirements

### Requirement: Authenticated Operational Dashboard

The `consultapp` Filament panel SHALL expose the operational dashboard at `/consultapp` to authenticated panel users. It MUST preserve the existing authentication boundary and MUST NOT add role-specific authorization for this change.

#### Scenario: Unauthenticated dashboard access is denied

- GIVEN a browser is not authenticated
- WHEN it requests `/consultapp`
- THEN it is redirected to panel authentication
- AND dashboard data is not disclosed

#### Scenario: Authenticated dashboard access is allowed

- GIVEN an authenticated panel user
- WHEN the user requests `/consultapp`
- THEN the operational dashboard renders within the `consultapp` panel

## MODIFIED Requirements

None.

## REMOVED Requirements

None.

## RENAMED Requirements

None.
