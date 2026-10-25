<script lang="ts" setup>
import { Check } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Wizard', href: '/examples/wizard' }],
    },
});

const steps = ['Account', 'Company', 'Confirm'];
const currentStep = ref(0);

const data = ref({ name: '', email: '', company: '', city: '' });

const isLastStep = computed(() => currentStep.value === steps.length - 1);

const canContinue = computed(() => {
    if (currentStep.value === 0) {
        return data.value.name.trim() !== '' && data.value.email.trim() !== '';
    }

    if (currentStep.value === 1) {
        return data.value.company.trim() !== '';
    }

    return true;
});

function next(): void {
    if (isLastStep.value) {
        toast.success('Wizard completed (demo only, nothing was saved).');
        currentStep.value = 0;

        return;
    }

    currentStep.value++;
}

function back(): void {
    currentStep.value = Math.max(0, currentStep.value - 1);
}
</script>

<template>
    <div class="px-4 pb-4 md:px-6 md:pb-6">
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Multi-step wizard</CardTitle>
                <CardDescription>
                    A stepper pattern for longer create flows.
                </CardDescription>

                <ol class="mt-4 flex items-center gap-3">
                    <li
                        v-for="(step, index) in steps"
                        :key="step"
                        class="flex items-center gap-2 text-sm"
                    >
                        <span
                            :class="
                                index <= currentStep
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground'
                            "
                            class="flex size-7 items-center justify-center rounded-full text-xs font-semibold"
                        >
                            <Check v-if="index < currentStep" class="size-4" />
                            <template v-else>{{ index + 1 }}</template>
                        </span>
                        <span
                            :class="
                                index === currentStep
                                    ? 'font-medium text-foreground'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ step }}
                        </span>
                        <span
                            v-if="index < steps.length - 1"
                            class="h-px w-8 bg-border"
                        />
                    </li>
                </ol>
            </CardHeader>

            <CardContent class="grid gap-4">
                <template v-if="currentStep === 0">
                    <div class="grid gap-2">
                        <Label for="wizard-name">Name</Label>
                        <Input id="wizard-name" v-model="data.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="wizard-email">Email</Label>
                        <Input
                            id="wizard-email"
                            v-model="data.email"
                            type="email"
                        />
                    </div>
                </template>

                <template v-else-if="currentStep === 1">
                    <div class="grid gap-2">
                        <Label for="wizard-company">Company</Label>
                        <Input id="wizard-company" v-model="data.company" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="wizard-city">City</Label>
                        <Input id="wizard-city" v-model="data.city" />
                    </div>
                </template>

                <dl v-else class="grid grid-cols-[8rem_1fr] gap-2 text-sm">
                    <dt class="text-muted-foreground">Name</dt>
                    <dd>{{ data.name }}</dd>
                    <dt class="text-muted-foreground">Email</dt>
                    <dd>{{ data.email }}</dd>
                    <dt class="text-muted-foreground">Company</dt>
                    <dd>{{ data.company }}</dd>
                    <dt class="text-muted-foreground">City</dt>
                    <dd>{{ data.city || '—' }}</dd>
                </dl>
            </CardContent>

            <CardFooter class="justify-between">
                <Button
                    :disabled="currentStep === 0"
                    variant="outline"
                    @click="back"
                >
                    Back
                </Button>
                <Button :disabled="!canContinue" @click="next">
                    {{ isLastStep ? 'Finish' : 'Continue' }}
                </Button>
            </CardFooter>
        </Card>
    </div>
</template>
