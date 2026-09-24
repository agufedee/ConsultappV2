# Exploration: planes-alimentarios

### Current State

The backlog defines `feature/planes-alimentarios` as the next change after `chart-evolucion`: a meal plan with optional descriptive/calorie data and a PDF attachment linked to a consultation. The database and Eloquent layer already contain most of the intended shape: `planes_alimentarios` has `consulta_id`, `objetivo_calorico`, `descripcion`, `archivo_adjunto`, and validity dates; `Consulta::planAlimentario()` is a `HasOne`; and `PlanAlimentario` already has the inverse `BelongsTo`, fillable attributes, casts, and a factory.

The plan is not currently exposed in any Filament form, infolist, table, or relation manager. `ConsultaResource` is the single source of truth for consultation forms and is reused by `ConsultasRelationManager`; `ViewConsulta` currently only exposes an edit action. The application has no custom file-download route, controller, policy, or file-upload precedent. `config/filesystems.php` has a private-root `local` disk, but no explicitly named `local_private` disk. The current `local` disk has `serve: true`, so it should not be repurposed for medical documents when the requested contract is an explicitly private disk.

The schema does not enforce uniqueness on `planes_alimentarios.consulta_id`, despite the model relationship being `HasOne`. This is a product and data-integrity decision: the existing `vigente_desde`/`vigente_hasta` fields suggest possible historical plans, while the current model and backlog describe one plan per consultation. The existing application also has panel authentication but no domain policies, so download authorization must be made explicit rather than inferred from storage visibility alone.

### Affected Areas

- `app/Filament/Resources/Consultas/ConsultaResource.php` — add the consultation-facing plan form/infolist contract, including constrained PDF upload and plan metadata, while preserving relation-manager form reuse.
- `app/Filament/Resources/Consultas/Pages/ViewConsulta.php` — expose an authorized download action or equivalent consultation-scoped document action.
- `app/Models/Consulta.php` and `app/Models/PlanAlimentario.php` — existing `HasOne`/`BelongsTo` boundary is usable, but the cardinality decision may require a uniqueness constraint or a relationship redesign.
- `database/migrations/2026_09_19_000004_create_planes_alimentarios_table.php` or a new additive migration — the existing migration has no unique index for `consulta_id`; deployed migration immutability means a new migration is required if one-plan-per-consulta is confirmed.
- `config/filesystems.php` — define the requested `local_private` disk with a non-served private root and explicit private visibility; avoid exposing clinical PDFs through the public disk or a served path.
- `routes/web.php`, `app/Http/Controllers/`, and authorization layer — likely integration points for a consultation-scoped authenticated download endpoint if the download is not implemented as a Filament action.
- `tests/Feature/Filament/ConsultaResourceTest.php` and new focused file-storage/download tests — cover upload validation, private-disk persistence, consultation ownership, missing-file behavior, and denial of cross-record access using Pest and `Storage::fake('local_private')`.
- `database/factories/PlanAlimentarioFactory.php` — extend only if tests need deterministic attachment paths or plan-cardinality states; the factory currently leaves `archivo_adjunto` null.
- `consultapp_sdd.md` and `openspec/specs/` — backlog alignment exists, but the secure-storage and cardinality decisions need to become explicit requirements in proposal/spec phases.

### Approaches

1. **Consultation-native plan section with an authenticated download action** — Keep plan data inside `ConsultaResource` and its view/edit flow, store PDFs on a dedicated `local_private` disk, and stream downloads only after resolving the consultation and its plan through the authenticated Filament context.
   - Pros: Reuses the existing resource and relation-manager architecture; keeps the first slice small; avoids a second navigation surface; supports patient/consultation scoping at the point where clinical data is already displayed.
   - Cons: Requires careful handling of the one-plan-per-consultation rule; download behavior is less reusable outside Filament unless extracted behind a small action/controller.
   - Effort: Medium

2. **Dedicated `PlanAlimentarioResource` or consultation relation manager** — Give plans their own Filament CRUD surface, with a dedicated upload form and download action.
   - Pros: Clear lifecycle boundary; easier to support multiple historical plans later; plan-specific tests and actions are isolated.
   - Cons: Adds navigation and UI complexity for a child record that the current domain models as one-per-consultation; increases changed lines and duplicated consultation context; still requires an explicit secure download boundary.
   - Effort: Medium/High

3. **Signed download endpoint with temporary URLs** — Store privately and return short-lived signed URLs or a signed route for downloads, with authorization performed before URL creation.
   - Pros: Clean HTTP boundary; can support non-Filament clients later; temporary URLs reduce long-lived link exposure.
   - Cons: More moving parts for the MVP; local disks need custom temporary-URL handling or an authenticated streaming endpoint; a signed URL is not a substitute for checking the consultation/user authorization before issuing it.
   - Effort: Medium

### Recommendation

Start with approach 1, but make the product decisions explicit before design: default to one optional plan per consultation, keep the PDF optional, use a dedicated `local_private` disk with a storage path that is never publicly served, and expose downloads through an authenticated consultation-scoped action that streams the file rather than returning a public URL. Preserve the existing text/calorie/validity fields and show the attachment state on the consultation view. Do not add a patient portal or external sharing in this slice.

The recommended first implementation slice is: define the cardinality contract; configure `local_private`; add a Filament plan section with PDF MIME/content-size validation; persist generated storage paths (never client filenames); add a secure download action that rejects missing files and unrelated consultation records; and add focused Pest/Storage tests. Deletion/replacement behavior must remove or reconcile the old private object so orphaned medical files are not accumulated.

Open product questions for the proposal are whether plans are strictly one-per-consultation or versioned over time, whether `vigente_desde` is required/defaulted to the consultation date, whether an uploaded PDF replaces an existing one or creates a new version, whether users may delete a plan/file, the maximum PDF size, and whether downloads should be inline or attachment downloads. Authorization should default to authenticated panel users only unless a future role model says otherwise.

### Risks

- The `HasOne` relationship is not backed by a unique database constraint; silently allowing multiple rows would make form updates and downloads ambiguous.
- The requested `local_private` disk does not exist yet; using the existing `local` disk could expose sensitive PDFs through Laravel's served local-disk behavior.
- File extension or client MIME metadata alone is insufficient; upload validation must inspect content MIME type and enforce a size limit, and generated storage names must be used.
- Storage visibility does not authorize access. Every download must be scoped to the authenticated consultation/plan and must not accept an arbitrary path from the request.
- Replacing or deleting an attachment can leave orphaned private files unless cleanup is designed and tested.
- There are no existing policies or download endpoints, so the authorization boundary is currently undefined and must not be implied by Filament authentication alone.
- The repository's OpenSpec context still says PHP 8.3/MySQL while the current application reports PHP 8.5/SQLite; design and verification should follow the installed versions and test both storage behavior and database constraints without assuming MySQL-only features.

### Ready for Proposal

Yes, with the cardinality decision called out as the primary product gate. The proposal should constrain the first slice to consultation-scoped private PDF management in the authenticated Filament panel, choose one-plan versus versioned-plan semantics, define download/replacement authorization and lifecycle behavior, and explicitly exclude patient self-service or external sharing.
