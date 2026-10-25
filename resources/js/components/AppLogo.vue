<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const appName = computed(() => page.props.name);
</script>

<template>
    <div
        class="app-logo-container flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-white" />
    </div>
    <div
        class="ml-1 grid flex-1 text-left text-sm group-data-[collapsible=icon]:hidden"
    >
        <span class="mb-0 truncate leading-tight font-semibold">{{
            appName
        }}</span>
    </div>
</template>

<style scoped>
div {
    .app-logo-container,
    img {
        width: 40px;
        height: 40px;
    }
}
</style>
