<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ callback: Object });
</script>

<template>
    <AdminLayout title="M-Pesa Callback">
        <pre class="overflow-auto rounded-lg bg-gray-100 p-4 text-xs dark:bg-[#0d111a]">{{ callback }}</pre>
    </AdminLayout>
</template>

