# Delta for Database Migrations

## MODIFIED Requirements

### Requirement: Planes Alimentarios Table Migration

The system SHALL preserve the existing `planes_alimentarios` columns and foreign-key cascade, SHALL add a non-null `requiere_plan` boolean to `consultas` with a false default, and SHALL add to `planes_alimentarios` a constrained status with default `pending`, nullable delivery date, and private generated attachment path. The schema MUST enforce one active request per Consulta and support deterministic queue ordering. Existing deployed migrations MUST remain immutable; changes MUST use a reversible additive migration where safe.
(Previously: the table stored one unconstrained consultation link, descriptive/calorie fields, optional attachment, and validity dates.)

#### Scenario: Lifecycle columns exist
- WHEN migrations run from a clean database
- THEN the request status defaults to `pending` and delivery date and attachment path accept null

#### Scenario: Table exists with correct columns
- WHEN `php artisan migrate:fresh` runs
- THEN the `planes_alimentarios` table retains its existing columns and the new lifecycle columns exist

#### Scenario: FK cascade on consulta delete
- GIVEN a consulta with 2 planes_alimentarios
- WHEN the consulta is deleted
- THEN both plans are automatically deleted

#### Scenario: One active request is enforced
- GIVEN a Consulta already has its active PlanAlimentario request
- WHEN a second active request for that Consulta is inserted
- THEN the database rejects the duplicate

#### Scenario: Rollback is focused
- GIVEN the additive lifecycle migration has run
- WHEN it is rolled back
- THEN only the added lifecycle schema is removed and existing consultation/plan data is not silently deleted

### Requirement: Queue Ordering Indexes

The migration set MUST provide indexes sufficient for filtering by status and ordering by request creation time and identifier without changing unrelated domain constraints.

#### Scenario: Pending queue query is indexable
- GIVEN pending requests exist
- WHEN the queue filters pending status and orders deterministically
- THEN the schema exposes status and ordering columns needed by that query
