<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);

const hasPermission = (p) => page.props.auth.permissions.includes(p);
const hasRole = (r) => page.props.auth.roles.includes(r);

// Mobile sidebar toggle
const sidebarOpen = ref(false);

const navItems = computed(() => [
    {
        label: 'Posts',
        icon: 'article',
        route: 'posts.index',
        match: 'posts.*',
        show: true,
    },
    {
        label: 'Users',
        icon: 'group',
        route: 'admin.users.index',
        match: 'admin.users.*',
        show: hasRole('superadmin'),
    },
    {
        label: 'System Logs',
        icon: 'receipt_long',
        route: 'admin.logs.index',
        match: 'admin.logs.*',
        show: hasRole('superadmin'),
    },
]);

const isActive = (match) => {
    try { return route().current(match); } catch { return false; }
};

// Logout modal
const showLogoutModal = ref(false);
const confirmLogout = () => router.post(route('logout'));

// Toast system
const toasts = ref([]);
const addToast = (message, type = 'success') => {
    const id = Date.now();
    toasts.value.push({ id, message, type });
    setTimeout(() => {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }, 4000);
};

watch(() => flash.value?.message, (msg) => {
    if (msg) addToast(msg, 'success');
});
watch(() => flash.value?.error, (msg) => {
    if (msg) addToast(msg, 'error');
});

const initials = (name) => name ? name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() : '?';
</script>

<template>
    <div class="min-h-screen bg-background">

        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- ─── Sidebar ──────────────────────────────────────── -->
        <aside
            class="fixed left-0 top-0 h-screen w-sidebar bg-surface border-r border-outline-variant flex flex-col p-4 z-50 transition-transform duration-300"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <!-- Brand -->
            <div class="flex items-center gap-3 px-2 mb-8">
                <div class="w-8 h-8 bg-primary rounded-xl flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-on-primary text-[20px]">article</span>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-on-surface leading-tight">Blog App</h1>
                    <p v-if="hasRole('superadmin') || hasRole('editor')" class="text-xs text-on-surface-variant opacity-70">Admin Console</p>
                </div>
                <!-- Close button mobile -->
                <button
                    class="ml-auto lg:hidden p-1 text-on-surface-variant hover:bg-surface-container-high rounded-lg"
                    @click="sidebarOpen = false"
                >
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <!-- New Post CTA (editors+) -->
            <Link
                v-if="hasPermission('create posts')"
                :href="route('posts.create')"
                class="mb-6 flex items-center justify-center gap-2 px-4 py-3 bg-primary text-on-primary rounded-xl text-xs font-semibold hover:opacity-90 transition-all active:scale-[0.98]"
                @click="sidebarOpen = false"
            >
                <span class="material-symbols-outlined text-[18px]">add</span>
                New Post
            </Link>

            <!-- Nav items -->
            <nav class="flex-1 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    v-show="item.show"
                    :href="route(item.route)"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm transition-colors"
                    :class="isActive(item.match)
                        ? 'bg-secondary-container text-on-secondary-container font-medium'
                        : 'text-on-surface-variant hover:bg-surface-container-high'"
                    @click="sidebarOpen = false"
                >
                    <span class="material-symbols-outlined text-[20px]">{{ item.icon }}</span>
                    {{ item.label }}
                </Link>
            </nav>

            <!-- Footer -->
            <div class="pt-4 border-t border-outline-variant space-y-1">
                <Link
                    :href="route('profile.edit')"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm transition-colors"
                    :class="isActive('profile.*')
                        ? 'bg-secondary-container text-on-secondary-container font-medium'
                        : 'text-on-surface-variant hover:bg-surface-container-high'"
                    @click="sidebarOpen = false"
                >
                    <span class="material-symbols-outlined text-[20px]">person</span>
                    Profile
                </Link>
                <button
                    @click="showLogoutModal = true"
                    class="w-full flex items-center gap-3 px-4 py-3 text-error hover:bg-error-container/20 transition-colors rounded-xl text-sm"
                >
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    Logout
                </button>
            </div>
        </aside>

        <!-- ─── Main ─────────────────────────────────────────── -->
        <div class="lg:ml-sidebar flex flex-col min-h-screen">

            <!-- Topbar -->
            <header class="sticky top-0 z-30 h-16 bg-surface/80 backdrop-blur-md border-b border-outline-variant flex items-center justify-between px-4 lg:px-6">
                <!-- Hamburger -->
                <button
                    class="lg:hidden p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full mr-3"
                    @click="sidebarOpen = true"
                >
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <!-- Page header slot -->
                <div class="flex-1 min-w-0">
                    <slot name="header" />
                </div>

                <!-- Right: user info → navigate to profile -->
                <Link
                    :href="route('profile.edit')"
                    class="flex items-center gap-3 ml-4 rounded-xl px-2 py-1.5 hover:bg-surface-container transition-colors"
                >
                    <div class="hidden sm:block text-right">
                        <p class="text-xs font-bold text-on-surface leading-none">{{ user.name }}</p>
                        <p class="text-[10px] text-primary uppercase tracking-wider mt-0.5">
                            {{ page.props.auth.roles[0] ?? 'user' }}
                        </p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-xs font-bold text-on-secondary-container flex-shrink-0">
                        {{ initials(user.name) }}
                    </div>
                </Link>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-4 lg:p-8">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="px-4 lg:px-6 py-4 border-t border-outline-variant/30 text-center">
                <p class="text-[11px] text-on-surface-variant opacity-50">© {{ new Date().getFullYear() }} Blog App. All rights reserved.</p>
            </footer>
        </div>
    </div>

    <!-- Logout confirmation modal -->
    <Teleport to="body">
        <div v-if="showLogoutModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="showLogoutModal = false" />
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-error-container flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-error text-[20px]">logout</span>
                    </div>
                    <h2 class="text-base font-bold text-on-surface">Sign out?</h2>
                </div>
                <p class="text-sm text-on-surface-variant mb-6">You will be signed out of your account.</p>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showLogoutModal = false"
                        class="px-4 py-2 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors"
                    >Cancel</button>
                    <button
                        @click="confirmLogout"
                        class="px-4 py-2 text-sm rounded-xl bg-error text-on-error hover:opacity-90 transition-opacity"
                    >Sign out</button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Toast notifications -->
    <Teleport to="body">
        <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none">
            <TransitionGroup
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 translate-y-2"
                leave-active-class="transition-all duration-300"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium min-w-[260px] max-w-sm"
                    :class="toast.type === 'error'
                        ? 'bg-error text-on-error'
                        : 'bg-zinc-900 text-white'"
                >
                    <span class="material-symbols-outlined text-[18px] flex-shrink-0">
                        {{ toast.type === 'error' ? 'error' : 'check_circle' }}
                    </span>
                    <span class="flex-1">{{ toast.message }}</span>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
