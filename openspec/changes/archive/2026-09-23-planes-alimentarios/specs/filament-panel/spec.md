# Delta for Filament Panel

## ADDED Requirements

### Requirement: Authenticated Dietary Plan Queue

The `consultapp` Filament panel MUST expose the dietary-plan request queue only to authenticated panel users. It MUST provide actions to mark a Consulta as requiring a plan, update allowed lifecycle fields, and record delivery date when actually delivered. It MUST NOT expose patient-facing, public, email, billing, or manual deletion actions.

#### Scenario: Unauthenticated queue access is blocked
- GIVEN a browser is not authenticated
- WHEN it requests the dietary-plan queue
- THEN it is redirected to panel authentication and no queue data is disclosed

#### Scenario: Authenticated staff works queue
- GIVEN an authenticated panel user
- WHEN the user opens the queue and updates a request
- THEN the patient context and lifecycle state are shown and persisted

### Requirement: Consultation-Scoped PDF Download Authorization

The panel MUST authorize each PDF download against the authenticated user and the requested consultation/request record. It MUST never accept an arbitrary storage path, expose a public URL, or return the PDF inline. Missing objects MUST fail closed without revealing storage details.

#### Scenario: Cross-consultation access is denied
- GIVEN an authenticated user requests a PDF belonging to another consultation context
- WHEN the download action runs
- THEN authorization fails and the file bytes are not returned

#### Scenario: Download is an attachment
- GIVEN an authorized user and an existing private PDF
- WHEN download is selected
- THEN the response has attachment disposition and the PDF is not made publicly addressable
