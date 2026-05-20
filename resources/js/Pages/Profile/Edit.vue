<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;

// Profile info form
const profileForm = useForm({ name: user.name, email: user.email });
const saveProfile = () => profileForm.patch(route('profile.update'), { preserveScroll: true });

// Password form
const currentPasswordInput = ref(null);
const passwordInput = ref(null);
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
const savePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};

// Delete account
const deleteForm = useForm({ password: '' });
const showDeleteModal = ref(false);
const deletePasswordInput = ref(null);
const openDeleteModal = () => { showDeleteModal.value = true; };
const closeDeleteModal = () => { showDeleteModal.value = false; deleteForm.reset(); deleteForm.clearErrors(); };
const deleteAccount = () => {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: closeDeleteModal,
        onError: () => deletePasswordInput.value?.focus(),
        onFinish: () => deleteForm.reset(),
    });
};
</script>

<template>
    <Head title="Profile" />

    <AdminLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-xs text-on-surface-variant">
                <span>Console</span>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-semibold">Profile</span>
            </nav>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <h2 class="text-2xl font-bold text-on-surface tracking-tight">Profile</h2>

            <!-- Profile Information -->
            <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px] text-on-surface-variant">person</span>
                    <div>
                        <p class="text-sm font-semibold text-on-surface">Profile Information</p>
                        <p class="text-xs text-on-surface-variant">Update your name and email address.</p>
                    </div>
                </div>
                <form @submit.prevent="saveProfile" class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label for="name" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Name</label>
                        <input
                            id="name"
                            v-model="profileForm.name"
                            type="text"
                            required
                            autofocus
                            autocomplete="name"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                        />
                        <p v-if="profileForm.errors.name" class="text-xs text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>{{ profileForm.errors.name }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <label for="email" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Email</label>
                        <input
                            id="email"
                            v-model="profileForm.email"
                            type="email"
                            required
                            autocomplete="username"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                        />
                        <p v-if="profileForm.errors.email" class="text-xs text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>{{ profileForm.errors.email }}
                        </p>
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700">
                        Your email address is unverified.
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <Transition enter-active-class="transition-opacity" enter-from-class="opacity-0" leave-active-class="transition-opacity" leave-to-class="opacity-0">
                            <span v-if="profileForm.recentlySuccessful" class="text-xs text-emerald-600 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span> Saved
                            </span>
                        </Transition>
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:opacity-90 transition-all active:scale-[0.98] disabled:opacity-50"
                        >
                            <span class="material-symbols-outlined text-[18px]">save</span>
                            {{ profileForm.processing ? 'Saving…' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Update Password -->
            <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant bg-surface-container-low flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px] text-on-surface-variant">lock</span>
                    <div>
                        <p class="text-sm font-semibold text-on-surface">Update Password</p>
                        <p class="text-xs text-on-surface-variant">Use a long, random password to stay secure.</p>
                    </div>
                </div>
                <form @submit.prevent="savePassword" class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label for="current_password" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Current Password</label>
                        <input
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="passwordForm.current_password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                        />
                        <p v-if="passwordForm.errors.current_password" class="text-xs text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>{{ passwordForm.errors.current_password }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <label for="new_password" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">New Password</label>
                        <input
                            id="new_password"
                            ref="passwordInput"
                            v-model="passwordForm.password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                        />
                        <p v-if="passwordForm.errors.password" class="text-xs text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>{{ passwordForm.errors.password }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <label for="password_confirmation" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                        />
                        <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">error</span>{{ passwordForm.errors.password_confirmation }}
                        </p>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-1">
                        <Transition enter-active-class="transition-opacity" enter-from-class="opacity-0" leave-active-class="transition-opacity" leave-to-class="opacity-0">
                            <span v-if="passwordForm.recentlySuccessful" class="text-xs text-emerald-600 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span> Saved
                            </span>
                        </Transition>
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:opacity-90 transition-all active:scale-[0.98] disabled:opacity-50"
                        >
                            <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                            {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="bg-white border border-error/20 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-error/20 bg-error-container/30 flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px] text-error">warning</span>
                    <div>
                        <p class="text-sm font-semibold text-on-surface">Delete Account</p>
                        <p class="text-xs text-on-surface-variant">Permanently delete your account and all its data.</p>
                    </div>
                </div>
                <div class="p-6">
                    <button
                        @click="openDeleteModal"
                        class="flex items-center gap-2 px-5 py-2.5 bg-error text-on-error rounded-xl text-sm font-semibold hover:opacity-90 transition-all active:scale-[0.98]"
                    >
                        <span class="material-symbols-outlined text-[18px]">delete_forever</span>
                        Delete Account
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Account Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="closeDeleteModal" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-error-container flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-error text-[20px]">warning</span>
                        </div>
                        <h2 class="text-base font-bold text-on-surface">Delete account?</h2>
                    </div>
                    <p class="text-sm text-on-surface-variant mb-4">This action cannot be undone. Please enter your password to confirm.</p>
                    <input
                        ref="deletePasswordInput"
                        v-model="deleteForm.password"
                        type="password"
                        placeholder="Your password"
                        @keyup.enter="deleteAccount"
                        class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-error/30 focus:border-error transition-all mb-2"
                    />
                    <p v-if="deleteForm.errors.password" class="text-xs text-error flex items-center gap-1 mb-4">
                        <span class="material-symbols-outlined text-[14px]">error</span>{{ deleteForm.errors.password }}
                    </p>
                    <div class="flex justify-end gap-3 mt-4">
                        <button @click="closeDeleteModal" class="px-4 py-2 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
                        <button
                            @click="deleteAccount"
                            :disabled="deleteForm.processing"
                            class="px-4 py-2 text-sm rounded-xl bg-error text-on-error hover:opacity-90 transition-opacity disabled:opacity-50"
                        >Delete Account</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
