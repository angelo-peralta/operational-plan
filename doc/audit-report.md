# Operational Plan System — Revised Implementation Audit

## Documentary-evidence upload correction — 2026-09-08

**Status: Complete and live-verified.** Laravel validation and the interface allow PDF/JPG/JPEG/PNG evidence up to 10 MB, but the active Herd PHP 8.4 runtime still used its 2 MB default with an 8 MB total POST limit. PHP therefore rejected ordinary documents before Laravel's validated upload action could process them. The active runtime now uses a 10 MB file limit and 64 MB POST limit, the development server was restarted, and `public/.user.ini` records matching PHP-FPM/CGI deployment defaults.

The latest application exception was also reviewed. It came from the Operational Plan detail redirect after an upload and referenced an older eager-load callback type. The current callback accepts Eloquent relations correctly, application caches were cleared, and the focused plan/accomplishment/evidence controller suite passes 43 tests with 187 assertions.

An authenticated 3 MB PDF was then posted through the live accomplishment endpoint. It passed MIME/size validation, was written to the private evidence disk with the correct size and `application/pdf` metadata, and returned HTTP 200 after the application redirect. The verification evidence, accomplishment row, private file, and temporary source file were removed afterward.

## Team-creation navigation cleanup — 2026-09-08

**Status: Complete.** The team selector and **New team** action were removed from both authenticated navigation layouts. The user's current Team remains an internal URL and membership shell required by existing routes, with no user-facing team control. No planning scope, Academic Year, organizational-unit authorization, or stored Team data changed.

## Revised Phase 6 verification and hardening - 2026-09-08

**Status: Revised Phase 6 implemented, tested, and stable.** The revised plan remains authoritative. The policies, nested request resolvers, hierarchy services, mutation actions, private evidence delivery, remark/evaluation data, report queries, and focused regression tests were reread before implementation changes; the findings below record the verified starting state and the completion record documents the resulting hardening.

| Audit area | Independently verified finding and hardening decision |
| --- | --- |
| Authorization and Executive scope | Canonical `users.role` plus current, role-compatible organizational assignments remain authoritative. Staff/Office Heads have direct-unit access. Every assigned Executive has university-wide read visibility, while hierarchy limits authoring, approval, return, evaluation, and remark-resolution authority. System Admin has support visibility without review authority, and nested private records return 404 outside the selected Academic Year or the user's read visibility. Retain this design. |
| Hierarchy traversal and self-approval | Descendant traversal is cycle-safe and excludes siblings/self. Plan and accomplishment review deny an Executive's assigned unit and personal creator/submitter/accountable conflicts; accomplishment review also denies co-accountable-unit conflicts. Retain these checks. |
| Closest evaluation authority | Assignment activation/dates and canonical Executive role are checked, but the closest-authority query does not require the authority organizational unit itself to be active. An inactive closer Executive unit can therefore block the next active ancestor, although the inactive Executive cannot review. Filter responsible authorities to active units and cover fallback to the President/next active ancestor. |
| Presidential evaluation | VP and Presidential evaluations are append-only rows on the current immutable submission. Presidential agreement must match the VP rating; an override must differ and links to the preserved VP row. Effective resolution prefers an accepted rated University-authority evaluation and otherwise the subordinate Executive evaluation. Retain and reverify this behavior. |
| Academic Year and closed-year isolation | Planning, accomplishment, evidence, evaluation, and remark mutations acquire the Academic Year/plan lock before reauthorization. Selected-year nested resolvers and report joins independently constrain both plan and reporting-period Academic Years. Closed years remain visible as history and reject ordinary mutations. Retain these controls. |
| Evidence privacy and manual review | Evidence uses randomized paths on the private local disk, validated MIME/size limits, policy-protected inline/download routes, and server-derived metadata. Serialized monitoring/evaluation/submission data contains approved metadata and checksums without `stored_path`. No AI analysis, OCR, external call, or automatic rating participates. Retain and add an explicit response-contract privacy assertion. |
| Remark visibility and history | Remark creation and status changes are scope-authorized, year/plan/period constrained, locked, and append status history. However, an editable Plan Item can currently be deleted while a field remark targets it, orphaning an immutable review record. Prevent deletion when direct Plan Item remarks exist and test that the target and history remain intact. |
| Report filtering | Dashboard, annual/reporting-period reports, detailed Executive remarks, and official output use the server-selected Academic Year and resolved organizational scope. Assigned Executives receive the same university-wide non-root aggregate coverage; effective ratings use only the latest submission and do not double-count VP plus President rows. Retain and reverify with the complete suite. |
| Legacy structures and AI boundary | Department writes and arbitrary role creation are disabled; legacy database fields/models remain only for migration, provenance, archived snapshots, and test compatibility and do not grant authority. The shared authenticated frontend payload still exposes unused legacy `department` and `roleId` fields, and the active accountable-user contract/UI still uses department terminology. Remove those active response/type fields and labels. `laravel/ai` and `config/ai.php` remain installed but dormant as permitted by the plan; no application call site uses them. |

Focused hardening baseline: 41 tests with 412 assertions passed across revised foundation authorization, Executive evaluation/Presidential override, evidence, remarks, reports, and official output before the fixes below. Focused regression tests and the complete backend/frontend validation matrix were then rerun.

### Revised Phase 6 completed

- Corrected closest-authority resolution so only active organizational units can block a higher active Executive. If a nearer VP authority unit is inactive, that assignment grants no review power and the next responsible active ancestor, including a university-root President, receives the manual evaluation. Assignment dates/status, canonical role, target scope, self-review rules, and append-only evaluation history remain enforced.
- Protected immutable review history when editing a returned/draft plan. A Plan Item with semester accomplishments was already non-deletable; a Plan Item with direct field-level review remarks is now also non-deletable. The check runs inside the existing Academic-Year-first plan lock, so the target, remark, and remark status histories remain intact.
- Added closed-Academic-Year regression coverage for every remark mutation. Existing open/addressed remarks and status histories remain visible, while new remarks, owner addressing, and Executive resolution are all rejected after closure.
- Added explicit private-response assertions for monitoring and Executive evaluation data. Authorized users receive evidence metadata and SHA-256 checksums, while neither camel-case nor database-form storage paths are serialized. File access remains available only through the policy-protected inline and download routes.
- Removed legacy `department` and `roleId` fields and the Department eager load from the shared authenticated Inertia payload. The canonical role, current valid organizational assignments, scope label, and policy permissions are the active client authority contract.
- Renamed the current Operational Plan accountable-user response from `departmentId` to `organizationalUnitId`, updated the TypeScript contracts and editor filtering, and removed department wording/identifiers from active plan creation, header, co-accountable, and navigation code. Disabled legacy Department routes/models and old snapshot readers remain for compatibility and never grant authority.
- Reverified server-side nested 404 boundaries, hierarchy scope, self-approval prevention, VP/President routing, Presidential agreement/override provenance, Academic Year isolation, report filters, effective-rating precedence, manual evidence review, field remarks, and append-only status/submission/evaluation history. No AI, Ollama, OCR, queue, external service, generated assessment, or automatic rating participates.

### Phase 6 verification

The complete regression suite passes **398 Pest tests with 2,470 assertions**. PHPStan level 7 passes with zero errors after rerunning alone with a 512 MB analyzer limit (the first parallel run exhausted its 128 MB worker limit). Pint passes; TypeScript checking passes; ESLint passes; Prettier passes; `git diff --check` passes; the 69 application routes load successfully; and the Vite production build passes. The existing optional Fontaine optimization notice remains informational and no dependency changed.

Phase 6 adds no database migration. The complete Pest suite rebuilt and migrated the isolated SQLite test schema. A live PostgreSQL check on 2026-09-08 authenticated successfully to PostgreSQL 18.4 at `127.0.0.1:5432/operational_plan_db`: `php artisan migrate --force --no-interaction` reported nothing to migrate, `migrate:status` confirmed all 33 migrations are applied, and `db:show --counts` confirmed the expected 31-table schema.

### Remaining after Phase 6

No revised MVP implementation phase or migration remains. The installed `laravel/ai` package/configuration and legacy compatibility artifacts remain dormant as explicitly permitted by the revised plan; removing dependencies or historical compatibility data was outside this phase and is not required by any MVP workflow.

## Revised Phase 5 verification and implementation - 2026-09-07

**Status: Revised Phase 5 implemented, tested, and stable.** The revised plan remains authoritative. These findings were independently verified against the Phase 4 schema, dashboard/report queries, routes, policies, Inertia data, print layout, and tests before major Phase 5 changes; the completion record below documents the resulting implementation.

| Requirement | Verified finding and implementation decision |
| --- | --- |
| Staff and Office Head dashboard | Retain the selected-Academic-Year unit dashboard, OP/KRA/Plan Item counts, semester submission/outcome summaries, pending-evidence calculation, and current organizational assignment scope. Add open/addressed Executive remark counts and an evaluation-history/completion summary. Rename active presentation terminology from department to organizational unit where this phase touches it. |
| Executive, VP, and President dashboard | Retain the existing oversight totals for visible active units, plans, workflow states, accomplishments, and ratings. Every assigned Executive now has university-wide read visibility; hierarchy continues to determine review and mutation authority. Add open/addressed remarks and evaluation-completion metrics; label units rather than departments. |
| Effective evaluation summaries | The current dashboard selects the latest submission revision but counts every rated evaluation row, so a VP evaluation plus Presidential evaluation is double-counted. Aggregate only the effective accepted evaluation for each accepted accomplishment: prefer its Presidential/root-authority evaluation, otherwise its accepted subordinate Executive evaluation. Returned/rejected work has no current effective rating. |
| Reports | No Reports route, controller, data builder, page, or navigation entry exists. Add selected-year, scope-filtered annual and reporting-period monitoring summaries with unit/plan/outcome/evidence/remark/effective-rating information. Reuse current models and queries; no reporting schema is required. |
| Official Operational Plan print/export | Retain the authorized, selected-year-isolated browser print view, complete OP structure, evaluation criteria, landscape print CSS, repeated table header, and row page-break protection. Replace both semester rating and remarks placeholders with effective evaluation data and concise effective remarks. Browser Print/Save as PDF remains the dependency-free PDF-friendly export. |
| Executive remarks summary in reports | The consolidated plan review summary exists in the application, but the official report has no optional detailed Executive Remarks Summary. Add an optional print section with author, target, KRA/Plan Item context, field, content, status, and period without forcing internal comments into every official table cell. |
| Academic Year and authorization | Dashboard queries already filter the selected Academic Year. Official plan resolution already enforces selected year and visible organizational scope with a 404 boundary. Apply the same server-side selected-year and organizational-scope rules to reports; never trust client-supplied unit or year identifiers. System administrators retain support visibility and no executive mutation authority. |
| AI boundary | Reporting is deterministic database aggregation and manual print/export only. The previously installed `laravel/ai` package and vendor configuration remain as inert legacy dependencies because this phase does not change dependencies, but no application action, controller, route, page, report query, queue, or test invokes them. No AI service, agent, OCR, generated summary, automated rating, or external service participates. |

Phase 5 preserved existing records and required no schema migration. It corrected effective-rating aggregation, extended dashboard data/types/UI, added scope-safe report data/controller/route/page/navigation, enriched the official print response and layout, and added focused dashboard/report/export tests.

### Revised Phase 5 completed

- Extended the Staff and Office Head dashboard with selected-year open/addressed/resolved Executive remark counts, current-submission evaluation completion, work awaiting review, and the total preserved evaluation-history count. Existing plan status, KRA/Plan Item counts, semester progress, outcomes, missing evidence, and empty states remain.
- Revised the Executive dashboard to report university-wide non-root organizational units for every assigned President or Vice President. Hierarchy still determines which units the Executive may approve, return, evaluate, or resolve remarks for. System administrator support visibility remains read-only and institution-wide.
- Replaced active dashboard department terminology with organizational-unit terminology. Legacy Department models and fields remain only where needed for historical fixtures or compatibility and do not grant report authority.
- Added one shared effective-evaluation query for aggregate reporting. It selects the latest immutable accomplishment submission, counts only accepted work, prefers an accepted rated Presidential/University-authority evaluation, and otherwise uses the accepted rated VP/initial Executive evaluation. VP and Presidential rows remain separate in history while a Presidential result contributes exactly one effective rating.
- Added a server-authorized Reports route and navigation entry. Annual and reporting-period reports use the selected Academic Year from server context and current organizational scope; client input can select only a reporting period that belongs to that year. Reports show unit/plan coverage, plan workflow states, KRA/Plan Item totals, accomplishment outcomes, pending evidence, remark states, evaluation completion/history, and effective rating summaries.
- Added the optional detailed Executive Remarks Summary to consolidated and official reports. Each row includes the author and President/VP authority label, organizational unit, KRA and Plan Item context, reviewed field and target, reporting period when applicable, content, and current status.
- Completed the official Operational Plan report with First and Second Semester effective ratings, rating labels, Presidential agreement/override provenance, and concise effective evaluation remarks. The existing official plan structure, accountable party, goal, planning columns, rating criteria, landscape layout, repeated print headers, and row-break protection remain.
- Added browser Print / Save as PDF actions and print-specific report styling. No PDF or spreadsheet dependency was introduced. The installed legacy AI package/configuration has no application call site and remains outside the MVP; no AI service, OCR, generated summary, automated recommendation, or automated rating participates in dashboards, reports, or exports.

### Phase 5 verification

Focused Phase 5 coverage verifies Staff/Office Head metrics, open and addressed remarks, evaluation completion/history, university-wide assigned-VP read visibility with hierarchy-scoped review actions, university-wide Presidential precedence, reporting-period and Academic Year isolation, invalid-period rejection, unassigned-user denial, evidence obligations, preserved VP/President history, official First/Second Semester output, and detailed remark context. The original focused dashboard/report/official set passed 25 tests with 516 assertions; the later university-wide Executive regression coverage is recorded below.

The complete regression suite passes 395 Pest tests with 2,423 assertions. PHPStan level 7 passes with zero errors; Pint passes; TypeScript checking passes; ESLint passes; Prettier passes; `git diff --check` passes; and the Vite production build passes. The existing optional Fontaine optimization notice remains informational and no dependency changed.

Phase 5 has no migration. The complete test suite rebuilds and migrates the isolated SQLite test schema successfully. A redundant PostgreSQL `migrate --force` check could not authenticate because the workspace's current `.env` has no database password; no Phase 5 database operation was pending, and the previously verified Phase 1-4 migration state remains documented above.

### Remaining after Phase 5

Revised Phase 6 is next: perform the final MVP hardening audit of authorization, hierarchy traversal, self-approval prevention, Executive scope, Presidential override, Academic Year isolation, evidence privacy, remark visibility, immutable history, closed-year restrictions, and report filtering, then run the complete backend and frontend validation matrix again.

## Revised Phase 4 verification and implementation - 2026-09-07

**Status: Revised Phase 4 implemented, migrated, tested, and stable.** The revised plan remains authoritative. These findings were independently verified against the applied PostgreSQL schema, policies, actions, queue data, interface, and tests before major Phase 4 changes; the completion record below documents the resulting implementation.

| Requirement | Verified finding and implementation decision |
| --- | --- |
| Executive queue and detailed review | Retain the selected-Academic-Year/reporting-period queue, immutable submission context, private evidence links, 0–3 rating scale, policy-derived UI permissions, and manual-only review screen. Expand it to show VP results awaiting Presidential review as well as ordinary submissions awaiting their responsible Executive. |
| Historical evaluation records | Existing evaluations are append-only and tied to an exact accomplishment submission, which is stronger than a bare accomplishment reference and will remain. The unique evaluation-per-submission constraint conflicts with VP plus President history; replace it with one evaluation per evaluator per submission while preserving every existing row and ID. |
| Decisions | Existing `accepted`, `request_additional_evidence`, and `rejected` decisions are retained. Add the distinct revised `request_revision` decision; both request actions return the accomplishment but remain distinguishable in history and UI. |
| Evaluator provenance | Existing rows store only `reviewer_id`. Add nullable legacy-safe `organizational_unit_id` authority provenance to new evaluations, derived server-side from the evaluator's active executive assignment and target hierarchy. Do not infer or fabricate authority for old rows. |
| VP routing | Current scope correctly limits Executives to descendant units and denies own-unit/self-associated reviews, but any higher ancestor can evaluate immediately. Determine the closest active executive authority for initial review so a scoped VP reviews their branch before the President, while the President directly reviews a VP area's own accomplishment. |
| Presidential review and override | Current policy permits evaluation only while an accomplishment is `under_review`, so accepted VP work cannot receive Presidential review. Add an append-only Presidential agreement or rating override on the same immutable submission; derive and retain the overridden VP evaluation relationship and never alter the VP row. |
| Effective rating | No effective-rating calculation exists. Resolve the accepted rating from the current submission, preferring a Presidential evaluation when present and otherwise the VP/initial Executive evaluation; expose both effective provenance and complete history. Returned/rejected work has no current effective rating. |
| Evaluation remarks | Existing evaluation remarks are one text field on each immutable evaluation and remain. Generalized remarks cannot yet target an Evaluation; add that canonical target/field, selected-year/plan/period isolation, authorized visibility, and Presidential additional remarks without changing the VP rating. |
| Authorization and AI boundary | Preserve selected-year nested 404s, server policies, Academic-Year-first accomplishment locking, no update/delete abilities, and organizational scope. System administrators remain read-only support users. No AI participates in evaluation or rating. |

Phase 4 changes are additive and history-preserving: one forward evaluation migration, authority/effective-rating services, policy/action validation, expanded history and Presidential review data, evaluation-target remarks, and corresponding UI/tests. Existing evaluations, submissions, evidence, and remarks remain intact.

### Revised Phase 4 completed

- Reworked initial evaluation routing around current organizational authority. The closest active Executive assignment above the owning unit receives the initial review, so a VP evaluates subordinate offices in their area before the President. A VP cannot evaluate their own VP-area record; the President directly evaluates that unit as its next executive authority. Canonical role, current assignment dates/status, hierarchy, Academic Year, period, plan state, self-review, accountability, and co-accountability checks remain server-side.
- Expanded the manual evaluation queue to include both `under_review` submissions for their responsible Executive and accepted VP evaluations awaiting Presidential action. It retains immutable submission context, Academic Year and reporting-period isolation, organizational unit/KRA/objective/strategy/KPI/target, expected evidence, reported results, private evidence links and metadata, prior field remarks, and complete evaluation history.
- Added the distinct `request_revision` decision alongside accept, request additional evidence, and reject. Both request decisions return the accomplishment without a rating and remain distinguishable in immutable history. Ratings remain manual integers from 0 through 3, with server and PostgreSQL checks for rating/remark combinations.
- Removed the one-evaluation-per-submission limitation while retaining one row per evaluator per immutable submission. New evaluations record the evaluator's server-derived authority unit. Older rows retain nullable unknown authority instead of receiving fabricated provenance.
- Added append-only Presidential agreement and override. Agreement creates a Presidential evaluation with the VP rating and may include extra remarks. Override requires a different rating, sets `is_override`, links `overrides_evaluation_id` to the preserved VP evaluation, and never updates or deletes the VP row. Clients cannot choose the authority unit or override target, and ordinary staff, office heads, system administrators, out-of-scope Executives, and duplicate evaluators cannot perform these actions.
- Added a current-submission effective-rating resolver. It returns no effective rating for returned/rejected work, uses the accepted VP/initial Executive rating when it is the highest authority present, and prefers the accepted Presidential evaluation when one exists. Queue and monitoring history expose the effective marker, authority, override provenance, and both VP and Presidential records.
- Extended generalized remarks to Evaluation targets with the canonical `evaluation` morph alias and field key. The target plan, Academic Year, and reporting period are derived and cross-checked on the server. Presidents can add a field-level remark to the VP evaluation without changing its rating, and the remark appears in the existing consolidated Operational Plan Review Summary and monitoring/evaluation threads.
- Kept evaluation entirely manual. No AI service, model, OCR pipeline, job, generated recommendation, or external call participates in queue selection, evidence review, decisions, remarks, or ratings.

### Phase 4 migration and verification

The forward migration `2026_09_07_060315_revise_evaluations_for_executive_authority_and_overrides.php` is applied to PostgreSQL as batch 3. It preserves existing evaluation rows and IDs, drops only the old unique-submission constraint, adds nullable authority provenance and override linkage, and enforces restrictive foreign keys, one evaluation per submission/reviewer, decision/rating/remark rules, and internally consistent override flags. Live schema inspection confirms the new columns, indexes, foreign keys, and four PostgreSQL check constraints.

Phase 4 coverage verifies closest-authority VP routing, President-direct review of VP-owned work, VP agreement visibility, Presidential agreement, Presidential override, immutable VP preservation, effective-rating precedence, duplicate/unauthorized/spoofed override rejection, request revision, request additional evidence, rejection, resubmission history, selected-year/period/scope isolation, evaluation-target remarks, and inclusion of those remarks in the consolidated review summary.

Final verification: 391 Pest tests with 2,274 assertions pass; PHPStan level 7 passes with zero errors; Pint passes; TypeScript checking passes; ESLint passes; Prettier passes; `git diff --check` passes; every migration is applied; and the Vite production build passes. The existing optional Fontaine optimization notice remains informational and no dependency was changed.

### Remaining after Phase 4

Revised Phase 5 is next: reverify and complete role-specific dashboards, open-remark and evaluation summaries, monitoring/annual reports, official print/PDF-friendly export, Academic Year filtering, and official use of the effective evaluation. The current dashboard rating query predates multiple evaluations per submission and must be changed in Phase 5 to aggregate only the effective rating so VP and Presidential history is not double-counted. Notifications and MVP hardening remain their later revised phases.

## Revised Phase 3 verification and implementation - 2026-09-07

**Status: Revised Phase 3 implemented, migrated, tested, and stable.** The revised plan remains authoritative. The findings below were independently verified before major Phase 3 implementation changes; the completion record documents the resulting implementation.

| Requirement | Verified finding and implementation decision |
| --- | --- |
| Semester accomplishment records | Retain the normalized accomplishment-per-Plan-Item/per-reporting-period model, unique period constraint, workflow states, transaction lock order, and immutable submission revisions. Add explicit creator and last-editor attribution for new records while leaving unknown legacy attribution nullable rather than inventing history. |
| Calculated accomplishment | Retain the existing guarded calculation for truly numeric target types. It avoids division by zero and leaves inverse/qualitative targets uncalculated. Expand Phase 3 coverage across both First and Second Semester and qualitative cases. |
| Reporting-period workflow | Retain same-Academic-Year resolution, approved-plan requirement, open-period mutation checks, office-head/executive submission authority, and staff encoding. Verify the full two-semester behavior and returned-record edit boundary. |
| Private evidence | Retain private Laravel storage, MIME/size validation, randomized paths, checksums, authorized inline viewing/download, and immutable evidence metadata in submission snapshots. Expose the checksum in authorized monitoring context so reviewers can verify the retained file metadata. |
| Return and resubmission | Retain the requirement for additional evidence after a return, the original submitted timestamp, resubmission timestamp, append-only submission snapshots, evaluation history, and all prior files. Extend field remarks into this workflow so requests and responses remain attached to the accomplishment or evidence. |
| Organizational terminology | The active monitoring and preserved manual-review responses and React types still expose an Operational Plan owner as `department`. Rename those active contracts to `organizationalUnit`; retain legacy `department` only as an immutable snapshot read fallback. No database ownership migration is needed because Phase 2 already completed it. |
| Field-level monitoring remarks | Phase 2 remarks currently target only the plan, KRA, and Plan Item, and owner addressing is tied to a draft/returned plan. Extend canonical targets and allowed fields to Accomplishment and Evidence, enforce plan/year/period isolation, allow addressing while the accomplishment is returned, and present the threads in monitoring. Evaluation-target remarks remain Phase 4 because the revised evaluation aggregate changes there. |
| Manual review and AI boundary | Existing evidence inspection and manual evaluation interfaces are useful and remain. No AI service, job, OCR, generated assessment, or AI-specific live status participates in the workflow. Presidential override/effective-rating provenance remains Revised Phase 4 and must not be claimed by this phase. |

Planned Phase 3 changes are deliberately additive: a forward accomplishment-authorship migration, active monitoring response/type cleanup, richer authorized evidence metadata, and monitoring remark targets/UI. Existing evidence files, accomplishment revisions, evaluations, and historical snapshots will be preserved.

### Revised Phase 3 completed

- Retained one normalized accomplishment per Plan Item and reporting period, the guarded numeric percentage calculation, qualitative handling, approved-plan/open-period checks, same-Academic-Year nested resolution, and Academic-Year-first mutation locking. First and Second Semester now have explicit end-to-end isolation coverage.
- Added nullable legacy-safe `created_by` and `updated_by` references to accomplishments. All new application writes record the creator and last actor, clients cannot spoof either field, and submission snapshots retain the available attribution without fabricating owners for older rows.
- Retained private Laravel Storage evidence with randomized paths, PDF/JPG/JPEG/PNG validation, metadata, SHA-256 checksums, and policy-protected inline viewing/download. Authorized monitoring and manual-review screens now expose the checksum while private storage paths remain server-only.
- Retained office-head/executive submission authority for directly managed units, staff encoding, manual `under_review`, immutable submission revisions, original and resubmission timestamps, and all prior evidence/evaluations. Returned accomplishments still require additional evidence before resubmission.
- Extended generalized remarks to Accomplishment and Evidence targets with canonical morph aliases, field allowlists, inferred/validated semester context, selected-Academic-Year and plan isolation, and server policies. Owners can mark remarks addressed while a returned accomplishment is editable; executives can resolve them after resubmission. Threads and exact accomplishment/evidence anchors are visible in monitoring, and histories are append-only.
- Renamed active monitoring and manual evidence-review response/UI contracts from `department` to `organizationalUnit`. Immutable older submission snapshots still accept `department` only as a read fallback.
- Preserved the existing manual-review context for Academic Year, reporting period, organizational unit, KRA, objective, strategy, KPI/target, expected evidence, reported result, uploaded files, and evaluation history. Presidential override and effective-rating provenance remain Revised Phase 4.
- Kept AI outside the MVP. Current accomplishment states contain only the revised manual states; historical AI-era submission values remain readable solely in frozen history. No AI service, agent, job, OCR, or generated assessment participates in Phase 3.

### Phase 3 migration and verification

The forward migration `2026_09_07_011332_add_authorship_to_accomplishments_table.php` is applied to PostgreSQL as batch 2. It adds restrictive nullable user references and does not rewrite or remove existing accomplishments.

Phase 3 tests cover creator/editor provenance and spoofing, First and Second Semester isolation, numeric calculation, qualitative handling, private evidence upload/view/download and unauthorized access, evidence metadata/checksums, organization-scope authorization, return/additional-evidence/resubmission revisions, selected-year/period/plan remark isolation, and preservation of evidence, remarks, status histories, submission snapshots, and evaluations.

Final verification: 383 Pest tests with 2,138 assertions pass; PHPStan level 7 passes with zero errors; Pint passes; TypeScript checking passes; ESLint passes; Prettier passes; `git diff --check` passes; every migration is applied; and the Vite production build passes. The existing optional Fontaine optimization notice remains informational and no dependency was changed.

### Remaining after Phase 3

Revised Phase 4 is next: finish the manual executive evaluation model and queue against the revised specification, add distinct request-revision/request-additional-evidence actions where required, support VP evaluation plus Presidential review/override, compute the effective rating with provenance, and preserve all evaluation history. Dashboards, reports/exports, notifications, and final hardening remain their later revised phases.

## Revised Phase 2 verification and implementation - 2026-09-07

**Status: Revised Phase 2 implemented, migrated, tested, and stable.** The user authorized continuation after stable Phase 1. The revised plan remains authoritative. The initial findings below were recorded before Phase 2 implementation changes; this completion record documents the verified result.

Verified starting point: Phase 1 migrations are applied, the backend suite passes 377 tests/2,032 assertions, and current planning code was reread against plan sections 16-24, 33-40, 43-46, 49-50, and Phase 2 implementation order.

| Requirement | Verified finding and implementation decision |
| --- | --- |
| OP/KRA/item editor and official fields | Existing structured editor, target/evidence fields, ordering, confirmation dialogs, and institutional print view are useful and will be retained. |
| Unit ownership and co-accountability | Plans and pivot still use department_id, Department relations, and department-named request/response fields. Rename columns in a forward migration and update active domain/UI paths to OrganizationalUnit; preserve all references and immutable old snapshots. Manual co-accountable text must not duplicate an existing configured unit. |
| Hierarchy approval and submission | Phase 1 policies enforce executive descendants, own-unit/self-approval denial, and head/executive final submission. Add explicit parent-executive authority presentation and test the staff-to-head-to-VP and VP-to-President workflows, including return/resubmit and closed-year denial. |
| Review history | Existing operational_plan_status_histories already stores actors, timestamps, reasons, transitions, and aggregate snapshots. Extend this ledger with explicit review action and actor authority context rather than duplicating/deleting its records. |
| Generalized and field remarks | No Remark model/table/request/policy exists. Add validated polymorphic targets and field allowlists, selected-year/plan/period isolation, immutable author/target context, and append-only status history. Executives create/resolve within subordinate scope; owning heads/staff mark addressed; no delete or silent content replacement. |
| Review Summary and threads | Add counts, grouping/filtering by executive author, KRA, item, field, status and optional semester; support independent President/VP remarks and links to exact editor sections. Show field threads and unresolved feedback in the review screen. |
| Retention and phase boundary | Preserve existing evaluations, evidence, return reasons and frozen snapshots. Keep AI dormant. Full semester workflow changes and Presidential evaluation overrides remain Phases 3/4; Phase 2 adds the remarks infrastructure without changing the effective-rating model. |

Planned schema: rename operational_plans.department_id and plan_item_co_accountables.department_id to organizational_unit_id; extend existing status history with review action/context; add remarks and remark_status_histories with restrictive parent/author references, typed target/field/status validation, optional reporting period and durable author/target context. Every remark mutation will acquire the Academic Year/plan lock and reauthorize against current scope.

### Revised Phase 2 completed

- Renamed active plan ownership and co-accountable pivot columns to `organizational_unit_id` through a forward migration. Updated active Eloquent relations, queries, requests, response data, Wayfinder forms, TypeScript types, editor, and official view. Narrow deprecated model aliases remain only for archived integrations and older factory call sites; authorization and application queries do not derive scope from them.
- Retained the existing structured Operational Plan/KRA/item editor, ordering, textual KPI targets, resources, documentary-evidence requirements, manual fallback entries, confirmation dialogs, official print view, and immutable status snapshots. Manual co-accountable entries now reject names/codes/acronyms that match a configured active organizational unit.
- Preserved hierarchy-based approval behavior: office heads and executives submit only directly managed plans; executives approve/return only descendant-unit plans; own-unit and personal authorship/submission/accountability approval remains denied; the President and VPs share the executive role and authority follows their assigned hierarchy.
- Extended append-only plan status history with an explicit action, the actor's canonical role, and the actor's active organizational assignments at decision time. Reopen events are distinct from ordinary returns. Existing history rows and old snapshot JSON remain readable; new snapshots use organizational-unit terminology.
- Added generalized immutable remarks for Operational Plans, KRAs, and Plan Items, with validated target-specific field keys, categories, optional same-year reporting periods, canonical morph aliases, Academic Year/plan ownership, and append-only status history. Executives create and resolve remarks only within subordinate scope. Owning heads/staff mark open remarks addressed while the plan is editable. Remark deletion and content replacement are unavailable.
- Added the consolidated review summary to the plan screen with total/open/addressed/resolved and President/VP counts, grouped target/field/period context, exact section anchors, author authority, and permitted status actions. Server policies remain authoritative for every action.
- Kept AI services outside the MVP. The dormant dependency/configuration and historical conversation schema remain unused; sample planning text about AI remains institutional content rather than automated application behavior.

### Phase 2 migrations and verification

The following forward migrations are applied:

1. `2026_09_07_001936_revise_operational_plan_organizational_ownership.php`
2. `2026_09_07_001938_create_remarks_table.php`
3. `2026_09_07_001940_create_remark_status_histories_table.php`
4. `2026_09_07_001941_extend_operational_plan_review_history.php`

The isolated migration test proves existing plan IDs, unit ownership, and co-accountable links survive the column rename. Authorization tests cover target and Academic Year isolation, executive creation/resolution, owner addressing, immutable status history, revised creation payloads, hierarchy approval boundaries, official views, and both legacy/new frozen snapshot readers.

Final verification: 380 Pest tests with 2,051 assertions pass; PHPStan level 7 passes; Pint passes; TypeScript checking passes; ESLint passes; Prettier passes; and the Vite production build passes.

### Development database incident

During verification, `php artisan migrate:fresh --env=testing` was run expecting an isolated testing environment. This repository has no `.env.testing`, so that standalone Artisan command used the configured PostgreSQL development connection and rebuilt its schema. The pre-existing development rows (previously verified as 3 users, 6 units, 1 plan, 4 plan-history entries, 1 KRA, 11 items, and 2 co-accountable links) were removed. No matching PostgreSQL backup or project dump was found. The database was repopulated with the project's idempotent representative development seeders and now contains 6 demo users, 16 configured units, and the sample Operational Plan. These seeded rows are replacement development data, not a restoration of the original records. Pest itself continues to use its configured in-memory SQLite database.

### Remaining after Phase 2

Revised Phase 3 remains the next incomplete phase: full semester accomplishment encoding/submission, manual evidence review presentation, return/resubmission behavior, and monitoring views must be reverified against the revised plan. Presidential evaluation override, effective-rating provenance, and evaluation-history presentation remain Phase 4. Dashboards, reports, exports, notifications, and later hardening remain in their revised later phases.

## Revised specification verification — 2026-09-05

**Authority:** `doc/plan.md` was read completely and supersedes the previous implementation plan. The historical audit below is retained as a record of earlier work; its phase numbering, completion claims, role model, and recommendation to implement exports are superseded by this section.

**Previous assignment: Revised Phase 1 - completed.** The initial audit found foundation conflicts with the revised plan and was recorded before major implementation changes. The completion section below records the implemented foundation, migration evidence, checks, and deferred Phase 2 work.

### Independently verified repository state before Phase 1

- The existing worktree contains accumulated user-requested changes and untracked implementation files. They are being retained. No project recreation, database reset, dependency replacement, or unrelated refactor is planned.
- Installed: Laravel 13.29.0, PHP runtime 8.4.14, React 19.2.8, Inertia Laravel 3.3.1 / React 3.7.0, TypeScript 5.9.3, Tailwind 4.3.3, Wayfinder 0.1.21, Fortify 1.39.0, Pest 5.1.3, Pint 1.30.5. Composer direct dependencies, package configuration, and Boost application information were inspected.
- Laravel React starter kit uses Inertia pages in `resources/js/pages`, local Radix/shadcn components with New York style/CSS variables, strict TypeScript, Vite/Inertia/React/Tailwind/Wayfinder plugins. Team membership remains a workspace URL shell, separate from planning ownership.
- PostgreSQL is the live development database; Pest uses SQLite in memory. Queue configuration is database in the application and sync in tests. Private Laravel Storage evidence delivery is implemented. No queue is needed for manual evaluation.
- Fortify password, passkey, two-factor, and registration functionality and the existing tenant-specific Microsoft Entra sign-in integration remain useful. Public registration currently creates an unassigned ordinary account; it must default to `staff` without accepting role/scope input.
- Live schema and read-only queries confirm flat `departments`, one `users.department_id`, old `roles` catalog, and `users.role` values `super_admin`, `reviewer`, `department_user` (one account each). Existing planning records and snapshots use department IDs. There are no organizational-unit or assignment tables yet.
- `routes/web.php` exposes Academic Year/period administration, flat department administration, custom role creation, users, planning/editor/official view/workflow, monitoring/evidence/submission, evaluation queue, and dashboard. These routes require scope integration; adding new models alone would leave broad legacy access active.

### Retain, migrate, refactor, and defer

| Area | Verified finding and disposition |
| --- | --- |
| Academic Years and reporting periods | Retain models, migrations, administration, ordered semesters, one-current uniqueness, backend session selector, same-year record resolution, and closed-year restrictions. Reverify under new roles. |
| Organizational ownership | Flat Department model/CRUD, single user department, and department-owned plan/pivot columns conflict with the revision. Migrate the existing department table to organizational units without changing IDs; add hierarchy and assignment metadata. Retain legacy plan/pivot foreign-key names temporarily behind a compatibility model until Phase 2, with all access derived from new assignments. |
| Roles | Replace active authorization with exactly `system_admin`, `executive`, `office_head`, `staff`. Retire custom inherited access profiles from active administration. Preserve existing catalog/account provenance. Map old administrators to system admin and old department users/reviewers to staff; never infer a VP or President assignment from an old reviewer role. |
| Authorization | `OperationalPlanPolicy`, `EvaluationPolicy`, scoped requests, listing controllers, and dashboard currently grant Reviewer global access; System Admin can approve/evaluate, and Department User can submit. Replace those permissions and query boundaries with reusable hierarchy/assignment scope, preserving Academic Year/status guards and transaction locks. |
| Existing planning | Retain OP/KRA/item editor, official fields, textual KPI, JSON evidence requirements, ordering, uniqueness, status history/snapshots, and locked mutations. Full organizational terminology/ownership refactor, executive approval history refinements, generalized remarks, field remarks and review summary belong to revised Phase 2. |
| Monitoring/evidence | Retain normalized semester accomplishments, private upload/view/download, metadata/checksums, calculated percentages, submission snapshots and resubmission history. Adjust authorization foundation now; verify the full revised monitoring workflow in Phase 3. |
| Old AI functionality | `laravel/ai` 0.11.0, `config/ai.php`, and conversation migration are dormant. No EvidenceAnalysis model, AnalyzeEvidence job, OCR service, or live AI evidence pipeline was found. Keep the dependency dormant; remove `queued_for_analysis`, `analyzing`, and `ready_for_review` from current accomplishment workflow, migrating live states to manual `under_review` while retaining historical snapshots. Sample OP content about AI remains institutional planning content. |
| Evaluations | Preserve manual evaluations and immutable submission linkage. Current unique evaluation-per-submission schema cannot support Presidential override; request_revision, authority provenance, override relationship, effective rating and history presentation remain Phase 4. Tighten current evaluator access immediately as part of Phase 1. |
| Remarks and reports | No generalized/field-level remark model or consolidated review summary exists. Current official view has empty rating/remark placeholders. These are incomplete under revised Phases 2 and 5; dashboard aggregation must be scope-safe now but is not revised Phase 5 completion. |

### Exact Phase 1 schema and authorization design

- `academic_years`: retain existing id, name, start_year/end_year, nullable starts_on/ends_on, status (draft/open/closed/archived), is_current and timestamps, existing uniqueness/current-year constraint and restrictive planning references.
- `reporting_periods`: retain id, academic_year_id, name, code, sequence, nullable dates, status and timestamps; retain per-year code/sequence uniqueness and seeded First/Second Semester records.
- `organizational_units`: rename existing departments in place, preserving IDs/name/code/description/is_active/timestamps and existing uniqueness. Add nullable self-referencing parent_id with restrictive deletion, acronym, type (university/executive_office/vice_presidential_area/school/college/center/office/department/unit/other), sort_order. Validate hierarchy cycles and parent changes server-side. No automatic production hierarchy or administrator directory import.
- `organizational_unit_user_assignments`: id, user_id, organizational_unit_id, assignment_type (executive/head/staff), nullable position_title, is_primary, nullable starts_on/ends_on, is_active, timestamps. Foreign keys preserve referenced identity. Unique user/unit assignment and at most one active primary assignment per user; validate date ordering and role/type compatibility. Multiple unit assignments are supported.
- `users`: canonical four-value role, preserve legacy role provenance and existing authentication identity. Existing department_id/role_id may remain compatibility/provenance fields during staged refactoring, but never determine authorization. Backfill explicit staff assignments from existing non-admin department links; no invented executive scope. Retain and retire legacy role catalog entries without deleting users or their historical authorship.
- Scope: system admin can administer and view for support, but cannot approve/evaluate by that role. Staff/office heads access valid direct assignments only. Executives access valid assigned units and descendants, and review subordinate units only. Root executive assignment yields university-wide scope through hierarchy; a VP has only their branch. Own-unit/self approval is denied. Inactive, future, expired, or incompatible assignments grant no mutation authority. Policies remain authoritative, lists and nested private-record resolvers use the same scope, and Academic Year/status checks remain mandatory.
- Representative development seeders will demonstrate root President and Academic Affairs VP sharing the executive role, an office head and staff assigned to SITE, and the institutional CPRINT sample. They must not overwrite existing credentials or fabricate production accounts.

### Revised Phase 1 completed - 2026-09-06

**Status: implemented, migrated, tested, and stable. Stop at Phase 1.** The findings above describe the independently verified starting point. The revised foundation below replaces the earlier role/department authorization; later-phase functionality remains deferred.

#### Implemented behavior and retained work

- Retained Academic Year/reporting-period models, uniqueness, administration, selected-year session context, closed-year restrictions, and existing transaction lock order. Existing authentication, team-shell, planning, private evidence, submission-history, and manual-evaluation regression tests pass with revised authorization.
- Renamed the department table in place to organizational units. Added configurable hierarchy, unit type/acronym/order, parent/children traversal, cycle rejection, restrictive references, and administration screens. Existing IDs and references are preserved.
- Added multiple organizational assignments per user with role-compatible executive/head/staff types, titles, inclusive optional dates, activation and primary flags. Enforced unique user/unit pairs and one active primary per user. Removing an assignment deactivates it while retaining its identity. Inactive units retain historical visibility but do not grant mutation authority.
- Made the canonical user role authoritative: system_admin, executive, office_head, staff. President and VP examples share executive; scope follows their root/branch assignments. Retired arbitrary role creation and legacy department writes. The old department index redirects to organizational-unit administration.
- Centralized organizational scope and applied it to policies, lists, dashboard aggregates, accountable-user validation, and nested plan/accomplishment/evidence resolution. Heads/staff have direct scope; executives inherit descendants; unassigned accounts receive no record access. System admins retain administrative support visibility without approval/evaluation authority. Staff cannot finalize submissions. Executives cannot review their own units or approve plans they authored, submitted, or are accountable for.
- Public registration remains unassigned staff; submitted role/scope values do not grant authority. Preserved password, passkey, two-factor, team membership, and Microsoft Entra identity behavior.
- Current accomplishment workflow uses manual under_review. Historical submission statuses use a separate enum/type so old queued/analyzing/ready-for-review history remains readable. No AI service, agent, OCR, queued analysis, or automated rating participates in the MVP. The installed AI dependency/configuration and empty historical conversation tables remain dormant; dependencies were not changed.
- Updated shared scope labels, permission-driven navigation, four-role directory, user assignment forms, hierarchy forms, and explicit unit selection for plan creation. Existing multiunit dashboard aggregation is scoped; this is foundation integration, not completion of revised dashboard/report phases.
- Added representative local/testing seed data: university root, Academic Affairs and subordinate schools, other participating offices, President, Academic VP, SITE head/staff, CPRINT staff, and system admin. Seeders preserve existing credentials and administrator edits and do not fabricate production accounts.

#### Migration and preservation evidence

Two new migrations were applied to PostgreSQL as batch 5:

1. database/migrations/2026_09_05_111038_revise_organizational_foundation_schema.php
2. database/migrations/2026_09_05_111039_migrate_legacy_organizational_foundation.php

No previously deployed migration was changed for this phase. The migrations add organizational_unit_user_assignments; rename departments to organizational_units; extend users with legacy_role/legacy_role_id provenance; and mark retired role-catalog entries inactive. Automatic down migrations refuse to discard hierarchy/assignments; corrections must use forward migrations.

Live post-migration verification found:

| Preserved entity | Verified count |
| --- | ---: |
| Organizational units (original department IDs) | 6 |
| Existing users | 3 |
| Operational plans | 1 |
| Plan status history entries | 4 |
| KRAs / plan items / co-accountable links | 1 / 11 / 2 |
| Backfilled explicit staff assignments | 2 |
| Active canonical roles | 4 |

The former super_admin is system_admin. The former reviewer and department_user are staff, with original role and catalog IDs preserved. No legacy reviewer was automatically made a VP or President. Existing units remain at their original unconfigured hierarchy level; a system admin must deliberately configure their parents and executive/head assignments through the new administration UI.

PostgreSQL foreign-key inspection confirms that users, operational plans, co-accountable links, hierarchy parents, and assignments reference organizational_units with restrictive deletion. A separate migration test verifies preservation of account passwords, plan ownership, history snapshots, manual evaluation records, and old submission status strings while normalizing only the current accomplishment status.

#### Files added and refactored

| Area | Principal Phase 1 files |
| --- | --- |
| New foundation domain | app/Models/OrganizationalUnit.php; app/Models/OrganizationalUnitUserAssignment.php; app/Enums/AssignmentType.php; app/Enums/OrganizationalUnitType.php; app/Enums/AccomplishmentSubmissionStatus.php |
| New actions | app/Actions/OrganizationalUnits/ResolveOrganizationalScope.php; app/Actions/OrganizationalUnits/SaveOrganizationalUnit.php; app/Actions/Users/SyncOrganizationalAssignments.php |
| New organizational administration | app/Http/Controllers/OrganizationalUnitController.php; StoreOrganizationalUnitRequest.php; UpdateOrganizationalUnitRequest.php; app/Policies/OrganizationalUnitPolicy.php; resources/js/pages/administration/organizational-units/index.tsx |
| New factories/seeder | database/factories/OrganizationalUnitFactory.php; OrganizationalUnitUserAssignmentFactory.php; database/seeders/OrganizationalUnitSeeder.php |
| Revised existing authorization | UserRole, User, Role, Department compatibility model; plan/accomplishment/evidence/evaluation and administration policies; PlanningRequest/MonitoringRequest; plan creation and accountable-user validation |
| Revised administration/authentication | User/Role/Department controllers and user requests/actions; Fortify registration; shared Inertia middleware; routes/web.php |
| Revised presentation/data | OperationalPlanController, MonitoringController, EvaluationQueueController, DashboardData; user/role administration pages; sidebar/header; dashboard; plan creation; monitoring status/history types |
| Retained history integration | Accomplishment/AccomplishmentSubmission status casts, RecordAccomplishmentSubmission, workflow factories and regression fixtures |
| New tests | tests/Feature/RevisedFoundationAuthorizationTest.php; OrganizationalUnitAdministrationTest.php; Models/OrganizationalUnitTest.php; Database/RevisedFoundationMigrationTest.php |
| Updated tests and shared guidance | Existing controller/policy/authentication/seeder/dashboard tests use explicit revised scopes; .ai/rules/general.md records the durable scope rule via Boost record-rule |

Existing untracked files from earlier work were preserved and extended; this inventory does not claim those earlier features were newly built during Phase 1. doc/plan.md was treated as input and was not rewritten.

#### Verification results

| Check | Result |
| --- | --- |
| Fresh pre-change backend baseline | Passed: 356 tests, 1,948 assertions |
| php artisan migrate --no-interaction | Passed on existing PostgreSQL database; both new migrations applied |
| php artisan migrate:status --no-interaction | All migrations applied |
| php artisan test --compact | Passed: 377 tests, 2,032 assertions |
| php vendor/bin/pint --dirty --format agent | Passed; formatting fixes applied |
| php vendor/bin/phpstan analyse --memory-limit=512M | Passed, zero errors. Default 128 MB analysis exhausted memory; the larger process-local limit completed successfully |
| npm run types:check | Passed, including historical submission-status types |
| npm run lint:check | Passed; final monitoring type annotation also passed targeted ESLint |
| npm run format:check | Passed; final monitoring type annotation formatted with Prettier |
| npm run build | Passed. Existing optional fontaine optimization warning remains; no dependency change was needed |
| Fresh isolated migrate --seed | Passed with 16 representative units and six demo accounts |
| Browser verification | Built assets rendered login, dashboard, hierarchy, and user administration; created a unit beneath Academic Affairs and saved a staff assignment title, then confirmed both in the isolated database; no JavaScript errors |
| git diff --check | Passed |

The browser used a temporary SQLite database, removed after verification. The temporary server/browser were stopped and the pre-existing hot-file configuration restored. No browser-test accounts were added to live PostgreSQL. Boost's connected transport became unavailable; read-only queries and record-rule were successfully invoked through the installed Boost MCP server over local stdio.

#### Next: Revised Phase 2, not started

- Complete operational-plan and co-accountable ownership/terminology refactoring from legacy department_id names to organizational-unit names, preserving IDs and historical snapshots.
- Verify the full revised plan status/approval workflow, executive own-office submission routing, history, and all updated official plan fields against Phase 2.
- Implement Phase 2 general/field-level remarks and consolidated review summaries.
- Later phases still require the full revised accomplishment/evidence workflow, Presidential evaluation override with retained evaluation history and effective decisions, and revised dashboards/reports. Existing manual evaluation-per-submission behavior is retained but does not satisfy the override requirement.
- Keep AI outside the MVP throughout subsequent phases.


---

# Historical audit — previous specification

## Audit metadata

- **Initial audit date:** 2026-08-29 (Asia/Manila)
- **Latest continuation:** 2026-09-05 (Asia/Manila)
- **Repository:** `main` tracking `origin/main`
- **Audited commit:** `4e075c00156fc789fcf42c46914bc5bc577230fd`
- **Initial worktree state:** Clean
- **Latest worktree state:** Contains the accumulated, user-requested Entra, sign-in, branding, role-management, layout, accomplishment-workflow, manual-evaluation, and selected-year dashboard changes; no unrelated dirty files were overwritten
- **Authoritative specification:** `doc/plan.md`, read in full before application changes
- **MVP1 audit brief:** The supplied pasted text, including Microsoft Entra ID authentication and the explicit exclusion of AI decision-making/automated evidence analysis
- **Audit scope:** `composer.json`, `composer.lock`, `package.json`, `.env.example`, authentication/service/database/queue/filesystem/AI configuration, routes, middleware, enums, models, migrations, factories, seeders, policies, Form Requests, actions, controllers, Inertia data mapping, React pages/components/types, and the backend test suite

## Repository and technology state

This is the Laravel React starter kit with its Team/current-team URL and membership shell. Project rules correctly distinguish Team from planning scope: `AcademicYear` is the operational root and `Department` owns planning records.

| Area | Actual state |
| --- | --- |
| Laravel | 13.29.0 (`laravel/framework`) |
| PHP | Composer requires `^8.3`; audited runtime is PHP 8.4.14 |
| React | 19.2.8 installed |
| Inertia | `inertiajs/inertia-laravel` 3.3.1; `@inertiajs/react` 3.7.0 |
| TypeScript | 5.9.3 installed; strict mode and `noImplicitAny` enabled |
| Tailwind CSS | 4.3.3 with the Vite plugin |
| shadcn/ui | `components.json` uses the `new-york` style, CSS variables, and Radix-backed local components; no standalone runtime package is expected |
| Wayfinder | PHP package 0.1.21; Vite plugin 0.1.7; generated route/action sources are intentionally ignored |
| Authentication packages | Fortify 1.39.0 plus passkeys and two-factor support; no Microsoft OAuth/OIDC package |
| AI package | `laravel/ai` 0.11.0 is installed and its conversation migration/config exist, but no evidence-analysis agent/job/model/workflow is implemented |
| Testing | Pest 5.1.3 with Pest Laravel 5.x; in-memory SQLite in `phpunit.xml` |
| Static analysis/formatting | Larastan 3.10.0, Pint 1.27.x, ESLint 9.39.5, Prettier 3.9.6 |
| Database | `.env.example` and the current development environment target PostgreSQL (`pgsql`); the forward accomplishment-history migration was applied successfully. Tests use SQLite in-memory through `:memory:`. |
| Queue | Database is the application/default and `.env.example` setting; tests use `sync` |
| Storage | Default/local disk is `storage/app/private`; evidence uses this disk and authorized controller responses |

## Test and quality-check baseline

| Command/check | Initial result |
| --- | --- |
| `php artisan test --compact` | Environment-blocked: 46 passed and 201 errored because this checkout has no `APP_KEY` |
| Test rerun with a process-local non-production test key and generated Vite manifest | **Passed:** 247 tests, 1,067 assertions |
| `composer run lint:check` | **Passed** (Pint) |
| `composer run types:check` | Environment-blocked at the default PHP 128 MB memory limit |
| `php -d memory_limit=1G vendor/bin/phpstan analyse` | **Passed:** no errors |
| `npm run format:check` | **Passed** |
| `npm run build` | **Passed**; Wayfinder generated ignored route/action modules and Vite produced the client bundle |
| `npm run lint:check` | **Failed:** four import-order errors in the sidebar and monitoring files |
| `npm run types:check` after Wayfinder generation | **Failed:** two readonly tuple incompatibilities in `resources/js/pages/monitoring/index.tsx` |
| `composer audit --format=json` | **Passed:** no advisories or abandoned packages |
| npm install audit | **Passed:** no vulnerabilities reported for 429 installed packages |

The failures caused by a missing application key, absent ignored Wayfinder output, and PHP memory configuration are environment/tooling conditions rather than application regressions. The four ESLint errors and two TypeScript errors were pre-existing source defects; their mechanical fixes are included in this session and all corresponding checks now pass.

## Security findings

- The initial scan found no hardcoded Microsoft credentials or token values. The post-change scan found only empty `.env.example` entries, environment-backed configuration/request field names, and explicit test fixtures for the requested patterns (`AZURE_AD_CLIENT_SECRET`, `client_secret`, `access_token`, `refresh_token`, `id_token`, or `Authorization: Bearer`). No production credential or token value is committed.
- Microsoft Entra settings are environment-backed. Tenant and client IDs must be UUIDs; `common`, `organizations`, and similar non-tenant aliases are rejected by that validation. Authorization and token calls use the configured tenant-specific endpoint, and authority/Graph base URLs must use HTTPS.
- The redirect creates cryptographically random state and a PKCE verifier, stores both in the server-side session, and sends an S256 challenge. The callback consumes state/verifier once, uses constant-time state comparison, exchanges the code server-side, and reads the stable object ID through Graph `/me`.
- Access and refresh tokens are not persisted. The access token exists only for the callback's Graph request; the application stores only the tenant/object identity pair on the local user.
- First login links only an existing local user by normalized email. Subsequent logins resolve by the unique tenant/object pair, allowing email changes without changing identity. Unknown users and identity/email collisions are denied.
- Microsoft profile data cannot assign `role` or `department_id`; those remain local fields controlled by Super Admin endpoints. A test injects hostile external role/department fields and verifies that the existing local values and password are preserved.
- Existing password authentication is rate limited and Laravel session routes are protected, but public Fortify registration remains enabled. It creates a local department-user account without a department. It cannot self-assign Reviewer or Super Admin, but it is not an approved-user-only Entra onboarding policy.
- Role and department assignment are controlled by Super Admin endpoints and server-side Form Requests/policies. Client-supplied scope, workflow, audit, and rating-like fields are prohibited in implemented requests.
- Department record isolation is enforced in scoped request resolvers and policies. Tests cover cross-department plan, accomplishment, and evidence requests, including 404 responses where record existence should not be disclosed.
- Evidence is validated as PDF/JPG/JPEG/PNG up to 10 MB, stored under generated UUID paths on the private local disk, and served only through authorized view/download routes. Metadata, uploader, size, MIME type, and SHA-256 checksum are retained.
- No unrestricted public evidence URL is implemented.

## Feature-by-feature audit

| Phase | Feature | Status | Evidence / Problem |
| --- | --- | --- | --- |
| Phase 1 | Entra Authentication | Complete (code) | Environment-backed tenant configuration, tenant-specific redirect/token endpoints, state plus PKCE, Graph identity lookup, unique stable tenant/object IDs, pre-provisioned-user linking, Laravel session regeneration/logout, UI action, throttling, safe failures, and mocked feature tests are implemented. Deployment still requires an Entra app registration and secret values outside source control. |
| Phase 1 | Academic Years | Complete | Entity, statuses, one-current partial unique index for PostgreSQL/SQLite, administration, default/selection middleware, history visibility, and closed-year edit restrictions exist; relevant tests pass. |
| Phase 1 | Reporting Periods | Complete | First/Second Semester records belong to Academic Year, have ordered/status/date handling, are seeded/created with years, are admin-managed, and are tested. |
| Phase 1 | Departments | Complete | CRUD without destructive deletion, active state, user ownership, administration authorization, and department isolation are implemented and tested. |
| Phase 1 | Roles / Authorization | Partial (role catalog complete) | A persisted operational-role catalog, built-in role backfill, Super Admin create/view UI, custom-role assignment, inherited access profiles, Entra/local authority preservation, policies, scoped resolvers, and tests are implemented. Evaluation authorization now prevents Department Users from rating or deciding submissions. Public registration remains enabled and still requires a production policy decision. |
| Phase 2 | Operational Plans | Complete | Academic Year/Department ownership and unique constraint, accountable fields, goal, draft/submitted/approved/returned/closed workflow, audit snapshots, closed-year/edit locks, official view, policies, and tests exist. |
| Phase 2 | KRAs | Complete | Relationship, code/name/description, manual ordering, validation, locking, policy enforcement, UI, and tests exist. |
| Phase 2 | Plan Items | Complete | Authoritative textual KPI, optional structured targets, resources, documentary requirements, linked/manual co-accountables, manual ordering, validation, locking, UI, and tests exist. |
| Phase 3 | Semester Monitoring | Complete | Separate per-period accomplishments, First/Second Semester isolation, numeric/qualitative percentage behavior, authorized Draft/Returned editing, submit/resubmit transitions, timestamps, Ready for Review state, immutable revision snapshots, monitoring UI, policies, and tests exist. Returned reports surface reviewer feedback and can be revised with additional evidence. |
| Phase 4 | Evidence Management | Complete | Evidence is separate from requirements, belongs to an accomplishment, is privately uploaded/viewed/downloaded with metadata and server authorization, and cross-department/file validation tests pass. Evidence is frozen while awaiting review; Returned reports accept additional evidence, and resubmission requires at least one new file. |
| Phase 5 | Manual Evaluation | Complete | A selected-year/reporting-period queue, complete frozen review context, authorized manual decisions, official rating validation, conditional remarks, locked status transitions, exact-revision linkage, append-only history, department feedback, and focused tests are implemented. |
| Phase 6 | Dashboard | Complete | Selected-Academic-Year Department dashboards show plan status, KRA/Plan Item counts, distinct reporting-period submissions, outstanding Plan Item-period evidence obligations, current review outcomes, and official rating summaries. Reviewer/Super Admin dashboards show active-department plan coverage/status, current review workload/outcomes, and latest-revision rating summaries. Isolation and empty states are tested; MVP1 intentionally has no AI-analysis-pending stage. |
| Phase 6 | Reports / Export | Partial | A selected-year-scoped printable official plan view exists with required planning columns and rating legend, but semester ratings and remarks are hard-coded placeholders. No completed evaluated export/report exists. |

## Detailed findings

### Authentication

Fortify continues to provide local email/password login, password reset, two-factor authentication, passkeys, registration, and logout. Microsoft Entra now provides an additional tenant-specific authorization-code flow with OAuth state and PKCE. The callback exchanges the code through Laravel's HTTP client, obtains the signed-in profile from Graph `/me`, logs in the authorized local user through the `web` guard, regenerates the session, and reuses Fortify's normal post-login response. The existing logout endpoint invalidates Microsoft-authenticated Laravel sessions and is covered by the focused test.

The migration adds nullable `entra_tenant_id` and `entra_object_id` UUIDs with a composite unique constraint. On first successful sign-in, only a pre-provisioned local email may be linked. Thereafter identity resolution uses the stable tenant/object pair rather than email, while a changed verified Graph email/name may be synchronized if it does not collide. Microsoft input never writes local `role`, `department_id`, password, or Team membership. Unknown and conflicting accounts receive safe login errors.

The checkout has no real tenant credentials, so no live Microsoft request was made. All external calls are prevented unless explicitly mocked in the feature tests. Before deployment, register the exact callback URI with the Entra app and supply tenant ID, client ID, client secret, and redirect URI through the deployment environment. Public local registration remains enabled and should be explicitly retained as policy or disabled before production.

### Foundation and planning

Academic Year selection is a backend session context resolved by middleware, not a cosmetic frontend filter. Planning and monitoring queries constrain records to that selected entity. Department users receive scoped 404s for records outside their department/year. Planning mutation actions use transactions and the required Academic-Year-before-plan lock order.

Operational Plan status history preserves actor, transition, remarks, timestamps, and aggregate snapshots. Approved/submitted plans are not freely editable by department users. Database uniqueness enforces one plan per Academic Year and Department.

### Semester monitoring and evidence

Accomplishment records correctly normalize reporting periods and enforce one current record per Plan Item/period. Application-level locked queries ensure the Reporting Period and Plan Item resolve through the same Academic Year. Numeric percentages are calculated only for supported operators and positive targets; qualitative/inverse targets remain uncalculated.

Department Users can now submit a complete Draft and resubmit a Returned accomplishment. The locked action reauthorizes the current database record, requires report content and uploaded evidence, and transitions directly to Ready for Review because MVP1 has no automated analysis stage. A Returned report must contain evidence added after its latest submission before it can be resubmitted.

Each successful submission appends an `accomplishment_submissions` revision containing the actor, transition, evidence count, timestamp, and a JSON snapshot of the Academic Year, Department, Operational Plan, KRA, Plan Item/KPI, Reporting Period, reported accomplishment, and immutable evidence metadata/checksums. Stored private paths and file contents are intentionally excluded. The first submission timestamp is preserved while the latest resubmission timestamp is updated. The monitoring UI exposes server-calculated permission/readiness, confirmation controls, timestamps, a collapsible revision timeline, immutable review history, and prominent reviewer feedback on Returned reports.

### Manual evaluation

Reviewer and Super Admin users now have a selected-Academic-Year, selected-reporting-period Evidence Review queue containing only Ready for Review submissions. Each full-width queue card is rendered from the immutable submission snapshot and includes the department, reporting period, KRA, objective, strategy, KPI/Target, resources, evidence requirements, reported accomplishment/value/percentage, submitter/revision, and authorized private evidence actions.

Accept requires a manually sel  ected official rating from 0 through 3. Ratings 0 and 1 require remarks. Request Additional Evidence stores no rating and requires remarks, while Reject requires remarks and permits an optional in-range rating, matching the specification's nullable rating field. Decisions transition Ready for Review to Accepted, Returned, or Rejected respectively. A closed reporting period can still be reviewed so submissions are not stranded after the reporting deadline, but a draft period, closed Academic Year, or non-approved plan cannot be evaluated.

Every evaluation references exactly one immutable accomplishment submission through a unique foreign key. The mutation transaction repeats the established Academic-Year-first lock order, reauthorizes the freshly locked record, derives the reviewer/revision/timestamps server-side, and creates a new row rather than updating prior history. The complete Returned/resubmitted/re-reviewed cycle is covered by tests.

### OCR and AI scope

No OCR extraction implementation was found. OCR is optional and does not block MVP1. No AI evidence-analysis implementation was found. The installed Laravel AI package/configuration and conversation tables are dormant; no code automatically validates evidence, approves/rejects it, or assigns a rating. MVP1 continuation must remain manual.

### Dashboard, reporting, and presentation

The dashboard now provides role-appropriate metrics scoped to the selected Academic Year. Department Users see only their assigned department's plan, reporting-period submissions, outstanding obligations, current review outcomes, and official ratings. Reviewer/Super Admin users see active-department coverage, plan and accomplishment pipelines, and institutional ratings. Accomplishment aggregates constrain both plan and reporting-period Academic Years, and rating summaries use only the latest immutable submission revision. The official print view remains correctly year-scoped but still does not populate stored semester ratings and remarks. A post-change source scan found no remaining mojibake marker bytes in application source paths.

### Database integrity

Foreign keys, restrictive/cascade behavior, indexes, one-current Academic Year uniqueness, one-plan-per-year/department uniqueness, one-accomplishment-per-item/period uniqueness, one-submission-revision-per-accomplishment uniqueness, one-evaluation-per-submission uniqueness, and co-accountable pivot uniqueness are present. PostgreSQL-compatible `jsonb` is used for structured plan arrays and submission snapshots, while SQLite tests validate portable behavior. The accomplishment-history and evaluation migrations were applied to the current PostgreSQL development database without a reset.

The Plan Item/Reporting Period same-year invariant is enforced in scoped requests, policies, and locked actions rather than by a database constraint. Existing tests exercise mismatched-year rejection. Accomplishment mutations consistently lock Academic Year, Operational Plan, Reporting Period, KRA, Plan Item, then Accomplishment before reauthorization. Evaluations store only the immutable submission foreign key rather than a second, potentially inconsistent accomplishment foreign key. PostgreSQL check constraints enforce the decision/rating and required-remarks contracts in addition to Form Request/action validation.

## Test coverage gaps

The suite covers local authentication/logout, role policies, Academic Year selection/isolation, closed-year restrictions, department isolation, plan uniqueness and planning CRUD/workflow, both semester records, draft accomplishment calculations, evidence upload/authorization, official-view year isolation, and seeders.

The new focused Entra suite covers tenant-specific redirect parameters, state and PKCE session data, invalid/non-specific tenant configuration, invalid state without an external request, rejected token exchange, unknown-user denial, stable identity after an email change, approved-user linking, identity collision, hostile external authorization fields, session regeneration, local logout, PKCE token payload, and Graph bearer-token use. The full suite also covers protected-route behavior and existing role authorization. A real tenant integration test is intentionally not run because no credentials are stored in the repository.

Focused tests now cover accomplishment submission/resubmission history, immutable snapshot content, required/new evidence, preserved timestamps/evidence, stale duplicate requests, protected workflow fields, role/status policy matrices, closed contexts, nested scope mismatches, selected-year and department isolation, monitoring serialization/readiness, and evidence freezing while awaiting review. Evaluation tests cover Accept/Request Additional Evidence/Reject, rating boundaries and conditional remarks, exact submission linkage, actor/timestamp derivation, stale duplicate protection, full return/resubmission/re-review history, queue scope/order/context, Department User denial, closed-period review, and monitoring history serialization. Dashboard tests cover role/access-profile branching, selected-year and department isolation, active/inactive department coverage, resubmission-safe distinct counts, current workflow states, missing-assignment/no-plan states, and latest-revision ratings. True simultaneous row-lock behavior is not claimed by the SQLite in-memory suite; PostgreSQL locking plus unique revision/evaluation constraints are the production safeguards. Evaluated export filtering remains untested because report integration is not yet implemented.

## Earliest incomplete or broken dependency

**The selected-Academic-Year role-specific dashboard is now implemented and verified.** The earliest remaining MVP1 application feature is the official printable/exportable report: populate First and Second Semester ratings and evaluation remarks from stored evaluations without leaking another Academic Year.

## Continuation plan

1. Completed: foundation, Microsoft Entra authentication, HRMS-inspired sign-in, operational planning workflow, private evidence handling, and persisted role management.
2. Completed: accomplishment Draft/Returned authoring, submit/resubmit action, direct Ready for Review transition, timestamp handling, and immutable revision snapshots.
3. Completed: expose server-derived readiness and permissions, submission confirmation, timestamps, and revision history in the monitoring UI.
4. Completed: add workflow, policy, isolation, evidence-freeze, snapshot, stale-request, and serialization tests; apply the forward history migration and run configured quality checks.
5. Completed: manual reviewer evaluation with an append-only exact-revision history, explicit decisions, rating/remarks rules, selected-year queue, department feedback, and full lifecycle tests.
6. Completed: selected-Academic-Year Department and Reviewer/Admin dashboards with planning, submission/review, outstanding-obligation, and latest-revision rating metrics plus isolation and empty-state tests.
7. Next: connect stored semester ratings and remarks to the selected-year official printable/exportable report and add export-isolation tests.

## Implementation work completed during the initial audit session

The initial version of this report was created before application code changed, as required. The focused implementation then added:

- Entra environment/service configuration and two guest, throttled routes.
- A redirect action generating tenant-specific authorization URLs, state, and S256 PKCE data.
- A callback action for one-time state validation, authorization-code exchange, Graph `/me` lookup, stable identity matching, approved-user linking, collision handling, and local-authority preservation.
- A thin controller integrating safe failures with Fortify's normal Laravel login response and session regeneration.
- A forward user migration with unique tenant/object UUID identity and hidden model attributes.
- A Wayfinder-backed Sign in with Microsoft action and visible safe callback errors on the login page.
- Ten focused feature tests with all Microsoft HTTP calls mocked and stray requests prohibited.
- Mechanical import-order fixes and a mutable Wayfinder route tuple in existing frontend files, clearing the pre-existing ESLint and TypeScript failures without changing monitoring behavior.

## Homepage / Sign-In UI

**Status:** Complete

**Reference:** SPUP-SIMS HRMS

The sign-in experience was intentionally adapted from the HRMS reference while retaining this application's Laravel, React, TypeScript, Inertia, Tailwind, shadcn/ui, Wayfinder, and Microsoft Entra architecture. The root route now redirects directly to `/login`; there is no intervening welcome page. The inspected reference was the local SPUP-SIMS checkout at commit `2b03402beb8129b260fb69d94c068d9e2e016bb5`. In that checkout, the relevant implementation is under the application root (`app/page.tsx`, `app/(auth)/login/page.tsx`, shared layout/styles, and UI primitives), rather than an `app/hrms` directory. The supplied anonymous GitHub path was unavailable during inspection.

### Files changed

- `resources/js/layouts/public-page-layout.tsx`: new reusable institutional public-page header and content shell
- `resources/js/pages/welcome.tsx`: removed so the application has no separate welcome screen
- `resources/js/pages/auth/login.tsx`: HRMS-inspired sign-in panel with Microsoft Entra as the primary action and existing local authentication retained in a secondary disclosure
- `resources/js/app.tsx`: exempts the sign-in page from the starter guest layout so it can use the dedicated public layout
- `routes/web.php`: redirects `/` to the named sign-in route
- `tests/Feature/ExampleTest.php`: verifies the root-to-sign-in redirect
- `doc/audit-report.md`: records the implementation and verification evidence

### Assets

- Reused the user-supplied `public/SPUP-final-logo.png` for the institutional seal.
- No asset was copied from SPUP-SIMS. The inspected reference did not expose a reusable homepage/login image asset or repository license; its textual `SP` tile and Lucide icons were therefore adapted with the project's existing image and icon dependencies.

### Verification

- `/` and `/login` returned HTTP 200 from the local Laravel server.
- Hydrated DOM inspection confirmed that the primary sign-in control is a semantic anchor with `href="/auth/microsoft/redirect"`; React does not call a Microsoft token endpoint or expose Microsoft credentials.
- Desktop (`1440x1000`) and small-screen (`500x900`) renders were compared directly with equivalent HRMS renders. The two-column desktop composition, stacked small-screen flow, hierarchy, spacing, card proportions, restrained emerald accent, border radii, shadows, and full-width primary action align with the reference. No clipping or horizontal overflow was visible at the verified widths.
- Keyboard-accessible native links and disclosure controls, visible focus treatments, semantic heading order, meaningful logo alt text, and dark/light contrast styles are present.

### Known visual differences

- The institutional SPUP seal replaces the reference's textual `SP` tile.
- The application keeps its existing Instrument Sans typography instead of adding the reference's Inter font, avoiding a global change to authenticated pages.
- The sign-in page includes the shared institutional header and a collapsed local-account recovery/invitation area so existing Fortify/passkey behavior remains available without competing with Microsoft sign-in.
- The primary action uses the server-owned Laravel Entra redirect and callback instead of the reference application's browser-side MSAL flow.
- The target supports the application's existing light and dark appearance modes; the captured HRMS reference is light-only. Headless Edge clamps very narrow desktop-window captures on this machine, so the reliable responsive visual comparison used a 500-pixel viewport in addition to source inspection of the fluid layout.

## Checks after implementation

| Command/check | Final result |
| --- | --- |
| `php artisan test --compact tests/Feature/Auth/MicrosoftEntraAuthenticationTest.php` | **Passed:** 10 tests, 71 assertions |
| `php artisan test --compact tests/Feature/ExampleTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/MicrosoftEntraAuthenticationTest.php tests/Feature/DashboardTest.php` with a process-local non-production test key | **Passed:** 25 tests, 158 assertions |
| `php artisan test --compact` with a process-local non-production test key | **Passed:** 257 tests, 1,144 assertions |
| `vendor/bin/pint --dirty --format agent` | **Passed** |
| `composer run lint:check` | **Passed** |
| `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress` | **Passed:** no errors |
| `npm run lint:check` | **Passed** |
| `npm run format:check` | **Passed** |
| `npm run types:check` | **Passed** |
| `npm run build` | **Passed:** 2,357 modules transformed; production manifest/bundle generated |
| `composer audit --format=json` | **Passed:** no advisories or abandoned packages |
| `npm audit --json` | **Passed:** zero vulnerabilities |
| `php artisan route:list --name=auth.microsoft --except-vendor` | **Passed:** redirect and callback routes registered |
| Credential-key scan | **Passed:** expected empty/config/test references only; no production credential values |
| `git diff --check` | **Passed** |

## University-wide Vice President visibility revision — 2026-09-08

**Status:** Complete, migrated, tested, and stable

The user has superseded the earlier VP read-isolation requirement: every assigned Vice President must have the same university-wide read visibility as the President and be able to see all participating schools, departments, offices, plans, accomplishments, evidence, dashboards, and reports. The prior descendant-only behavior is implemented centrally by `ResolveOrganizationalScope::visibleUnitIds`, so changing that boundary will update every server-authorized read path without client-side exceptions.

Approval, return, evaluation, and remark-resolution authority remain hierarchy-based. This preserves the VP-first evaluation workflow, the President's university-root override authority, append-only evaluation history, and self-review protections while expanding read access only. Unassigned Executive accounts must continue to receive no operational record access, and Academic Year isolation remains unchanged.

The KRA Management sidebar route and Wayfinder target were correct, but `KeyResultAreaManagementController` rendered a component name that did not match the existing React page. The controller now renders `administration/key-results/index`, and an HTTP regression test covers successful Super Admin navigation and Executive denial.

The existing KRA Management page only aggregated plan-owned KRAs for the selected Academic Year. That conflicted with the requested institutional default: `key_result_areas` are historical, plan-owned rows and cannot safely serve as mutable global defaults. A separate `key_result_area_templates` catalog now stores global code, name, description, display order, active status, and nullable audit actors. It is independent of Academic Year and organizational-unit scope.

KRA Management is now a complete System Admin-only CRUD. The responsive page lists active and inactive templates in explicit display order and provides create, edit, activate/deactivate, and confirmed delete actions. Requests normalize codes and text, enforce unique codes and field limits, reject client-controlled audit fields, and actions reauthorize while holding catalog row locks. The last active template cannot be deactivated or deleted, ensuring that every new Operational Plan has an institutional KRA structure.

Creating an Operational Plan copies all active templates into plan-owned KRA rows in display order before recording the initial Draft status snapshot. Inactive templates are excluded. Existing plan rows and status-history snapshots remain unchanged after a template is edited, deactivated, or deleted, so future catalog maintenance cannot rewrite review or evaluation history.

Every assigned Executive now resolves all 16 current organizational units for read access in the live PostgreSQL data. Tests verify that unrelated schools, departments, offices, accomplishments, and private evidence are visible while cross-branch approval/evaluation remains forbidden. Unassigned Executives still have no operational visibility.

Two forward migrations were applied successfully to the current PostgreSQL database. The live global catalog contains the default active `KRA 1 — Quality Teaching, Learning, and Student Services` row. The idempotent seeder preserves later administrator edits.

### Verification

| Command/check | Result |
| --- | --- |
| Focused KRA CRUD, navigation, plan-snapshot, seeder, and plan-controller tests | **Passed:** 20 tests, 156 assertions |
| `php artisan test --compact` | **Passed:** 413 tests, 2,558 assertions |
| `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress` | **Passed:** no errors |
| `vendor/bin/pint --dirty --format agent` | **Passed** |
| `npm run types:check`, `npm run lint:check`, and `npm run format:check` | **Passed** |
| `npm run build` | **Passed:** 2,377 modules transformed; production KRA Management bundle generated |
| `php artisan migrate --force` | **Passed:** global template schema and default-data migrations applied |
| Live PostgreSQL template query | **Passed:** one active default template in display order |

The Vite build emitted a non-blocking notice that optimized font fallbacks need the optional `fontaine` package; the build completed successfully. No dependency was added because project rules require approval for dependency changes.

## Role management follow-up — 2026-09-04

**Status:** Complete for role creation and assignment

Super Administrators now have a dedicated, full-width **Role Management** page under Administration. They can add a named operational role, optional description, and one of the three established access profiles: Super Admin, Reviewer, or Department User. New roles immediately appear in User Management and can be assigned when creating or editing an account.

### Implementation and data migration

- Added the `roles` catalog with unique names/slugs, descriptions, access profiles, system-role protection, and assigned-user counts.
- Added `users.role_id` with a restrictive foreign key. The forward data migration created the three built-in roles and backfilled existing users without deleting or replacing accounts.
- Kept the legacy `users.role` value synchronized as a compatibility field, while authorization and displayed role labels use the linked operational role whenever `role_id` is present.
- Updated local registration, administrative user creation/update, factories, demo seeders, and Microsoft Entra account linking so role assignments remain local and consistent.
- Added Super Admin-only routes, request validation, policy enforcement, Wayfinder-backed Inertia forms, navigation entries, responsive role cards, and accessible validation/help text.
- Updated User Management to submit stored role IDs. Custom roles visibly state their inherited access, and Department User profiles still require an active department.

### Guardrails and intended scope

- A custom role inherits one existing server-side access profile; this release does not introduce a granular permission editor or a fourth authorization matrix.
- Built-in roles, access profiles, role editing, and role deletion are intentionally unavailable. This prevents destructive changes while roles are assigned.
- Operational roles remain separate from starter-kit Team membership roles.
- Protected fields (`slug`, `is_system`, and direct legacy `role` input) cannot be client-assigned. Duplicate normalized slugs, invalid access profiles, and generated identifiers beyond the database limit are rejected before persistence.

### Verification

| Command/check | Result |
| --- | --- |
| Focused role, user-assignment, registration, Entra, policy, and seeder tests | **Passed:** 37 tests, 233 assertions |
| `php artisan test --compact` | **Passed:** 275 tests, 1,245 assertions |
| `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` | **Passed:** no errors |
| `vendor/bin/pint --dirty --format agent` | **Passed** |
| `npm run lint:check` | **Passed** |
| `npm run format:check` | **Passed** |
| `npm run types:check` | **Passed** |
| `npm run build` | **Passed:** 2,358 modules transformed; production role-management bundle generated |
| `php artisan route:list --name=administration.roles --except-vendor` | **Passed:** GET index and POST store routes registered |
| Role migration status | **Passed:** all three role migrations are marked Ran in the current PostgreSQL database |
| Current catalog check | **Passed:** 3 built-in role rows exist for the 3 current user accounts |
| `git diff --check` | **Passed** |

## Accomplishment submission continuation — 2026-09-04

**Status:** Complete for department submission and resubmission

The next audited workflow dependency has been implemented without adding the dormant AI analysis states to MVP1. A Department User can submit an owned Draft or resubmit a Returned accomplishment while its Academic Year, approved Operational Plan, and Reporting Period remain open. A successful action moves directly to **Ready for Review**, where authoring and evidence upload are frozen until a future reviewer decision.

### Implementation and integrity

- Added a forward-only `accomplishment_submissions` migration with restrictive foreign keys, a unique accomplishment/revision constraint, indexed timestamps/status, evidence counts, and PostgreSQL-compatible JSON snapshots.
- Added model relationships and casts plus a transactional submit action that follows the established Academic-Year-first lock order and reauthorizes the freshly locked accomplishment.
- Required meaningful accomplishment content and at least one uploaded evidence row. Returned reports require an additional evidence row beyond the latest immutable revision before resubmission.
- Preserved the original `submitted_at`, recorded the latest `resubmitted_at`, and kept every previous evidence row and submission snapshot unchanged.
- Snapshots preserve the selected year, department, plan/KRA/item/KPI context, reporting period, reported values/narrative/percentage, and evidence metadata/checksums. Private storage paths, binaries, and credentials are excluded.
- Added a scoped Form Request and thin controller. Client attempts to set scope, report data, workflow status, actor/timestamps, revision, or snapshot through the submit endpoint are rejected.
- Added server-derived submit permission/readiness data, a Wayfinder-backed confirmation dialog, disabled-state guidance, first/latest submission timestamps, complete status styling, Returned-edit copy, and a responsive collapsible revision timeline.

### Verification

| Command/check | Result |
| --- | --- |
| Focused accomplishment, evidence, policy, monitoring, and percentage suite | **Passed:** 83 tests, 374 assertions |
| `php artisan test --compact` | **Passed:** 297 tests, 1,415 assertions |
| `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress` | **Passed:** no errors |
| `vendor/bin/pint --dirty --format agent` | **Passed** |
| `npm run lint:check` | **Passed** |
| `npm run format:check` | **Passed** |
| `npm run types:check` | **Passed** |
| `npm run build` | **Passed:** 2,361 modules transformed; production monitoring bundle generated |
| `php artisan route:list --name=monitoring.accomplishments.submit --except-vendor` | **Passed:** scoped POST route registered |
| `php artisan migrate --no-interaction` | **Passed:** forward history migration applied to the current PostgreSQL database |
| `php artisan db:table accomplishment_submissions` | **Passed:** 9 columns, JSONB snapshot, 4 indexes including compound uniqueness, and 2 restrictive foreign keys confirmed |
| Application-source mojibake marker scan | **Passed:** no marker bytes found |

The optional `agent-browser` CLI was not installed on this workstation, so no new automated screenshot is claimed for this continuation. Inertia response assertions, TypeScript, ESLint, Prettier, and the production Vite build verify the frontend integration; a live visual pass remains advisable when the application is next opened in a browser.

The SQLite suite deterministically covers duplicate/stale requests but cannot honestly prove concurrent row locks across database connections. Production serialization relies on PostgreSQL row locks and the unique `(accomplishment_id, revision)` constraint; a separate PostgreSQL integration suite would be required to claim a true simultaneous-request test.

## Manual evaluation continuation — 2026-09-04

**Status:** Complete for manual reviewer evaluation and immutable review history

The next audited workflow dependency is implemented without AI scoring or automated approval. Reviewer and Super Admin users can open a selected-Academic-Year/reporting-period Evidence Review queue, inspect the exact submitted revision and its private evidence, and record one of the three required human decisions.

### Implementation and integrity

- Added `evaluations` with one unique, restrictive foreign key to the exact `accomplishment_submissions` revision plus reviewer, decision, optional rating, remarks, and immutable review timestamps. Omitting a duplicate `accomplishment_id` prevents cross-revision/cross-year foreign-key drift.
- Added typed decision/rating enums and PostgreSQL constraints for the accepted/requested/rejected rating contract and mandatory remarks. Form Request and action validation provide the same protection before persistence.
- Added a transactional evaluation action that reuses the Academic Year → Operational Plan → Reporting Period → KRA → Plan Item → Accomplishment lock order and reauthorizes after locking. Reviewer identity, submission revision, resulting status, and timestamps are server-derived.
- Added server policies and nested resolvers. Department Users cannot evaluate even their own submissions; mismatched year/item/period/accomplishment requests return 404; closed Academic Years, draft periods, and non-approved plans are not evaluable. Closed reporting periods remain reviewable while their Academic Year stays open.
- Added the full-width Evidence Review queue with selected-period filtering, oldest-waiting-first order, frozen submission context, authorized evidence view/download links, and action-specific confirmation dialogs.
- Added reusable append-only review history and prominent Request Additional Evidence feedback to Monitoring. Returned reports retain earlier decisions/evidence, accept a new evidence file, and create a new submission/evaluation revision on resubmission.
- No OCR, AI analysis, automatic evidence validation, automated rating, or automated final decision was added.

### Verification

| Command/check | Result |
| --- | --- |
| Focused evaluation, queue, policy, monitoring, submission, evidence, and authorization suite | **Passed:** 96 tests, 550 assertions |
| `php artisan test --compact` | **Passed:** 344 tests, 1,667 assertions |
| `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress` | **Passed:** no errors |
| `vendor/bin/pint --dirty --format agent` and `composer run lint:check` | **Passed** |
| `npm run lint:check` | **Passed** |
| `npm run format:check` | **Passed** |
| `npm run types:check` | **Passed** |
| `npm run build` | **Passed:** 2,366 modules transformed; production evaluation and monitoring bundles generated |
| `php artisan route:list --name=evaluations --except-vendor` | **Passed:** selected-year queue GET and scoped evaluation POST routes registered |
| `php artisan migrate --no-interaction` | **Passed:** forward evaluation migration applied to the existing PostgreSQL database |
| `php artisan db:table evaluations` | **Passed:** 8 columns, unique submission-revision link, 4 indexes, and 2 restrictive foreign keys confirmed |
| Credential-key filename-only scan | **Passed:** expected environment-backed configuration/action and mocked-test references only; no new browser-visible credential reference |
| `git diff --check` | **Passed** |

The Vite build repeated the existing non-blocking notice about the optional `fontaine` package and completed successfully. The in-memory SQLite suite verifies deterministic stale-request handling; it does not claim a true simultaneous PostgreSQL race. Production safety combines row locks, post-lock authorization, one submission per accomplishment/revision, and one evaluation per submission revision.

## Selected-Academic-Year dashboard continuation — 2026-09-05

**Status:** Complete for role-specific Department and Reviewer/Admin dashboard metrics

The next audited reporting dependency is now implemented as selected-Academic-Year aggregation plus responsive dashboard presentation. It uses the existing planning, accomplishment-submission, and manual-evaluation records; it does not introduce AI analysis, automated scoring, or automatic decisions.

### Implementation and integrity

- Added `DashboardData` as a dedicated aggregation boundary and integrated it through the existing invokable dashboard controller without changing invitation behavior.
- Department Users receive only their assigned department's selected-year plan status, KRA and Plan Item counts, distinct submitted accomplishments per reporting period, outstanding Plan Item-period evidence-backed submission obligations, current review outcomes, and official rating summary.
- Reviewer, Super Admin, and custom roles inheriting those access profiles receive active-department plan coverage, plan status pipeline, current accomplishment review pipeline, and institutional rating summary. Inactive departments are intentionally excluded from institutional obligations and metrics.
- Accomplishment and rating queries constrain both the Operational Plan Academic Year and Reporting Period Academic Year. Department queries additionally constrain the assigned department, preventing mixed-year and cross-department leakage even if inconsistent records exist.
- Reporting-period submission totals count each accomplishment once despite preserved resubmission revisions. Rating summaries use only the latest immutable submission revision with an official rating, so superseded review history does not distort current metrics.
- Added responsive Department and oversight dashboard states, navigation to planning/monitoring/evidence review, and safe no-year, no-plan, and no-department states. The interface explicitly identifies official decisions as manual reviewer work.
- The date-sensitive returned-accomplishment resubmission test now establishes its simulated submission time before creating the new evidence, keeping chronological snapshot expectations deterministic across calendar days without changing production behavior.

### Verification

| Command/check | Result |
| --- | --- |
| `php artisan test --compact tests/Feature/DashboardTest.php` | **Passed:** 13 tests, 266 assertions |
| Focused dashboard, selected-year, evaluation, monitoring, and role suite | **Passed:** 61 tests, 692 assertions |
| `php artisan test --compact` | **Passed:** 351 tests, 1,879 assertions |
| `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress` | **Passed:** no errors |
| `vendor/bin/pint --dirty --format agent` and `composer run lint:check` | **Passed** |
| `npm run lint:check` | **Passed** |
| `npm run format:check` | **Passed** |
| `npm run types:check` | **Passed** |
| `npm run build` | **Passed:** 2,366 modules transformed; production dashboard bundle generated |
| PostgreSQL read-only Department and oversight aggregation execution | **Passed:** both role-specific query paths returned selected-year summaries |
| Credential-key filename-only scan | **Passed:** expected environment-backed configuration/action and mocked-test references only; no new browser-visible credential reference |
| Mojibake marker scan of application source paths | **Passed:** no matches |
| `git diff --check` | **Passed** |

No automated browser screenshot is claimed for this continuation. The Inertia response assertions, frontend static checks, and production Vite build verify the integration; a live visual pass remains advisable when the application is next opened in a browser.

## Remaining incomplete MVP1 work

- Decide before production whether public local Fortify registration remains intentional; it is separate from the approved-user-only Entra linking policy
- Configure and validate the Entra app registration in the deployment environment; no live credentials belong in this repository
- Official report ratings/remarks and completed selected-year export

## Next recommended task

Connect stored official evaluations to the selected-Academic-Year printable/exportable Operational Plan. Render each Plan Item's First and Second Semester ratings and applicable remarks, preserve empty cells where no evaluation exists, and test department/year isolation and cross-year exclusion. Excel remains optional for MVP1.

## VP Administration organizational scope correction — 2026-09-08

**Status:** Complete

The central `ResolveOrganizationalScope` action grants every assigned Executive university-wide read visibility regardless of whether a unit is a school, office, center, department, or another supported type. The plan, monitoring, evidence, dashboard, and report queries all consume that central read scope and contain no school-only filter. Descendant hierarchy remains authoritative for review and mutation actions.

The local seeded hierarchy does not currently provide the intended VP Administration branch. `Administrative and General Services` is an empty Vice Presidential area, while CPRINT, ICT, Quality Assurance, and Human Resources are direct children of the university root. The live local database has the same layout and has no seeded VP Administration account. Consequently, an Executive assigned to the Administrative and General Services area cannot see those administrative units even though the authorization code behaves correctly.

The correction places CPRINT, ICT, Quality Assurance, and Human Resources beneath the Administrative and General Services authority node. Fresh local/testing data now includes `vp.admin@example.com` as an `executive` assigned to that node. Finance and Academic Affairs remain unrelated sibling branches for approval and evaluation authority, while the later Executive visibility revision grants the VP university-wide read access.

A guarded forward data migration updates only affected units that still have the original university-root parent. A unit already moved elsewhere by an administrator is preserved. The migration was applied to the current PostgreSQL database, and the VP Administration demo account was seeded. Its review authority remains the `ADMIN`, `CPRINT`, `ICT`, `QA`, and `HR` branch; its read visibility now includes all 16 units.

### Verification

| Command/check | Result |
| --- | --- |
| Focused hierarchy migration, seeder, and organizational-scope tests | **Passed:** 15 tests, 80 assertions |
| `php artisan test --compact` | **Passed:** 400 tests, 2,477 assertions |
| `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress` | **Passed:** no errors |
| `vendor/bin/pint --dirty --format agent` and `composer run lint:check` | **Passed** |
| `npm run types:check`, `npm run lint:check`, and `npm run format:check` | **Passed** |
| `npm run build` | **Passed:** 2,373 modules transformed |
| `php artisan migrate --no-interaction` | **Passed:** hierarchy correction applied as batch 4 |
| Live PostgreSQL hierarchy and scope query | **Passed:** all four administrative descendants visible; Academic Affairs, Finance, and SITE excluded |
| `git diff --check` | **Passed** |
