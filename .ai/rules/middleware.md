---
paths:
  - app/Http/Middleware/HandleInertiaRequests.php
---

# Middleware

## Share only canonical planning authority
The authenticated Inertia payload exposes the canonical `users.role`, current valid organizational assignments, and policy-derived permissions. Do not eager-load or share legacy `department_id`, `role_id`, Department, or role-catalog data as active user authority.
