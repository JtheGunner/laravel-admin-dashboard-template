<script lang="ts" setup>
import { Form } from '@inertiajs/vue3';
import { UsersIcon } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
        icon: UsersIcon,
    },
});
</script>

<template>
    <Form
        v-slot="{ errors, processing }"
        :reset-on-success="['password', 'password_confirmation']"
        class="flex flex-col gap-6"
        :action="store()"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    :tabindex="1"
                    autocomplete="name"
                    autofocus
                    name="name"
                    placeholder="Full name"
                    required
                    type="text"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                    required
                    type="email"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    :passwordrules="passwordRules"
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    required
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    :passwordrules="passwordRules"
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    required
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                :disabled="processing"
                class="mt-2 w-full"
                data-test="register-user-button"
                tabindex="5"
                type="submit"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                :tabindex="6"
                class="underline underline-offset-4"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
