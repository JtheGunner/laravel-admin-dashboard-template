<script lang="ts" setup>
import { Form } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/two-factor/login';

defineOptions({
    layout: {
        title: 'Two-factor authentication',
        description: 'Confirm access to your account to continue',
        icon: ShieldCheck,
    },
});

const usingRecoveryCode = ref<boolean>(false);
const code = ref<string>('');

const toggleLabel = computed<string>(() =>
    usingRecoveryCode.value
        ? 'Use an authentication code instead'
        : 'Use a recovery code instead',
);

function toggleMode(clearErrors: () => void): void {
    usingRecoveryCode.value = !usingRecoveryCode.value;
    code.value = '';
    clearErrors();
}
</script>

<template>
    <Form
        v-slot="{ errors, processing, clearErrors }"
        :action="store()"
        class="space-y-6"
        reset-on-error
    >
        <template v-if="!usingRecoveryCode">
            <p class="text-center text-sm text-muted-foreground">
                Enter the 6-digit code from your authenticator app.
            </p>
            <div class="flex flex-col items-center justify-center space-y-3">
                <InputOTP v-model="code" :maxlength="6" autofocus name="code">
                    <InputOTPGroup>
                        <InputOTPSlot
                            v-for="index in 6"
                            :key="index"
                            :index="index - 1"
                        />
                    </InputOTPGroup>
                </InputOTP>
                <InputError :message="errors.code" />
            </div>
        </template>

        <template v-else>
            <p class="text-center text-sm text-muted-foreground">
                Enter one of your emergency recovery codes.
            </p>
            <div class="grid gap-2">
                <Input
                    autofocus
                    name="recovery_code"
                    placeholder="Recovery code"
                    required
                    type="text"
                />
                <InputError :message="errors.recovery_code" />
            </div>
        </template>

        <Button :disabled="processing" class="w-full" type="submit">
            <Spinner v-if="processing" />
            Continue
        </Button>

        <div class="text-center text-sm text-muted-foreground">
            <button
                class="text-foreground underline underline-offset-4"
                type="button"
                @click="toggleMode(clearErrors)"
            >
                {{ toggleLabel }}
            </button>
        </div>
    </Form>
</template>
