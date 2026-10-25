import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import type { SimpleIcon } from 'simple-icons';

export type BreadcrumbItem = {
    icon?: string;
    title: string;
    href?: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | SimpleIcon;
    isActive?: boolean;
    isExpended?: boolean;
};

// Nested navigation tree: a node is either a link (href)
// or a collapsible group (items) — both are optionally combinable.
export type NavTreeItem = {
    title: string;
    href?: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | SimpleIcon;
    items?: NavTreeItem[];
    // Controls whether the group is expanded (true) or collapsed (false)
    // by default. Without a value: top level open, nested groups closed.
    // If the group contains the active route, it is expanded regardless.
    defaultOpen?: boolean;
    // Optional badge count shown next to the menu item label.
    count?: number;
    // When true, the link opens in a new browser tab via a plain <a> tag.
    external?: boolean;
};
