<script lang="ts" setup>
import type { Component } from 'vue';

defineProps<{
    title: string;
    icon: Component;
}>();
</script>

<template>
    <section class="flex flex-col gap-6">
        <div class="flex items-center gap-3">
            <span
                class="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary"
            >
                <component :is="icon" class="size-5" />
            </span>
            <h2 class="text-lg font-semibold text-foreground">
                {{ title }}
            </h2>
            <div class="h-px flex-1 bg-border" />
        </div>

        <slot />
    </section>
</template>
