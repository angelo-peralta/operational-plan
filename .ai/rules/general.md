---
paths:
  - '**/*'
---

# General

## Keep starter teams separate from planning scope
The starter-kit Team/current_team layer is only the workspace URL and membership shell. AcademicYear is the operational planning root and Department controls record ownership. Never derive an Academic Year or Department from Team/TeamRole, and never scope Academic Year bindings through Team.
