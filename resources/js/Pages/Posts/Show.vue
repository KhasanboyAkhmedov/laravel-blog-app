<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ post: Object });
</script>

<template>
    <AdminLayout>
        <template #header>{{ post.title }}</template>

        <div class="max-w-3xl bg-white rounded shadow p-8">
            <div class="flex items-center gap-3 text-sm text-gray-500 mb-6">
                <span>By {{ post.author?.name }}</span>
                <span>&middot;</span>
                <span>{{ new Date(post.created_at).toLocaleDateString() }}</span>
            </div>
            <div class="prose max-w-none text-gray-800 whitespace-pre-wrap">{{ post.content }}</div>
            <div class="mt-8">
                <Link :href="route('posts.index')" class="text-sm text-blue-600 hover:underline">
                    &larr; Back to posts
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
