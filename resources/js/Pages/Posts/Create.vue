<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    content: '',
});

const submit = () => form.post(route('posts.store'));
</script>

<template>
    <AdminLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-xs text-on-surface-variant">
                <span>Console</span>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <Link :href="route('posts.index')" class="hover:text-primary transition-colors">Posts</Link>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-semibold">New Post</span>
            </nav>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Page title -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('posts.index')"
                    class="p-2 hover:bg-surface-container rounded-xl text-on-surface-variant transition-colors"
                    title="Back to Posts"
                >
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </Link>
                <h2 class="text-2xl font-bold text-on-surface tracking-tight">New Post</h2>
            </div>

            <!-- Form card -->
            <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low">
                    <p class="text-xs text-on-surface-variant">Fill in the fields below to publish a new post.</p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <!-- Title -->
                    <div class="space-y-1.5">
                        <label for="title" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">
                            Title
                        </label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            placeholder="Enter post title…"
                            autofocus
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                        />
                        <p v-if="form.errors.title" class="text-xs text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <!-- Content -->
                    <div class="space-y-1.5">
                        <label for="content" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">
                            Content
                        </label>
                        <textarea
                            id="content"
                            v-model="form.content"
                            rows="12"
                            placeholder="Write your post content here…"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all resize-y"
                        />
                        <p v-if="form.errors.content" class="text-xs text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>
                            {{ form.errors.content }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <Link
                            :href="route('posts.index')"
                            class="px-5 py-2.5 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:opacity-90 shadow-sm transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-[18px]">publish</span>
                            {{ form.processing ? 'Publishing…' : 'Publish Post' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
