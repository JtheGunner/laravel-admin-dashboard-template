<script lang="ts" setup>
import { Form } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import PasskeySection from '@/components/security/PasskeySection.vue';
import TwoFactorSection from '@/components/security/TwoFactorSection.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';
import type { PasskeySummary } from '@/types';

type Props = {
    passwordRules: string;
    canManageTwoFactor: boolean;
    canManagePasskeys: boolean;
    passkeys: PasskeySummary[];
    twoFactorEnabled?: boolean;
    requiresConfirmation?: boolean;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
                icon: 'lock-keyhole',
            },
        ],
    },
});
</script>

<template>
    <div class="space-y-6">
        <Heading
            description="Ensure your account is using a long, random password to stay secure"
            title="Update password"
            variant="small"
        />

        <Form
            v-slot="{ errors, processing }"
            :options="{
                preserveScroll: true,
            }"
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-6"
            reset-on-success
            :action="SecurityController.update()"
        >
            <div class="grid gap-2">
                <Label for="current_password">Current password</Label>
                <PasswordInput
                    id="current_password"
                    autocomplete="current-password"
                    class="mt-1 block w-full"
                    name="current_password"
                    placeholder="Current password"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <PasswordInput
                    id="password"
                    :passwordrules="props.passwordRules"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    name="password"
                    placeholder="New password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    :passwordrules="props.passwordRules"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    name="password_confirmation"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    Save
                </Button>
            </div>
        </Form>

        <TwoFactorSection
            v-if="props.canManageTwoFactor"
            :enabled="props.twoFactorEnabled ?? false"
            :requires-confirmation="props.requiresConfirmation ?? false"
        />

        <PasskeySection
            v-if="props.canManagePasskeys"
            :passkeys="props.passkeys"
        />
    </div>
</template>
