<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ post: Object });
</script>

<template>
    <AdminLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-xs text-on-surface-variant">
                <span>Console</span>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <Link :href="route('posts.index')" class="hover:text-primary transition-colors">Posts</Link>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-semibold line-clamp-1 max-w-[200px]">{{ post.title }}</span>
            </nav>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Back + title -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('posts.index')"
                    class="p-2 hover:bg-surface-container rounded-xl text-on-surface-variant transition-colors flex-shrink-0"
                    title="Back to Posts"
                >
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </Link>
                <h2 class="text-2xl font-bold text-on-surface tracking-tight line-clamp-2">{{ post.title }}</h2>
            </div>

            <!-- Article card -->
            <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                <!-- Meta bar -->
                <div class="px-6 py-4 bg-surface-container-low border-b border-outline-variant flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-secondary-container flex items-center justify-center text-[11px] font-bold text-on-secondary-container flex-shrink-0">
                            {{ post.author?.name ? post.author.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() : '?' }}
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-on-surface leading-none">{{ post.author?.name ?? 'Unknown' }}</p>
                            <p class="text-[10px] text-on-surface-variant mt-0.5">Author</p>
                        </div>
                    </div>
                    <div class="w-px h-6 bg-outline-variant"></div>
                    <div class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                        <span class="material-symbols-outlined text-[16px]">calendar_today</span>
                        {{ new Date(post.created_at).toLocaleDateString('en', { month: 'long', day: 'numeric', year: 'numeric' }) }}
                    </div>
                    <div v-if="post.updated_at !== post.created_at" class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                        <span class="material-symbols-outlined text-[16px]">update</span>
                        Updated {{ new Date(post.updated_at).toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 lg:p-8">
                    <div class="text-sm text-on-surface leading-relaxed whitespace-pre-wrap">{{ post.content }}</div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
