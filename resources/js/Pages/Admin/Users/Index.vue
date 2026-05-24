<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    roles: Array,
    authUserId: String,
});

const isSelf = (userId) => userId === props.authUserId;

// Role change
const updateRole = (userId, role) => {
    if (!role) return;
    router.patch(route('admin.users.update-role', userId), { role });
};

// Create user modal
const showCreate = ref(false);
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});
const openCreate = () => { createForm.reset(); showCreate.value = true; };
const closeCreate = () => { showCreate.value = false; };
const submitCreate = () => {
    createForm.post(route('admin.users.store'), { onSuccess: closeCreate });
};

// Edit user modal
const showEdit = ref(false);
const editingUser = ref(null);
const editForm = useForm({ name: '', email: '', password: '', password_confirmation: '' });

const openEdit = (user) => {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    showEdit.value = true;
};
const closeEdit = () => { showEdit.value = false; editingUser.value = null; };
const submitEdit = () => {
    editForm.patch(route('admin.users.update', editingUser.value.id), { onSuccess: closeEdit });
};

// Delete user modal
const showDelete = ref(false);
const deletingUser = ref(null);

const openDelete = (user) => { deletingUser.value = user; showDelete.value = true; };
const closeDelete = () => { showDelete.value = false; deletingUser.value = null; };
const submitDelete = () => {
    router.delete(route('admin.users.destroy', deletingUser.value.id), { onSuccess: closeDelete });
};

const initials = (name) => name ? name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() : '?';

const roleColor = (role) => ({
    superadmin: 'bg-primary-fixed text-on-primary-fixed-variant',
    editor: 'bg-secondary-container text-on-secondary-container',
    viewer: 'bg-surface-container-high text-on-surface-variant',
}[role] ?? 'bg-surface-container text-on-surface-variant');
</script>

<template>
    <AdminLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-xs text-on-surface-variant">
                <span>Console</span>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-semibold">Users</span>
            </nav>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page title + CTA -->
            <div class="flex items-end justify-between">
                <h2 class="text-2xl font-bold text-on-surface tracking-tight">Users</h2>
                <button
                    @click="openCreate"
                    class="flex items-center gap-2 px-5 py-2.5 bg-primary text-on-primary rounded-xl text-xs font-semibold hover:opacity-90 shadow-sm transition-all active:scale-[0.98]"
                >
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    New User
                </button>
            </div>

            <!-- Stats bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2 bg-white border border-outline-variant rounded-xl p-4 flex items-center gap-6">
                    <div>
                        <p class="text-[11px] text-on-surface-variant uppercase tracking-widest opacity-60">Total Users</p>
                        <p class="text-xl font-bold text-on-surface mt-0.5">{{ users.total }}</p>
                    </div>
                    <div class="w-px h-8 bg-outline-variant"></div>
                    <div>
                        <p class="text-[11px] text-on-surface-variant uppercase tracking-widest opacity-60">This Page</p>
                        <p class="text-xl font-bold text-on-surface mt-0.5">{{ users.data.length }}</p>
                    </div>
                </div>
                <div class="bg-primary text-on-primary rounded-xl p-4 flex flex-col justify-between relative overflow-hidden">
                    <p class="text-xs opacity-80">Current Page</p>
                    <p class="text-lg font-bold">{{ users.current_page }} / {{ users.last_page }}</p>
                    <div class="absolute -right-3 -bottom-3 w-20 h-20 bg-white/10 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[640px]">
                        <thead class="bg-surface-container-low border-b border-outline-variant">
                            <tr>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold w-[35%]">User</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold hidden md:table-cell">Email</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold">Role</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold text-right">Change Role</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="hover:bg-surface-container transition-colors group"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-xs font-bold text-on-secondary-container flex-shrink-0">
                                            {{ initials(user.name) }}
                                        </div>
                                        <p class="text-sm font-semibold text-on-surface">{{ user.name }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span class="text-sm text-on-surface-variant">{{ user.email }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-for="role in user.roles"
                                        :key="role.id"
                                        :class="['inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold capitalize', roleColor(role.name)]"
                                    >{{ role.name }}</span>
                                    <span v-if="!user.roles.length" class="text-xs text-on-surface-variant opacity-60">—</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <select
                                        @change="updateRole(user.id, $event.target.value)"
                                        class="text-xs border border-outline-variant rounded-xl bg-surface-container-low px-3 py-2 text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all appearance-none cursor-pointer pr-8"
                                    >
                                        <option value="">Assign role…</option>
                                        <option
                                            v-for="role in roles"
                                            :key="role.id"
                                            :value="role.name"
                                            :selected="user.roles.some(r => r.name === role.name)"
                                        >{{ role.name }}</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <template v-if="!isSelf(user.id)">
                                        <button
                                            @click="openEdit(user)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-on-surface-variant hover:bg-surface-container transition-colors"
                                            title="Edit user"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <button
                                            @click="openDelete(user)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-error hover:bg-error/10 transition-colors ml-1"
                                            title="Delete user"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </template>
                                    <span v-else class="text-xs text-on-surface-variant opacity-40">You</span>
                                </td>
                            </tr>
                            <tr v-if="!users.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant text-sm">
                                    <span class="material-symbols-outlined text-[40px] block mb-2 opacity-30">group</span>
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-surface-container-lowest border-t border-outline-variant flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-on-surface-variant">
                        Showing {{ users.from }}–{{ users.to }} of {{ users.total }} results
                    </span>
                    <div class="flex items-center gap-1 flex-wrap justify-center">
                        <a
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url || undefined"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors"
                            :class="link.active
                                ? 'bg-primary text-on-primary font-semibold'
                                : link.url
                                    ? 'text-on-surface-variant hover:bg-surface-container cursor-pointer'
                                    : 'text-outline opacity-40 pointer-events-none'"
                        >
                            <span v-if="link.label.includes('Previous')" class="material-symbols-outlined text-[18px]">chevron_left</span>
                            <span v-else-if="link.label.includes('Next')" class="material-symbols-outlined text-[18px]">chevron_right</span>
                            <span v-else v-html="link.label" />
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="closeCreate" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-primary text-[20px]">person_add</span>
                            </div>
                            <h2 class="text-base font-bold text-on-surface">Create New User</h2>
                        </div>
                        <button @click="closeCreate" class="p-1.5 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Modal form -->
                    <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                        <!-- Name -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Name</label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                placeholder="Full name"
                                autofocus
                                class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                            />
                            <p v-if="createForm.errors.name" class="text-xs text-error flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>{{ createForm.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Email</label>
                            <input
                                v-model="createForm.email"
                                type="email"
                                placeholder="user@example.com"
                                class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                            />
                            <p v-if="createForm.errors.email" class="text-xs text-error flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>{{ createForm.errors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Password</label>
                            <input
                                v-model="createForm.password"
                                type="password"
                                placeholder="Min. 8 characters"
                                class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                            />
                            <p v-if="createForm.errors.password" class="text-xs text-error flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>{{ createForm.errors.password }}
                            </p>
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Confirm Password</label>
                            <input
                                v-model="createForm.password_confirmation"
                                type="password"
                                placeholder="Repeat password"
                                class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                            />
                        </div>

                        <!-- Role -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Role</label>
                            <select
                                v-model="createForm.role"
                                class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                            >
                                <option value="">Select a role…</option>
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                            <p v-if="createForm.errors.role" class="text-xs text-error flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>{{ createForm.errors.role }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeCreate"
                                class="px-4 py-2.5 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors"
                            >Cancel</button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:opacity-90 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="material-symbols-outlined text-[18px]">person_add</span>
                                {{ createForm.processing ? 'Creating…' : 'Create User' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div v-if="showEdit" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="closeEdit" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-secondary/10 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-secondary text-[20px]">edit</span>
                            </div>
                            <h2 class="text-base font-bold text-on-surface">Edit User</h2>
                        </div>
                        <button @click="closeEdit" class="p-1.5 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>
                    <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Name</label>
                            <input v-model="editForm.name" type="text" placeholder="Full name"
                                class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                            <p v-if="editForm.errors.name" class="text-xs text-error flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>{{ editForm.errors.name }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Email</label>
                            <input v-model="editForm.email" type="email" placeholder="user@example.com"
                                class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                            <p v-if="editForm.errors.email" class="text-xs text-error flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>{{ editForm.errors.email }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">New Password <span class="normal-case font-normal text-on-surface-variant opacity-60">(leave blank to keep current)</span></label>
                            <input v-model="editForm.password" type="password" placeholder="Min. 8 characters"
                                class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                            <p v-if="editForm.errors.password" class="text-xs text-error flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>{{ editForm.errors.password }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Confirm New Password</label>
                            <input v-model="editForm.password_confirmation" type="password" placeholder="Repeat new password"
                                class="w-full px-4 py-2.5 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm text-on-surface placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all" />
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="closeEdit"
                                class="px-4 py-2.5 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
                            <button type="submit" :disabled="editForm.processing"
                                class="flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-semibold hover:opacity-90 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="material-symbols-outlined text-[18px]">save</span>
                                {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div v-if="showDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="closeDelete" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm">
                    <div class="p-6 text-center space-y-4">
                        <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mx-auto">
                            <span class="material-symbols-outlined text-error text-[24px]">person_remove</span>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-on-surface">Delete User?</h2>
                            <p class="text-sm text-on-surface-variant mt-1">
                                <strong>{{ deletingUser?.name }}</strong> will be permanently deleted. Their posts will remain but show as written by a deleted user.
                            </p>
                        </div>
                        <div class="flex gap-3 justify-center pt-2">
                            <button @click="closeDelete"
                                class="px-4 py-2.5 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors">Cancel</button>
                            <button @click="submitDelete"
                                class="flex items-center gap-2 px-4 py-2.5 bg-error text-on-error rounded-xl text-sm font-semibold hover:opacity-90 transition-all active:scale-[0.98]">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                Delete User
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
