<script setup>
import { reactive, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Admin/Pagination.vue';

const props = defineProps({ users: Object, roles: Array, permissions: Array, filters: Object });

const filter = reactive({ search: props.filters.search || '', status: props.filters.status || '', role: props.filters.role || '' });
const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    status: 'active',
    status_reason: '',
    suspended_until: '',
    role_ids: [],
    permission_ids: [],
});

watch(filter, () => router.get(route('admin.users.index'), filter, { preserveState: true, replace: true }), { deep: true });

const resetForm = () => {
    form.id = null;
    form.name = '';
    form.email = '';
    form.password = '';
    form.status = 'active';
    form.status_reason = '';
    form.suspended_until = '';
    form.role_ids = [];
    form.permission_ids = [];
};
const edit = (user) => {
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.status = user.status;
    form.status_reason = user.status_reason || '';
    form.suspended_until = user.suspended_until || '';
    form.role_ids = user.roles.map((role) => role.id);
    form.permission_ids = user.permissions.map((permission) => permission.id);
};
const save = () => form.id
    ? form.put(route('admin.users.update', form.id), { preserveScroll: true, onSuccess: resetForm })
    : form.post(route('admin.users.store'), { preserveScroll: true, onSuccess: resetForm });
const destroyUser = (user) => {
    if (confirm(`Delete ${user.name}?`)) router.delete(route('admin.users.destroy', user.id), { preserveScroll: true });
};
const postAction = (name, user) => router.post(route(`admin.users.${name}`, user.id), {}, { preserveScroll: true });
</script>

<template>
    <AppLayout title="Users">
        <div class="mb-6 flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Users</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create users, assign roles or direct permissions, and control account access from one table.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input v-model="filter.search" class="rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="Search users">
                <select v-model="filter.status" class="rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="terminated">Terminated</option>
                </select>
                <select v-model="filter.role" class="rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All roles</option>
                    <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                </select>
            </div>
        </div>

        <div class="grid gap-6 2xl:grid-cols-[440px_1fr]">
            <form class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="save">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ form.id ? 'Edit user' : 'Create user' }}</h3>
                <div class="mt-4 grid gap-4">
                    <input v-model="form.name" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950" placeholder="Full name" required>
                    <input v-model="form.email" type="email" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950" placeholder="Email address" required>
                    <input v-model="form.password" type="password" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950" :placeholder="form.id ? 'New password optional' : 'Password'" :required="!form.id">
                    <div class="grid gap-3 md:grid-cols-2">
                        <select v-model="form.status" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                        </select>
                        <input v-model="form.suspended_until" type="datetime-local" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950">
                    </div>
                    <textarea v-model="form.status_reason" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950" rows="2" placeholder="Status reason" />
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase text-gray-500">Roles</p>
                            <div class="max-h-40 space-y-2 overflow-y-auto rounded-md border border-gray-200 p-3 dark:border-gray-800">
                                <label v-for="role in roles" :key="role.id" class="flex items-center gap-2 text-sm">
                                    <input v-model="form.role_ids" type="checkbox" :value="role.id" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    {{ role.name }}
                                </label>
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase text-gray-500">Direct permissions</p>
                            <div class="max-h-40 space-y-2 overflow-y-auto rounded-md border border-gray-200 p-3 dark:border-gray-800">
                                <label v-for="permission in permissions" :key="permission.id" class="flex items-center gap-2 text-sm">
                                    <input v-model="form.permission_ids" type="checkbox" :value="permission.id" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    {{ permission.name }}
                                </label>
                            </div>
                        </div>
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
                        <tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Access</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ user.name }}<p class="text-xs font-normal text-gray-500">{{ user.email }}</p></td>
                            <td class="px-4 py-3 text-xs">
                                <p>Roles: {{ user.roles.map((role) => role.name).join(', ') || 'None' }}</p>
                                <p class="text-gray-500">Direct: {{ user.permissions.map((permission) => permission.name).join(', ') || 'None' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="user.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : user.status === 'suspended' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300'">{{ user.status }}</span>
                                <p class="mt-1 text-xs text-gray-500">{{ user.status_reason }}</p>
                            </td>
                            <td class="space-x-3 px-4 py-3 text-right">
                                <button class="text-blue-600 dark:text-blue-300" @click="edit(user)">Edit</button>
                                <button class="text-amber-600 dark:text-amber-300" @click="postAction('suspend', user)">Suspend</button>
                                <button class="text-emerald-600 dark:text-emerald-300" @click="postAction('activate', user)">Activate</button>
                                <button class="text-red-600 dark:text-red-300" @click="postAction('terminate', user)">Terminate</button>
                                <button class="text-gray-500 dark:text-gray-400" @click="destroyUser(user)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t border-gray-200 p-4 dark:border-gray-800"><Pagination :links="users.links" /></div>
            </div>
        </div>
    </AppLayout>
</template>
