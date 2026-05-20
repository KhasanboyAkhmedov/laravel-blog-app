<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
const flash = page.props.flash;

const hasPermission = (permission) =>
    page.props.auth.permissions.includes(permission);

const hasRole = (role) =>
    page.props.auth.roles.includes(role);
</script>

<template>
    <div class="flex h-screen bg-gray-100 overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 flex-shrink-0 bg-gray-800 text-white flex flex-col">
            <div class="p-5 text-xl font-bold border-b border-gray-700">
                Blog App
            </div>

            <nav class="flex-1 p-4 space-y-1">
                <Link
                    :href="route('posts.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 text-sm"
                    :class="{ 'bg-gray-700': route().current('posts.*') }"
                >
                    Posts
                </Link>

                <Link
                    v-if="hasPermission('create posts')"
                    :href="route('posts.create')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 text-sm"
                >
                    + New Post
                </Link>

                <div v-if="hasRole('superadmin')" class="pt-4">
                    <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Admin</p>

                    <Link
                        :href="route('admin.users.index')"
                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 text-sm"
                    >
                        Users
                    </Link>

                    <Link
                        v-if="hasPermission('view logs')"
                        :href="route('admin.logs.index')"
                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 text-sm"
                    >
                        Activity Logs
                    </Link>
                </div>
            </nav>

            <!-- User footer -->
            <div class="p-4 border-t border-gray-700 text-sm text-gray-400">
                <p class="truncate">{{ user.name }}</p>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="mt-1 text-xs text-red-400 hover:text-red-300"
                >
                    Logout
                </Link>
            </div>
        </aside>

        <!-- Main area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow px-6 py-3 flex items-center justify-between">
                <slot name="header">
                    <span class="text-gray-600 text-sm"></span>
                </slot>
                <span class="text-sm text-gray-500">{{ user.name }}</span>
            </header>

            <!-- Flash message -->
            <div
                v-if="flash?.message"
                class="mx-6 mt-4 p-3 bg-green-100 text-green-800 rounded text-sm"
            >
                {{ flash.message }}
            </div>

            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
