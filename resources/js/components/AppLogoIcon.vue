<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img alt="Logo" src="/android-icon.png" />
</template>

<style scoped>
img {
    border-radius: 7px;
}
</style>
