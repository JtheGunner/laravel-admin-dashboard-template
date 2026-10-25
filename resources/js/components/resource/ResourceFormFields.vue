<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import ColorPickerField from '@/components/resource/ColorPickerField.vue';
import HtmlOrTextField from '@/components/resource/HtmlOrTextField.vue';
import IconPickerField from '@/components/resource/IconPickerField.vue';
import RepeaterField from '@/components/resource/RepeaterField.vue';
import RichTextField from '@/components/resource/RichTextField.vue';
import { Checkbox } from '@/components/ui/checkbox';
import FileUpload from '@/components/ui/file-upload/FileUpload.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { FieldConfig } from '@/types/resource';

/**
 * Renders a field grid from a field configuration, bound to an Inertia
 * useForm object. Used by the CRUD dialog (ResourceIndex) and by standalone
 * forms (e.g. "My Profile → General").
 */
defineProps<{
    fields: FieldConfig[];
    // Inertia useForm instance (reactive; carries field values + .errors).
    form: Record<string, any>;
}>();
</script>

<template>
    <!-- The `form` prop is intentionally the reactive Inertia useForm object; -->
    <!-- mutating form[field.name] via v-model is the intended pattern here. -->
    <!-- eslint-disable vue/no-mutating-props -->
    <template v-for="field in fields" :key="field.name">
        <div
            v-if="field.type === 'section'"
            class="col-span-full mt-2 flex items-center gap-3"
        >
            <span
                class="text-xs font-semibold tracking-widest whitespace-nowrap text-primary uppercase"
            >
                {{ field.label }}
            </span>
            <div class="h-px flex-1 bg-border" />
        </div>

        <div
            v-else
            :class="field.colSpan === 2 ? 'col-span-full' : ''"
            class="grid min-w-0 gap-2"
        >
            <template v-if="field.type === 'checkbox'">
                <!-- Invisible label spacer so checkbox fields align with labelled
                 fields in the two-column grid row. Only needed when the field
                 shares a column — a full-width field (colSpan 2) has no neighbour
                 to align with. -->
                <Label
                    v-if="field.colSpan !== 2"
                    aria-hidden="true"
                    class="pointer-events-none opacity-0"
                    >&nbsp;</Label
                >
                <label
                    :class="field.colSpan !== 2 ? 'h-9' : ''"
                    :for="field.name"
                    class="flex cursor-pointer items-center gap-2.5"
                >
                    <Checkbox :id="field.name" v-model="form[field.name]" />
                    <span class="text-sm">{{ field.label }}</span>
                </label>
            </template>

            <template v-else>
                <Label :for="field.name">{{ field.label }}</Label>

                <FileUpload
                    v-if="field.type === 'file' || field.type === 'image'"
                    :id="field.name"
                    v-model="form[field.name]"
                    :accept="
                        field.accept ||
                        (field.type === 'image' ? 'image/*' : undefined)
                    "
                    :label="field.placeholder"
                    :max-size-mb="field.maxSizeMb || 2"
                />

                <div
                    v-else-if="field.type === 'multiselect'"
                    class="overflow-y-auto rounded-lg border border-input bg-white dark:bg-input/30"
                >
                    <label
                        v-for="opt in field.options ?? []"
                        :key="String(opt.value)"
                        :class="{
                            'bg-primary/5': (
                                form[field.name] as unknown[]
                            )?.includes(opt.value),
                        }"
                        class="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm transition-colors hover:bg-muted/50"
                    >
                        <Checkbox
                            :model-value="
                                (form[field.name] as unknown[])?.includes(
                                    opt.value,
                                )
                            "
                            @update:model-value="
                                (checked: boolean | 'indeterminate') => {
                                    const current: unknown[] = Array.isArray(
                                        form[field.name],
                                    )
                                        ? [...form[field.name]]
                                        : [];
                                    form[field.name] = checked
                                        ? [...current, opt.value]
                                        : current.filter(
                                              (v) => v !== opt.value,
                                          );
                                }
                            "
                        />
                        <span>{{ opt.label }}</span>
                    </label>
                    <div
                        v-if="(field.options ?? []).length === 0"
                        class="px-3 py-4 text-center text-sm text-muted-foreground"
                    >
                        No options available.
                    </div>
                </div>

                <Select
                    v-else-if="field.type === 'select'"
                    v-model="form[field.name]"
                    :disabled="field.disabled"
                >
                    <SelectTrigger :id="field.name">
                        <SelectValue
                            :placeholder="field.placeholder ?? 'Please select…'"
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="opt in field.options ?? []"
                            :key="String(opt.value)"
                            :value="String(opt.value)"
                        >
                            {{ opt.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <textarea
                    v-else-if="field.type === 'textarea'"
                    :id="field.name"
                    v-model="form[field.name]"
                    :disabled="field.disabled"
                    :placeholder="field.placeholder"
                    class="flex min-h-24 w-full rounded-lg border border-input bg-white px-4 py-2.5 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                    rows="4"
                />

                <RichTextField
                    v-else-if="field.type === 'richtext'"
                    v-model="form[field.name]"
                />

                <HtmlOrTextField
                    v-else-if="field.type === 'htmlOrText'"
                    v-model="form[field.name]"
                />

                <RepeaterField
                    v-else-if="field.type === 'repeater'"
                    v-model="form[field.name]"
                    :placeholder="field.placeholder"
                />

                <IconPickerField
                    v-else-if="field.type === 'icon'"
                    v-model="form[field.name]"
                />

                <ColorPickerField
                    v-else-if="field.type === 'color'"
                    v-model="form[field.name]"
                />

                <div
                    v-else-if="field.type === 'range'"
                    class="flex items-center gap-3"
                >
                    <input
                        v-model.number="form[field.name]"
                        :max="field.max ?? 100"
                        :min="field.min ?? 0"
                        :step="field.step ?? 1"
                        class="w-full accent-primary"
                        type="range"
                    />
                    <span class="w-12 text-right text-sm tabular-nums">
                        {{ form[field.name] }}
                    </span>
                </div>

                <Input
                    v-else
                    :id="field.name"
                    v-model="form[field.name]"
                    :disabled="field.disabled"
                    :placeholder="field.placeholder"
                    :type="
                        field.type === 'number'
                            ? 'number'
                            : field.type === 'date'
                              ? 'date'
                              : field.type === 'datetime'
                                ? 'datetime-local'
                                : field.type === 'password'
                                  ? 'password'
                                  : 'text'
                    "
                    class="disabled:bg-background dark:disabled:bg-background"
                />
            </template>

            <InputError :message="form.errors[field.name]" />

            <!-- Per-field extension slot, e.g. an action button next to a
             specific field (named `after-<fieldName>`). -->
            <slot :form="form" :name="`after-${field.name}`" />
        </div>
    </template>
</template>
