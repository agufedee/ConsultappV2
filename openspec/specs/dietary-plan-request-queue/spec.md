# Dietary Plan Request Queue Specification

## Purpose

Provide staff with a patient-oriented dietary-plan queue linked to consultations.

## Requirements

### Requirement: Intentional Request Creation and Queue

The system MUST create a request only when staff marks a `Consulta`. Each request MUST belong to one consultation and patient. The queue MUST show both contexts and order by creation time, then request identifier, ascending.

#### Scenario: Marked consultation enters queue
- GIVEN an authenticated panel user edits a Consulta
- WHEN the user enables the requires-plan control and saves
- THEN one active PlanAlimentario request exists for that Consulta and appears in the queue

#### Scenario: Unmarked consultation creates nothing
- GIVEN a Consulta is saved with the requires-plan control disabled
- WHEN the save completes
- THEN no PlanAlimentario request is created for that Consulta

#### Scenario: Queue order is deterministic
- GIVEN three pending requests, including two with equal creation timestamps
- WHEN staff opens the queue
- THEN requests are oldest first and ties use ascending request identifier

### Requirement: Request Lifecycle

Status MUST be `pending`, `delivered`, or `payment_pending`, displayed as `falta de pago` for the latter. New requests MUST be `pending`. Staff MAY move among statuses; payment pending MUST NOT block delivery. A delivery date is required only for `delivered`. Manual deletion UI MUST NOT be provided.

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

#### Scenario: Payment pending is non-blocking
- GIVEN a request is awaiting payment
- WHEN staff sets status `payment_pending`
- THEN status persists, delivery date remains null, and no billing action is triggered

### Requirement: Optional Private PDF

The request MAY have one current PDF. Validation MUST inspect PDF content and reject files larger than 5 MB. Files MUST use generated paths on a dedicated non-served private disk. Downloads MUST require an authenticated panel user, resolve through the consultation/request record, and return attachment disposition. Replacement MUST remove the previous object only after successful storage. Public URLs and manual deletion are out of scope.

#### Scenario: Valid PDF is private and downloadable
- GIVEN an authenticated panel user and a valid PDF of 5 MB or less
- WHEN the user uploads it and then selects download
- THEN the generated path is private and the response is an authenticated attachment download

#### Scenario: Invalid PDF is rejected
- GIVEN an upload with non-PDF content or size greater than 5 MB
- WHEN staff saves the request
- THEN validation fails, no new path is persisted, and the existing attachment remains unchanged

#### Scenario: Replacement cleans up old object
- GIVEN a request with an existing private PDF
- WHEN a valid replacement is uploaded successfully
- THEN the new path is persisted and the old private object no longer exists

#### Scenario: Unauthorized or missing file is denied
- GIVEN an unauthenticated user, an unrelated request, or a missing private object
- WHEN a download is requested
- THEN access is denied or a not-found response is returned without exposing a filesystem path

### Requirement: First-Slice Lifecycle Boundary

The first slice MUST use one active PlanAlimentario row per Consulta. Recurring requests, validity/version history, automatic generation, patient self-service, external sharing, email, billing, and public URLs are out of scope.

#### Scenario: Existing request is updated
- GIVEN a Consulta already has its active request
- WHEN staff edits metadata or attachment
- THEN the existing row is updated and no second request is created
