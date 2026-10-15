# Operational Dashboard Specification

## Purpose

Define the authenticated `/consultapp` operational desk for bounded dietary-plan triage without creating a second lifecycle workflow.

## Requirements

### Requirement: Authenticated Operational Desk

The system SHALL render the operational desk only to authenticated `consultapp` panel users. It MUST use the existing panel authorization boundary and MUST NOT introduce role-policy behavior.

#### Scenario: Unauthenticated access is blocked

- GIVEN a visitor is not authenticated
- WHEN the visitor requests `/consultapp`
- THEN the visitor is redirected to panel authentication
- AND no operational plan data is disclosed

#### Scenario: Authenticated access succeeds

- GIVEN an authenticated panel user
- WHEN the user opens `/consultapp`
- THEN the operational desk renders

### Requirement: Bounded Pending Plan Projection

The dashboard SHALL show at most the 10 oldest `pending` `PlanAlimentario` records, ordered by `created_at ASC` and then `id ASC`. Displayed rows MUST provide patient and consultation context through eager-loaded relationships. `delivered` and `payment_pending` records MUST NOT appear in this list.

#### Scenario: Pending plans are bounded and deterministic

- GIVEN more than 10 pending plans, including equal creation timestamps
- WHEN an authenticated user opens the dashboard
- THEN only 10 plans are shown, oldest first
- AND ties are ordered by ascending plan identifier

#### Scenario: Context is available without hidden per-row loading

- GIVEN a displayed plan belongs to a Consulta and Paciente
- WHEN the dashboard renders the row
- THEN patient and consultation context is shown

#### Scenario: No pending plans exist

- GIVEN there are no pending plans
- WHEN the dashboard renders
- THEN a stable empty state is shown

### Requirement: Navigation Shortcuts and Queue Link

The dashboard SHALL provide shortcuts for new consultation, new patient, and the existing searchable patient list. It SHALL provide a link from the pending-plan area to the full authenticated dietary-plan queue.

#### Scenario: Operational navigation is available

- GIVEN an authenticated panel user
- WHEN the dashboard renders, including an empty state
- THEN all three shortcuts and the full queue link are available

### Requirement: Direct Delivery From Pending Plans

The dashboard MAY deliver a listed pending plan, but the action MUST require a non-empty delivery date and MUST use the existing `PlanAlimentario` lifecycle invariant. Invalid submission MUST leave the record unchanged; valid submission MUST persist `Delivered` with the supplied date.

#### Scenario: Invalid delivery is rejected

- GIVEN a pending plan
- WHEN the user submits delivery without a non-empty date
- THEN validation fails and status and delivery date remain unchanged

#### Scenario: Valid delivery removes the plan after refresh

- GIVEN a pending plan and a valid delivery date
- WHEN the user confirms delivery and the dashboard refreshes
- THEN the plan is `Delivered` with that date
- AND it no longer appears in the pending list

### Requirement: Scope Boundary

The dashboard MUST NOT add statuses, migrations, dependencies, public URLs, patient-facing behavior, payment/download workflows, or role-policy work. The existing dietary-plan queue SHALL remain the lifecycle source of truth.

#### Scenario: Existing queue remains authoritative

- GIVEN a user needs complete queue operations
- WHEN the user follows the dashboard queue link
- THEN the existing authenticated queue is used for lifecycle and complete-list operations
