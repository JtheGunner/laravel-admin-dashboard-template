# Design System — Admin Dashboard

## Product Context

- **What this is:** Generic Laravel + Inertia + Vue admin dashboard template (demo domain: shop with customers, products, categories, orders)
- **Who it's for:** Developers who need a ready-made admin backend as a starting point
- **Space/industry:** Admin / back-office tooling
- **Design origin:** [TailAdmin](https://tailadmin.com/) design language + [shadcn-vue](https://www.shadcn-vue.com/) "new-york" style on top of Tailwind CSS v4

## Aesthetic Direction

- **Direction:** Data-dense admin — clean, functional, no decoration without purpose
- **Decoration level:** None — every element must earn its place
- **Mood:** Quiet and focused; the data is the hero, the chrome stays out of the way
- **Light mode:** Gray-50 canvas (`#f9fafb`), white bordered cards, cool blue accent
- **Dark mode:** Deep navy base (`#101828`), slate-toned card surfaces (`#1a2231`)

## Typography

- **Font:** [Outfit](https://fonts.bunny.net) (weights 100–900), loaded from Bunny Fonts CDN with `display=swap`
- **CSS variable:** `--font-sans: Outfit, sans-serif`
- **Body class:** `font-sans antialiased`
- **Scale:** Tailwind defaults (no custom type scale overrides)
  - Table headers / labels: `text-xs font-medium`
  - Body / form labels: `text-sm`
  - Card titles / section headings: `text-sm font-medium` or `text-base font-semibold`

## Color Tokens

These CSS variables are set in `resources/css/app.css`. Always reference tokens — never hardcode hex values.

### Light mode (`:root`)

| Token | Value | Usage |
|-------|-------|-------|
| `--background` | `#f9fafb` | Page canvas (gray-50) |
| `--foreground` | `#1d2939` | Primary text |
| `--card` | `#ffffff` | Card / dialog surface |
| `--card-foreground` | `#1d2939` | Text on cards |
| `--primary` | `#465fff` | Brand blue — buttons, active states, links |
| `--primary-foreground` | `#ffffff` | Text on primary buttons |
| `--secondary` | `#f2f4f7` | Secondary button backgrounds |
| `--secondary-foreground` | `#344054` | Text on secondary |
| `--muted` | `#f2f4f7` | Subtle tinted backgrounds |
| `--muted-foreground` | `#667085` | Placeholder / helper text |
| `--accent` | `#ecf3ff` | Tinted blue hover / active surfaces |
| `--accent-foreground` | `#465fff` | Text on accent surface |
| `--destructive` | `#f04438` | Delete / error |
| `--border` | `#e4e7ec` | Card borders, dividers |
| `--input` | `#d0d5dd` | Input border color |
| `--ring` | `#465fff` | Focus ring color |
| `--sidebar-background` | `#ffffff` | Sidebar surface |
| `--sidebar-accent` | `#ecf3ff` | Active sidebar item |
| `--sidebar-foreground` | `#344054` | Sidebar text |

### Dark mode (`.dark`)

| Token | Value |
|-------|-------|
| `--background` | `#101828` |
| `--foreground` | `#f2f4f7` |
| `--card` | `#1a2231` |
| `--muted` | `#1d2939` |
| `--muted-foreground` | `#98a2b3` |
| `--accent` | `#1c2547` |
| `--accent-foreground` | `#7592ff` |
| `--border` | `#2a3441` |
| `--input` | `#344054` |
| `--sidebar-background` | `#1a2231` |

### Chart palette

Used in ApexCharts / `StatChart.vue`. Reference by token class (`text-chart-1`, etc.):

| Token | Light | Dark | Semantic |
|-------|-------|------|----------|
| `--chart-1` | `#465fff` | `#7592ff` | Primary / applications |
| `--chart-2` | `#12b76a` | `#12b76a` | Success / growth |
| `--chart-3` | `#f79009` | `#f79009` | Warning / amber |
| `--chart-4` | `#0ba5ec` | `#0ba5ec` | Info / sky |
| `--chart-5` | `#7592ff` | `#9cb9ff` | Secondary accent |

## Border Radius

The base radius variable is `--radius: 0.625rem` (10px). All others derive from it:

| Class | Value | Usage |
|-------|-------|-------|
| `rounded-sm` | 6px | Small controls, tags |
| `rounded-md` | 8px | Buttons, inline badges |
| `rounded-lg` | 10px | Inputs, dropdowns |
| `rounded-xl` | 14px | Dialogs, popovers |
| `rounded-2xl` | 16px | Table/card containers |
| `rounded-full` | 9999px | Avatar, circular icon buttons |

## Shadows

Two levels, defined in `@theme inline`:

| Class | Usage |
|-------|-------|
| `shadow-card` | Default card / table container |
| `shadow-card-lg` | Elevated dialogs, sticky floating elements |
| `shadow-xs` | Input fields |

Cards also carry a `border border-border` — the 1px border and subtle shadow work together.

## Layout

- **Scroll context:** The `<main>` inside `AppSidebarLayout` is `h-svh overflow-y-auto` — this is the only scroll container. Never use `window.scrollY` or add scroll to sub-elements.
- **Page content area:** `flex h-full flex-1 flex-col gap-4 px-4 pb-4 md:px-6 md:pb-6`
- **Card containers:** `rounded-2xl border border-border bg-card text-card-foreground shadow-card overflow-clip`
- **Section gap:** `gap-4` between stacked cards
- **Sticky elements:** Stick at `top-[70px]` (height of the page header bar); use `z-10` for table header, `z-20` for bulk-action bar

## Spacing

Standard Tailwind 4px base unit. Common idioms in the resource table:

| Context | Classes |
|---------|---------|
| Table cell | `px-4 py-3` |
| Table header | `px-4 py-3 text-xs font-medium text-muted-foreground` |
| Card header toolbar | `px-6 py-5` |
| Bulk-action bar | `px-6 py-3` |
| Empty state | `px-4 py-16 text-center` |
| Icon button | `size-8` or `size-10` |

## Motion

No custom easing or duration tokens — Tailwind defaults apply:

- **Hover color/bg transitions:** `transition-colors` (CSS default ~150ms)
- **Transform:** `transition-transform` — used sparingly (color picker swatch scale on hover)
- **Complex animations:** `tw-animate-css` imported for enter/leave transitions on dialogs and toasts
- **Rule:** No idle/looping animations. Motion only when it aids comprehension (loading states, dialog open/close).

## Input Fields

All interactive inputs must use this surface pattern — solid white in light mode, semi-transparent in dark:

```
bg-white dark:bg-input/30
border border-input
rounded-lg
shadow-xs
focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50
outline-none
transition-[color,box-shadow]
```

Never use `bg-transparent` or `bg-background` for input controls — they would inherit the page canvas and disappear against it.

## Badge System

Badges use a **tinted-background + colored-text + inset-ring** pattern. The token palette is defined in `resources/js/lib/badgeColors.ts`. Always use `badgeClasses(token)` or reference `BADGE_COLORS` — never hardcode badge classes outside that file.

**Pattern:** `bg-{color}-500/10 text-{color}-700 ring-{color}-500/20 ring-inset dark:text-{color}-400`

Available tokens: `slate` `red` `orange` `amber` `yellow` `lime` `green` `emerald` `teal` `cyan` `sky` `blue` `indigo` `violet` `purple` `fuchsia` `pink` `rose`

**Default (no color):** `bg-muted text-muted-foreground ring-border`

**Semantic boolean badges (hardcoded, not from palette):**
- Yes / active: `bg-emerald-500/10 text-emerald-600 dark:text-emerald-400`
- No / inactive: `bg-red-500/10 text-red-700 dark:text-red-400`

**Semantic action buttons in table rows:**
- Restore action: `bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-400`
- Delete action: `bg-red-500/10 text-red-600 hover:bg-red-500/20 dark:bg-red-500/20 dark:text-red-400`

## Row Tint System

The `RowTint` type and class maps live in `resources/js/lib/rowTint.ts`. Tints mark rows semantically without disrupting legibility.

**Available tint keys:** `amber` `rose` `red` `emerald` `green` `sky` `violet` `slate`

**Table row:** `bg-{color}-100/60 dark:bg-{color}-400/10`

**Card (mobile):** `border-l-4 border-l-{color}-400 bg-{color}-50/70 dark:border-l-{color}-400/60 dark:bg-{color}-400/10`

Always use `rowTintClass(row, fn)` — never write tint classes ad-hoc.

## Sticky Table Header

The table `<thead>` sits at `sticky top-[70px] z-10` with a blurred background:

```
sticky top-[70px] z-10 bg-muted/95 backdrop-blur
```

The bulk-action bar (when rows are selected) uses `sticky top-[70px] z-20 bg-accent` — it overlays the header.

## Tooltips

Use `<AppTooltip text="…">` wrapping the trigger element. Never:
- Use native `title=` for hover hints (renders an unstyled browser tooltip)
- Hand-roll `TooltipContent` / `TooltipProvider` primitives
- Wrap a `DialogTrigger` or `DropdownMenuTrigger` in `AppTooltip` (reka-ui conflict swallows clicks)

## Icons

Icon library: Lucide (imported from `lucide-vue-next`). All icons passed via props use **string names** resolved through `ICON_MAP` — never pass icon functions across the Inertia props boundary (`DataCloneError`).

Simple Icons are also available for brand logos (tech stack, companies).

## Decisions Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2025 | TailAdmin design language | Clean admin aesthetic; Outfit font; gray-50 canvas + white cards |
| 2025 | shadcn-vue "new-york" style | Accessible primitives (reka-ui), fits Tailwind v4 CSS variables |
| 2025 | `bg-white dark:bg-input/30` for inputs | Solid white makes inputs legible against gray-50 canvas in light mode |
| 2025 | Sticky at `top-[70px]` | Height of the page header; sticky works because `<main>` is the scroll container |
| 2025 | Row tint via fixed palette | Consistent semantic marking across all tables; prevents ad-hoc class proliferation |
| 2025 | Badge via `badgeClasses(token)` | Full Tailwind class strings required; dynamic construction breaks the scanner |
| 2026-08-20 | DESIGN.md created | Centralizes design tokens and UI patterns for AI agent reference |