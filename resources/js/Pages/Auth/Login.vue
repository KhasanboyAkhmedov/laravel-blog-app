<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign in" />

        <h2 class="text-lg font-bold text-on-surface mb-1">Welcome back</h2>
        <p class="text-xs text-on-surface-variant mb-6">Sign in to your account to continue</p>

        <div v-if="status" class="mb-4 flex items-center gap-2 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-xs border border-emerald-200">
            <span class="material-symbols-outlined text-[16px]">check_circle</span>
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Email -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                    class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                />
                <p v-if="form.errors.email" class="text-xs text-error flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>{{ form.errors.email }}
                </p>
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Password</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs text-primary hover:opacity-80 transition-opacity"
                    >Forgot password?</Link>
                </div>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                />
                <p v-if="form.errors.password" class="text-xs text-error flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">error</span>{{ form.errors.password }}
                </p>
            </div>

            <!-- Remember me -->
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                    type="checkbox"
                    v-model="form.remember"
                    class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/30"
                />
                <span class="text-xs text-on-surface-variant">Remember me</span>
            </label>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:opacity-90 shadow-sm transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed mt-2"
            >
                <span class="material-symbols-outlined text-[18px]">login</span>
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
            </button>
        </form>
    </GuestLayout>
</template>
