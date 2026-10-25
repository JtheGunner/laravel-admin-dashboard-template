<script lang="ts" setup>
import DeleteUser from '@/components/DeleteUser.vue';
import { edit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
                icon: 'settings-2',
            },
        ],
    },
});
</script>

<template>
    <DeleteUser />
</template>
