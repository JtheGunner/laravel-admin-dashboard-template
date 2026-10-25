<script lang="ts" setup>
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <svg
        aria-label="Logo"
        role="img"
        viewBox="0 0 32 32"
        xmlns="http://www.w3.org/2000/svg"
    >
        <rect fill="#465fff" height="32" rx="7" width="32" />
        <rect fill="#fff" height="8" rx="2" width="8" x="7" y="7" />
        <rect
            fill="#fff"
            height="8"
            opacity=".7"
            rx="2"
            width="8"
            x="17"
            y="7"
        />
        <rect
            fill="#fff"
            height="8"
            opacity=".7"
            rx="2"
            width="8"
            x="7"
            y="17"
        />
        <rect fill="#fff" height="8" rx="2" width="8" x="17" y="17" />
    </svg>
</template>
