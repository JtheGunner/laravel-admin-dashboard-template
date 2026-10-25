<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { usePasskeyRegister } from '@laravel/passkeys/vue';
import { KeyRound } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy } from '@/routes/passkey';
import type { PasskeySummary } from '@/types';

defineProps<{
    passkeys: PasskeySummary[];
}>();

const name = ref<string>('');

const { register, isLoading, error, isSupported } = usePasskeyRegister({
    onSuccess: () => {
        name.value = '';
        router.reload({ only: ['passkeys'] });
    },
});

async function submit(): Promise<void> {
    await register(name.value.trim());
}

function remove(passkey: PasskeySummary): void {
    router.delete(destroy(passkey.id).url, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-6">
        <Heading
            description="Sign in without a password using your device's biometrics or a security key"
            title="Passkeys"
            variant="small"
        />

        <p v-if="!isSupported" class="text-sm text-muted-foreground">
            Your browser does not support passkeys.
        </p>

        <form
            v-else
            class="flex max-w-md flex-col gap-2 sm:flex-row"
            @submit.prevent="submit"
        >
            <div class="grid flex-1 gap-2">
                <Input
                    v-model="name"
                    maxlength="255"
                    placeholder="Passkey name, e.g. MacBook Pro"
                    required
                />
                <InputError :message="error ?? undefined" />
            </div>
            <Button :disabled="isLoading || !name.trim()" type="submit">
                Add passkey
            </Button>
        </form>

        <ul v-if="passkeys.length" class="divide-y rounded-lg border">
            <li
                v-for="passkey in passkeys"
                :key="passkey.id"
                class="flex items-center justify-between gap-4 p-3"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <KeyRound class="size-4 shrink-0 text-muted-foreground" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ passkey.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Added {{ passkey.created_at_diff }} · Last used
                            {{ passkey.last_used_at_diff ?? 'never' }}
                        </p>
                    </div>
                </div>
                <Button size="sm" variant="outline" @click="remove(passkey)">
                    Remove
                </Button>
            </li>
        </ul>
    </div>
</template>
