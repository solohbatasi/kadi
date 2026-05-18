<script setup>
import { reactive, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ permissions: Object, groups: Array, filters: Object });

const filter = reactive({ search: props.filters.search || '', group: props.filters.group || '' });
const form = useForm({ id: null, name: '', group: '', description: '' });

watch(filter, () => {
    router.get(route('admin.permissions.index'), filter, { preserveState: true, replace: true });
}, { deep: true });

const resetForm = () => {
    form.id = null;
    form.name = '';
    form.group = '';
    form.description = '';
};
const edit = (permission) => {
    form.id = permission.id;
    form.name = permission.name;
    form.group = permission.group || '';
    form.description = permission.description || '';
};
const save = () => form.id
    ? form.put(route('admin.permissions.update', form.id), { preserveScroll: true, onSuccess: resetForm })
    : form.post(route('admin.permissions.store'), { preserveScroll: true, onSuccess: resetForm });
const destroyPermission = (permission) => {
    if (confirm(`Delete ${permission.name}?`)) router.delete(route('admin.permissions.destroy', permission.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Permissions">
        <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Permissions</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Define exact capabilities for roles, direct user overrides, and API-aware administration.</p>
            </div>
            <div class="flex gap-2">
                <input v-model="filter.search" class="rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="Search permissions">
                <select v-model="filter.group" class="rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All groups</option>
                    <option v-for="group in groups" :key="group" :value="group">{{ group }}</option>
                </select>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[380px_1fr]">
            <form class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="save">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ form.id ? 'Edit permission' : 'Create permission' }}</h3>
                <div class="mt-4 space-y-4">
                    <input v-model="form.name" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950" placeholder="users.update" required>
                    <input v-model="form.group" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950" placeholder="Group, e.g. users">
                    <textarea v-model="form.description" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950" rows="3" placeholder="What this permission controls" />
                    <div class="flex gap-2">
                        <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-violet-600 dark:hover:bg-violet-500" :disabled="form.processing">Save</button>
                        <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm dark:border-gray-700" @click="resetForm">Clear</button>
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                        <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Group</th><th class="px-4 py-3">Usage</th><th class="px-4 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="permission in permissions.data" :key="permission.id">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ permission.name }}<p class="text-xs font-normal text-gray-500">{{ permission.description }}</p></td>
                            <td class="px-4 py-3">{{ permission.group || 'General' }}</td>
                            <td class="px-4 py-3">{{ permission.roles_count }} roles, {{ permission.users_count }} users</td>
                            <td class="px-4 py-3 text-right">
                                <button class="mr-3 text-blue-600 dark:text-blue-300" @click="edit(permission)">Edit</button>
                                <button class="text-red-600 dark:text-red-300" @click="destroyPermission(permission)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t border-gray-200 p-4 dark:border-gray-800"><Pagination :links="permissions.links" /></div>
            </div>
        </div>
    </AppLayout>
</template>
