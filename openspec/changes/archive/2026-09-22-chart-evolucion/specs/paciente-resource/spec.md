# Delta for Paciente Resource

## ADDED Requirements

### Requirement: Optional Weight Chart in View Footer

The patient View page MUST place the patient-scoped optional weight chart in the footer, after the primary patient information and relation content. The page MUST retain its existing patient information and `Consultas` relation content regardless of chart visibility.

#### Scenario: Footer placement follows patient content

- GIVEN a patient View page has primary information and consultation relation content
- WHEN the page is rendered for a patient with consultations
- THEN the collapsed weight-chart widget and its show control are available in the footer after that content

#### Scenario: Hiding the chart preserves the page

- GIVEN a patient View page is rendered and the chart is available
- WHEN the user chooses not to show the chart
- THEN the footer chart is hidden and primary information plus relation content remain available

#### Scenario: Empty patient view has no chart widget

- GIVEN a patient View page is opened for a patient with no consultations
- WHEN the page is rendered
- THEN the existing patient view and relation content render without a weight-chart widget
