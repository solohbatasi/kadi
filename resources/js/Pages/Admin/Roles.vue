<script setup>
import { reactive, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ roles: Object, permissions: Array, filters: Object });

const filter = reactive({ search: props.filters.search || '' });
const form = useForm({ id: null, name: '', description: '', permission_ids: [] });

watch(filter, () => router.get(route('admin.roles.index'), filter, { preserveState: true, replace: true }), { deep: true });

const resetForm = () => {
    form.id = null;
    form.name = '';
    form.description = '';
    form.permission_ids = [];
};
const edit = (role) => {
    form.id = role.id;
    form.name = role.name;
    form.description = role.description || '';
    form.permission_ids = role.permissions.map((permission) => permission.id);
};
const save = () => form.id
    ? form.put(route('admin.roles.update', form.id), { preserveScroll: true, onSuccess: resetForm })
    : form.post(route('admin.roles.store'), { preserveScroll: true, onSuccess: resetForm });
const destroyRole = (role) => {
    if (confirm(`Delete ${role.name}?`)) router.delete(route('admin.roles.destroy', role.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Roles">
        <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Roles</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bundle permissions into operational roles for administrators, support, billing, and field teams.</p>
            </div>
            <input v-model="filter.search" class="rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="Search roles">
        </div>

        <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
            <form class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="save">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ form.id ? 'Edit role' : 'Create role' }}</h3>
                <div class="mt-4 space-y-4">
                    <input v-model="form.name" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950" placeholder="Administrator" required>
                    <textarea v-model="form.description" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950" rows="3" placeholder="Role purpose" />
                    <div class="max-h-56 space-y-2 overflow-y-auto rounded-md border border-gray-200 p-3 dark:border-gray-800">
                        <label v-for="permission in permissions" :key="permission.id" class="flex items-center gap-2 text-sm">
                            <input v-model="form.permission_ids" type="checkbox" :value="permission.id" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span>{{ permission.name }}</span>
                            <span class="text-xs text-gray-400">{{ permission.group }}</span>
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-violet-600 dark:hover:bg-violet-500" :disabled="form.processing">Save</button>
                        <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm dark:border-gray-700" @click="resetForm">Clear</button>
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                        <tr><th class="px-4 py-3">Role</th><th class="px-4 py-3">Permissions</th><th class="px-4 py-3">Users</th><th class="px-4 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="role in roles.data" :key="role.id">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ role.name }}<p class="text-xs font-normal text-gray-500">{{ role.description }}</p></td>
                            <td class="px-4 py-3"><span class="text-xs">{{ role.permissions.map((permission) => permission.name).join(', ') || 'No permissions' }}</span></td>
                            <td class="px-4 py-3">{{ role.users_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <button class="mr-3 text-blue-600 dark:text-blue-300" @click="edit(role)">Edit</button>
                                <button class="text-red-600 dark:text-red-300" @click="destroyRole(role)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t border-gray-200 p-4 dark:border-gray-800"><Pagination :links="roles.links" /></div>
            </div>
        </div>
    </AppLayout>
</template>
