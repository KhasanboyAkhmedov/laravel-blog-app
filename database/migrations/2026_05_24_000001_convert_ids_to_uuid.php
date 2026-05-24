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
        // posts.user_id intentionally left nullable — SET NULL keeps posts alive when user is deleted
        DB::statement('ALTER TABLE activity_logs ALTER COLUMN id SET NOT NULL');

        // ── 9. Promote UUID columns to primary keys ───────────────────────
        DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE posts ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE activity_logs ADD PRIMARY KEY (id)');

        // ── 10. Re-add FK constraints on new UUID columns ─────────────────
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
