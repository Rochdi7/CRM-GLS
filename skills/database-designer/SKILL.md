---
name: database-designer
description: Design normalized, auditable, scalable database schemas for a multi-center school CRM, including foreign keys, indexes, constraints, payment allocation, advances, attendance, groups, employees, and reporting needs.
---

# Database Designer

## Mission

Design reliable relational schemas for a school CRM before migrations are written.

Optimize for:

- data integrity
- multi-center operation
- auditability
- financial correctness
- reporting
- maintainability
- realistic growth

## Discovery

Before designing:

1. Read existing migrations and schema documentation.
2. Identify existing naming conventions and database engine.
3. Identify actors and workflows.
4. Identify lifecycle and status transitions.
5. Identify reporting and filtering requirements.
6. Identify data that must remain historically accurate.
7. Identify multi-center ownership.
8. Identify expected volume and concurrency.

Clearly label assumptions.

## Core modeling principles

### Separate identity from transactions

Do not merge these concepts:

- person/student
- student account/contact
- inscription
- group enrollment
- fee
- invoice
- payment
- payment allocation
- advance
- attendance record
- employee
- user login

A student normally has one identity record and can have multiple inscriptions over time.

An inscription represents enrollment in a program, level, academic period, or commercial agreement.

### Financial model

Prefer a ledger-like structure:

- `fees` or `receivables`: what is owed
- `payments`: money received
- `payment_allocations`: how payments are applied to fees
- unallocated payment amount: an advance/credit
- `refunds` or reversal entries
- `cash_registers` and cash movements
- `cheques` with due and clearance status

Never store only a mutable `remaining_amount` without retaining source transactions.

Derived balances should be calculated from authoritative records or maintained through a controlled, tested projection.

### Multi-center model

Decide for every table whether it is:

- global
- center-owned
- shared across multiple centers
- historically tied to a center

For many-to-many assignments, use pivots such as:

```text
employee_center
user_center
student_center
```

Use a primary-center field only when the business truly needs it, and keep it separate from authorized/assigned centers.

## Column standards

Use appropriate types:

- IDs: follow project convention, such as `bigint` or UUID/ULID
- money: `decimal(15, 2)` or justified precision
- percentages: constrained decimal
- dates: `date`
- moments: timezone-aware timestamps when supported
- phone numbers: strings
- national IDs: strings
- status: string/enum strategy consistent with the project
- flexible metadata: JSON only for non-relational, rarely queried data

Avoid:

- float for money
- comma-separated IDs
- polymorphism without a real need
- excessive nullable columns
- status booleans that cannot represent lifecycle
- storing age instead of date of birth
- storing calculated totals without a source-of-truth strategy

## Keys and constraints

Every design must evaluate:

- primary key
- foreign keys
- unique constraints
- composite unique constraints
- check constraints
- not-null constraints
- delete behavior
- default values

Examples:

```text
unique(center_id, receipt_number)
unique(inscription_id, fee_type_id, due_date)
unique(group_id, student_id, active_period)
unique(attendance_session_id, inscription_id)
```

The exact constraint must reflect the business rule.

## Delete behavior

Choose deliberately:

- `restrict` for financial and historical dependencies
- `cascade` for true owned children with no independent meaning
- `nullOnDelete` for optional references where history remains meaningful
- soft delete for recoverable operational records

Do not cascade-delete payments, allocations, receipts, or audit history because a parent was deleted.

## Indexing

Create indexes based on real access patterns.

Common indexes:

- foreign keys
- status
- center plus date
- center plus status
- due date
- payment date
- group plus active status
- student plus created date
- employee plus center
- searchable normalized phone/email fields

Use composite indexes in the order matching common query predicates.

Avoid redundant indexes that are already covered by another index prefix.

## Auditability

For important state changes, consider:

- `created_by`
- `updated_by`
- `approved_by`
- `cancelled_by`
- `cancelled_at`
- `cancellation_reason`
- immutable receipt number
- reversal reference
- source/import identifier
- audit log/event table

Financial corrections should use reversals or adjustments rather than destructive edits.

## School CRM domains

Evaluate relationships for:

### Students and inscriptions

- students
- student contacts/accounts
- guardians
- inscriptions
- inscription contacts
- documents
- statuses
- levels/programs
- center
- enrollment dates

### Groups and scheduling

- groups
- levels
- teachers
- rooms
- schedules
- sessions
- holidays
- group enrollment
- capacity
- conflicts

### Attendance

- sessions
- attendance records
- statuses
- source/device/import
- recorded_by
- correction history

### Finance

- fee types
- fees
- invoices
- installments
- payments
- allocations
- advances
- cheques
- refunds
- cash registers
- cash movements
- expenses
- receipt numbering

### Human resources

- employees
- categories
- center assignments
- user account
- contracts
- compensation
- attendance
- status

### Inventory

- items
- warehouses/centers
- stock movements
- purchases
- issues to students/employees
- adjustments
- suppliers

## Required deliverables

# Database Design: [Module]

## Assumptions

List explicit assumptions.

## Entity overview

Explain each entity and why it exists.

## Relationship diagram

Provide Mermaid ERD or PlantUML code.

## Table specification

For each table provide:

| Column | Type | Nullable | Default | FK/Constraint | Purpose |
|---|---|---:|---|---|---|

## Relationships

Describe cardinality and ownership.

## Constraints

List all unique, check, and referential rules.

## Index plan

| Index | Columns | Query supported | Reason |
|---|---|---|---|

## Lifecycle

Describe statuses and allowed transitions.

## Financial integrity

When finance is involved, explain:

- source of truth
- allocation behavior
- advance behavior
- refunds/reversals
- rounding
- concurrency protection
- receipt numbering

## Multi-center isolation

Explain exactly how center access is represented and enforced.

## Migration order

List migrations in dependency order.

## Example Laravel migrations

Generate only when requested.

## Query examples

Include representative reporting and operational queries.

## Risk review

Identify:

- data-loss risks
- ambiguous relationships
- reporting limitations
- concurrency risks
- migration risks

## Rules

- Do not design from UI screenshots alone; infer carefully and mark assumptions.
- Do not denormalize prematurely.
- Do not normalize lookup values so aggressively that common operations become impractical.
- Do not use JSON as a substitute for relational modeling.
- Do not rely on application validation when a database constraint can guarantee integrity.
- Do not remove historical records to simplify the schema.
