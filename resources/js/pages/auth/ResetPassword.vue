<script lang="ts" setup>
import { Form } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
        icon: KeyRound,
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Form
        v-slot="{ errors, processing }"
        :reset-on-success="['password', 'password_confirmation']"
        :transform="(data) => ({ ...data, token, email })"
        :action="update()"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">E-Mail-Adresse</Label>
                <Input
                    id="email"
                    v-model="inputEmail"
                    autocomplete="email"
                    class="mt-1 block w-full"
                    name="email"
                    readonly
                    type="email"
                />
                <InputError :message="errors.email" class="mt-2" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    :passwordrules="passwordRules"
                    autocomplete="new-password"
                    autofocus
                    class="mt-1 block w-full"
                    name="password"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation"> Confirm password </Label>
                <PasswordInput
                    id="password_confirmation"
                    :passwordrules="passwordRules"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    name="password_confirmation"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                :disabled="processing"
                class="mt-4 w-full"
                data-test="reset-password-button"
                type="submit"
            >
                <Spinner v-if="processing" />
                Reset password
            </Button>
        </div>
    </Form>
</template>
