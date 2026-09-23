# Patient Weight Evolution Specification

## Purpose

Provide an optional, read-only chronological view of one `Paciente`'s consultation weights without adding persistence or changing consultation data.

## Requirements

### Requirement: Patient-Scoped Weight Chart

The system MUST provide one optional chart for the current `Paciente` using every consultation's recorded `fecha` and `peso`, ordered by consultation date ascending. It MUST expose only a weight series with a basic tooltip identifying the date and weight, and MUST NOT expose IMC, combined series, dual axes, or unrelated patient data.

#### Scenario: Ordered weight history is shown

- GIVEN a patient has consultations on 2026-03-10 (70 kg) and 2026-01-15 (72 kg)
- WHEN the user chooses to show the chart
- THEN the chart presents both consultations in 2026-01-15, 2026-03-10 order with their corresponding weights

#### Scenario: Tooltip identifies a point

- GIVEN the chart is visible with a recorded consultation
- WHEN the user inspects that data point
- THEN the tooltip identifies the consultation date and recorded weight

#### Scenario: Patient data remains isolated

- GIVEN two patients have consultations with different weights
- WHEN the chart is shown on the first patient's View page
- THEN it contains only the first patient's consultation weights

#### Scenario: No consultations omit the widget

- GIVEN the current patient has no consultations
- WHEN the patient View page is rendered
- THEN no weight-chart widget or empty chart container is rendered

### Requirement: Local Visibility Choice

The chart MUST have a page-local visibility control that lets the user show or hide it. It MUST be hidden or collapsed by default until the user chooses to show it, and the choice MUST NOT be persisted or affect another patient page.

#### Scenario: Chart is optional by default

- GIVEN the current patient has consultations
- WHEN the patient View page is first rendered
- THEN the chart is hidden or collapsed and a control to show it is available

#### Scenario: User hides the visible chart

- GIVEN the current patient has consultations and the chart is visible
- WHEN the user chooses to hide it
- THEN the chart is not visible while the page remains available

#### Scenario: Visibility is not persisted

- GIVEN the user changed chart visibility on one patient page
- WHEN the user reloads or opens another patient page
- THEN visibility follows the documented page-local default rather than a stored preference
