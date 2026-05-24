# UUID Migration + Superadmin User Management — Design Spec
**Date:** 2026-05-24  
**Status:** Approved

---

## Overview

Two connected changes:
1. Replace all auto-increment integer PKs with UUIDs across user-facing tables
2. Give superadmin full CRUD over users (edit name/email/password, delete)

---

## Part 1 — UUID Migration

### Scope

**Tables receiving UUID primary keys:**

| Table | PK | FK columns changed |
|---|---|---|
| `users` | `id` BIGINT → UUID | — |
| `posts` | `id` BIGINT → UUID | `user_id` BIGINT → UUID |
| `activity_logs` | `id` BIGINT → UUID | `user_id` BIGINT → UUID, `model_id` BIGINT → string |
| `sessions` | unchanged | `user_id` BIGINT → UUID |
| `model_has_roles` | unchanged | `model_id` BIGINT → UUID |
| `model_has_permissions` | unchanged | `model_id` BIGINT → UUID |

**Tables left as integer (internal Spatie tables, never user-facing):**
- `roles`, `permissions`, `role_has_permissions` — no change

### Migration Strategy

Single migration file. Existing data (including users) is preserved. Steps in order:

1. Add temporary UUID columns alongside existing integer columns
2. Populate UUIDs for all existing rows using PostgreSQL `gen_random_uuid()`
3. Populate FK UUID columns by joining tables (e.g. `posts.user_uuid` from `users.uuid`)
4. Drop all FK constraints and old PKs (required before column type changes in PostgreSQL)
5. Drop old integer columns; rename UUID columns into place
6. Re-add PKs and FK constraints on the new UUID columns
7. Repeat FK steps for Spatie pivot tables (`model_has_roles`, `model_has_permissions`)

The entrypoint script runs `php artisan migrate --force` on every container start, so the migration applies automatically.

### Model Changes

Add `HasUuids` trait to `User`, `Post`, `ActivityLog`. This:
- Auto-generates UUIDs on `create`
- Sets `$keyType = 'string'` and `$incrementing = false` automatically

### Service Changes

`ActivityLoggerService::logRaw()` parameter `int $modelId` → `string $modelId` since all model IDs are now UUID strings.

---

## Part 2 — Superadmin User Management

### New Capabilities

**Edit user** — superadmin can update any user's name, email, and password. Password field is optional (blank = keep current). Superadmin cannot edit themselves from this panel.

**Delete user** — superadmin can delete any user with a confirmation dialog. Posts remain in the DB (author shows as "Deleted user"). Superadmin cannot delete themselves.

### Backend

**New controller methods on `Admin\UserController`:**

- `update(Request $request, User $user)` — PATCH `/admin/users/{user}`
  - Validates: `name` required string max:255, `email` required unique ignoring current user, `password` nullable min:8 confirmed
  - Updates user fields, hashes password only if provided
  - Logs `profile_updated` action via `ActivityLoggerService::logRaw()`
  - Returns `back()->with('message', ...)`

- `destroy(Request $request, User $user)` — DELETE `/admin/users/{user}`
  - Aborts 403 if superadmin tries to delete themselves (`$user->id === auth()->id()`)
  - Deletes user
  - Logs `account_deleted` action via `ActivityLoggerService::logRaw()`
  - Returns `back()->with('message', ...)`

**New routes in `routes/web.php`** (inside existing `admin` middleware group):
```php
Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
```

### Frontend

**Edit modal** — triggered by an "Edit" icon button on each user row. Contains fields for Name, Email, Password, Password Confirmation. Uses existing `Modal` and `TextInput` components. Submits via Inertia `router.patch()`.

**Delete confirmation** — triggered by a "Delete" icon button. Uses existing `Modal` component for confirmation. Submits via Inertia `router.delete()`.

**Self-action guard** — edit and delete buttons are hidden (or disabled) for the currently logged-in superadmin's own row.

---

## Constraints

- No `migrate:fresh` — existing data must be preserved
- Superadmin cannot edit or delete their own account from the admin panel
- Password hashing uses `Hash::make()` (existing pattern)
- Activity log entries for edit/delete use `ActivityLoggerService::logRaw()` (existing pattern)
