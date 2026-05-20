<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    posts: Object,
});

const page = usePage();
const hasPermission = (p) => page.props.auth.permissions.includes(p);

const destroy = (id) => {
    if (confirm('Delete this post?')) {
        router.delete(route('posts.destroy', id));
    }
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
                            <Link
                                :href="route('posts.show', post.id)"
                                class="text-blue-600 hover:underline"
                            >View</Link>
                            <Link
                                v-if="hasPermission('edit posts')"
                                :href="route('posts.edit', post.id)"
                                class="text-yellow-600 hover:underline"
                            >Edit</Link>
                            <button
                                v-if="hasPermission('delete posts')"
                                @click="destroy(post.id)"
                                class="text-red-600 hover:underline"
                            >Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!posts.data.length">
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">No posts yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Simple pagination links -->
        <div class="mt-4 flex gap-2">
            <Link
                v-for="link in posts.links"
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="px-3 py-1 rounded text-sm border"
                :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300'"
            />
        </div>
    </AdminLayout>
</template>
