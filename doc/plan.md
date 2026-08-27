# Operational Plan System — MVP Master Implementation Prompt

You are working inside an existing Laravel application.

Your task is to build the MVP of an **Operational Plan Management, Monitoring, Evaluation, Evidence Validation, and Reporting System** based on the institution's actual Operational Plan format.

Do not recreate the Laravel project from scratch.

Before writing code, inspect:

* installed Laravel version
* PHP version
* React starter kit structure
* Inertia version
* TypeScript configuration
* Tailwind / shadcn components
* PostgreSQL configuration
* Laravel AI package/version
* queue configuration
* authentication implementation
* existing migrations
* existing models
* existing routes

Use the APIs and conventions that actually exist in the installed package versions.

---

# 1. Technology Stack

Use the existing stack:

* Laravel
* Official Laravel React Starter Kit
* React
* TypeScript
* Inertia.js
* Tailwind CSS
* shadcn/ui components included by the starter kit
* PostgreSQL
* Laravel authentication
* Laravel Policies / Gates
* Laravel Form Requests
* Laravel Queue
* Database queue for MVP
* Laravel Storage
* `laravel/ai`
* Ollama for local AI development
* Local model: `qwen3-vl:4b-instruct`
* Pest or the testing framework already installed

Do not introduce:

* Next.js
* a separate React application
* Python
* FastAPI
* MongoDB
* Kubernetes
* RabbitMQ
* microservices
* a separate authentication system
* a separate AI application

Keep the MVP as a **modular Laravel monolith**.

---

# 2. Core System Principle: Academic Year Is the Root Context

The entire operational planning system must be organized by **Academic Year**.

Examples:

* AY 2025–2026
* AY 2026–2027
* AY 2027–2028

An Academic Year is not simply a text field on a report.

It is a first-class database entity and the main context used throughout the application.

The user must be able to select an Academic Year from the interface.

After selecting an Academic Year:

* dashboard statistics must show only data from that academic year
* departments' operational plans must show only data from that academic year
* accomplishments must belong to that academic year through their operational plan
* evidence must belong to accomplishments within that academic year
* evaluations must belong to reporting periods within that academic year
* monitoring screens must filter by that academic year
* reports must filter by that academic year
* exports must contain only records from that academic year

Never mix records from different academic years in an annual Operational Plan export.

Use the Academic Year as the main planning container.

Conceptually:

Academic Year
→ Departments
→ Operational Plans
→ Key Result Areas
→ Plan Items
→ Semester Accomplishments
→ Evidence
→ AI Analysis
→ Human Evaluation

---

# 3. Academic Year Model

Create an `academic_years` table.

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

Example:

name:
`AY 2025-2026`

start_year:
`2025`

end_year:
`2026`

Statuses:

* draft
* open
* closed
* archived

Rules:

* only authorized administrators may create academic years
* there should normally be only one current academic year
* closing an academic year should prevent ordinary department users from modifying plans/accomplishments in that year
* historical academic years must remain viewable
* do not delete historical academic years that already contain planning data

Create an Academic Year selector in the application layout.

The selected academic year should persist while navigating the system.

Routes, query parameters, session state, or another clean Laravel/Inertia approach may be used, but authorization and database filtering must happen server-side.

Do not rely only on frontend filtering.

---

# 4. Reporting Periods

Each Academic Year should contain reporting periods.

For the current institutional format, seed:

* First Semester
* Second Semester

Create a `reporting_periods` table.

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

Example:

First Semester:
`first_semester`

Second Semester:
`second_semester`

This is preferable to hardcoding `first_semester_rating` and `second_semester_rating` columns everywhere.

The export can still display the official columns:

* First Semester
* Second Semester

but internally evaluations should be normalized by reporting period.

---

# 5. Institutional Operational Plan Format

The system must preserve all major fields appearing in the institution's existing Operational Plan form.

The plan header contains:

* Academic Year
* Office
* Accountable
* Goal

The operational plan table contains:

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

The evaluation criteria are:

* 3 = Exceeded Requirements
* 2 = Met Requirements
* 1 = Somehow Met Requirements
* 0 = Did Not Meet Requirements

These fields must be represented in the database and in the export.

---

# 6. Departments / Offices

Create a `departments` table.

The term Department represents an office/unit participating in operational planning.

Suggested fields:

* id
* name
* code
* description nullable
* is_active boolean
* timestamps

Examples may include:

* Center for Planning, Research, Innovations, and New Technologies
* Information and Communications Technology
* Quality Assurance
* Human Resources
* Finance
* Planning Office

Relationships:

Department has many Users.

Department has many Operational Plans across different Academic Years.

---

# 7. Users and Roles

Use the existing starter-kit User model.

Add:

* department_id nullable
* role

MVP roles:

## Super Admin

Can:

* manage Academic Years
* manage reporting periods
* manage departments
* manage users
* view all plans
* view all accomplishments
* view all evidence
* review evidence
* perform evaluations
* access all reports
* export plans
* reopen records when administratively necessary

## Reviewer / Planning Office

Can:

* view plans across departments
* review submitted plans
* monitor accomplishments
* review evidence
* view AI analysis
* assign official evaluation ratings
* enter remarks
* request additional evidence
* reject unsupported submissions
* access monitoring reports
* export operational plans

## Department User

Belongs to one department.

Can:

* access their department
* create/edit their department's plan for an open Academic Year
* create KRAs and Plan Items where allowed
* submit the plan
* report semester accomplishments
* upload documentary evidence
* submit accomplishments
* view AI analysis where permitted
* view reviewer feedback
* submit additional evidence when requested

Department users must never gain access to another department's private records by modifying a URL or request parameter.

Enforce authorization on the Laravel backend.

---

# 8. Operational Plan

Create an `operational_plans` table.

Suggested fields:

* id
* academic_year_id
* department_id
* accountable_user_id nullable
* accountable_name nullable
* accountable_position nullable
* goal text
* status
* submitted_at nullable
* approved_at nullable
* approved_by nullable
* returned_at nullable
* timestamps

Statuses:

* draft
* submitted
* approved
* returned
* closed

Important constraint:

A department should normally have only one Operational Plan for a particular Academic Year.

Add a database-level uniqueness constraint where appropriate:

`academic_year_id + department_id`

Example:

Academic Year:
AY 2025–2026

Office:
Center for Planning, Research, Innovations, and New Technologies

Acronym:
CPRINT

The `acronym` field is optional and may be left blank when the office or department does not have an acronym.

Accountable:
CPRINT Director

Goal:
To ensure the full integration of ethical AI and digital technologies in teaching, learning, research, and administrative processes through adoption, capacity building, monitoring, and efficient, timely, and high-quality reporting systems.

---

# 9. Key Result Areas

Create a `key_result_areas` table.

Suggested fields:

* id
* operational_plan_id
* code nullable
* name
* description nullable
* sort_order
* timestamps

Example:

Code:
KRA 1

Name:
Quality Teaching, Learning, and Student Services

Relationship:

Operational Plan has many Key Result Areas.

Key Result Area has many Plan Items.

KRAs should maintain manual ordering.

Do not rely on database IDs for display order.

---

# 10. Plan Items

Replace the previous generic `Activity` concept with a `PlanItem`.

A Plan Item represents one operational-plan row or logical planning entry underneath a Key Result Area.

Create `plan_items`.

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
* timestamps

The textual KPI/Target field is authoritative because institutional targets may be complex.

Examples:

`Institutional research-based AI guidelines are developed and approved`

`At least 1 monitoring/evaluation study conducted per year on AI use in instruction`

`80% of programs evaluated using AI integration research-based tools`

`100% of researchers trained on AI tools for research`

`At least 2 AI-in-research capability workshops are conducted per year`

`At least 70–80% of research outputs demonstrate use of AI tools`

`At least 1 research study on AI applications in research processes completed per year`

`100% of required reports submitted on or before the specified due date`

`0% delayed submissions across all research projects`

Do not assume all KPIs can be represented by one numeric value.

Keep:

`kpi_target_text`

for the official wording.

Structured fields such as:

* target_value
* target_unit
* target_operator

may be populated when useful for automated calculations.

Examples of target operators:

* equals
* at_least
* at_most
* percentage_at_least
* percentage_at_most
* zero_tolerance
* qualitative

---

# 11. Unit Co-Accountable

The existing form includes `Unit Co-Accountable`.

A Plan Item may have multiple co-accountable units.

Examples:

* VPA
* ICT
* QA
* CEARA
* VPA
* HR

Prefer a many-to-many relationship between Plan Items and Departments/Organizational Units where possible.

Create a pivot such as:

`plan_item_co_accountables`

Fields:

* plan_item_id
* department_id

If the institution needs to reference a unit that does not yet exist in the Departments table, allow a temporary/manual text representation only if necessary.

Prefer linked records over comma-separated strings.

---

# 12. Resources Needed

The official plan includes `Resources Needed`.

Store this on the Plan Item.

Examples:

* AI Policy Development and Research Team
* Digital Tools and Platforms for AI Monitoring and Evaluation
* Training and Capability-Building Resources
* AI Tools and Software Subscriptions for Research
* Qualified Trainers and Resource Persons
* Training and Implementation Support Materials
* Centralized Research Monitoring System / Dashboard
* Research Monitoring Personnel / System Administrator
* Standardized Reporting and Monitoring Tools

For the MVP this may remain structured as text.

---

# 13. Documentary Evidence Requirements

The existing Operational Plan includes a column called:

`Documentary Evidence`

This describes what evidence should exist when an accomplishment is reported.

Examples:

* Approved AI Guidelines and Policy Documents
* Monitoring and Evaluation Reports on AI Use
* Program Evaluation Records and Tools
* Training Records and Certificates
* Research Outputs Demonstrating AI Utilization
* Completed Research Study on AI in Research Processes
* System Screenshots and Dashboard Reports
* Research Tracking Database / Monitoring Logs
* Submission Records and Reports
* Compliance Reports

Store expected evidence as a structured array where possible.

Example:

```json
[
  "Training Records",
  "Certificates"
]
```

Do not confuse:

`Documentary Evidence Requirements`

with:

`Actual Evidence Files`

The requirement comes from the Operational Plan.

The actual evidence is uploaded later during accomplishment reporting.

---

# 14. Semester Accomplishments

Create an `accomplishments` table.

Suggested fields:

* id
* plan_item_id
* reporting_period_id
* reported_value nullable
* accomplishment_text text nullable
* percentage_accomplished nullable
* status
* submitted_by
* submitted_at nullable
* resubmitted_at nullable
* timestamps

Statuses:

* draft
* submitted
* queued_for_analysis
* analyzing
* ready_for_review
* accepted
* returned
* rejected

Unique constraint:

A Plan Item should normally have only one current accomplishment record per reporting period.

If historical revisions are required, preserve revision history rather than destructively overwriting data.

---

# 15. Accomplishment Percentage

Where both the target and accomplishment are truly numeric:

`reported value / target value × 100`

Calculate the value in the system.

Do not allow division by zero.

Do not force percentage calculations onto qualitative targets.

For targets such as:

`Institutional AI guidelines developed and approved`

use qualitative evaluation.

For targets such as:

`At least 2 workshops`

structured numeric comparison can be used.

Do not allow a manually entered percentage to override a system-calculable percentage without authorization.

---

# 16. Actual Evidence Uploads

Create an `evidence` table.

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

Use reasonable upload-size limits.

Use Laravel Storage.

Do not store file binary data directly inside PostgreSQL.

Evidence files must be private.

Do not expose predictable public URLs.

Use authorized controller routes for:

* viewing
* downloading

Only authorized users should be able to access evidence.

---

# 17. AI Evidence Analysis

Use the installed official `laravel/ai` package.

Create an AI agent:

`EvidenceAnalyzer`

Local development provider:

Ollama

Model:

`qwen3-vl:4b-instruct`

Provider/model/base URL must come from configuration/environment variables.

Do not hardcode API settings in application code.

---

# 18. Purpose of the AI

The AI does not evaluate the department politically or administratively.

Its job is narrowly defined:

Determine whether the uploaded evidence sufficiently supports the reported accomplishment in relation to:

* Objective
* Strategy
* KPI / Target
* Documentary Evidence Requirements
* Reporting Period
* Department's reported accomplishment

Evaluate:

## Relevance

Does the evidence actually relate to the Plan Item?

## Completeness

Are the expected documentary evidence requirements present?

## Consistency

Does the evidence agree with what the department reported?

## Quantity Support

If the claim says:

`2 workshops completed`

does the available evidence appear to support two distinct workshops?

## Date / Period Consistency

Does the evidence appear to belong to the selected Academic Year and reporting period?

## Documentary Requirement Match

Example:

Expected:
Training Records and Certificates

Uploaded:
one unrelated memorandum

This should not be considered sufficient.

## Missing Evidence

Clearly identify missing requirements.

## Uncertainty

If the model cannot reliably establish something, it must say so.

Never invent evidence.

Never claim that a signature, date, event, participant, document, or accomplishment was found when it was not actually identifiable.

---

# 19. Evidence Sufficiency Is Different From Official Performance Rating

Keep these as two separate concepts.

## AI Evidence Sufficiency

Question:

Does the uploaded evidence support the claimed accomplishment?

Possible statuses:

* sufficient
* partially_sufficient
* insufficient
* uncertain

## Official Evaluation Rating

Question:

How well did the unit meet the operational-plan requirement?

Official institutional scale:

* 3 = Exceeded Requirements
* 2 = Met Requirements
* 1 = Somehow Met Requirements
* 0 = Did Not Meet Requirements

AI may provide factual analysis that assists a reviewer, but AI must not be the final authority for the official rating.

The human reviewer assigns the official rating.

---

# 20. AI Structured Output

Use structured output.

Target schema:

```json
{
  "status": "partially_sufficient",
  "score": 78,
  "confidence": 84,
  "reported_accomplishment": "2 workshops completed",
  "supported_accomplishment": "Evidence clearly supports 1 workshop and partially supports a second workshop.",
  "findings": [
    {
      "requirement": "Training Records",
      "status": "complete",
      "explanation": "Training records were identified for the submitted activity."
    },
    {
      "requirement": "Certificates",
      "status": "partial",
      "explanation": "Certificates were identified for only part of the reported accomplishment."
    }
  ],
  "missing_evidence": [
    "Complete certificate records supporting the second workshop"
  ],
  "period_consistency": "consistent",
  "recommendation": "request_additional_evidence",
  "summary": "The available documentation partially supports the reported accomplishment."
}
```

Allowed overall statuses:

* sufficient
* partially_sufficient
* insufficient
* uncertain

Requirement statuses:

* complete
* partial
* missing
* uncertain

Recommendations:

* likely_sufficient
* request_additional_evidence
* manual_review

Period consistency:

* consistent
* inconsistent
* uncertain

Score:

0–100

Confidence:

0–100

Validate structured output before saving.

Do not blindly trust model output.

---

# 21. Evidence Analysis Storage

Create `evidence_analyses`.

Suggested fields:

* id
* accomplishment_id
* provider
* model
* prompt_version
* status
* score nullable
* confidence nullable
* sufficiency_status nullable
* supported_accomplishment text nullable
* findings jsonb nullable
* missing_evidence jsonb nullable
* period_consistency nullable
* recommendation nullable
* summary nullable
* raw_response jsonb/text nullable
* error_message nullable
* started_at nullable
* completed_at nullable
* timestamps

Statuses:

* queued
* analyzing
* completed
* failed

Preserve previous analyses after resubmission.

Never overwrite the audit history.

---

# 22. Queue AI Analysis

AI analysis must happen asynchronously.

Create a queued job such as:

`AnalyzeEvidence`

Workflow:

Department submits accomplishment
→ EvidenceAnalysis record created
→ status = queued
→ AnalyzeEvidence dispatched
→ status = analyzing
→ Laravel AI calls Ollama
→ response validated
→ EvidenceAnalysis updated
→ status = completed
→ accomplishment becomes ready_for_review

On failure:

→ analysis status = failed
→ save safe error information
→ accomplishment remains reviewable
→ human review remains possible

AI downtime must not block the entire Operational Plan System.

Use:

`QUEUE_CONNECTION=database`

for MVP.

The queue should run using:

```bash
php artisan queue:work
```

Do not require Redis initially.

---

# 23. Human Evaluation

Create an `evaluations` or `evidence_reviews` table.

Suggested fields:

* id
* accomplishment_id
* reviewer_id
* rating nullable
* decision
* remarks nullable
* reviewed_at
* timestamps

Official rating:

* 0
* 1
* 2
* 3

Decision:

* accepted
* request_additional_evidence
* rejected

Rating mapping:

0:
Did Not Meet Requirements

1:
Somehow Met Requirements

2:
Met Requirements

3:
Exceeded Requirements

Require reviewer remarks when:

* rating is 0
* rating is 1
* requesting additional evidence
* rejecting a submission

Preserve review history.

Do not overwrite previous evaluations after resubmission.

---

# 24. Official Operational Plan Table View

Create an Operational Plan view that resembles the institution's existing form.

Columns:

1. Key Result Area
2. Objectives
3. Strategies
4. Key Performance Indicators / Targets
5. Unit Co-Accountable
6. Resources Needed
7. Documentary Evidence
8. Evaluation Rating

   * First Semester
   * Second Semester
9. Remarks

The browser data-entry UI does not need to force users to edit a giant spreadsheet-like table.

Use usable forms/cards/dialogs for data entry.

However, provide a `Print / Official View` that visually resembles the institutional table.

Where practical, repeated KRA/objective cells may be visually grouped.

---

# 25. Export by Academic Year

This is a core requirement.

The user must explicitly select an Academic Year before exporting.

Example:

`AY 2025–2026`

Export must include only:

* operational plans belonging to AY 2025–2026
* KRAs under those plans
* Plan Items under those KRAs
* First Semester ratings belonging to AY 2025–2026
* Second Semester ratings belonging to AY 2025–2026
* remarks belonging to those evaluations

Never accidentally include records from another Academic Year.

Support at least an official printable/exportable report.

The export should contain:

Institution header/title

`OPERATIONAL PLAN`

`AY 2025–2026`

Then:

Office:
[Department / Office]

Accountable:
[Accountable Person / Position]

Goal:
[Goal]

Evaluation Criteria:

`3 - Exceeded Requirements`

`2 - Met Requirements`

`1 - Somehow Met Requirements`

`0 - Did Not Meet Requirements`

Then the official table:

* Key Result Area
* Objectives
* Strategies
* Key Performance Indicators / Targets
* Unit Co-Accountable
* Resources Needed
* Documentary Evidence
* First Semester
* Second Semester
* Remarks

For the MVP prioritize:

1. browser print view
2. PDF-friendly output

Do not make Excel export a blocker unless it can be added cleanly.

Architect exports so Excel can be added later.

---

# 26. Academic Year Filtering Everywhere

Every major page should clearly show the currently selected Academic Year.

Examples:

Dashboard — AY 2025–2026

Operational Plans — AY 2025–2026

Evidence Review — AY 2025–2026

Reports — AY 2025–2026

Users should not become confused about which year's records they are editing.

If an Academic Year is closed:

Department Users:
read-only

Reviewer:
read-only by default

Super Admin:
may have an explicit administrative reopen mechanism if implemented

Never silently allow editing of historical years.

---

# 27. Dashboard

## Department Dashboard

For selected Academic Year show:

* plan status
* number of KRAs
* number of Plan Items
* First Semester submissions
* Second Semester submissions
* pending evidence
* returned submissions
* accepted accomplishments
* evaluation summary

## Reviewer / Admin Dashboard

For selected Academic Year show:

* departments with plans
* departments without plans
* draft plans
* submitted plans
* approved plans
* accomplishments awaiting review
* evidence analysis pending
* returned accomplishments
* accepted accomplishments
* average/summary ratings where meaningful

Do not mix Academic Years.

---

# 28. Planning Workflow

For selected Academic Year:

Department User
→ Create Operational Plan
→ Enter Accountable
→ Enter Goal
→ Add KRA
→ Add Plan Items
→ Submit Plan

Reviewer:

Submitted
→ Approve

or:

Submitted
→ Return for Revision

After approval:

Department User should not freely change official planning fields.

Changes after approval should require an authorized workflow or administrative action.

---

# 29. Semester Monitoring Workflow

Example:

Approved Plan Item
→ First Semester reporting period opens
→ Department enters accomplishment
→ Department uploads evidence
→ Department submits
→ AI analysis runs
→ Reviewer reviews
→ Reviewer assigns rating 0–3
→ Reviewer enters remarks
→ First Semester evaluation completed

Later:

Second Semester reporting period
→ same workflow

The official plan export then displays:

First Semester Rating

Second Semester Rating

Remarks

for the selected Academic Year.

---

# 30. Evidence Resubmission

If reviewer requests additional evidence:

Current evaluation/review remains in history.

Accomplishment becomes returned.

Department uploads additional evidence.

Department resubmits.

Create a new EvidenceAnalysis.

Reviewer reviews again.

Never destroy:

* previous evidence
* previous AI analysis
* previous review
* previous reviewer remarks

Auditability is essential.

---

# 31. Authorization

Create Policies for important entities.

At minimum:

* AcademicYear
* Department
* OperationalPlan
* KeyResultArea
* PlanItem
* Accomplishment
* Evidence
* Evaluation

Rules include:

Department User cannot view another department's operational-plan details.

Department User cannot modify another department's plan.

Department User cannot assign official ratings.

Department User cannot approve their own evidence.

Department User cannot edit a closed Academic Year.

Reviewer can review but should not casually alter the department's original planning data.

Super Admin has administrative access.

Always enforce these rules server-side.

---

# 32. Validation

Use Form Requests where appropriate.

Validate:

* Academic Year
* department ownership
* plan uniqueness by department/year
* plan status
* reporting period belongs to same Academic Year
* dates
* numeric target fields
* official rating range 0–3
* file type
* file size
* required evidence metadata
* review permissions
* closed Academic Year restrictions

Do not rely on React validation alone.

---

# 33. Audit Trail

Preserve important events.

At minimum capture:

* who created a plan
* Academic Year
* who submitted a plan
* when submitted
* who approved/returned it
* who created/modified Plan Items
* who submitted accomplishments
* reporting period
* who uploaded evidence
* evidence upload timestamps
* AI provider
* AI model
* prompt version
* AI analysis timestamp
* reviewer
* official rating
* reviewer decision
* remarks
* review timestamp
* resubmissions

Do not silently overwrite important historical information.

---

# 34. Seed Data

Create useful development seeders.

Academic Years:

* AY 2025–2026
* AY 2026–2027

Make one current/open.

Reporting periods:

* First Semester
* Second Semester

Departments:

* Center for Planning, Research, Innovations, and New Technologies
* Information and Communications Technology
* Quality Assurance
* Human Resources
* Finance
* Planning Office

Create demo accounts:

* Super Admin
* Reviewer
* Department User

Clearly mark them as development/demo credentials.

---

# 35. Seed a Sample Plan Based on the Institutional Format

Create a sample Operational Plan for:

Academic Year:
AY 2025–2026

Office:
Center for Planning, Research, Innovations, and New Technologies

Accountable:
CPRINT Director

Goal:

`To ensure the full integration of ethical AI and digital technologies in teaching, learning, research, and administrative processes through 100% adoption, capacity building, and efficient, timely, and high-quality reporting systems.`

Create:

KRA 1:
`Quality Teaching, Learning, and Student Services`

Add representative Plan Items based on the supplied institutional sample.

Examples include:

### Plan Item

Objective:
`Ethical usage and integration of AI tools in teaching and learning modules`

Strategy:
`Develop AI Usage Guidelines`

KPI / Target:
`Institutional research-based AI guidelines are developed and approved`

Documentary Evidence:
`Approved AI Guidelines and Policy Documents`

### Plan Item

Strategy:
`Monitor and Evaluate AI Use`

KPI / Target:
`At least 1 monitoring/evaluation study conducted per year on AI use in instruction`

Co-Accountable:
VPA, ICT, QA, CEARA

Resources:
`Digital Tools and Platforms for AI Monitoring and Evaluation`

Documentary Evidence:
`Monitoring and Evaluation Reports on AI Use`

### Plan Item

KPI / Target:
`80% of programs evaluated using AI integration research-based tools`

Documentary Evidence:
`Program Evaluation Records and Tools`

### Plan Item

Objective:
`100% professional development and internalization of AI-based LMS for Student Services`

Strategy:
`Build faculty capability on AI-assisted research`

KPI / Target:
`100% of researchers trained on AI tools for research`

Documentary Evidence:
`Training Records and Certificates`

### Plan Item

KPI / Target:
`At least 2 AI-in-research capability workshops are conducted per year`

Resources:
`Qualified Trainers and Resource Persons`

Documentary Evidence:
`Research Outputs Demonstrating AI Utilization`

### Plan Item

Strategy:
`Monitor and promote AI utilization in research outputs`

KPI / Target:
`At least 70–80% of research outputs demonstrate use of AI tools`

### Plan Item

KPI / Target:
`At least 1 research study on AI applications in research processes completed per year`

### Plan Item

Strategy:
`Develop Research Monitoring System`

KPI / Target:
`100% of research projects tracked through a centralized monitoring system`

Resources:
`Centralized Research Monitoring System / Dashboard`

Documentary Evidence:
`System Screenshots and Dashboard Reports`

### Plan Item

KPI / Target:
`Real-time monitoring dashboard established and operational`

Resources:
`Research Monitoring Personnel / System Administrator`

Documentary Evidence:
`Research Tracking Database / Monitoring Logs`

### Plan Item

Strategy:
`Strengthen compliance through monitoring and feedback mechanisms`

KPI / Target:
`100% on-time submission of required reports`

Resources:
`Standardized Reporting and Monitoring Tools`

Documentary Evidence:
`Submission Records and Reports`

### Plan Item

KPI / Target:
`0% delayed submissions across all research projects`

Documentary Evidence:
`Compliance Reports`

The exact wording may be cleaned slightly, but preserve the meaning and structure of the supplied official document.

---

# 36. UI Structure

Create navigation approximately like:

Dashboard

Academic Years

Operational Plans

Monitoring

Evidence Review

Reports

Administration

* Departments
* Users

The Academic Year selector should be clearly visible near the top navigation/header.

Example:

`Academic Year: [ AY 2025–2026 ▼ ]`

Changing the Academic Year updates the context of the application.

---

# 37. Operational Plan Editing UI

Do not force users to type into an enormous table.

Use a structured editor.

Example:

Operational Plan

* Academic Year
* Office
* Accountable
* Goal

KRA 1

* Code
* Name

Plan Item

* Objective
* Strategy
* KPI / Target
* Structured numeric target fields when applicable
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

Once submitted/approved, respect workflow restrictions.

---

# 38. Reviewer Screen

For each accomplishment display:

Academic Year

Reporting Period

Office

KRA

Objective

Strategy

KPI / Target

Expected Documentary Evidence

Reported Accomplishment

Uploaded Evidence

AI Evidence Analysis

AI Sufficiency Score

AI Confidence

AI Findings

Missing Evidence

AI Recommendation

Then Human Evaluation:

Rating:

* 3 Exceeded Requirements
* 2 Met Requirements
* 1 Somehow Met Requirements
* 0 Did Not Meet Requirements

Remarks

Actions:

* Accept
* Request Additional Evidence
* Reject

Use confirmation dialogs for final actions.

---

# 39. AI Failure Behavior

Handle gracefully:

* Ollama not running
* model unavailable
* AI timeout
* invalid structured response
* unreadable evidence
* unsupported document
* model uncertainty

Display:

`AI analysis unavailable. Manual review is required.`

Never lose the department's submission because AI failed.

Never prevent an authorized human reviewer from reviewing evidence manually.

---

# 40. Testing

Create feature tests for at least:

* authentication
* Academic Year selection
* Academic Year isolation
* closed-year restrictions
* department isolation
* unique plan per department/year
* plan creation
* KRA creation
* Plan Item creation
* plan submission
* plan approval
* First Semester accomplishment
* Second Semester accomplishment
* evidence upload
* unauthorized evidence access
* reviewer access
* AI job dispatch
* AI failure handling
* evaluation rating
* request additional evidence
* rejection
* resubmission
* export filters by Academic Year

AI calls must be fake/mockable.

Automated tests must not require Ollama to actually be running.

---

# 41. Out of Scope for MVP

Do not implement yet:

* custom AI model training
* fine-tuning
* vector databases
* embeddings
* RAG
* semantic search
* automatic official rating
* automatic final approval
* complex OCR microservice
* Python service
* mobile application
* SMS
* real-time websockets
* multi-tenant SaaS
* Kubernetes
* advanced budgeting
* digital signatures
* complex report designer
* advanced forecasting
* predictive analytics

---

# 42. Coding Standards

Follow existing Laravel conventions.

Use:

* Eloquent relationships
* Form Requests
* Policies
* PHP enums where appropriate
* service/action classes for important workflows
* queued Jobs
* TypeScript
* React functional components
* reusable UI components

Keep controllers thin.

Do not build unnecessary repositories.

Do not hardcode:

* Academic Year IDs
* department IDs
* AI API keys
* AI URLs
* model names where config should be used

Do not perform unrelated refactors.

---

# 43. Implementation Order

Implement incrementally.

## Phase 1 — Foundation

Inspect project.

Then implement:

* AcademicYear
* ReportingPeriod
* Department
* User role/department fields
* enums
* relationships
* seeders
* Academic Year selector
* authorization foundation

Run tests.

Stop and summarize.

---

## Phase 2 — Operational Plan

Implement:

* OperationalPlan
* KeyResultArea
* PlanItem
* Co-Accountable units
* Documentary Evidence Requirements
* plan editor
* submission workflow
* approval/return workflow
* official plan view

Run tests.

Stop and summarize.

---

## Phase 3 — Semester Monitoring

Implement:

* Accomplishment
* reporting-period workflow
* calculated accomplishment where appropriate
* First Semester monitoring
* Second Semester monitoring

Run tests.

Stop and summarize.

---

## Phase 4 — Evidence

Implement:

* private evidence storage
* upload
* view
* download
* evidence metadata
* permissions

Run tests.

Stop and summarize.

---

## Phase 5 — AI

Implement:

* EvidenceAnalyzer
* structured output
* AnalyzeEvidence job
* Ollama configuration
* EvidenceAnalysis
* error handling

First prove text-only communication.

Then add supported image/document handling incrementally.

Run tests.

Stop and summarize.

---

## Phase 6 — Human Evaluation

Implement:

* reviewer queue
* detailed evidence review
* AI findings
* official 0–3 rating
* remarks
* accept
* request additional evidence
* reject
* resubmission
* review history

Run tests.

Stop and summarize.

---

## Phase 7 — Dashboard and Export

Implement:

* Academic Year dashboard
* department monitoring
* annual reports
* official Operational Plan print view
* export filtered by selected Academic Year

Run tests.

---

# 44. MVP Definition of Done

The MVP is complete when this scenario works:

1. Super Admin creates AY 2025–2026.
2. First Semester and Second Semester exist under that Academic Year.
3. Super Admin creates departments and users.
4. Department User selects AY 2025–2026.
5. Department User creates its Operational Plan.
6. Department enters Accountable and Goal.
7. Department creates KRA 1.
8. Department creates Plan Items containing:

   * Objective
   * Strategy
   * KPI / Target
   * Unit Co-Accountable
   * Resources Needed
   * Documentary Evidence Requirements
9. Department submits the plan.
10. Reviewer approves it.
11. First Semester opens.
12. Department reports an accomplishment.
13. Department uploads documentary evidence.
14. Department submits it.
15. AI analysis is queued.
16. Ollama analyzes evidence.
17. Structured AI results are stored.
18. Reviewer sees the evidence and AI findings.
19. Reviewer assigns official rating 0–3.
20. Reviewer enters remarks.
21. Second Semester can later repeat the process.
22. Another department cannot access these records.
23. User switches to AY 2026–2027.
24. AY 2025–2026 records no longer appear in ordinary AY 2026–2027 views.
25. User switches back to AY 2025–2026 and historical data remains intact.
26. Reviewer selects AY 2025–2026 and exports the Operational Plan.
27. The export contains:

    * Office
    * Accountable
    * Goal
    * KRAs
    * Objectives
    * Strategies
    * KPI / Targets
    * Unit Co-Accountable
    * Resources Needed
    * Documentary Evidence
    * First Semester Rating
    * Second Semester Rating
    * Remarks
28. No records belonging to another Academic Year appear in that export.

---

# 45. First Instruction to Execute

Do not build the entire MVP immediately.

Start by inspecting the repository.

Report:

1. Laravel version
2. PHP version
3. React version
4. Inertia version
5. starter-kit structure
6. `laravel/ai` version
7. current database configuration
8. current queue configuration
9. authentication implementation
10. current migrations
11. current models
12. current routes

Then propose the exact Phase 1 schema, including:

* academic_years
* reporting_periods
* departments
* required users-table changes

Explain the relationships and important constraints.

Then implement **Phase 1 only**.

Run migrations.

Run relevant automated tests.

Run frontend/type checks if configured.

Fix any failures.

At the end, provide a concise summary of:

* files created
* files modified
* database tables added
* tests added
* commands run
* remaining Phase 2 work

Do not proceed to Phase 2 until Phase 1 is stable.
