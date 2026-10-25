import {
    Activity,
    BugOff,
    Clock,
    FormInput,
    Globe,
    Layers,
    LayoutGrid,
    LayoutTemplate,
    Package,
    Palette,
    ScrollText,
    ShoppingCart,
    Tags,
    Telescope,
    UserCog,
    Users,
    UsersRound,
    Wand2,
    Wrench,
} from '@lucide/vue';
import { dashboard } from '@/routes';
import type { NavItem, NavTreeItem } from '@/types';

// Single source of truth for the app navigation. The sidebar (AppSidebar.vue)
// renders the tree below; the command palette (CommandPalette.vue) derives its
// flat, searchable destination list from the same tree via `flattenNavItems`.

// Top-level, non-collapsible entries.
export const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

// Example sections built on the generic CRUD engine.
const catalogNavItem: NavTreeItem = {
    title: 'Catalog',
    icon: Package,
    defaultOpen: true,
    items: [
        { title: 'Customers', href: '/customers', icon: UsersRound },
        { title: 'Products', href: '/products', icon: Package },
        { title: 'Categories', href: '/categories', icon: Tags },
        { title: 'Orders', href: '/orders', icon: ShoppingCart },
    ],
};

// Static example pages that showcase UI building blocks.
const examplesNavItem: NavTreeItem = {
    title: 'Examples',
    icon: LayoutTemplate,
    items: [
        {
            title: 'Form fields',
            href: '/examples/form-fields',
            icon: FormInput,
        },
        { title: 'Wizard', href: '/examples/wizard', icon: Wand2 },
        { title: 'Components', href: '/examples/components', icon: Palette },
    ],
};

// Administration (admins only; the backend enforces it via the manage-users gate).
const administrationNavItem: NavTreeItem = {
    title: 'Administration',
    icon: UserCog,
    items: [{ title: 'Users', href: '/users', icon: Users }],
};

const logsNavItem: NavTreeItem = {
    title: 'Logs',
    icon: ScrollText,
    defaultOpen: true,
    items: [{ title: 'Activity', href: '/logs/activity', icon: Activity }],
};

const laravelNavItem: NavTreeItem = {
    title: 'Laravel',
    icon: Globe,
    defaultOpen: true,
    items: [
        {
            title: 'Laravel Telescope',
            href: '/telescope',
            icon: Telescope,
            external: true,
        },
    ],
};

const debugNavItem: NavTreeItem = {
    title: 'Debug',
    icon: BugOff,
    defaultOpen: true,
    items: [
        { title: 'Scheduled tasks', href: '/debug/schedule', icon: Clock },
        { title: 'Queue', href: '/debug/queue', icon: Layers },
        logsNavItem,
        laravelNavItem,
    ],
};

// Tools (own collapsible menu item).
const toolNavItem: NavTreeItem = {
    title: 'Tools',
    icon: Wrench,
    items: [{ title: 'Icons', href: '/icons', icon: Palette }],
};

// Collapsible top-level menu items rendered by the sidebar.
export const managementNavItems: NavTreeItem[] = [
    catalogNavItem,
    examplesNavItem,
    administrationNavItem,
    debugNavItem,
    toolNavItem,
];

// Recursively merge row counts into a nav tree. Keys in `counts` are path
// strings that must match a node's `href` exactly.
export function enrichWithCounts(
    items: NavTreeItem[],
    counts: Record<string, number>,
): NavTreeItem[] {
    return items.map((item) => ({
        ...item,
        count:
            item.href && typeof item.href === 'string'
                ? (counts[item.href] ?? item.count)
                : item.count,
        items: item.items ? enrichWithCounts(item.items, counts) : undefined,
    }));
}

// Recursively collect every navigable leaf (an entry carrying an `href`) from a
// nav tree. Collapsible group headers without an `href` are skipped, so the
// result is a flat list suitable for the command palette's search.
export function flattenNavItems(items: NavTreeItem[]): NavItem[] {
    return items.flatMap((item) => {
        const self: NavItem[] = item.href
            ? [{ title: item.title, href: item.href, icon: item.icon }]
            : [];
        const children = item.items ? flattenNavItems(item.items) : [];

        return [...self, ...children];
    });
}

// Normalize a nav/breadcrumb href — either a plain path string or a Wayfinder
// route object ({ url, method }) — down to its URL path for comparison.
function hrefToUrl(href: NavItem['href'] | undefined): string | undefined {
    if (!href) {
        return undefined;
    }

    return typeof href === 'string' ? href : (href as { url?: string }).url;
}

// Resolve the sidebar menu icon registered for a destination href, so a page can
// inherit its menu icon instead of re-declaring it. Returns undefined when the
// href is not a menu destination.
export function iconForHref(
    href: NavItem['href'] | undefined,
): NavItem['icon'] {
    const url = hrefToUrl(href);

    if (!url) {
        return undefined;
    }

    return flattenNavItems([...mainNavItems, ...managementNavItems]).find(
        (item) => hrefToUrl(item.href) === url,
    )?.icon;
}
