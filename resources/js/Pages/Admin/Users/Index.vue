<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    roles: Array,
});

const updateRole = (userId, role) => {
    router.patch(route('admin.users.update-role', userId), { role });
};
</script>

<template>
    <AdminLayout>
        <template #header>User Management</template>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Change Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <span
                                v-for="role in user.roles"
                                :key="role.id"
                                class="inline-block px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs"
                            >{{ role.name }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <select
                                @change="updateRole(user.id, $event.target.value)"
                                class="text-sm border-gray-300 rounded"
                            >
                                <option value="">-- assign role --</option>
                                <option
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.name"
                                    :selected="user.roles.some(r => r.name === role.name)"
                                >{{ role.name }}</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
