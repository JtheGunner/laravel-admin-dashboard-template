<script lang="ts" setup>
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
                icon: 'layout-grid',
            },
        ],
    },
});
</script>

<template>
    <div class="space-y-6">
        <Heading
            description="Update your account's appearance settings"
            title="Appearance settings"
            variant="small"
        />
        <AppearanceTabs />
    </div>
</template>
