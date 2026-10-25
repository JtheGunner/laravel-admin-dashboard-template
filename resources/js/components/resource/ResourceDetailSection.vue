<script lang="ts" setup>
import { Pencil } from '@lucide/vue';
import AppTooltip from '@/components/AppTooltip.vue';
import ResourceFieldValue from '@/components/resource/ResourceFieldValue.vue';
import ResourceFormFields from '@/components/resource/ResourceFormFields.vue';
import { Button } from '@/components/ui/button';
import { useCopy } from '@/composables/useCopy';
import { fieldCopyableText, fieldHasCopyableContent } from '@/lib/resourceCell';
import type { FieldConfig, ResourceRow } from '@/types/resource';

const props = withDefaults(
    defineProps<{
        /** The section's FieldConfig (type: 'section'). Provides the card label. */
        section: FieldConfig;
        /** Non-section fields belonging to this card. */
        fields: FieldConfig[];
        /** The record whose values are displayed. */
        row: ResourceRow;
        /** Inertia useForm instance — required when editable is true. */
        form?: Record<string, any>;
        /** Whether this section is currently in edit mode. */
        editing: boolean;
        /** Whether a save is in progress (disables the save button). */
        saving: boolean;
        /** When false, the edit button is hidden. Default: true. */
        editable?: boolean;
    }>(),
    { editable: true },
);

const emit = defineEmits<{
    edit: [];
    save: [];
    cancel: [];
}>();

// Only render fields that have a value in read mode (skip empty optional fields).
// Always render all fields in edit mode.
const visibleReadFields = props.fields.filter((f) => f.type !== 'section');

const { copyText } = useCopy();
</script>

<template>
    <section
        :id="'section-' + section.name"
        class="@container overflow-clip rounded-2xl border border-border bg-card shadow-card"
    >
        <!-- Card header: actions wrap below the title when the card gets too narrow -->
        <div
            class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-border px-6 py-4"
        >
            <div class="flex min-w-0 items-center gap-2">
                <!-- Icon from FieldConfig.sectionIcon or injected via slot -->
                <component
                    :is="section.sectionIcon"
                    v-if="section.sectionIcon"
                    class="size-5 shrink-0 text-muted-foreground"
                />
                <slot name="icon" />
                <h3
                    class="min-w-0 text-base font-semibold break-words text-foreground"
                >
                    {{ section.label }}
                </h3>
            </div>

            <!-- Actions never exceed the card width; button labels (<span class="truncate">) ellipsize instead -->
            <div
                class="ml-auto flex max-w-full min-w-0 flex-wrap items-center justify-end gap-2 *:max-w-full *:min-w-0"
            >
                <!-- Action buttons always shown in the section header (e.g. AI generation) -->
                <slot name="headerActions" />

                <Button
                    v-if="!editing && editable"
                    class="border-primary/25 bg-primary/8 text-primary hover:bg-primary/15 hover:text-primary"
                    size="sm"
                    variant="outline"
                    @click="emit('edit')"
                >
                    <Pencil class="size-4" />
                    <span class="truncate">Edit</span>
                </Button>
            </div>
        </div>

        <!-- Body wrapper: applies maxHeight + scroll when configured -->
        <div
            :class="section.maxHeight ? 'overflow-y-auto' : ''"
            :style="section.maxHeight ? { maxHeight: section.maxHeight } : {}"
        >
            <!-- Custom body slot: replaces the standard dl/form entirely -->
            <template v-if="$slots.customBody">
                <slot name="customBody" :editing="editing" />
            </template>

            <!-- Standard read/edit view -->
            <template v-else>
                <!-- Optional content before the read/edit area (e.g. read-only research facts) -->
                <slot name="prependBody" />

                <!-- Read view: dl grid, two columns once the card itself is wide enough -->
                <dl
                    v-if="!editing"
                    class="grid grid-cols-1 gap-x-6 gap-y-5 px-6 py-5 @md:grid-cols-2"
                >
                    <template
                        v-for="field in visibleReadFields"
                        :key="field.name"
                    >
                        <div
                            :class="field.colSpan === 2 ? 'col-span-full' : ''"
                            class="min-w-0"
                        >
                            <dt class="text-xs text-muted-foreground">
                                {{ field.label }}
                            </dt>
                            <dd
                                class="mt-0.5 text-sm font-medium break-words text-foreground"
                            >
                                <AppTooltip
                                    :text="
                                        fieldHasCopyableContent(row, field)
                                            ? 'Double-click to copy'
                                            : ''
                                    "
                                >
                                    <span
                                        :class="
                                            fieldHasCopyableContent(row, field)
                                                ? 'cursor-copy'
                                                : ''
                                        "
                                        class="inline-block max-w-full align-top"
                                        @dblclick.stop="
                                            fieldHasCopyableContent(
                                                row,
                                                field,
                                            ) &&
                                            copyText(
                                                fieldCopyableText(row, field),
                                                $event.currentTarget as HTMLElement,
                                            )
                                        "
                                    >
                                        <ResourceFieldValue
                                            :field="field"
                                            :row="row"
                                        />
                                    </span>
                                </AppTooltip>
                            </dd>
                        </div>
                    </template>

                    <p
                        v-if="visibleReadFields.length === 0"
                        class="col-span-full text-sm text-muted-foreground italic"
                    >
                        No details provided.
                    </p>
                </dl>

                <!-- Edit view: ResourceFormFields -->
                <div v-else class="px-6 py-5">
                    <div
                        class="grid auto-rows-min grid-cols-1 items-start gap-x-6 gap-y-5 @md:grid-cols-2"
                    >
                        <ResourceFormFields :fields="fields" :form="form!" />
                    </div>

                    <!-- Slot for extra content inside edit mode (e.g. a custom helper button) -->
                    <slot name="editExtra" />
                </div>
            </template>
        </div>

        <!-- Save / Cancel — shown below body when in edit mode -->
        <div
            v-if="editing"
            class="flex justify-end gap-3 border-t border-border px-6 pt-4 pb-5"
        >
            <Button type="button" variant="outline" @click="emit('cancel')">
                Cancel
            </Button>
            <Button :loading="saving" type="button" @click="emit('save')">
                Save
            </Button>
        </div>

        <!-- Slot for extra content always visible below the card body -->
        <slot name="footer" />
    </section>
</template>
