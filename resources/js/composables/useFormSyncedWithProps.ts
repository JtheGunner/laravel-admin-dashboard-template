import { watch } from 'vue';

type FormValues = Record<string, unknown>;

/**
 * Keeps an Inertia `useForm` in step with the server record it was seeded from.
 *
 * `useForm(props.item)` only reads the props once, so after a background reload
 * (history back, a partial reload after another action, …) the form would keep
 * showing — and later re-submitting — outdated values. Whenever the source
 * changes, every field the user has not touched since the last sync (its value
 * still equals what was last taken from the server) adopts the new server value;
 * fields with pending user input are left alone.
 */
export function useFormSyncedWithProps<Source>(
    form: FormValues,
    source: () => Source,
    toValues: (source: Source) => FormValues,
): void {
    let lastSynced = toValues(source());

    watch(source, (next) => {
        const nextValues = toValues(next);

        for (const [key, value] of Object.entries(nextValues)) {
            if (JSON.stringify(form[key]) === JSON.stringify(lastSynced[key])) {
                form[key] = value;
            }
        }

        lastSynced = nextValues;
    });
}
