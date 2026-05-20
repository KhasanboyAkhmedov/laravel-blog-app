<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ logs: Object });

const actionConfig = (action) => ({
    created: { label: 'Created', icon: 'add_circle', classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    updated: { label: 'Updated', icon: 'edit', classes: 'bg-amber-50 text-amber-700 border-amber-200' },
    deleted: { label: 'Deleted', icon: 'delete', classes: 'bg-error-container/60 text-error border-error/20' },
}[action] ?? { label: action, icon: 'info', classes: 'bg-surface-container text-on-surface-variant border-outline-variant' });

// Payload modal
const payloadLog = ref(null);
const openPayload = (log) => { payloadLog.value = log; };
const closePayload = () => { payloadLog.value = null; };
const copyPayload = () => {
    navigator.clipboard.writeText(JSON.stringify(payloadLog.value?.payload, null, 2));
};

const modelName = (model) => model ? model.split('\\').pop() : '—';
</script>

<template>
    <AdminLayout>
        <template #header>
            <nav class="flex items-center gap-2 text-xs text-on-surface-variant">
                <span>Console</span>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-semibold">System Logs</span>
            </nav>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page title -->
            <div class="flex items-end justify-between">
                <h2 class="text-2xl font-bold text-on-surface tracking-tight">System Logs</h2>
                <div class="flex items-center gap-2 px-4 py-2 bg-surface-container rounded-xl border border-outline-variant">
                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant">history</span>
                    <span class="text-xs text-on-surface-variant">Audit trail</span>
                </div>
            </div>

            <!-- Stats bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2 bg-white border border-outline-variant rounded-xl p-4 flex items-center gap-6">
                    <div>
                        <p class="text-[11px] text-on-surface-variant uppercase tracking-widest opacity-60">Total Events</p>
                        <p class="text-xl font-bold text-on-surface mt-0.5">{{ logs.total }}</p>
                    </div>
                    <div class="w-px h-8 bg-outline-variant"></div>
                    <div>
                        <p class="text-[11px] text-on-surface-variant uppercase tracking-widest opacity-60">This Page</p>
                        <p class="text-xl font-bold text-on-surface mt-0.5">{{ logs.data.length }}</p>
                    </div>
                </div>
                <div class="bg-primary text-on-primary rounded-xl p-4 flex flex-col justify-between relative overflow-hidden">
                    <p class="text-xs opacity-80">Current Page</p>
                    <p class="text-lg font-bold">{{ logs.current_page }} / {{ logs.last_page }}</p>
                    <div class="absolute -right-3 -bottom-3 w-20 h-20 bg-white/10 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead class="bg-surface-container-low border-b border-outline-variant">
                            <tr>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold">When</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold">User</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold">Action</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold hidden md:table-cell">Model</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold hidden lg:table-cell">ID</th>
                                <th class="px-6 py-4 text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold text-right">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-surface-container transition-colors group"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs text-on-surface-variant">
                                        {{ new Date(log.created_at).toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                                    </span>
                                    <br>
                                    <span class="text-[10px] text-outline">
                                        {{ new Date(log.created_at).toLocaleTimeString('en', { hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold text-on-secondary-container flex-shrink-0">
                                            {{ log.user?.name ? log.user.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() : '?' }}
                                        </div>
                                        <span class="text-sm text-on-surface">{{ log.user?.name ?? 'Deleted user' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold border', actionConfig(log.action).classes]">
                                        <span class="material-symbols-outlined text-[13px]">{{ actionConfig(log.action).icon }}</span>
                                        {{ actionConfig(log.action).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <span class="text-xs text-on-surface-variant font-mono bg-surface-container px-2 py-0.5 rounded-lg">{{ modelName(log.model) }}</span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <span class="text-xs text-on-surface-variant">#{{ log.model_id }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        v-if="log.payload && Object.keys(log.payload).length"
                                        @click="openPayload(log)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-[11px] font-medium rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors opacity-60 group-hover:opacity-100"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">data_object</span>
                                        View
                                    </button>
                                    <span v-else class="text-xs text-outline opacity-40">—</span>
                                </td>
                            </tr>
                            <tr v-if="!logs.data.length">
                                <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant text-sm">
                                    <span class="material-symbols-outlined text-[40px] block mb-2 opacity-30">receipt_long</span>
                                    No log entries yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-surface-container-lowest border-t border-outline-variant flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-on-surface-variant">
                        Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }} results
                    </span>
                    <div class="flex items-center gap-1 flex-wrap justify-center">
                        <Link
                            v-for="link in logs.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-xs transition-colors"
                            :class="link.active
                                ? 'bg-primary text-on-primary font-semibold'
                                : link.url
                                    ? 'text-on-surface-variant hover:bg-surface-container'
                                    : 'text-outline opacity-40 pointer-events-none'"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Payload Modal -->
        <Teleport to="body">
            <div v-if="payloadLog" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-sm" @click="closePayload" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-surface-container-high flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-on-surface-variant text-[20px]">data_object</span>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-on-surface">Payload</h2>
                                <p class="text-[10px] text-on-surface-variant mt-0.5 capitalize">
                                    {{ payloadLog.action }} · {{ modelName(payloadLog.model) }} #{{ payloadLog.model_id }}
                                </p>
                            </div>
                        </div>
                        <button @click="closePayload" class="p-1.5 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- JSON block -->
                    <div class="p-4">
                        <div class="bg-zinc-900 rounded-xl overflow-auto max-h-96">
                            <div class="flex items-center justify-between px-4 py-2.5 border-b border-zinc-700">
                                <span class="text-[10px] text-zinc-400 font-mono uppercase tracking-widest">JSON</span>
                                <button
                                    @click="copyPayload"
                                    class="flex items-center gap-1 text-[11px] text-zinc-400 hover:text-white transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[14px]">content_copy</span>
                                    Copy JSON
                                </button>
                            </div>
                            <pre class="px-4 py-4 text-xs text-emerald-300 font-mono leading-relaxed overflow-x-auto whitespace-pre">{{ JSON.stringify(payloadLog.payload, null, 2) }}</pre>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 pb-5 flex justify-end">
                        <button
                            @click="closePayload"
                            class="px-4 py-2.5 text-sm rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors"
                        >Close</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
