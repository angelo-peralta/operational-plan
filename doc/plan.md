# REVISED — Operational Plan System MVP Master Implementation Prompt

> **REVISED PLAN**
>
> This implementation plan supersedes the previous MVP plan.
>
> Major revisions:
>
> * AI analysis is removed from the MVP.
> * Ollama and `laravel/ai` are not part of the current MVP workflow.
> * Evidence review and evaluation are fully manual.
> * `departments` are generalized into hierarchical `organizational_units`.
> * The University President and Vice Presidents use the same `executive` system role.
> * Every assigned Executive has university-wide read visibility without separate hardcoded President/VP roles.
> * The University President has university-wide executive review and override authority.
> * A Vice President has executive authority over the organizational units under their area.
> * VP Academic can immediately evaluate Operational Plans/accomplishments belonging to schools/offices under Academic Affairs.
> * The University President can subsequently review and override a VP evaluation.
> * Previous VP evaluations must remain in history after a Presidential override.
> * Remarks/comments may be attached to individual sections and fields of an Operational Plan, not only to the whole plan.
> * Remarks from executives must be summarized into an Operational Plan Review Summary.
> * `Department User` is replaced by `Secretary / Staff`.
> * Planning Reviewer, Program Head, and separate Unit User roles are removed from the MVP.
> * Only offices that actually participate in the system should be created/enabled. Do not assume that every administrator listed in the university directory requires an account.
>
> Keep the application as a modular Laravel monolith.

---

# 1. Project Objective

You are working inside an existing Laravel application.

Build the MVP of an:

**Operational Plan Management, Monitoring, Manual Evaluation, Evidence Management, Executive Review, Remarks, and Reporting System**

based on the institution's actual Operational Plan format.

Do not recreate the Laravel project from scratch.

Before modifying code, inspect:

* installed Laravel version
* PHP version
* React starter kit structure
* React version
* Inertia version
* TypeScript configuration
* Tailwind configuration
* shadcn/ui components
* PostgreSQL configuration
* authentication implementation
* existing migrations
* existing models
* existing routes
* existing authorization implementation
* existing test framework
* existing queue configuration only for awareness

Use the APIs and conventions that actually exist in the installed versions.

---

# 2. Technology Stack — Revised

Use the existing stack:

* Laravel
* Official Laravel React Starter Kit
* React
* TypeScript
* Inertia.js
* Tailwind CSS
* shadcn/ui
* PostgreSQL
* Laravel authentication
* Laravel Policies / Gates
* Laravel Form Requests
* Laravel Storage
* Pest or the testing framework already installed

For the current revised MVP:

**Do not implement AI functionality.**

Do not build:

* AI evidence analysis
* Ollama integration
* AI agents
* AI structured outputs
* AI queues
* OCR services
* Python services

If `laravel/ai` already exists in the project, do not necessarily uninstall it unless removal is clearly safe and necessary.

Simply do not make it part of the current MVP workflow.

Do not introduce:

* Next.js
* separate React application
* Python
* FastAPI
* MongoDB
* Kubernetes
* RabbitMQ
* microservices
* separate authentication
* separate evidence-review service

Keep the system a:

**modular Laravel monolith**

---

# 3. Core Principle — Academic Year Is the Root Context

The entire system is organized under an Academic Year.

Examples:

* AY 2025–2026
* AY 2026–2027
* AY 2027–2028

Academic Year is a first-class database entity.

Conceptually:

Academic Year
→ Organizational Units
→ Operational Plans
→ Key Result Areas
→ Plan Items
→ Reporting Periods
→ Accomplishments
→ Evidence
→ Executive Evaluation
→ Remarks
→ Reports

The user selects an Academic Year from the application interface.

After selection:

* dashboards use only that Academic Year
* Operational Plans use only that Academic Year
* accomplishments use only that Academic Year
* evaluations use only that Academic Year
* evidence belongs only to accomplishments within that Academic Year
* remarks are shown within that Academic Year context
* reports are filtered by that Academic Year
* exports contain only that Academic Year's records

Academic Years must never be mixed in annual reports or exports.

Server-side filtering is mandatory.

Do not rely solely on React filtering.

---

# 4. Academic Years

Create:

`academic_years`

Suggested fields:

* id
* name
* start_year
* end_year
* starts_on nullable
* ends_on nullable
* status
* is_current boolean
* timestamps

Statuses:

* draft
* open
* closed
* archived

Rules:

* System Admin manages Academic Years.
* Normally only one Academic Year is current.
* Closed years are read-only for ordinary users.
* Historical years remain viewable.
* Historical years containing planning records must not be deleted.
* Academic Year context persists while navigating the application.

---

# 5. Reporting Periods

Each Academic Year contains reporting periods.

For the MVP seed:

* First Semester
* Second Semester

Create:

`reporting_periods`

Suggested fields:

* id
* academic_year_id
* name
* code
* sequence
* starts_on nullable
* ends_on nullable
* status
* timestamps

Example codes:

`first_semester`

`second_semester`

Do not hardcode semester ratings as permanent columns throughout the database.

Evaluations belong to reporting periods.

---

# 6. Revised Organizational Structure

Replace the narrow `departments` concept with:

`organizational_units`

This represents any organizational entity participating in Operational Planning.

Possible unit types:

* university
* executive_office
* vice_presidential_area
* school
* college
* center
* office
* department
* unit
* other

Suggested fields:

* id
* parent_id nullable
* name
* code nullable
* acronym nullable
* type
* description nullable
* is_active boolean
* sort_order nullable
* timestamps

Use a hierarchical parent-child structure.

Example:

St. Paul University Philippines
│
├── Office of the President
│
├── Academic Affairs
│   │
│   ├── Graduate School
│   ├── School of Medicine
│   ├── School of Arts, Sciences and Teacher Education
│   ├── School of Business, Accountancy and Hospitality Management
│   ├── School of Information Technology and Engineering
│   ├── School of Nursing and Allied Health Sciences
│   └── Basic Education Unit
│
├── Finance
├── Administrative and General Services
├── CPRINT
├── ICT
└── other participating offices

Do not assume this list is final.

The administrator must be able to configure participating organizational units.

Only important offices that actually use the system should be activated.

Do not automatically create system accounts for every administrator appearing in the university directory.

---

# 7. Organizational Assignments

Do not restrict a user to one hardcoded `department_id`.

Create a relationship such as:

`organizational_unit_user_assignments`

Suggested fields:

* id
* user_id
* organizational_unit_id
* assignment_type
* position_title nullable
* is_primary boolean
* starts_on nullable
* ends_on nullable
* is_active boolean
* timestamps

Possible assignment types:

* executive
* head
* staff

This allows one person to have legitimate assignments involving more than one organizational unit if required.

Authorization must consider:

1. application role
2. organizational assignment
3. organizational hierarchy
4. Academic Year
5. record status

---

# 8. Revised MVP Roles

Use only four main application roles for the MVP:

* `system_admin`
* `executive`
* `office_head`
* `staff`

Do not create separate roles for:

* President
* VP Academic
* VP Finance
* Reviewer
* Planning Office
* Program Head
* Department User
* Unit User

President and Vice Presidents use the same:

`executive`

role.

Their difference comes from organizational scope.

---

# 9. System Admin

System Admin is a technical/administrative role.

Can:

* manage Academic Years
* manage reporting periods
* manage organizational units
* manage users
* assign users to organizational units
* manage user roles
* configure organizational hierarchy
* view records for administrative support
* reopen records when authorized
* access system-level reports
* correct configuration errors when administratively necessary

System Admin does not automatically become an Operational Plan evaluator simply because they administer the software.

Do not mix system administration authority with academic/executive evaluation authority.

---

# 10. Executive Role

Both the:

* University President
* Vice Presidents

use the same:

`executive`

role.

Executives can:

* create their own unit's Operational Plan
* review subordinate Operational Plans
* approve subordinate Operational Plans
* return subordinate plans for revision
* view accomplishments university-wide
* view uploaded evidence university-wide
* manually evaluate accomplishments
* assign official 0–3 ratings
* add remarks
* add field-level remarks
* request corrections/additional evidence
* access reports university-wide
* export Operational Plans university-wide

The President and Vice Presidents have the same university-wide read visibility. Organizational scope still determines where an Executive may author, approve, return, evaluate, or resolve remarks.

---

# 11. University President Scope

The University President is an Executive assigned to the top/root executive organizational unit.

The President can:

* see Operational Plans university-wide
* see subordinate executive areas
* see schools/offices beneath Vice Presidents
* evaluate Operational Plans/accomplishments throughout the university
* review existing VP evaluations
* add additional remarks
* override a VP's evaluation when necessary
* provide the effective/final executive evaluation when an override is issued

The President should not require a different application role from the VP.

Presidential review and override authority must come from the university-root organizational assignment.

---

# 12. Vice President Scope

Example:

VP Academic is:

`role = executive`

assigned to:

`Academic Affairs`

The VP Academic has university-wide read visibility over all participating organizational units. The Academic Affairs assignment determines the branch where the VP may approve, return, evaluate, and resolve remarks.

For example:

Academic Affairs
│
├── Graduate School
├── School of Medicine
├── SASTE
├── SBAHM
├── SITE
├── SNAHS
└── Basic Education Unit

The VP Academic can:

* see all university Operational Plans, schools, departments, offices, and evidence
* review plans within Academic Affairs
* approve or return them
* see semester accomplishments
* view evidence
* evaluate accomplishments immediately
* assign official ratings
* add remarks at any relevant part of the OP

Every assigned Vice President may view unrelated areas. They must not approve, return, evaluate, or resolve Executive remarks outside their assigned organizational authority.

---

# 13. Office Head

`office_head`

represents the accountable leader of a participating organizational unit.

Examples may include:

* Dean
* Director
* Department Head
* Office Head
* Center Director
* Principal
* other accountable heads

Office Heads can:

* view their own organizational unit
* create their unit's Operational Plan
* edit draft plans
* create KRAs
* create Plan Items
* edit planning fields before approval
* review work prepared by their Secretary/Staff
* submit their OP
* report accomplishments
* upload evidence
* view executive remarks
* respond to returned plans
* respond to requests for corrections/additional evidence
* view ratings and evaluation history
* export their own Operational Plan where permitted

Office Heads cannot:

* approve their own OP
* assign executive ratings to their own accomplishments
* override an Executive evaluation
* view private records belonging to unrelated organizational units

---

# 14. Secretary / Staff

Replace the previous `Department User` concept with:

`staff`

This role represents:

* Secretary
* Executive Secretary
* Administrative Assistant
* designated encoder
* designated office staff

Staff are assigned to an organizational unit.

Staff can:

* prepare draft Operational Plans
* encode Accountable information
* encode Goal
* create KRAs
* create Plan Items
* edit draft data
* encode semester accomplishments
* upload evidence
* view remarks
* make revisions requested by their Office Head or Executive
* assist with record preparation

Staff should normally not:

* give final OP approval
* assign official evaluation ratings
* override evaluations
* approve their own office's OP
* access unrelated organizational units
* submit the final OP unless explicitly authorized by a future permission setting

For the MVP:

**Final Operational Plan submission should normally be performed by the Office Head.**

---

# 15. Same Role, Different Executive Authority

Do not hardcode separate permission logic like:

`if user is VP Academic`

where organizational hierarchy can determine authority instead.

Example:

VP Academic:

Role:
`executive`

Assignment:
`Academic Affairs`

President:

Role:
`executive`

Assignment:
`Office of the President / university root`

The role determines:

**what the user can do.**

The organizational assignment determines:

**where the user can perform executive mutations and evaluations.**

All assigned Executives receive university-wide read visibility.

The hierarchy determines:

**whose records the user can approve/evaluate.**

---

# 16. Operational Plan

Create:

`operational_plans`

Suggested fields:

* id
* academic_year_id
* organizational_unit_id
* accountable_user_id nullable
* accountable_name nullable
* accountable_position nullable
* goal text
* status
* created_by
* submitted_by nullable
* submitted_at nullable
* approved_at nullable
* approved_by nullable
* returned_at nullable
* closed_at nullable
* timestamps

Statuses:

* draft
* submitted
* returned
* approved
* closed

Database constraint:

Normally one Operational Plan per:

`academic_year_id + organizational_unit_id`

Do not silently create duplicate annual OPs for the same organizational unit.

---

# 17. Approval Authority

Operational Plan approval follows the organizational hierarchy.

Example:

SITE
→ parent = Academic Affairs
→ executive = VP Academic

Therefore:

SITE Operational Plan
→ submitted by SITE Office Head
→ reviewed by VP Academic
→ approved/returned by VP Academic

For VP Academic's own OP:

Academic Affairs
→ parent executive authority = University President

Therefore:

VP Academic OP
→ submitted
→ reviewed by University President
→ approved/returned by University President

An Executive must not approve their own Operational Plan.

Never allow self-approval.

---

# 18. Plan Approval History

Do not rely only on `approved_by` on the Operational Plan.

Preserve review history using a table such as:

`operational_plan_reviews`

Suggested fields:

* id
* operational_plan_id
* reviewer_id
* action
* remarks nullable
* reviewed_at
* timestamps

Actions:

* submitted
* approved
* returned
* reopened

This creates an auditable plan approval history.

---

# 19. Institutional Operational Plan Format

Preserve the institution's official fields.

Plan header:

* Academic Year
* Office
* Accountable
* Goal

Operational Plan table:

* Key Result Area
* Objectives
* Strategies
* Key Performance Indicators / Targets
* Unit Co-Accountable
* Resources Needed
* Documentary Evidence
* Evaluation Rating

  * First Semester
  * Second Semester
* Remarks

Evaluation criteria:

* 3 = Exceeded Requirements
* 2 = Met Requirements
* 1 = Somehow Met Requirements
* 0 = Did Not Meet Requirements

---

# 20. Key Result Areas

System Admin manages one global KRA template catalog. These templates are the institutional defaults used by every school, department, office, center, and other organizational unit.

Create:

`key_result_area_templates`

Suggested fields:

* id
* code nullable
* name
* description nullable
* sort_order
* is_active
* created_by nullable
* updated_by nullable
* timestamps

Only System Admin may create, update, activate/deactivate, reorder, or delete global KRA templates. This catalog is not scoped to an Academic Year or organizational unit.

When an Operational Plan is created, copy every active global template into plan-owned `key_result_areas` rows in template order. The copied rows are snapshots. Later template changes apply only to future Operational Plans and must never rewrite an existing plan, approval snapshot, remark target, or evaluation history.

Create:

`key_result_areas`

Suggested fields:

* id
* operational_plan_id
* code nullable
* name
* description nullable
* sort_order
* created_by nullable
* updated_by nullable
* timestamps

KRAs maintain manual ordering.

Never rely on database IDs for display order.

Plan-owned KRAs remain editable while the Operational Plan workflow permits editing. Editing a plan-owned KRA does not change the global template.

---

# 21. Plan Items

Create:

`plan_items`

Suggested fields:

* id
* key_result_area_id
* objective text
* strategy text nullable
* kpi_target_text text
* target_value decimal nullable
* target_unit string nullable
* target_operator nullable
* target_frequency nullable
* resources_needed text nullable
* documentary_evidence_requirements jsonb nullable
* sort_order
* status
* created_by nullable
* updated_by nullable
* timestamps

`kpi_target_text` remains authoritative.

Do not assume every KPI can be represented numerically.

Optional structured operators:

* equals
* at_least
* at_most
* percentage_at_least
* percentage_at_most
* zero_tolerance
* qualitative

---

# 22. Unit Co-Accountable

A Plan Item may have multiple co-accountable organizational units.

Create:

`plan_item_co_accountables`

Suggested fields:

* plan_item_id
* organizational_unit_id

Prefer linked organizational records rather than comma-separated text.

A manual-text fallback may be allowed only when a referenced unit does not exist in the organizational structure.

---

# 23. Resources Needed

Store:

`resources_needed`

on the Plan Item.

For the MVP it may remain text.

---

# 24. Documentary Evidence Requirements

The Plan Item identifies expected documentary evidence.

Examples:

* Approved Policy Documents
* Monitoring Reports
* Program Evaluation Records
* Training Records
* Certificates
* Research Outputs
* Screenshots
* Monitoring Logs
* Submission Records
* Compliance Reports

Store as structured JSON where practical.

Example:

[
"Training Records",
"Certificates"
]

Expected documentary evidence is different from actual uploaded evidence.

---

# 25. Semester Accomplishments

Create:

`accomplishments`

Suggested fields:

* id
* plan_item_id
* reporting_period_id
* reported_value nullable
* accomplishment_text text nullable
* percentage_accomplished nullable
* status
* created_by
* submitted_by nullable
* submitted_at nullable
* resubmitted_at nullable
* timestamps

Statuses:

* draft
* submitted
* under_review
* returned
* accepted
* rejected

Remove AI-specific statuses such as:

* queued_for_analysis
* analyzing
* ready_for_review

One current accomplishment should normally exist per:

`plan_item_id + reporting_period_id`

Preserve revision history where required.

---

# 26. Accomplishment Percentage

Where the KPI is truly numeric:

`reported value / target value × 100`

Calculate this in the system.

Do not divide by zero.

Do not force numeric calculations onto qualitative KPIs.

Do not allow arbitrary manual percentage overrides where the percentage can be calculated reliably, unless an authorized workflow is explicitly introduced later.

---

# 27. Actual Evidence Uploads

Create:

`evidence`

Suggested fields:

* id
* accomplishment_id
* uploaded_by
* evidence_type nullable
* title nullable
* description nullable
* original_filename
* stored_path
* mime_type
* file_size
* checksum nullable
* timestamps

MVP file types:

* PDF
* JPG
* JPEG
* PNG

Use Laravel Storage.

Evidence must be private.

Do not expose predictable public file URLs.

Provide authorized controller routes for:

* view
* download

Access must be checked server-side.

---

# 28. Manual Evidence Review

All evidence review is manual for the revised MVP.

No AI analysis occurs after accomplishment submission.

Workflow:

Office Head / Staff
→ enters accomplishment
→ uploads evidence
→ Office Head submits
→ accomplishment status = submitted / under_review
→ authorized Executive reviews accomplishment and evidence
→ Executive evaluates
→ Executive accepts, returns, or rejects

The system must display enough context for the Executive to manually assess:

* Academic Year
* Reporting Period
* Organizational Unit
* KRA
* Objective
* Strategy
* KPI / Target
* Documentary Evidence Requirements
* Reported Accomplishment
* Uploaded Evidence

---

# 29. Executive Evaluation

Create:

`evaluations`

Suggested fields:

* id
* accomplishment_id
* evaluator_id
* organizational_unit_id nullable
* rating nullable
* decision
* remarks nullable
* is_override boolean default false
* overrides_evaluation_id nullable
* evaluated_at
* timestamps

Official rating:

* 0
* 1
* 2
* 3

Mapping:

0 = Did Not Meet Requirements

1 = Somehow Met Requirements

2 = Met Requirements

3 = Exceeded Requirements

Decisions:

* accepted
* request_revision
* request_additional_evidence
* rejected

Evaluation records are historical.

Never overwrite an older evaluation.

---

# 30. VP Evaluation

A Vice President may evaluate an accomplishment belonging to an organizational unit inside their executive scope.

Example:

SITE accomplishment
→ VP Academic reviews
→ VP Academic assigns rating 2
→ VP Academic enters remarks

This evaluation becomes the current/effective executive rating unless superseded by a higher-authority evaluation.

---

# 31. Presidential Review and Override

The University President can review an OP/accomplishment that has already been evaluated by a Vice President.

The President may:

* agree with the VP evaluation
* add additional remarks without changing the rating
* create a new evaluation
* override the VP rating

When overriding:

Do NOT modify or delete the VP evaluation.

Example:

VP Evaluation:

Rating:
2 — Met Requirements

President Evaluation:

Rating:
3 — Exceeded Requirements

`is_override = true`

`overrides_evaluation_id = [VP evaluation ID]`

The effective/final rating becomes the President's rating.

The VP evaluation remains visible in history.

---

# 32. Effective Rating Rule

The application must distinguish between:

* evaluation history
* current/effective rating

If only a VP evaluation exists:

Effective Rating = VP rating

If a President evaluation overrides the VP:

Effective Rating = President rating

Do not hide the VP evaluation.

Display both.

Example:

First Semester

VP Academic
2 — Met Requirements

President
3 — Exceeded Requirements
Overrides VP Evaluation

Official / Effective Rating
3 — Exceeded Requirements

---

# 33. Revised Remark System

Remarks must not exist only as one large text field for the whole Operational Plan.

Executives must be able to attach remarks to specific parts of the OP.

Create a generalized table such as:

`remarks`

Suggested fields:

* id
* academic_year_id
* operational_plan_id
* author_id
* remarkable_type
* remarkable_id
* field_key nullable
* reporting_period_id nullable
* category nullable
* content
* status
* resolved_by nullable
* resolved_at nullable
* timestamps

Use a polymorphic relationship where appropriate.

Possible targets include:

* OperationalPlan
* KeyResultArea
* PlanItem
* Accomplishment
* Evidence
* Evaluation

---

# 34. Field-Level Remarks

For Plan Items, allow a remark to identify a specific field.

Possible `field_key` values:

* accountable
* goal
* kra_name
* objective
* strategy
* kpi_target_text
* co_accountables
* resources_needed
* documentary_evidence_requirements
* accomplishment_text
* evidence
* evaluation

Example:

Plan Item 15

Field:

`kpi_target_text`

VP Academic Remark:

"Please specify whether this target is per semester or per Academic Year."

The President may independently add:

"Agree with the VP. The measurement period must be explicit."

Both remarks remain visible.

---

# 35. Remark Status

Statuses:

* open
* addressed
* resolved

Executives can create remarks.

Office Head/Staff can view remarks and make requested revisions.

Where appropriate they may mark a remark:

`addressed`

The authorized Executive may mark it:

`resolved`

Do not delete old remarks simply because a change was made.

Preserve the review history.

---

# 36. Operational Plan Review Summary

Create a consolidated review summary.

The summary should aggregate remarks belonging to the selected Operational Plan.

Display:

* total remarks
* open remarks
* addressed remarks
* resolved remarks
* remarks by President
* remarks by VP
* remarks grouped by KRA
* remarks grouped by Plan Item
* remarks grouped by field
* semester-specific remarks where applicable

Example:

REVIEW SUMMARY

Open Remarks: 5
Resolved Remarks: 3

VP Academic

KRA 1 → Objective
"Clarify the intended outcome."

KRA 1 → KPI / Target
"Specify measurement period."

President

KRA 1 → KPI / Target
"Agree with VP; revise before approval."

KRA 2 → Documentary Evidence
"Provide the specific required documentary record."

Clicking a remark in the UI should navigate to or highlight the relevant section where practical.

Do not require users to search manually through the entire OP for comments.

---

# 37. Official Operational Plan View

Provide a Print / Official View resembling the institutional form.

Columns:

1. Key Result Area
2. Objectives
3. Strategies
4. KPI / Targets
5. Unit Co-Accountable
6. Resources Needed
7. Documentary Evidence
8. Evaluation Rating

   * First Semester
   * Second Semester
9. Remarks

The editing interface does not need to be a giant spreadsheet.

Use structured forms/cards/dialogs for editing.

The official view should reproduce the institutional table layout as closely as practical.

---

# 38. Planning Workflow — Revised

For the selected Academic Year:

Secretary / Staff
→ prepares draft OP

Office Head
→ reviews draft
→ edits if necessary
→ submits OP

System determines parent Executive.

Executive
→ reviews OP
→ adds section/field remarks where necessary

Decision:

Approved
→ OP becomes approved/locked

Returned
→ Office Head/Staff revises
→ remarks remain visible
→ Office Head resubmits

After approval:

Planning fields should not be freely changed.

Administrative reopening or controlled revision is required.

---

# 39. Example Academic Approval Workflow

SITE Secretary
→ prepares SITE OP

SITE Dean
→ reviews
→ submits

VP Academic
→ reviews
→ remarks where necessary
→ approves or returns

Because SITE belongs under Academic Affairs.

---

# 40. VP Academic's Own Operational Plan

VP Academic / assigned staff
→ prepares Academic Affairs OP

VP Academic
→ submits

University President
→ reviews
→ comments
→ approves or returns

VP Academic cannot approve their own OP.

---

# 41. Semester Monitoring Workflow — Revised

Approved Plan Item
→ First Semester opens
→ Office Head/Staff records accomplishment
→ Evidence uploaded
→ Office Head submits
→ Executive reviews manually
→ Executive assigns rating 0–3
→ Executive adds remarks
→ Executive accepts / returns / rejects

If under Academic Affairs:

VP Academic can evaluate immediately.

President may subsequently:

* view
* add remarks
* evaluate
* override VP rating

Second Semester repeats the same workflow.

---

# 42. Evidence Resubmission

If additional evidence or revisions are requested:

Existing evaluation remains in history.

Existing evidence remains stored.

Existing remarks remain stored.

Accomplishment becomes returned.

Office Head/Staff:

* uploads additional evidence
* updates accomplishment where allowed
* responds to remarks
* resubmits

Executive reviews again.

Create a new evaluation rather than overwriting the previous one.

Auditability is mandatory.

---

# 43. Authorization

Create Policies for at least:

* AcademicYear
* OrganizationalUnit
* OperationalPlan
* KeyResultArea
* PlanItem
* Accomplishment
* Evidence
* Evaluation
* Remark

Rules include:

Staff:

* can access assigned organizational unit only
* cannot access unrelated private OPs
* cannot approve OPs
* cannot assign executive ratings

Office Head:

* can access/manage own organizational unit
* can submit own unit OP
* cannot approve own OP
* cannot assign an executive rating to their own accomplishment

Executive:

* can view university-wide records and organizational units
* can approve subordinate OPs
* can evaluate subordinate accomplishments

President Executive:

* has university-wide executive scope
* may review and override subordinate Executive evaluations

Vice President Executive:

* limited by organizational scope
* cannot act on unrelated executive branches without explicit authorization

System Admin:

* has technical administrative access
* does not automatically become academic evaluator

Closed Academic Years:

* ordinary editing prohibited
* reopen requires explicit administrative action

All authorization must be enforced server-side.

---

# 44. Organizational Scope Resolution

Implement reusable application logic for hierarchy-based scope.

Example conceptual methods:

* `isAssignedToUnit()`
* `canManageUnit()`
* `canViewUnit()`
* `canApprovePlan()`
* `canEvaluateAccomplishment()`
* `isAncestorOf()`
* `descendantUnitIds()`

Do not duplicate organizational access logic across controllers.

Consider a focused service/action/helper where appropriate.

Policies remain the authoritative authorization layer.

---

# 45. Validation

Use Form Requests.

Validate:

* Academic Year
* organizational unit ownership
* unique OP per unit/year
* plan status
* reporting period belongs to same Academic Year
* organizational hierarchy permissions
* user assignments
* approval authority
* no self-approval
* accomplishment status
* evidence file type
* evidence file size
* official rating 0–3
* override authority
* valid overridden evaluation
* remark target
* remark field key
* closed Academic Year restrictions

Do not rely solely on frontend validation.

---

# 46. Audit Trail

Preserve important events including:

* plan creator
* Academic Year
* organizational unit
* user who modified draft
* submission
* submission timestamp
* approval
* return for revision
* approver
* approval timestamp
* KRA changes
* Plan Item changes
* accomplishment submission
* reporting period
* evidence uploader
* evidence timestamps
* evaluation author
* rating
* decision
* evaluation timestamp
* Presidential override
* original VP evaluation
* remarks
* remark author
* remark target
* remark status changes
* resubmissions

Do not silently overwrite historically important data.

---

# 47. Dashboard — Revised

## Staff / Office Head Dashboard

For selected Academic Year show:

* OP status
* number of KRAs
* Plan Item count
* open Executive remarks
* addressed remarks
* First Semester accomplishment status
* Second Semester accomplishment status
* missing/pending evidence
* returned accomplishments
* accepted accomplishments
* current ratings
* evaluation history summary

## Executive Dashboard

For selected Academic Year show data only within Executive scope.

Examples:

* subordinate organizational units
* units with OP
* units without OP
* draft OPs
* submitted OPs
* returned OPs
* approved OPs
* accomplishments awaiting review
* returned accomplishments
* accepted accomplishments
* open remarks
* evaluation completion
* rating summaries where meaningful

For President:

show university-wide information.

For VP Academic:

show Academic Affairs and descendant units.

Do not mix Academic Years.

---

# 48. Navigation — Revised

Suggested navigation:

Dashboard

Operational Plans

Monitoring

Evidence

Evaluations

Remarks / Review Summary

Reports

Administration

* Academic Years
* Organizational Units
* Users
* Assignments

Show Academic Year selector prominently.

Example:

`Academic Year: [ AY 2025–2026 ▼ ]`

For Executives, optionally show current scope:

`Scope: Academic Affairs`

or

`Scope: University-wide`

---

# 49. Operational Plan Editor

Use a structured editor.

Header:

* Academic Year
* Organizational Unit / Office
* Accountable
* Position
* Goal

KRA:

* Code
* Name
* Description

Plan Item:

* Objective
* Strategy
* KPI / Target
* structured target fields where applicable
* Unit Co-Accountable
* Resources Needed
* Documentary Evidence Requirements

Allow:

* Add KRA
* Edit KRA
* Reorder KRA
* Add Plan Item
* Edit Plan Item
* Reorder Plan Items
* Remove draft Plan Items

Display remark indicators beside fields.

Example:

KPI / Target
`At least 2 workshops per Academic Year`

`2 Executive Remarks`

Users can open the remark thread for that exact field.

---

# 50. Executive Review Screen

For Operational Plan review display:

* Academic Year
* Organizational Unit
* Accountable
* Goal
* KRA structure
* Plan Items
* field-level comment buttons
* unresolved remarks
* review summary

Actions:

* Approve
* Return for Revision
* Add Remark

Use confirmation dialogs for final approval/return actions.

---

# 51. Executive Evaluation Screen

For each accomplishment display:

* Academic Year
* Reporting Period
* Organizational Unit
* KRA
* Objective
* Strategy
* KPI / Target
* expected Documentary Evidence
* reported accomplishment
* uploaded evidence
* previous evaluations
* previous remarks

Evaluation section:

Rating:

* 3 Exceeded Requirements
* 2 Met Requirements
* 1 Somehow Met Requirements
* 0 Did Not Meet Requirements

Remarks

Decision:

* Accept
* Request Revision
* Request Additional Evidence
* Reject

For President additionally show:

* VP evaluation
* VP rating
* VP remarks
* option to agree
* option to add Presidential evaluation
* option to override rating

Preserve every evaluation.

---

# 52. Reports and Export

User must explicitly select an Academic Year.

Exports include only records belonging to the selected Academic Year.

Official report contains:

OPERATIONAL PLAN

Academic Year

Office

Accountable

Goal

Evaluation Criteria:

3 - Exceeded Requirements
2 - Met Requirements
1 - Somehow Met Requirements
0 - Did Not Meet Requirements

Official table:

* Key Result Area
* Objectives
* Strategies
* KPI / Targets
* Unit Co-Accountable
* Resources Needed
* Documentary Evidence
* First Semester
* Second Semester
* Remarks

Where a President has overridden the VP evaluation:

the effective Presidential rating should appear as the official exported rating.

Evaluation history should remain available in the application.

Do not accidentally export records belonging to another Academic Year.

Prioritize:

1. browser print view
2. PDF-friendly layout

Excel may be added later.

---

# 53. Remarks in Reports

The official table may show concise/effective evaluation remarks.

Do not force every internal comment into the official printed table.

Provide a separate optional:

`Executive Remarks Summary`

containing:

* author
* target section
* KRA
* Plan Item
* field
* remark
* status

This allows detailed review records without making the official Operational Plan table unreadable.

---

# 54. Seed Data — Revised

Seed:

Academic Years:

* AY 2025–2026
* AY 2026–2027

Reporting Periods:

* First Semester
* Second Semester

Create only representative participating organizational units for development.

Example:

* St. Paul University Philippines
* Office of the President
* Academic Affairs
* CPRINT
* ICT
* Finance
* Administrative and General Services
* Graduate School
* School of Medicine
* SASTE
* SBAHM
* SITE
* SNAHS
* Basic Education Unit

Do not treat this as the final production organizational roster.

Production units must remain administratively configurable.

---

# 55. Demo Users — Revised

Create development/demo accounts such as:

System Admin

President Executive

VP Academic Executive

School Dean / Office Head

Secretary / Staff

Clearly identify them as development credentials.

Example relationships:

President Executive
→ assigned to university/root executive scope

VP Academic Executive
→ assigned to Academic Affairs

SITE Dean
→ office_head
→ assigned to SITE

SITE Secretary
→ staff
→ assigned to SITE

---

# 56. Sample Plan

Continue using the institutional sample Operational Plan as development seed data.

Example:

Academic Year:

AY 2025–2026

Office:

Center for Planning, Research, Innovations, and New Technologies

Goal:

To ensure the full integration of ethical AI and digital technologies in teaching, learning, research, and administrative processes through 100% adoption, capacity building, and efficient, timely, and high-quality reporting systems.

The topic of the sample OP may contain references to AI because that is part of the institution's planning content.

This does NOT mean the Operational Plan System itself uses AI.

Preserve representative:

* KRA
* Objective
* Strategy
* KPI / Target
* Co-Accountable
* Resources
* Documentary Evidence

from the existing institutional sample.

---

# 57. Testing — Revised

Create feature tests for at least:

## Authentication and Context

* authentication
* Academic Year selection
* Academic Year persistence
* Academic Year isolation
* closed-year restrictions

## Organization

* organizational hierarchy
* organizational assignment
* staff unit isolation
* office head unit isolation
* executive university-wide read visibility
* VP approval/evaluation scope isolation
* President university-wide scope

## Operational Plan

* unique OP per unit/year
* plan creation
* KRA creation
* Plan Item creation
* staff draft editing
* Office Head submission
* self-approval prevention
* VP approval of subordinate school OP
* President approval of VP OP
* plan return and resubmission

## Remarks

* add OP-level remark
* add KRA remark
* add Plan Item remark
* add field-level remark
* staff can view remarks
* unrelated users cannot view private remarks
* address remark
* resolve remark
* remark summary aggregation
* remarks preserved after resubmission

## Monitoring

* First Semester accomplishment
* Second Semester accomplishment
* accomplishment calculation
* qualitative KPI handling

## Evidence

* evidence upload
* private evidence access
* unauthorized evidence access
* evidence resubmission

## Evaluation

* VP evaluation
* rating validation 0–3
* VP evaluation visible to President
* President evaluation
* President override of VP evaluation
* VP evaluation preserved after override
* effective rating uses President override
* unauthorized user cannot override
* request additional evidence
* rejection
* evaluation history preserved

## Reports

* dashboard respects Academic Year
* executive dashboard respects organizational scope
* export filters Academic Year
* export uses effective rating
* records from another Academic Year never appear

No automated test should require AI, Ollama, or external services.

---

# 58. Out of Scope — Revised MVP

Do not implement yet:

* AI evidence analysis
* Ollama
* automatic evidence validation
* automatic official rating
* AI recommendations
* OCR
* custom AI model training
* fine-tuning
* embeddings
* vector databases
* RAG
* semantic search
* Python
* FastAPI
* mobile app
* SMS
* real-time websockets
* Kubernetes
* microservices
* advanced budgeting
* digital signatures
* complex report designer
* forecasting
* predictive analytics
* automatic final approval

All evaluations remain human/manual.

---

# 59. Coding Standards

Follow existing Laravel conventions.

Use:

* Eloquent relationships
* recursive/hierarchical organizational relationships where appropriate
* Form Requests
* Policies
* PHP enums where appropriate
* action/service classes for important workflows
* TypeScript
* React functional components
* reusable components

Keep controllers thin.

Do not introduce unnecessary repository layers.

Do not hardcode:

* Academic Year IDs
* organizational unit IDs
* user IDs
* President user ID
* VP Academic user ID
* school IDs

Authorization must be relationship/scope-based.

Do not perform unrelated refactors.

---

# 60. Revised Implementation Order

## Phase 1 — Revised Foundation

Inspect the existing project.

Implement:

* AcademicYear
* ReportingPeriod
* OrganizationalUnit
* hierarchical parent/child relationships
* User role
* OrganizationalUnitUserAssignment
* role enums
* assignment enums
* Academic Year selector
* authorization foundation
* organizational scope resolution
* representative seeders

Run:

* migrations
* backend tests
* frontend type checking
* frontend build/check if configured

Fix failures.

Stop and summarize.

---

## Phase 2 — Operational Plan and Executive Approval

Implement:

* OperationalPlan
* KeyResultArea
* PlanItem
* co-accountable organizational units
* Documentary Evidence Requirements
* plan editor
* Office Head submission
* Executive approval/return
* hierarchy-based approving authority
* prevent self-approval
* Operational Plan review history
* official plan view

Also implement:

* generalized Remarks
* field-level remarks
* remark status
* Review Summary

Run tests.

Stop and summarize.

---

## Phase 3 — Semester Monitoring and Evidence

Implement:

* Accomplishment
* reporting-period workflow
* calculated accomplishment where appropriate
* First Semester
* Second Semester
* private evidence upload
* evidence view
* evidence download
* evidence metadata
* evidence permissions
* return/resubmission behavior

No AI.

Run tests.

Stop and summarize.

---

## Phase 4 — Manual Executive Evaluation

Implement:

* Executive evaluation queue/list
* detailed accomplishment review
* official rating 0–3
* evaluation remarks
* accept
* request revision
* request additional evidence
* reject
* evaluation history
* VP evaluation
* Presidential review
* Presidential override
* effective rating calculation
* preservation of previous evaluations

Run tests.

Stop and summarize.

---

## Phase 5 — Dashboard, Reports, and Export

Implement:

* Staff dashboard
* Office Head dashboard
* Executive dashboard
* President university-wide dashboard
* VP university-wide dashboard with scoped review actions
* open remarks summary
* evaluation summaries
* monitoring reports
* annual reports
* official Operational Plan print view
* PDF-friendly export
* Academic Year isolation
* effective evaluation rating in official report

Run tests.

Stop and summarize.

---

## Phase 6 — MVP Hardening

Audit:

* authorization
* hierarchy traversal
* self-approval prevention
* executive scope
* President override
* Academic Year isolation
* evidence privacy
* remark visibility
* audit history
* closed Academic Year restrictions
* report filtering

Run the complete automated test suite.

Run frontend/type checks.

Fix all relevant failures.

---

# 61. Revised MVP Definition of Done

The revised MVP is complete when the following scenario works:

1. System Admin creates AY 2025–2026.

2. First Semester and Second Semester exist under the Academic Year.

3. System Admin creates the organizational hierarchy.

4. System Admin creates or assigns:

   * President Executive
   * VP Academic Executive
   * School Dean / Office Head
   * Secretary / Staff

5. President is assigned university-wide/root executive scope.

6. VP Academic is assigned Academic Affairs.

7. Schools under VP Academic are children/descendants of Academic Affairs.

8. Secretary prepares the school's Operational Plan.

9. Office Head reviews it.

10. Office Head submits the plan.

11. VP Academic can immediately see the submitted OP through university-wide Executive read visibility.

12. VP Academic can add remarks to:

    * Goal
    * KRA
    * Objective
    * Strategy
    * KPI / Target
    * Co-Accountable
    * Resources
    * Documentary Evidence
    * other supported sections

13. The system summarizes all VP remarks in the Operational Plan Review Summary.

14. VP Academic approves or returns the school OP.

15. If returned, previous remarks remain visible.

16. Office Head/Staff revises the plan.

17. VP approves the plan.

18. VP Academic prepares/submits their own Academic Affairs OP.

19. VP Academic cannot approve their own OP.

20. University President receives the VP Academic OP.

21. President approves or returns it.

22. First Semester opens.

23. School Staff/Office Head records accomplishment.

24. Evidence is uploaded.

25. Office Head submits accomplishment.

26. VP Academic manually reviews accomplishment and evidence.

27. VP Academic assigns an official rating 0–3.

28. VP Academic adds evaluation remarks.

29. President can see the VP evaluation.

30. President may agree without changing it.

31. President may add additional remarks.

32. President may create a higher-authority evaluation.

33. President may override the VP rating.

34. When President overrides:

    * VP rating remains in history
    * VP remarks remain in history
    * President rating becomes effective/final
    * override relationship is recorded

35. Second Semester can repeat the same process.

36. Staff cannot access unrelated schools/offices.

37. Office Head cannot access unrelated private OPs.

38. VP Academic can view unrelated VP areas but cannot approve, return, or evaluate records outside Academic Affairs.

39. President can view university-wide OPs.

40. User switches to AY 2026–2027.

41. AY 2025–2026 records disappear from ordinary AY 2026–2027 views.

42. User switches back to AY 2025–2026.

43. Historical information remains intact.

44. Executive selects AY 2025–2026.

45. Executive exports the Operational Plan.

46. Export contains:

    * Office
    * Accountable
    * Goal
    * KRAs
    * Objectives
    * Strategies
    * KPI / Targets
    * Co-Accountable Units
    * Resources Needed
    * Documentary Evidence
    * First Semester Rating
    * Second Semester Rating
    * effective/final remarks

47. If the President overrode the VP, the Presidential rating appears as the official/effective rating.

48. VP evaluation remains available in evaluation history.

49. No data belonging to another Academic Year appears in the export.

50. No AI or Ollama dependency is required for any MVP workflow.

---

# 62. First Instruction to Execute — REVISED

Do not build the entire MVP immediately.

Start by inspecting the repository.

Report:

1. Laravel version
2. PHP version
3. React version
4. Inertia version
5. starter-kit structure
6. TypeScript configuration
7. Tailwind/shadcn configuration
8. current PostgreSQL configuration
9. current queue configuration for awareness only
10. authentication implementation
11. current migrations
12. current models
13. current routes
14. existing Policies/Gates
15. existing tests

Then compare the current codebase against this:

**REVISED MVP PLAN**

Identify anything already implemented from the previous plan that conflicts with the revised requirements.

In particular inspect for:

* `departments`
* `department_id`
* old user roles
* Reviewer / Planning Office role
* Department User role
* AI models/services
* `EvidenceAnalysis`
* `AnalyzeEvidence`
* Ollama configuration
* AI-specific accomplishment statuses
* existing evaluation structure
* existing remarks implementation

Do not blindly delete working code.

Report what should be:

* retained
* renamed
* migrated
* refactored
* disabled
* removed

Then propose the exact revised Phase 1 database schema for:

* `academic_years`
* `reporting_periods`
* `organizational_units`
* `organizational_unit_user_assignments`
* required changes to `users`

Explain:

* organizational hierarchy
* Executive scope
* President scope
* VP scope
* Office Head scope
* Staff scope
* important uniqueness constraints
* server-side authorization strategy

Then implement:

**REVISED PHASE 1 ONLY**

Run migrations.

Run relevant automated tests.

Run frontend/type checks if configured.

Fix failures related to Phase 1.

At the end provide a concise implementation summary:

* files created
* files modified
* migrations added
* migrations changed
* database tables added
* old structures migrated/refactored
* roles created
* seeders created
* tests added
* commands run
* test results
* remaining Revised Phase 2 work

Do not proceed to Phase 2 until Revised Phase 1 is stable.

---

# FINAL IMPLEMENTATION PRINCIPLE

This is the **REVISED Operational Plan System MVP**.

The MVP is:

**Academic Year centered**

**hierarchy-aware**

**manually evaluated**

**Executive supervised**

**President-overridable**

**remark-driven**

**auditable**

**organization-scoped**

**Laravel based**

and contains **no AI dependency for now**.

The President and Vice Presidents share the same `executive` role.

They have the same university-wide read visibility. Their organizational assignments determine authoring, approval, evaluation, and remark-resolution authority.

The VP evaluates the Operational Plans and accomplishments under their area.

The President and Vice Presidents can view university-wide records. The President may override a subordinate Executive evaluation through the university-root authority assignment.

Every important comment or evaluation must preserve history.

Remarks should be attachable to specific sections and fields and summarized for convenient executive review.

Secretary / Staff users support their Office Head but do not replace executive or Office Head accountability.
