<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    posts: Object,
    authUserId: Number,
    isSuperadmin: Boolean,
});

const page = usePage();
const hasPermission = (p) => page.props.auth.permissions.includes(p);

const canEdit   = (post) => hasPermission('edit posts')   && (props.isSuperadmin || post.author?.id === props.authUserId);
const canDelete = (post) => hasPermission('delete posts') && (props.isSuperadmin || post.author?.id === props.authUserId);

// Delete modal
const pendingId = ref(null);
const showModal = ref(false);
const openDelete = (id) => { pendingId.value = id; showModal.value = true; };
const cancelDelete = () => { showModal.value = false; pendingId.value = null; };
const confirmDelete = () => router.delete(route('posts.destroy', pendingId.value), { onFinish: cancelDelete });

const initials = (name) => name ? name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() : '?';
</script>

<template>
    <AdminLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-xs text-on-surface-variant">
                <span>Console</span>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-semibold">Posts</span>
            </nav>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page title + CTA -->
            <div class="flex items-end justify-between">
                <h2 class="text-2xl font-bold text-on-surface tracking-tight">Posts</h2>
                <Link
                    v-if="hasPermission('create posts')"
                    :href="route('posts.create')"
                    class="flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary rounded-xl text-xs font-semibold hover:opacity-90 shadow-sm transition-all active:scale-[0.98]"
                >
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    New Post
                </Link>
            </div>

            <!-- Stats bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2 bg-white border border-outline-variant rounded-xl p-4 flex items-center gap-6">
                    <div>
                        <p class="text-[11px] text-on-surface-variant uppercase tracking-widest opacity-60">Total Posts</p>
                        <p class="text-xl font-bold text-on-surface mt-0.5">{{ posts.total }}</p>
                    </div>
                    <div class="w-px h-8 bg-outline-variant"></div>
                    <div>
                        <p class="text-[11px] text-on-surface-variant uppercase tracking-widest opacity-60">This Page</p>
                        <p class="text-xl font-bold text-on-surface mt-0.5">{{ posts.data.length }}</p>
                    </div>
                </div>
                <div class="bg-primary text-on-primary rounded-xl p-4 flex flex-col justify-between relative overflow-hidden">
                    <p class="text-xs opacity-80">Current Page</p>
                    <p class="text-lg font-bold">{{ posts.current_page }} / {{ posts.last_page }}</p>
                    <div class="absolute -right-3 -bottom-3 w-20 h-20 bg-white/10 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <!-- Desktop table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[640px]">
                        <thead class="bg-surface-container-low border-b border-outline-variant">
                            <tr>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold w-[45%]">Title</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold">Author</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold hidden md:table-cell">Date</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <tr
                                v-for="post in posts.data"
                                :key="post.id"
                                class="hover:bg-surface-container transition-colors group"
                            >
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-on-surface line-clamp-2">{{ post.title }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold text-on-secondary-container flex-shrink-0">
                                            {{ initials(post.author?.name) }}
                                        </div>
                                        <span class="text-sm text-on-surface hidden sm:block">{{ post.author?.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span class="text-sm text-on-surface-variant">{{ new Date(post.created_at).toLocaleDateString('en', { month:'short', day:'numeric', year:'numeric' }) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                        <Link
                                            :href="route('posts.show', post.id)"
                                            class="p-1.5 hover:bg-surface-container-highest rounded-lg text-secondary transition-colors"
                                            title="View"
                                        >
                                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                                        </Link>
                                        <Link
                                            v-if="canEdit(post)"
                                            :href="route('posts.edit', post.id)"
                                            class="p-1.5 hover:bg-tertiary-fixed-dim/20 rounded-lg text-tertiary transition-colors"
                                            title="Edit"
                                        >
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </Link>
                                        <button
                                            v-if="canDelete(post)"
                                            @click="openDelete(post.id)"
                                            class="p-1.5 hover:bg-error-container/40 rounded-lg text-error transition-colors"
                                            title="Delete"
                                        >
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!posts.data.length">
                                <td colspan="4" class="px-6 py-12 text-center text-on-surface-variant text-sm">
                                    <span class="material-symbols-outlined text-[40px] block mb-2 opacity-30">article</span>
                                    No posts yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-surface-container-lowest border-t border-outline-variant flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-on-surface-variant">
                        Showing {{ posts.from }}–{{ posts.to }} of {{ posts.total }} results
                    </span>
                    <div class="flex items-center gap-1 flex-wrap justify-center">
                        <Link
                            v-for="link in posts.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors"
                            :class="link.active
                                ? 'bg-primary text-on-primary font-semibold'
                                : link.url
                                    ? 'text-on-surface-variant hover:bg-surface-container'
                                    : 'text-outline opacity-40 pointer-events-none'"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="cancelDelete" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-error-container flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-error text-[20px]">delete</span>
                        </div>
                        <h2 class="text-base font-bold text-on-surface">Delete post?</h2>
                    </div>
                    <p class="text-sm text-on-surface-variant mb-6">This action cannot be undone. The post will be permanently removed.</p>
                    <div class="flex justify-end gap-3">
                        <button @click="cancelDelete" class="px-4 py-2 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
                        <button @click="confirmDelete" class="px-4 py-2 text-sm rounded-xl bg-error text-on-error hover:opacity-90 transition-opacity">Delete</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
