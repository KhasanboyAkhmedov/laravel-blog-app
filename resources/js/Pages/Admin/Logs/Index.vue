<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ logs: Object });

const actionColor = (action) => ({
    created: 'bg-green-100 text-green-800',
    updated: 'bg-yellow-100 text-yellow-800',
    deleted: 'bg-red-100 text-red-800',
}[action] ?? 'bg-gray-100 text-gray-800');
</script>

<template>
    <AdminLayout>
        <template #header>Activity Logs</template>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Model</th>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="log in logs.data" :key="log.id">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ new Date(log.created_at).toLocaleString() }}
                        </td>
                        <td class="px-4 py-3">{{ log.user?.name ?? 'Deleted user' }}</td>
                        <td class="px-4 py-3">
                            <span :class="['inline-block px-2 py-0.5 rounded text-xs font-medium', actionColor(log.action)]">
                                {{ log.action }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ log.model.split('\\').pop() }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ log.model_id }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate">
                            {{ JSON.stringify(log.payload) }}
                        </td>
                    </tr>
                    <tr v-if="!logs.data.length">
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">No logs yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex gap-2">
            <Link
                v-for="link in logs.links"
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="px-3 py-1 rounded text-sm border"
                :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300'"
            />
        </div>
    </AdminLayout>
</template>
