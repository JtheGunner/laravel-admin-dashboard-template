<script lang="ts" setup>
import { Form } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Confirm your password',
        description:
            'This is a secure area of the application. Please confirm your password before continuing.',
        icon: KeyRound,
    },
});
</script>

<template>
    <Form v-slot="{ errors, processing }" reset-on-success :action="store()">
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">Password</Label>
                <PasswordInput
                    id="password"
                    autocomplete="current-password"
                    autofocus
                    class="mt-1 block w-full"
                    name="password"
                    required
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    :disabled="processing"
                    class="w-full"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    Confirm password
                </Button>
            </div>
        </div>
    </Form>
</template>
