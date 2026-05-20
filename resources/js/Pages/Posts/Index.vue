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

// Editors can only act on their own posts; superadmin can act on any
const canEdit = (post) =>
    hasPermission('edit posts') && (props.isSuperadmin || post.author?.id === props.authUserId);

const canDelete = (post) =>
    hasPermission('delete posts') && (props.isSuperadmin || post.author?.id === props.authUserId);

// Delete modal state
const pendingDeleteId = ref(null);
const showModal = ref(false);

const openDeleteModal = (id) => {
    pendingDeleteId.value = id;
    showModal.value = true;
};

const cancelDelete = () => {
    showModal.value = false;
    pendingDeleteId.value = null;
};

const confirmDelete = () => {
    router.delete(route('posts.destroy', pendingDeleteId.value), {
        onFinish: () => cancelDelete(),
    });
};
</script>

<template>
    <AdminLayout>
        <template #header>Posts</template>

        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-semibold">All Posts</h1>
            <Link
                v-if="hasPermission('create posts')"
                :href="route('posts.create')"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
            >
                New Post
            </Link>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Author</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="post in posts.data" :key="post.id">
                        <td class="px-4 py-3 font-medium">{{ post.title }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ post.author?.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ new Date(post.created_at).toLocaleDateString() }}</td>
                        <td class="px-4 py-3 flex gap-3">
                            <Link :href="route('posts.show', post.id)" class="text-blue-600 hover:underline">
                                View
                            </Link>
                            <Link
                                v-if="canEdit(post)"
                                :href="route('posts.edit', post.id)"
                                class="text-yellow-600 hover:underline"
                            >
                                Edit
                            </Link>
                            <button
                                v-if="canDelete(post)"
                                @click="openDeleteModal(post.id)"
                                class="text-red-600 hover:underline"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!posts.data.length">
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">No posts yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex gap-2 flex-wrap">
            <Link
                v-for="link in posts.links"
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="px-3 py-1 rounded text-sm border"
                :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300'"
            />
        </div>

        <!-- Delete confirmation modal -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40" @click="cancelDelete" />

                <!-- Dialog -->
                <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Delete post?</h2>
                    <p class="text-sm text-gray-500 mb-6">
                        This action cannot be undone. The post and its activity log entry will be permanently removed.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="cancelDelete"
                            class="px-4 py-2 text-sm rounded border border-gray-300 text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmDelete"
                            class="px-4 py-2 text-sm rounded bg-red-600 text-white hover:bg-red-700"
                        >
                            Yes, delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
