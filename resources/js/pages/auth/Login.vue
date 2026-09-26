<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LoginForm from '@/components/forms/LoginForm.vue';

defineOptions({
    layout: {
        title: 'Realize o login na sua conta',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>

    <Head title="Log in" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <LoginForm :canResetPassword="canResetPassword" />
</template>
