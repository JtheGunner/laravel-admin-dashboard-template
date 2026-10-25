import * as LucideIcons from '@lucide/vue';
import * as SimpleIcons from 'simple-icons';

export interface LucideIconEntry {
    name: string;
    componentName: string;
}

export interface SiIcon {
    slug: string;
    title: string;
    path: string;
    hex?: string;
}

function toKebab(s: string): string {
    return s.replace(/([A-Z])/g, (_, c, i) => (i ? '-' : '') + c.toLowerCase());
}

export const ALL_LUCIDE: LucideIconEntry[] = Object.keys(LucideIcons)
    .filter((key) => {
        if (key.startsWith('Lucide') || key.endsWith('Icon')) {
            return false;
        }

        return /^[A-Z]/.test(key);
    })
    .map((key) => ({ name: toKebab(key), componentName: key }))
    .sort((a, b) => a.name.localeCompare(b.name));

export const ALL_SI: SiIcon[] = Object.entries(
    SimpleIcons as Record<string, unknown>,
)
    .filter(([key]) => key.startsWith('si') && key.length > 2)
    .map(([, icon]) => {
        const si = icon as SiIcon;

        return { slug: si.slug, title: si.title, path: si.path, hex: si.hex };
    })
    .sort((a, b) => a.slug.localeCompare(b.slug));

/** Renders a Simple Icon SVG. When `hex` is supplied the icon is filled with its brand colour. */
export function siSvg(path: string, hex?: string): string {
    const fill = hex ? `#${hex}` : 'currentColor';

    return `<svg viewBox="0 0 24 24" fill="${fill}" xmlns="http://www.w3.org/2000/svg"><path d="${path}"/></svg>`;
}

export function siIconBySlug(slug: string): SiIcon | undefined {
    const key = 'si' + slug.charAt(0).toUpperCase() + slug.slice(1);

    return (SimpleIcons as Record<string, unknown>)[key] as SiIcon | undefined;
}
