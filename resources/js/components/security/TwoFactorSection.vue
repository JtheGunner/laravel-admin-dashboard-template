<script lang="ts" setup>
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import {
    confirm,
    disable,
    enable,
    qrCode,
    recoveryCodes,
    regenerateRecoveryCodes,
    secretKey,
} from '@/routes/two-factor';

type Props = {
    enabled: boolean;
    requiresConfirmation: boolean;
};

type QrCodeResponse = { svg: string; url: string };
type SecretKeyResponse = { secretKey: string };

const props = defineProps<Props>();

const qrCodeSvg = ref<string>('');
const manualSecret = ref<string>('');
const codes = ref<string[]>([]);
const otp = ref<string>('');
const isSettingUp = ref<boolean>(false);

async function fetchJson<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
    }

    return (await response.json()) as T;
}

async function loadSetupDetails(): Promise<void> {
    const [qr, secret] = await Promise.all([
        fetchJson<QrCodeResponse>(qrCode().url),
        fetchJson<SecretKeyResponse>(secretKey().url),
    ]);

    qrCodeSvg.value = qr.svg;
    manualSecret.value = secret.secretKey;
    isSettingUp.value = true;
}

async function loadRecoveryCodes(): Promise<void> {
    codes.value = await fetchJson<string[]>(recoveryCodes().url);
}

async function onEnabled(): Promise<void> {
    await loadSetupDetails();

    if (!props.requiresConfirmation) {
        await loadRecoveryCodes();
        isSettingUp.value = false;
    }
}

async function onConfirmed(): Promise<void> {
    isSettingUp.value = false;
    otp.value = '';
    await loadRecoveryCodes();
}

function onDisabled(): void {
    isSettingUp.value = false;
    qrCodeSvg.value = '';
    manualSecret.value = '';
    codes.value = [];
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                description="Add a second layer of security with a time-based one-time code"
                title="Two-factor authentication"
                variant="small"
            />
            <Badge :variant="enabled ? 'default' : 'secondary'">
                {{ enabled ? 'Enabled' : 'Disabled' }}
            </Badge>
        </div>

        <Form
            v-if="!enabled && !isSettingUp"
            :action="enable()"
            :options="{ preserveScroll: true }"
            @success="onEnabled"
        >
            <Button type="submit">Enable two-factor authentication</Button>
        </Form>

        <div v-if="isSettingUp" class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Scan this QR code with your authenticator app, then enter the
                6-digit code it shows.
            </p>
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div
                class="inline-block rounded-lg bg-white p-3"
                v-html="qrCodeSvg"
            />
            <p class="text-sm text-muted-foreground">
                Can't scan? Enter this key manually:
                <code class="rounded bg-muted px-1.5 py-0.5 font-mono">{{
                    manualSecret
                }}</code>
            </p>

            <Form
                v-slot="{ errors, processing }"
                :action="confirm()"
                :options="{ preserveScroll: true }"
                class="space-y-3"
                @success="onConfirmed"
            >
                <InputOTP v-model="otp" :maxlength="6" name="code">
                    <InputOTPGroup>
                        <InputOTPSlot
                            v-for="index in 6"
                            :key="index"
                            :index="index - 1"
                        />
                    </InputOTPGroup>
                </InputOTP>
                <InputError :message="errors.code" />
                <Button :disabled="processing" type="submit">
                    Confirm and activate
                </Button>
            </Form>
        </div>

        <div v-if="enabled" class="space-y-4">
            <div v-if="codes.length" class="space-y-2">
                <p class="text-sm text-muted-foreground">
                    Store these recovery codes in a safe place. Each one can be
                    used once if you lose access to your authenticator.
                </p>
                <ul
                    class="grid max-w-sm gap-1 rounded-lg bg-muted p-4 font-mono text-sm"
                >
                    <li v-for="recoveryCode in codes" :key="recoveryCode">
                        {{ recoveryCode }}
                    </li>
                </ul>
            </div>

            <div class="flex flex-wrap gap-3">
                <Button
                    v-if="!codes.length"
                    type="button"
                    variant="outline"
                    @click="loadRecoveryCodes"
                >
                    Show recovery codes
                </Button>

                <Form
                    :action="regenerateRecoveryCodes()"
                    :options="{ preserveScroll: true }"
                    @success="loadRecoveryCodes"
                >
                    <Button type="submit" variant="outline">
                        Regenerate recovery codes
                    </Button>
                </Form>

                <Form
                    :action="disable()"
                    :options="{ preserveScroll: true }"
                    @success="onDisabled"
                >
                    <Button type="submit" variant="destructive">
                        Disable
                    </Button>
                </Form>
            </div>
        </div>
    </div>
</template>
