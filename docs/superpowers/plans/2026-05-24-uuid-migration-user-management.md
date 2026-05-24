# UUID Migration + Superadmin User Management — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace all integer PKs with UUIDs across user-facing tables, and add edit/delete user controls for superadmin.

**Architecture:** Single irreversible migration converts all PKs/FKs in one transaction using PostgreSQL's `gen_random_uuid()`. `HasUuids` trait added to models. New `update`/`destroy` controller methods + routes + frontend modals built on existing patterns.

**Tech Stack:** Laravel 13, PostgreSQL 17, Inertia.js, Vue 3, Spatie Permission, Docker

---

## File Map

| Action | File |
|---|---|
| Create | `database/migrations/2026_05_24_000001_convert_ids_to_uuid.php` |
| Modify | `app/Models/User.php` |
| Modify | `app/Models/Post.php` |
| Modify | `app/Models/ActivityLog.php` |
| Modify | `app/Services/ActivityLoggerService.php` |
| Modify | `app/Http/Controllers/Admin/UserController.php` |
| Modify | `routes/web.php` |
| Modify | `resources/js/Pages/Admin/Users/Index.vue` |

---

## Task 1: Create UUID migration file

**Files:**
- Create: `database/migrations/2026_05_24_000001_convert_ids_to_uuid.php`

- [ ] **Step 1: Create the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add temporary UUID columns to all affected tables ──────────
        Schema::table('users', fn(Blueprint $t) => $t->uuid('new_id')->nullable());

        Schema::table('posts', function (Blueprint $t) {
            $t->uuid('new_id')->nullable();
            $t->uuid('new_user_id')->nullable();
        });

        Schema::table('activity_logs', function (Blueprint $t) {
            $t->uuid('new_id')->nullable();
            $t->uuid('new_user_id')->nullable();
            $t->string('new_model_id')->nullable();
        });

        Schema::table('sessions', fn(Blueprint $t) => $t->uuid('new_user_id')->nullable());
        Schema::table('model_has_roles', fn(Blueprint $t) => $t->uuid('new_model_id')->nullable());
        Schema::table('model_has_permissions', fn(Blueprint $t) => $t->uuid('new_model_id')->nullable());

        // ── 2. Generate UUIDs for all rows in PK tables ───────────────────
        DB::statement('UPDATE users SET new_id = gen_random_uuid()');
        DB::statement('UPDATE posts SET new_id = gen_random_uuid()');
        DB::statement('UPDATE activity_logs SET new_id = gen_random_uuid()');

        // ── 3. Populate FK UUID columns by joining on old integer IDs ──────
        DB::statement('UPDATE posts SET new_user_id = u.new_id FROM users u WHERE posts.user_id = u.id');
        DB::statement('UPDATE activity_logs SET new_user_id = u.new_id FROM users u WHERE activity_logs.user_id = u.id');
        DB::statement("
            UPDATE activity_logs SET new_model_id =
                CASE model
                    WHEN 'App\\Models\\Post' THEN (SELECT new_id::text FROM posts WHERE posts.id = activity_logs.model_id)
                    WHEN 'App\\Models\\User' THEN (SELECT new_id::text FROM users WHERE users.id = activity_logs.model_id)
                    ELSE NULL
                END
        ");
        DB::statement('UPDATE sessions SET new_user_id = u.new_id FROM users u WHERE sessions.user_id = u.id');
        DB::statement("UPDATE model_has_roles SET new_model_id = u.new_id FROM users u WHERE model_has_roles.model_id = u.id AND model_has_roles.model_type = 'App\\Models\\User'");
        DB::statement("UPDATE model_has_permissions SET new_model_id = u.new_id FROM users u WHERE model_has_permissions.model_id = u.id AND model_has_permissions.model_type = 'App\\Models\\User'");

        // ── 4. Drop FK constraints (required before dropping referenced columns)
        Schema::table('posts', fn(Blueprint $t) => $t->dropForeign(['user_id']));
        Schema::table('activity_logs', fn(Blueprint $t) => $t->dropForeign(['user_id']));

        // ── 5. Drop Spatie composite PKs and indexes (model_id is part of PK)
        DB::statement('ALTER TABLE model_has_roles DROP CONSTRAINT model_has_roles_role_model_type_primary');
        DB::statement('ALTER TABLE model_has_permissions DROP CONSTRAINT model_has_permissions_permission_model_type_primary');
        DB::statement('DROP INDEX model_has_roles_model_id_model_type_index');
        DB::statement('DROP INDEX model_has_permissions_model_id_model_type_index');
        DB::statement('DROP INDEX IF EXISTS sessions_user_id_index');

        // ── 6. Drop old integer columns (dropping the PK column also drops its PK constraint)
        Schema::table('users', fn(Blueprint $t) => $t->dropColumn('id'));
        Schema::table('posts', fn(Blueprint $t) => $t->dropColumn(['id', 'user_id']));
        Schema::table('activity_logs', fn(Blueprint $t) => $t->dropColumn(['id', 'user_id', 'model_id']));
        Schema::table('sessions', fn(Blueprint $t) => $t->dropColumn('user_id'));
        Schema::table('model_has_roles', fn(Blueprint $t) => $t->dropColumn('model_id'));
        Schema::table('model_has_permissions', fn(Blueprint $t) => $t->dropColumn('model_id'));

        // ── 7. Rename new UUID columns into place ─────────────────────────
        DB::statement('ALTER TABLE users RENAME COLUMN new_id TO id');
        DB::statement('ALTER TABLE posts RENAME COLUMN new_id TO id');
        DB::statement('ALTER TABLE posts RENAME COLUMN new_user_id TO user_id');
        DB::statement('ALTER TABLE activity_logs RENAME COLUMN new_id TO id');
        DB::statement('ALTER TABLE activity_logs RENAME COLUMN new_user_id TO user_id');
        DB::statement('ALTER TABLE activity_logs RENAME COLUMN new_model_id TO model_id');
        DB::statement('ALTER TABLE sessions RENAME COLUMN new_user_id TO user_id');
        DB::statement('ALTER TABLE model_has_roles RENAME COLUMN new_model_id TO model_id');
        DB::statement('ALTER TABLE model_has_permissions RENAME COLUMN new_model_id TO model_id');

        // ── 8. Add NOT NULL where required ────────────────────────────────
        DB::statement('ALTER TABLE users ALTER COLUMN id SET NOT NULL');
        DB::statement('ALTER TABLE posts ALTER COLUMN id SET NOT NULL');
        // posts.user_id is intentionally left nullable (SET NULL on user delete keeps posts alive)
        DB::statement('ALTER TABLE activity_logs ALTER COLUMN id SET NOT NULL');

        // ── 9. Promote UUID columns to primary keys ───────────────────────
        DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE posts ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE activity_logs ADD PRIMARY KEY (id)');

        // ── 10. Re-add FK constraints on new UUID columns ─────────────────
        // posts.user_id → SET NULL so posts survive user deletion (spec requirement)
        DB::statement('ALTER TABLE posts ADD CONSTRAINT posts_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE activity_logs ADD CONSTRAINT activity_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');

        // ── 11. Restore Spatie composite PKs and indexes ──────────────────
        DB::statement('ALTER TABLE model_has_roles ADD PRIMARY KEY (role_id, model_id, model_type)');
        DB::statement('ALTER TABLE model_has_permissions ADD PRIMARY KEY (permission_id, model_id, model_type)');
        DB::statement('CREATE INDEX model_has_roles_model_id_model_type_index ON model_has_roles (model_id, model_type)');
        DB::statement('CREATE INDEX model_has_permissions_model_id_model_type_index ON model_has_permissions (model_id, model_type)');

        // ── 12. Restore sessions user_id index ────────────────────────────
        DB::statement('CREATE INDEX sessions_user_id_index ON sessions (user_id)');
    }

    public function down(): void
    {
        throw new \RuntimeException('UUID migration is irreversible. Restore from a database backup if needed.');
    }
};
```

- [ ] **Step 2: Commit**

```bash
git add database/migrations/2026_05_24_000001_convert_ids_to_uuid.php
git commit -m "feat: add UUID migration for users, posts, activity_logs, sessions, spatie tables"
```

---

## Task 2: Add HasUuids to models and fix ActivityLoggerService

**Files:**
- Modify: `app/Models/User.php`
- Modify: `app/Models/Post.php`
- Modify: `app/Models/ActivityLog.php`
- Modify: `app/Services/ActivityLoggerService.php`

- [ ] **Step 1: Update User model**

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasUuids;

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

- [ ] **Step 2: Update Post model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'title', 'content'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

- [ ] **Step 3: Update ActivityLog model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'action', 'model', 'model_id', 'payload'];

    protected $casts = ['payload' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 4: Fix ActivityLoggerService — change int types to string**

```php
<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLoggerService
{
    /**
     * Log an event tied to an Eloquent model instance.
     * Used by observers (Post created/updated/deleted).
     */
    public static function log(Model $model, string $action, ?array $payload = null): void
    {
        ActivityLog::create([
            'user_id'  => auth()->id(),
            'action'   => $action,
            'model'    => get_class($model),
            'model_id' => $model->getKey(),
            'payload'  => $payload,
        ]);
    }

    /**
     * Log an event without a model instance.
     * Used for auth events, role changes, profile updates, etc.
     */
    public static function logRaw(
        string $action,
        string $model,
        string $modelId,
        ?array $payload = null,
        ?string $userId = null
    ): void {
        ActivityLog::create([
            'user_id'  => $userId ?? auth()->id(),
            'action'   => $action,
            'model'    => $model,
            'model_id' => $modelId,
            'payload'  => $payload,
        ]);
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php app/Models/Post.php app/Models/ActivityLog.php app/Services/ActivityLoggerService.php
git commit -m "feat: add HasUuids to models, update ActivityLoggerService to string IDs"
```

---

## Task 3: Apply the migration and verify

- [ ] **Step 1: Restart Docker to trigger migration**

```powershell
docker compose down
docker compose up -d
```

Wait ~30 seconds for the `app` container to run `php artisan migrate --force`.

- [ ] **Step 2: Check migration ran**

```powershell
docker compose logs app --tail 30
```

Expected output includes: `Running migrations...` with no errors.

- [ ] **Step 3: Verify UUID columns in the database**

```powershell
docker exec -it blog_db psql -U postgres -d blog_db -c "SELECT id, name, email FROM users LIMIT 3;"
```

Expected: `id` column shows UUID strings like `550e8400-e29b-41d4-a716-446655440000`, not integers.

- [ ] **Step 4: Verify posts and activity_logs**

```powershell
docker exec -it blog_db psql -U postgres -d blog_db -c "SELECT id, user_id, title FROM posts LIMIT 3;"
docker exec -it blog_db psql -U postgres -d blog_db -c "SELECT id, user_id, model_id FROM activity_logs LIMIT 3;"
```

Expected: all `id`, `user_id`, `model_id` columns show UUID strings.

- [ ] **Step 5: Open http://localhost:8000 and log in**

Confirm the app still works — log in, view posts, check the admin users page.

---

## Task 4: Add UserController update + destroy + routes

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Replace UserController with updated version**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users'      => User::with('roles')->latest()->paginate(15),
            'roles'      => Role::all(['id', 'name']),
            'authUserId' => auth()->id(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        ActivityLoggerService::logRaw(
            action:  'user_created',
            model:   User::class,
            modelId: $user->id,
            payload: [
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $validated['role'],
                'created_by' => auth()->user()->email,
            ],
        );

        return back()->with('message', "User {$user->name} created.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot edit your own account from this panel.');

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email,' . $user->id,
            'password'              => 'nullable|string|min:8|confirmed',
            'password_confirmation' => 'nullable|string',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        ActivityLoggerService::logRaw(
            action:  'profile_updated',
            model:   User::class,
            modelId: $user->id,
            payload: ['name' => $user->name, 'email' => $user->email, 'updated_by' => auth()->user()->email],
        );

        return back()->with('message', "User {$user->name} updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot delete your own account.');

        $name = $user->name;

        ActivityLoggerService::logRaw(
            action:  'account_deleted',
            model:   User::class,
            modelId: $user->id,
            payload: ['name' => $name, 'email' => $user->email, 'deleted_by' => auth()->user()->email],
        );

        $user->delete();

        return back()->with('message', "User {$name} deleted.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate(['role' => 'required|string|exists:roles,name']);

        $oldRoles = $user->getRoleNames()->toArray();

        $user->syncRoles([$request->role]);

        ActivityLoggerService::logRaw(
            action:  'role_changed',
            model:   User::class,
            modelId: $user->id,
            payload: [
                'user'       => $user->email,
                'old_roles'  => $oldRoles,
                'new_role'   => $request->role,
                'changed_by' => auth()->user()->email,
            ],
        );

        return back()->with('message', 'Role updated.');
    }
}
```

- [ ] **Step 2: Add routes to `routes/web.php`**

```php
<?php

use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('posts.index');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', fn () => redirect()->route('posts.index'))->name('dashboard');

    Route::resource('posts', PostController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    });
});

require __DIR__.'/auth.php';
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/UserController.php routes/web.php
git commit -m "feat: add superadmin update and destroy user endpoints"
```

---

## Task 5: Update Admin Users frontend with edit and delete modals

**Files:**
- Modify: `resources/js/Pages/Admin/Users/Index.vue`

- [ ] **Step 1: Replace with complete updated file**

```vue
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    roles: Array,
    authUserId: String,
});

// ── Role change ───────────────────────────────────────────────────────────────
const updateRole = (userId, role) => {
    if (!role) return;
    router.patch(route('admin.users.update-role', userId), { role });
};

// ── Create user ───────────────────────────────────────────────────────────────
const showCreate = ref(false);
const createForm = useForm({ name: '', email: '', password: '', password_confirmation: '', role: '' });
const openCreate = () => { createForm.reset(); showCreate.value = true; };
const closeCreate = () => { showCreate.value = false; };
const submitCreate = () => { createForm.post(route('admin.users.store'), { onSuccess: closeCreate }); };

// ── Edit user ─────────────────────────────────────────────────────────────────
const showEdit = ref(false);
const editTarget = ref(null);
const editForm = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const openEdit = (user) => {
    editTarget.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    showEdit.value = true;
};
const closeEdit = () => { showEdit.value = false; editTarget.value = null; };
const submitEdit = () => {
    editForm.patch(route('admin.users.update', editTarget.value.id), { onSuccess: closeEdit });
};

// ── Delete user ───────────────────────────────────────────────────────────────
const showDelete = ref(false);
const deleteTarget = ref(null);
const openDelete = (user) => { deleteTarget.value = user; showDelete.value = true; };
const closeDelete = () => { showDelete.value = false; deleteTarget.value = null; };
const submitDelete = () => {
    router.delete(route('admin.users.destroy', deleteTarget.value.id), { onSuccess: closeDelete });
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const isSelf = (userId) => userId === props.authUserId;
const initials = (name) => name ? name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() : '?';
const roleColor = (role) => ({
    superadmin: 'bg-primary-fixed text-on-primary-fixed-variant',
    editor:     'bg-secondary-container text-on-secondary-container',
    viewer:     'bg-surface-container-high text-on-surface-variant',
}[role] ?? 'bg-surface-container text-on-surface-variant');
</script>

<template>
    <AdminLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-xs text-on-surface-variant">
                <span>Console</span>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-semibold">Users</span>
            </nav>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page title + CTA -->
            <div class="flex items-end justify-between">
                <h2 class="text-2xl font-bold text-on-surface tracking-tight">Users</h2>
                <button
                    @click="openCreate"
                    class="flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary rounded-xl text-xs font-semibold hover:opacity-90 shadow-sm transition-all active:scale-[0.98]"
                >
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    New User
                </button>
            </div>

            <!-- Stats bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2 bg-white border border-outline-variant rounded-xl p-4 flex items-center gap-6">
                    <div>
                        <p class="text-[11px] text-on-surface-variant uppercase tracking-widest opacity-60">Total Users</p>
                        <p class="text-xl font-bold text-on-surface mt-0.5">{{ users.total }}</p>
                    </div>
                    <div class="w-px h-8 bg-outline-variant"></div>
                    <div>
                        <p class="text-[11px] text-on-surface-variant uppercase tracking-widest opacity-60">This Page</p>
                        <p class="text-xl font-bold text-on-surface mt-0.5">{{ users.data.length }}</p>
                    </div>
                </div>
                <div class="bg-primary text-on-primary rounded-xl p-4 flex flex-col justify-between relative overflow-hidden">
                    <p class="text-xs opacity-80">Current Page</p>
                    <p class="text-lg font-bold">{{ users.current_page }} / {{ users.last_page }}</p>
                    <div class="absolute -right-3 -bottom-3 w-20 h-20 bg-white/10 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead class="bg-surface-container-low border-b border-outline-variant">
                            <tr>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold w-[30%]">User</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold hidden md:table-cell">Email</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold">Role</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold">Change Role</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="hover:bg-surface-container transition-colors group"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-xs font-bold text-on-secondary-container flex-shrink-0">
                                            {{ initials(user.name) }}
                                        </div>
                                        <p class="text-sm font-semibold text-on-surface">{{ user.name }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span class="text-sm text-on-surface-variant">{{ user.email }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-for="role in user.roles"
                                        :key="role.id"
                                        :class="['inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold capitalize', roleColor(role.name)]"
                                    >{{ role.name }}</span>
                                    <span v-if="!user.roles.length" class="text-xs text-on-surface-variant opacity-60">—</span>
                                </td>
                                <td class="px-6 py-4">
                                    <select
                                        :disabled="isSelf(user.id)"
                                        @change="updateRole(user.id, $event.target.value)"
                                        class="text-xs border border-outline-variant rounded-xl bg-surface-container-low px-3 py-2 text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all appearance-none cursor-pointer pr-8 disabled:opacity-40 disabled:cursor-not-allowed"
                                    >
                                        <option value="">Assign role…</option>
                                        <option
                                            v-for="role in roles"
                                            :key="role.id"
                                            :value="role.name"
                                            :selected="user.roles.some(r => r.name === role.name)"
                                        >{{ role.name }}</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            v-if="!isSelf(user.id)"
                                            @click="openEdit(user)"
                                            class="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors"
                                            title="Edit user"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <button
                                            v-if="!isSelf(user.id)"
                                            @click="openDelete(user)"
                                            class="p-2 rounded-lg text-on-surface-variant hover:bg-error-container/60 hover:text-error transition-colors"
                                            title="Delete user"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                        <span v-if="isSelf(user.id)" class="text-[10px] text-outline opacity-50 pr-2">you</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant text-sm">
                                    <span class="material-symbols-outlined text-[40px] block mb-2 opacity-30">group</span>
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-surface-container-lowest border-t border-outline-variant flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-on-surface-variant">
                        Showing {{ users.from }}–{{ users.to }} of {{ users.total }} results
                    </span>
                    <div class="flex items-center gap-1 flex-wrap justify-center">
                        <a
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url || undefined"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors"
                            :class="link.active
                                ? 'bg-primary text-on-primary font-semibold'
                                : link.url
                                    ? 'text-on-surface-variant hover:bg-surface-container cursor-pointer'
                                    : 'text-outline opacity-40 pointer-events-none'"
                        >
                            <span v-if="link.label.includes('Previous')" class="material-symbols-outlined text-[18px]">chevron_left</span>
                            <span v-else-if="link.label.includes('Next')" class="material-symbols-outlined text-[18px]">chevron_right</span>
                            <span v-else v-html="link.label" />
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="closeCreate" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-primary text-[20px]">person_add</span>
                            </div>
                            <h2 class="text-base font-bold text-on-surface">Create New User</h2>
                        </div>
                        <button @click="closeCreate" class="p-1.5 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>
                    <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Name</label>
                            <input v-model="createForm.name" type="text" placeholder="Full name" autofocus class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                            <p v-if="createForm.errors.name" class="text-xs text-error flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">error</span>{{ createForm.errors.name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Email</label>
                            <input v-model="createForm.email" type="email" placeholder="user@example.com" class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                            <p v-if="createForm.errors.email" class="text-xs text-error flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">error</span>{{ createForm.errors.email }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Password</label>
                            <input v-model="createForm.password" type="password" placeholder="Min. 8 characters" class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                            <p v-if="createForm.errors.password" class="text-xs text-error flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">error</span>{{ createForm.errors.password }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Confirm Password</label>
                            <input v-model="createForm.password_confirmation" type="password" placeholder="Repeat password" class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Role</label>
                            <select v-model="createForm.role" class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                                <option value="">Select a role…</option>
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                            <p v-if="createForm.errors.role" class="text-xs text-error flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">error</span>{{ createForm.errors.role }}</p>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="closeCreate" class="px-4 py-2.5 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
                            <button type="submit" :disabled="createForm.processing" class="flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:opacity-90 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="material-symbols-outlined text-[18px]">person_add</span>
                                {{ createForm.processing ? 'Creating…' : 'Create User' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Edit User Modal -->
        <Teleport to="body">
            <div v-if="showEdit" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="closeEdit" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-amber-600 text-[20px]">edit</span>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-on-surface">Edit User</h2>
                                <p class="text-[11px] text-on-surface-variant mt-0.5">{{ editTarget?.email }}</p>
                            </div>
                        </div>
                        <button @click="closeEdit" class="p-1.5 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>
                    <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Name</label>
                            <input v-model="editForm.name" type="text" placeholder="Full name" autofocus class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                            <p v-if="editForm.errors.name" class="text-xs text-error flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">error</span>{{ editForm.errors.name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Email</label>
                            <input v-model="editForm.email" type="email" placeholder="user@example.com" class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                            <p v-if="editForm.errors.email" class="text-xs text-error flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">error</span>{{ editForm.errors.email }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">New Password <span class="normal-case font-normal opacity-60">(leave blank to keep current)</span></label>
                            <input v-model="editForm.password" type="password" placeholder="Min. 8 characters" class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                            <p v-if="editForm.errors.password" class="text-xs text-error flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">error</span>{{ editForm.errors.password }}</p>
                        </div>
                        <div class="space-y-1.5" v-if="editForm.password">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Confirm New Password</label>
                            <input v-model="editForm.password_confirmation" type="password" placeholder="Repeat new password" class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="closeEdit" class="px-4 py-2.5 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
                            <button type="submit" :disabled="editForm.processing" class="flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:opacity-90 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="material-symbols-outlined text-[18px]">save</span>
                                {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="closeDelete" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm">
                    <div class="p-6 text-center space-y-3">
                        <div class="w-12 h-12 rounded-full bg-error-container/60 flex items-center justify-center mx-auto">
                            <span class="material-symbols-outlined text-error text-[24px]">person_remove</span>
                        </div>
                        <h2 class="text-base font-bold text-on-surface">Delete user?</h2>
                        <p class="text-sm text-on-surface-variant">
                            <span class="font-semibold text-on-surface">{{ deleteTarget?.name }}</span>
                            will be permanently deleted. Their posts will remain but show no author.
                        </p>
                    </div>
                    <div class="flex gap-3 px-6 pb-6">
                        <button @click="closeDelete" class="flex-1 px-4 py-2.5 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
                        <button @click="submitDelete" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-error text-on-error rounded-xl text-sm font-semibold hover:opacity-90 transition-all active:scale-[0.98]">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Admin/Users/Index.vue
git commit -m "feat: add edit and delete user modals for superadmin"
```

---

## Task 6: Final verification

- [ ] **Step 1: Open http://localhost:8000/admin/users as superadmin**

Confirm:
- All user IDs in the browser network tab show UUIDs (not integers)
- Edit button appears on all rows except your own
- Delete button appears on all rows except your own
- Your own row shows "you" label instead

- [ ] **Step 2: Test edit user**

Click Edit on a non-self user → change their name → Save. Confirm the name updates in the table and a success message appears.

- [ ] **Step 3: Test delete user**

Click Delete on a non-self user → confirm in the dialog. Confirm the user disappears from the table.

- [ ] **Step 4: Verify activity log**

Go to http://localhost:8000/admin/logs and confirm `profile_updated` and `account_deleted` entries appear with UUID model IDs in the ID column.

- [ ] **Step 5: Check post URLs show UUIDs**

Go to http://localhost:8000/posts — click any post. The URL should be `/posts/550e8400-...` not `/posts/3`.
