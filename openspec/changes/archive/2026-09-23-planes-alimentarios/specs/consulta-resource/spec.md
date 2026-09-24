# Delta for Consulta Resource

## ADDED Requirements

### Requirement: Requires-Plan Control

The ConsultaResource form MUST expose an optional requires-plan control. Saving it enabled MUST create or update the single active PlanAlimentario request for that Consulta; saving it disabled MUST leave no request for an unmarked Consulta. The form MUST expose request status, conditional delivery date, and optional PDF validation while preserving existing Consulta fields and relation-manager reuse.

#### Scenario: Relation manager uses the control
- GIVEN the consultation form is opened from a Paciente relation manager
- WHEN staff marks requires-plan and saves
- THEN the same queue request contract is applied without a duplicated form schema

#### Scenario: Delivery date is conditional
- GIVEN the request status is not `delivered`
- WHEN the form is validated
- THEN delivery date is null or cleared and is not required

#### Scenario: PDF validation is enforced in the form
- GIVEN staff selects a non-PDF or file larger than 5 MB
- WHEN the form is submitted
- THEN validation fails before replacing any existing attachment

### Requirement: Consulta Queue Context

The resource MUST provide an authenticated panel entry point to the patient-oriented dietary-plan queue, showing patient name, consultation context, status, request date/order, delivery date when present, and attachment availability. The queue MUST use the deterministic ordering defined by the dietary-plan-request-queue specification.

#### Scenario: Queue identifies patient and consultation
- GIVEN two patients have pending requests
- WHEN staff opens the queue
- THEN each row shows the associated patient and consultation and is ordered deterministically
