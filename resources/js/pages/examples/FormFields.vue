<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import ResourceFormFields from '@/components/resource/ResourceFormFields.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { FieldConfig } from '@/types/resource';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Form fields', href: '/examples/form-fields' }],
    },
});

// Every field type supported by the generic form renderer. The same FieldConfig[]
// declaration drives the create/edit dialog of every table.
const fields: FieldConfig[] = [
    { name: 'section_text', label: 'Text input', type: 'section' },
    { name: 'text', label: 'Text', placeholder: 'Plain text' },
    { name: 'password', label: 'Password', type: 'password' },
    { name: 'number', label: 'Number', type: 'number' },
    { name: 'date', label: 'Date', type: 'date' },
    { name: 'datetime', label: 'Date and time', type: 'datetime' },
    { name: 'textarea', label: 'Textarea', type: 'textarea', colSpan: 2 },
    { name: 'section_choice', label: 'Choices', type: 'section' },
    {
        name: 'select',
        label: 'Select',
        type: 'select',
        options: [
            { value: 'one', label: 'Option one' },
            { value: 'two', label: 'Option two' },
        ],
    },
    {
        name: 'multiselect',
        label: 'Multiselect',
        type: 'multiselect',
        options: [
            { value: 'a', label: 'Alpha' },
            { value: 'b', label: 'Beta' },
            { value: 'c', label: 'Gamma' },
        ],
    },
    { name: 'checkbox', label: 'Checkbox', type: 'checkbox' },
    {
        name: 'range',
        label: 'Range slider',
        type: 'range',
        min: 0,
        max: 10,
        step: 1,
    },
    { name: 'section_special', label: 'Special inputs', type: 'section' },
    { name: 'icon', label: 'Icon picker', type: 'icon' },
    { name: 'color', label: 'Color picker', type: 'color' },
    { name: 'image', label: 'Image upload', type: 'image' },
    { name: 'file', label: 'File upload', type: 'file' },
    {
        name: 'repeater',
        label: 'Repeater (list of strings)',
        type: 'repeater',
        colSpan: 2,
    },
    {
        name: 'richtext',
        label: 'Rich text editor',
        type: 'richtext',
        colSpan: 2,
    },
];

const form = useForm({
    text: '',
    password: '',
    number: null,
    date: '',
    datetime: '',
    textarea: '',
    select: null,
    multiselect: [],
    checkbox: false,
    range: 5,
    icon: '',
    color: '',
    image: null,
    file: null,
    repeater: ['First entry'],
    richtext: '<p>Edit <strong>me</strong>.</p>',
});

function showSubmittedValues(): void {
    toast.success('The form is a demo and is not submitted to the server.');
}
</script>

<template>
    <div class="px-4 pb-4 md:px-6 md:pb-6">
        <Card class="max-w-4xl">
            <CardHeader>
                <CardTitle>Form fields</CardTitle>
                <CardDescription>
                    All field types the generic form renderer supports.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    class="grid gap-5 sm:grid-cols-2"
                    @submit.prevent="showSubmittedValues"
                >
                    <ResourceFormFields :fields="fields" :form="form" />
                    <div class="col-span-full flex justify-end">
                        <Button type="submit">Submit</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
