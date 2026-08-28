---
paths:
  - app/Http/Middleware/HandleInertiaRequests.php
---

# Middleware

## Include attributes used by authorization
When partially eager-loading the authenticated user's department, include is_active. OperationalPlanPolicy::create calls Department::isActive(), and loadMissing will not fetch a column omitted from an already-loaded relation.
