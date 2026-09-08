---
paths:
  - '**/*'
---

# General

## Keep starter teams separate from planning scope
The starter-kit Team/current_team layer is only the workspace URL and membership shell. AcademicYear is the operational planning root and Department controls record ownership. Never derive an Academic Year or Department from Team/TeamRole, and never scope Academic Year bindings through Team.

## Use the revised organizational scope without legacy authority
doc/plan.md is authoritative. Authorization uses users.role and current organizational_unit_user_assignments plus organizational_units hierarchy through ResolveOrganizationalScope. Legacy department_id/role_id and the Department compatibility model never independently grant access. Team remains the workspace shell. Retain history snapshots and legacy role provenance; plan ownership column renames belong to revised Phase 2.

## Give assigned Executives university-wide read visibility
Every user with the canonical Executive role and a current compatible organizational assignment can view university-wide plans, accomplishments, evidence, dashboards, and reports. Organizational hierarchy still limits authoring, approval, return, evaluation, and remark-resolution authority. The university-root assignment remains the source of Presidential override authority.

## Preserve global KRA templates as future-plan defaults
System Admin manages `key_result_area_templates` as the university-wide default catalog. Creating an Operational Plan copies active templates into plan-owned `key_result_areas` before its initial status snapshot. Later template edits, deactivation, or deletion apply only to future plans and must never rewrite existing plan rows or history.

## Keep PHP upload limits aligned with evidence validation
Evidence accepts PDF, JPG, JPEG, and PNG files up to 10 MB. The serving PHP runtime must use `upload_max_filesize = 10M` or higher and `post_max_size` must be larger; `public/.user.ini` records the deployable PHP-FPM/CGI defaults. Local Laravel Herd PHP settings must match because the CLI development server does not consume per-directory user INI files.
