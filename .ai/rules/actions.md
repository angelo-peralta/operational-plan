---
paths:
  - 'app/Actions/**'
---

# Actions

## Lock Academic Year before planning mutations
Every Operational Plan, KRA, and Plan Item mutation must run in a transaction and acquire the Academic Year row lock before the Operational Plan lock via LockOperationalPlanForMutation. This serializes Academic Year closure with writes and prevents stale open-year authorization.
