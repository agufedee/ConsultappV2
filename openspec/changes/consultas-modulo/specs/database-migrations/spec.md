# Delta for Database Migrations

## ADDED Requirements

### Requirement: Widen IMC Column Migration

The system MUST provide an additive migration `widen_imc_on_consultas` changing the `consultas.imc` column from DECIMAL(4,2) to DECIMAL(5,2). The migration MUST NOT alter any other column, constraint, foreign key, or table, and MUST NOT change the ConsultaFactory IMC formula. Rollback MUST restore `imc` to DECIMAL(4,2).

#### Scenario: Migration widens the imc column

- GIVEN a consultas table with imc DECIMAL(4,2)
- WHEN `php artisan migrate` runs
- THEN `imc` is DECIMAL(5,2)

#### Scenario: Existing data preserved

- GIVEN consultas rows exist with imc values
- WHEN the migration runs
- THEN all imc values remain unchanged

#### Scenario: Extreme IMC persists without error

- GIVEN the migration has run
- WHEN a consulta is saved with peso 300.00 and altura 100.00
- THEN imc 300.00 is stored without a database error

#### Scenario: Rollback restores the previous width

- GIVEN the migration has run
- WHEN `php artisan migrate:rollback` runs
- THEN `imc` returns to DECIMAL(4,2) and no other column is changed