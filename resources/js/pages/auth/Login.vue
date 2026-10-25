<script lang="ts" setup>
import { Form, router } from '@inertiajs/vue3';
import { usePasskeyVerify } from '@laravel/passkeys/vue';
import { Key, Fingerprint } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Log in',
        description: 'Enter your email and password to log in',
        icon: Key,
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canUsePasskeys?: boolean;
}>();

const {
    verify: verifyPasskey,
    isLoading: isVerifyingPasskey,
    error: passkeyError,
    isSupported: passkeysSupported,
} = usePasskeyVerify({
    onSuccess: (response) => {
        router.visit(response.redirect ?? dashboard().url);
    },
});
</script>

<template>
    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-slot="{ errors, processing }"
        :reset-on-success="['password']"
        class="flex flex-col gap-6"
        :action="store()"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    :tabindex="1"
                    autocomplete="email"
                    autofocus
                    name="email"
                    placeholder="email@example.com"
                    required
                    type="email"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        :tabindex="5"
                        class="text-sm"
                    >
                        Forgot password?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    :tabindex="2"
                    autocomplete="current-password"
                    name="password"
                    placeholder="Password"
                    required
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label class="flex items-center space-x-3" for="remember">
                    <Checkbox id="remember" :tabindex="3" name="remember" />
                    <span>Remember me</span>
                </Label>
            </div>

            <Button
                :disabled="processing"
                :tabindex="4"
                class="mt-4 w-full"
                data-test="login-button"
                type="submit"
            >
                <Spinner v-if="processing" />
                Log in
            </Button>
        </div>

        <div v-if="canUsePasskeys && passkeysSupported" class="grid gap-2">
            <Button
                :disabled="isVerifyingPasskey"
                class="w-full"
                data-test="passkey-login-button"
                type="button"
                variant="outline"
                @click="verifyPasskey"
            >
                <Fingerprint />
                Log in with a passkey
            </Button>
            <InputError :message="passkeyError ?? undefined" />
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Don't have an account?
            <TextLink :href="register()" :tabindex="5">Sign up</TextLink>
        </div>
    </Form>
</template>
