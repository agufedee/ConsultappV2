# Delta for Dietary Plan Request Queue

## MODIFIED Requirements

### Requirement: Request Lifecycle

Status MUST be `pending`, `delivered`, or `payment_pending`, displayed as `falta de pago` for the latter. New requests MUST be `pending`. Staff MAY move among statuses through the existing queue or the operational dashboard delivery action; payment pending MUST NOT block delivery. A delivery date is required only for `delivered`. Manual deletion UI MUST NOT be provided.
(Previously: Lifecycle changes were described only through the full dietary-plan queue.)

#### Scenario: New request is pending

- GIVEN staff marks a consultation
- WHEN the request is created
- THEN status is `pending` and delivery date is null

#### Scenario: Delivered requires actual date

- GIVEN a pending request
- WHEN staff changes status to `delivered` with delivery date 2026-09-23
- THEN both values are persisted

#### Scenario: Invalid delivered transition is rejected

- GIVEN a request with no delivery date
- WHEN staff submits status `delivered`
- THEN validation fails and the request remains unchanged

#### Scenario: Dashboard delivery uses the same invariant

- GIVEN a pending request shown on the operational dashboard
- WHEN the user submits a non-empty delivery date
- THEN status becomes `delivered` and the date is persisted

#### Scenario: Payment pending is non-blocking

- GIVEN a request is awaiting payment
- WHEN staff sets status to `payment_pending`
- THEN status persists, delivery date remains null, and no billing action is triggered

## ADDED Requirements

None.

## REMOVED Requirements

None.

## RENAMED Requirements

None.
