<script lang="ts" setup>
import { computed, ref } from 'vue';
import ResourceDetailSection from '@/components/resource/ResourceDetailSection.vue';
import type { FieldConfig, ResourceRow } from '@/types/resource';

interface ParsedSection {
    section: FieldConfig;
    fields: FieldConfig[];
}

const props = withDefaults(
    defineProps<{
        /**
         * Base field config (same shape as passed to ResourceIndex). Used for the
         * read-only display. Sections are parsed from `type: 'section'` entries.
         */
        fields: FieldConfig[];
        /**
         * Edit-mode field config — may include additional sections (e.g.
         * an extra edit-only section) and disabled overrides. When omitted,
         * `fields` is used for editing too.
         */
        editFields?: FieldConfig[];
        /** The record to display. */
        row: ResourceRow;
        /** Inertia useForm instance (required when any section is editable). */
        form?: Record<string, any>;
        /** Whether a save is in progress. Passed down to the active section. */
        saving?: boolean;
        /** When false, no section shows an edit button. Default: true. */
        editable?: boolean;
    }>(),
    { editable: true },
);

const emit = defineEmits<{
    /** Emitted when the user clicks Save inside a section. */
    save: [sectionName: string];
    /** Emitted when the user clicks Cancel inside a section. */
    cancel: [sectionName: string];
}>();

/** Parse a flat FieldConfig[] into sections, splitting at type === 'section'. */
function parseSections(fields: FieldConfig[]): ParsedSection[] {
    const result: ParsedSection[] = [];
    let currentSection: FieldConfig | null = null;
    let currentFields: FieldConfig[] = [];

    for (const field of fields) {
        if (field.type === 'section') {
            if (currentSection !== null) {
                result.push({ section: currentSection, fields: currentFields });
            }

            currentSection = field;
            currentFields = [];
        } else {
            currentFields.push(field);
        }
    }

    if (currentSection !== null) {
        result.push({ section: currentSection, fields: currentFields });
    }

    return result;
}

/** Sections derived from the display (read-only) field config. */
const displaySections = computed<ParsedSection[]>(() =>
    parseSections(props.fields),
);

/**
 * Sections derived from the edit field config, indexed by section name for
 * fast lookup. Falls back to displaySections when editFields is not provided.
 */
const editSectionMap = computed<Map<string, ParsedSection>>(() => {
    const map = new Map<string, ParsedSection>();
    const parsed = parseSections(props.editFields ?? props.fields);

    for (const s of parsed) {
        map.set(s.section.name, s);
    }

    return map;
});

/** Which section is currently in edit mode (by section.name). */
const editingSection = ref<string | null>(null);

function startEditing(sectionName: string): void {
    editingSection.value = sectionName;
}

function handleSave(sectionName: string): void {
    emit('save', sectionName);
}

function handleCancel(sectionName: string): void {
    emit('cancel', sectionName);
    editingSection.value = null;
}

/** Called by Show.vue after a successful save to clear the editing state. */
function clearEditing(): void {
    editingSection.value = null;
}

defineExpose({ clearEditing, startEditing });
</script>

<template>
    <div
        class="grid grid-cols-1 gap-5 px-4 pb-4 md:grid-cols-2 md:px-6 md:pb-6"
    >
        <template v-for="parsed in displaySections" :key="parsed.section.name">
            <ResourceDetailSection
                :class="parsed.section.colSpan === 2 ? 'md:col-span-2' : ''"
                :editing="editingSection === parsed.section.name"
                :editable="
                    parsed.section.sectionEditable !== undefined
                        ? parsed.section.sectionEditable
                        : editable
                "
                :fields="
                    editingSection === parsed.section.name
                        ? (editSectionMap.get(parsed.section.name)?.fields ??
                          parsed.fields)
                        : parsed.fields
                "
                :form="form"
                :row="row"
                :saving="saving ?? false"
                :section="parsed.section"
                @cancel="handleCancel(parsed.section.name)"
                @edit="startEditing(parsed.section.name)"
                @save="handleSave(parsed.section.name)"
            >
                <!-- Forward named slots to the correct section via dynamic slot names -->
                <template v-if="$slots[parsed.section.name + '-icon']" #icon>
                    <slot :name="parsed.section.name + '-icon'" />
                </template>
                <template
                    v-if="$slots[parsed.section.name + '-headerActions']"
                    #headerActions
                >
                    <slot :name="parsed.section.name + '-headerActions'" />
                </template>
                <template
                    v-if="$slots[parsed.section.name + '-prependBody']"
                    #prependBody
                >
                    <slot :name="parsed.section.name + '-prependBody'" />
                </template>
                <template
                    v-if="$slots[parsed.section.name + '-editExtra']"
                    #editExtra
                >
                    <slot :name="parsed.section.name + '-editExtra'" />
                </template>
                <template
                    v-if="$slots[parsed.section.name + '-footer']"
                    #footer
                >
                    <slot :name="parsed.section.name + '-footer'" />
                </template>
                <template
                    v-if="$slots[parsed.section.name + '-customBody']"
                    #customBody="{ editing }"
                >
                    <slot
                        :name="parsed.section.name + '-customBody'"
                        :editing="editing"
                    />
                </template>
            </ResourceDetailSection>
        </template>
    </div>
</template>
