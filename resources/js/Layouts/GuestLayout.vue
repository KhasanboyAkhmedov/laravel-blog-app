<script setup>
</script>

<template>
    <div class="min-h-screen bg-background flex items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <!-- Brand mark -->
            <div class="flex items-center justify-center gap-3 mb-8">
                <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-on-primary text-[22px]">article</span>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-on-surface leading-tight">Blog App</h1>
                    <p class="text-xs text-on-surface-variant opacity-70">Admin Console</p>
                </div>
            </div>

            <!-- Card -->
            <div class="bg-white border border-outline-variant rounded-2xl shadow-sm p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
