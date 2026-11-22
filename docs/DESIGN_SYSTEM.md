# Design System — EataLyft Technologies

**Version:** 1.1  
**Date:** 2026-11-22  
**Status:** Implemented (Foundation Layer)  
**Primary UI:** Laravel Blade + Bootstrap 5 (Sneat Pro)  
**Reference Design:** Next.js Studio project (`D:\Git Projects\studio`)

---

## 1. Design Philosophy

The EataLyft design system must serve two contexts:

1. **Public Marketing Pages** — Clean, modern, conversion-focused (hero sections, service cards, pricing bento grids)
2. **Authenticated Admin Dashboard** — Dense, functional, role-scoped (data tables, forms, navigation, modals)

**Guiding Principles:**
- One coherent design system — no duplicate dashboards
- Bootstrap 5 (Sneat Pro) as the foundation for Blade views
- Studio Tailwind tokens used as design reference only — NOT copied into Laravel
- No raw hex values in components; use semantic CSS variables or Bootstrap utilities
- WCAG AA contrast compliance (4.5:1 body text, 3:1 UI components)
- 44px minimum touch targets on mobile
- Dark mode support via CSS class toggle

---

## 2. Layout Architecture

### Layout Reference

| Layout File | Path | Used By | Features |
|-------------|------|---------|----------|
| **Public Layout** | `resources/views/layouts/public.blade.php` | Marketing pages, Auth pages | Responsive nav, dropdowns, language toggle, footer, social links, dark mode toggle |
| **App Layout** | `resources/views/layouts/app.blade.php` | Authenticated + Admin pages | Sneat Pro sidebar (260px), topbar (64px), user menu, currency/language switchers, notifications dropdown |

### Layout Tokens (from Studio `globals.css`)

| Token | Value | Usage |
|-------|-------|-------|
| `--nav-h` | `64px` | Top navigation bar height |
| `--bottom-nav-h` | `64px` | Mobile bottom navigation height |
| `--sidebar-w` | `260px` | Admin sidebar width (matches Sneat Pro) |
| `--max-content` | `1280px` | Content max-width |
| `--max-wide` | `1536px` | Wide layout max-width |
| `--touch-min` | `44px` | Minimum touch target |

### Responsive Breakpoints

| Breakpoint | Value | Usage |
|------------|-------|-------|
| Mobile | `< 640px` | Single column, bottom nav, full-width buttons |
| Tablet | `640px – 1023px` | 2-column grid, sidebar collapsible |
| Desktop | `≥ 1024px` | 3-4 column grid, fixed sidebar, top nav |

---

## 3. Color System

### Brand Color (Primary)

| Token | Light | Dark | WCAG AA |
|-------|-------|------|---------|
| Primary | `#DC2626` (red-600) | `#EF4444` (red-500) | 4.83:1 on white |
| Primary Hover | `#B91C1C` (red-700) | `#F87171` (red-400) | — |
| Primary Active | `#991B1B` (red-800) | `#DC2626` (red-600) | — |
| Primary Foreground | `#FFFFFF` | `#FFFFFF` | — |

### Semantic Colors

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| Success | `#166534` | `#22C55E` | Success states, confirmations |
| Warning | `#B45309` | `#F59E0B` | Warnings, cautions |
| Error | `#DC2626` | `#EF4444` | Error states, destructive actions |
| Info | `#1D4ED8` | `#3B82F6` | Informational messages |
| Background | `#FFFFFF` | `#09090B` | Page background |
| Surface | `#FAFAFA` | `#131316` | Card surfaces |
| Border | `#E4E4E7` | `#27272A` | Dividers, input borders |
| Text Primary | `#0A0A0A` | `#FAFAFA` | Body text |
| Text Muted | `#52525B` | `#A1A1AA` | Secondary text |

### Color Usage Rules

1. **No raw hex in components** — Use Bootstrap utilities (`text-primary`, `bg-danger`) or CSS variables
2. **Primary red (#DC2626)** — Brand buttons, active states, links; never for body text smaller than 18px on soft backgrounds
3. **Dark mode** — Toggle via `.dark` class on `<html>` or `<body>`
4. **Contrast floor** — White text at `/40` alpha raised to `/55`; `/50` raised to `/70` on dark surfaces

---

## 4. Typography

### Font Stack

| Token | Font | Fallback | Usage |
|-------|------|----------|-------|
| `--font-display` | Poppins | -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif | Headings, hero text |
| `--font-body` | Poppins | -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif | Body text, UI labels |
| `--font-code` | monospace | — | Code snippets, technical data |

### Fluid Type Scale (clamp)

| Token | Mobile | Desktop | Usage |
|-------|--------|---------|-------|
| `--text-xs` | `clamp(0.7rem, 1.5vw, 0.75rem)` | 0.75rem | Captions, labels |
| `--text-sm` | `clamp(0.8rem, 1.8vw, 0.875rem)` | 0.875rem | Small text, helper text |
| `--text-base` | `clamp(0.9rem, 2vw, 1rem)` | 1rem | Body text |
| `--text-lg` | `clamp(1rem, 2.2vw, 1.125rem)` | 1.125rem | Lead paragraphs |
| `--text-xl` | `clamp(1.1rem, 2.5vw, 1.25rem)` | 1.25rem | Subheadings |
| `--text-2xl` | `clamp(1.3rem, 3vw, 1.5rem)` | 1.5rem | Section headings |
| `--text-3xl` | `clamp(1.6rem, 4vw, 1.875rem)` | 1.875rem | Page titles |
| `--text-4xl` | `clamp(2rem, 5vw, 2.25rem)` | 2.25rem | Hero headings |
| `--text-hero` | `clamp(2.5rem, 6vw, 3.75rem)` | 3.75rem | Marketing hero |

### Line Heights

| Element | Value |
|---------|-------|
| h1-h3 | 1.15 – 1.3 |
| p | 1.6 – 1.7 |
| Body | 1.6 |

---

## 5. Spacing Scale

| Token | Value | Usage |
|-------|-------|-------|
| `--space-1` | 4px | Tight spacing |
| `--space-2` | 8px | Component gaps |
| `--space-3` | 12px | Form field gaps |
| `--space-4` | 16px | Standard padding |
| `--space-5` | 20px | Section gaps |
| `--space-6` | 24px | Card padding |
| `--space-8` | 32px | Section margins |
| `--space-10` | 40px | Large gaps |
| `--space-12` | 48px | Page sections |
| `--space-16` | 64px | Hero spacing |
| `--space-20` | 80px | Major sections |

**Legacy aliases:** `--space-xs` (8px), `--space-sm` (12px), `--space-md` (16px), `--space-lg` (24px), `--space-xl` (32px), `--space-2xl` (40px), `--space-3xl` (48px), `--space-4xl` (56px)

---

## 6. Radius Scale

| Token | Value | Usage |
|-------|-------|-------|
| `--radius-input` | 8px | Form inputs |
| `--radius-button` | 50px | Pill buttons |
| `--radius-card` | 12px | Cards |
| `--radius-pill` | 999px | Badges, pills |
| `--radius-sm` | 8px | Small elements |
| `--radius-md` | 12px | Medium elements |
| `--radius-lg` | 16px | Large cards |
| `--radius-xl` | 24px | Modals, hero cards |
| `--radius-full` | 9999px | Avatars, circles |

---

## 7. Shadow / Elevation System

| Token | Value | Usage |
|-------|-------|-------|
| `--shadow-sm` | `0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04)` | Subtle elevation |
| `--shadow-md` | `0 4px 16px rgba(0,0,0,0.10), 0 2px 6px rgba(0,0,0,0.06)` | Cards, dropdowns |
| `--shadow-lg` | `0 10px 40px rgba(0,0,0,0.14), 0 4px 12px rgba(0,0,0,0.08)` | Modals, overlays |
| `--shadow-xl` | `0 24px 64px rgba(0,0,0,0.18)` | Deep overlays |
| `--el-shadow-card` | `0 1px 2px rgba(10,10,10,0.04), 0 8px 24px rgba(10,10,10,0.06)` | Studio card shadow |
| `--el-shadow-pop` | `0 4px 12px rgba(10,10,10,0.08), 0 16px 40px rgba(10,10,10,0.14)` | Popups, tooltips |

---

## 8. Component Standards

### Buttons

| Variant | Usage | Classes (Bootstrap) |
|---------|-------|---------------------|
| Primary | Main actions | `btn btn-primary` |
| Secondary | Secondary actions | `btn btn-outline-secondary` |
| Success | Confirmations | `btn btn-success` |
| Danger | Destructive actions | `btn btn-danger` |
| Ghost | Subtle actions | `btn btn-link` |
| Pill | CTAs, badges | `btn btn-primary rounded-pill` |

**Button Rules:**
- Minimum 44px touch target on mobile
- Primary brand color: `#DC2626` (light) / `#EF4444` (dark)
- White text on primary buttons (4.83:1 contrast)
- Active state: scale 0.97 (`.press-active:active`)

### Cards

| Variant | Usage | Radius |
|---------|-------|--------|
| Default | Content containers | 12px |
| Elevated | Hoverable cards | 12px + shadow-md |
| Pinned | Fixed position cards | 16px |
| Glass | Blurred overlay | 16px + backdrop-filter |

**Card Rules:**
- Border: 1px solid `#E4E4E7` (light) / `#27272A` (dark)
- Padding: 16px–24px
- Hover: translateY(-3px) + shadow-lift
- No underlines on card links

### Form Controls

| Element | Radius | Height | Border |
|---------|--------|--------|--------|
| Input | 8px | 44px (mobile) / 40px (desktop) | 1px solid `#E4E4E7` |
| Select | 8px | 44px | 1px solid `#E4E4E7` |
| Textarea | 8px | Auto | 1px solid `#E4E4E7` |
| Checkbox | 4px | 24px | — |
| Radio | 50% | 24px | — |

**Form Rules:**
- Font-size: 16px minimum (prevents iOS zoom)
- Focus ring: 2px solid brand color, 2px offset
- Error state: border `#DC2626`, background `rgba(220,38,38,0.08)`
- Success state: border `#16A34A`, background `rgba(22,163,74,0.08)`
- Labels: above input, font-weight 600, text-muted color

### Tables

| Element | Style |
|---------|-------|
| Header | Background `#F8F9FA`, text `#0A0A0A`, font-weight 600 |
| Body | Alternating row backgrounds (zebra striping) |
| Borders | 1px solid `#E4E4E7` |
| Hover | Light background highlight |
| Mobile | Horizontal scroll wrapper |

### Modals

| Element | Style |
|---------|-------|
| Backdrop | `rgba(0,0,0,0.5)` |
| Panel | White background, rounded-xl (24px), shadow-lg |
| Close button | Top-right, ghost variant |
| Footer | Flex row, action buttons right-aligned |

### Toast Notifications

| Variant | Background | Text | Icon |
|---------|------------|------|------|
| Success | `rgba(22,163,74,0.08)` | `#166534` | Check circle |
| Error | `rgba(220,38,38,0.08)` | `#DC2626` | Exclamation circle |
| Warning | `rgba(180,83,9,0.10)` | `#B45309` | Alert triangle |
| Info | `rgba(29,78,216,0.10)` | `#1D4ED8` | Info circle |

**Toast Rules:**
- Position: top-right or bottom-right
- Auto-dismiss: 5 seconds
- Dismissible: X button
- z-index: 110 (above modals)

---

## 9. Empty States

| Element | Style |
|---------|-------|
| Icon | 64px, muted color |
| Heading | text-lg, text-primary |
| Description | text-base, text-muted |
| Action | Primary button, centered |
| Spacing | 48px vertical padding |

---

## 10. Loading States

| Variant | Usage |
|---------|-------|
| Skeleton | Card/content placeholders (pulse animation) |
| Spinner | Button loading state, inline |
| Page | Full-page overlay, center spinner |
| Skeleton text | 70% width lines, muted background |

---

## 11. Error States

| Level | Style |
|-------|-------|
| Field error | Red border, red helper text below field |
| Form error | Alert banner at top of form |
| Page error | Empty state + retry button |
| API error | Toast notification, auto-dismiss 5s |

---

## 12. Navigation Structure

### Public Navigation

```
Logo (EataLyft)
├── Home
├── Services (dropdown)
│   ├── Bus Tickets
│   ├── eSIM
│   ├── Hotels
│   └── Gift Cards
├── About
├── Agencies
├── FAQ
├── Contact
├── Language (EN/FR toggle)
└── Theme (Light/Dark toggle)
```

### Admin Navigation (Sidebar)

```
Dashboard
├── Users & KYC
│   ├── Users
│   └── KYC Reviews
├── System
│   ├── Settings
│   ├── Audit Logs
│   ├── Health
│   ├── Roles & Permissions
│   └── Integrations
├── Agencies
│   ├── Agencies
│   └── Branches
├── Bus
│   ├── Routes
│   ├── Trips
│   ├── Inventory
│   ├── Tickets
│   └── Bookings
├── Hotels
├── eSIM
├── Listings
├── Boxo Miniapps
├── Currencies & FX
│   ├── Currencies
│   ├── Exchange Rates
│   ├── Margins
│   └── History
└── Notifications
```

---

## 13. z-Index Scale

| Layer | Value | Usage |
|-------|-------|-------|
| Content | 1 | Page content |
| Sticky header | 20 | Fixed nav bars |
| Dock | 40 | Bottom navigation |
| Sheet | 60 | Slide-over panels |
| Toast | 80 | Notifications |
| Prompt | 90 | Cookie consent, install prompts |
| Modal | 100 | Dialogs, modals |
| Toast (high) | 110 | Critical toasts |

---

## 14. Safe Area Support

| Utility | Usage |
|---------|-------|
| `.safe-top` | `padding-top: env(safe-area-inset-top, 0px)` |
| `.safe-bottom` | `padding-bottom: env(safe-area-inset-bottom, 0px)` |
| `.safe-left` | `padding-left: env(safe-area-inset-left, 0px)` |
| `.safe-right` | `padding-right: env(safe-area-inset-right, 0px)` |
| `.pb-safe-4` | `padding-bottom: max(1rem, env(safe-area-inset-bottom))` |
| `.pb-safe-6` | `padding-bottom: max(1.5rem, env(safe-area-inset-bottom))` |
| `.has-bottom-nav` | `padding-bottom: max(6rem, calc(5rem + env(safe-area-inset-bottom)))` |

---

## 15. Studio Design Reference (NOT Copied)

The following Studio design tokens were reviewed for reference only. **Do not copy React components into Laravel.**

| Category | Studio Implementation | Laravel Status |
|----------|----------------------|----------------|
| Colors | `--el-brand-primary: #DC2626`, `--el-bg-primary`, `--el-text-*` | Partially in `resources/css/landing.css`; not unified |
| Typography | Poppins display/body, fluid clamp scales | Sneat Pro uses Inter; not aligned |
| Spacing | `--space-1` through `--space-20` (4px base) | Bootstrap spacing utilities |
| Radius | `--el-radius-card: 12px`, `--el-radius-pill: 999px` | Bootstrap border-radius |
| Shadows | `--el-shadow-card`, `--el-shadow-pop` elevation system | Bootstrap shadows |
| Dark Mode | Full CSS variable override via `.dark` class | Sneat Pro supports dark; tokens not aligned |
| Components | Button (variants), Card, PublicNav, Hero, SiteFooter | Blade partials; no component library |
| Layout | `--nav-h: 64px`, `--sidebar-w: 260px` | Sneat Pro sidebar (260px matches) |

**Decision:** Create a CSS variable bridge file that maps Studio tokens to Bootstrap-compatible classes, allowing gradual migration without duplicating frameworks.

---

## 16. Implementation Guidelines

| Rule | Description |
|------|-------------|
| No raw hex | Use Bootstrap utilities or CSS variables |
| No duplicate frameworks | Bootstrap 5 only for Blade; Tailwind v4 only for Vite-compiled assets |
| 44px touch targets | All interactive elements on mobile |
| WCAG AA contrast | 4.5:1 body text, 3:1 UI components |
| Dark mode support | `.dark` class toggle on html/body |
| Safe area handling | `env(safe-area-inset-*)` for notched devices |
| No component library duplication | Blade partials, not React components |
| Consistent spacing | Use `--space-*` tokens or Bootstrap spacing classes |

---

## 17. Implementation Status (Phase 01 Complete)

### Implemented

| File | Purpose | Location |
|------|---------|----------|
| Design token bridge CSS | Canonical CSS custom properties | `resources/css/design-tokens.css` |
| Deployed design tokens | Direct-linked copy (no Vite build required) | `public/css/design-tokens.css` |
| Reusable button component | Button with variants, sizes, loading state | `resources/views/components/button.blade.php` |
| Reusable card component | Card with variants, shadow, hover | `resources/views/components/card.blade.php` |
| Reusable form-field component | Form field with label, errors, helper | `resources/views/components/form-field.blade.php` |

### Layout Integration

| Layout | Change |
|--------|--------|
| Public layout (`layouts/public.blade.php`) | `<link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}">` added |
| App layout (`layouts/app.blade.php`) | `<link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}">` added |

### Design Token Coverage

| Category | Tokens Implemented |
|----------|-------------------|
| Brand colors | `--ely-brand-primary`, `--ely-brand-primary-hover`, `--ely-brand-primary-active`, `--ely-brand-primary-fg`, `--ely-brand-soft`, `--ely-brand-gradient` |
| Neutral colors | `--ely-bg-primary`, `--ely-bg-surface`, `--ely-bg-elevated`, `--ely-border-default`, `--ely-border-soft`, `--ely-text-primary`, `--ely-text-muted`, `--ely-text-inverse` |
| Status colors | `--ely-success`, `--ely-success-soft`, `--ely-warning`, `--ely-warning-soft`, `--ely-error`, `--ely-error-soft`, `--ely-info`, `--ely-info-soft` |
| Typography | `--ely-font-display`, `--ely-font-body`, `--ely-font-code`, `--ely-text-xs` through `--ely-text-hero`, `--ely-line-height-tight`, `--ely-line-height-base` |
| Spacing | `--ely-space-1` through `--ely-space-20` |
| Radius | `--ely-radius-input`, `--ely-radius-button`, `--ely-radius-card`, `--ely-radius-pill`, `--ely-radius-sm` through `--ely-radius-full` |
| Shadows | `--ely-shadow-sm` through `--ely-shadow-xl`, `--ely-shadow-card`, `--ely-shadow-pop` |
| Layout | `--ely-nav-h`, `--ely-bottom-nav-h`, `--ely-sidebar-w`, `--ely-max-content`, `--ely-max-wide`, `--ely-touch-min` |
| Focus/Disabled | `--ely-focus-ring`, `--ely-focus-offset`, `--ely-disabled-opacity`, `--ely-disabled-cursor` |
| z-index | `--ely-z-content` through `--ely-z-toast` |
| Dark mode | Full override via `data-theme="dark"` / `.dark` |
| Bootstrap mapping | `--bs-primary`, `--bs-danger`, `--bs-success`, `--bs-warning`, `--bs-info`, `--bs-body-bg`, `--bs-body-color`, `--bs-card-bg`, `--bs-card-border-color`, `--bs-input-bg`, `--bs-input-border-color`, `--bs-btn-primary-bg`, `--bs-btn-primary-border`, `--bs-navbar-bg`, `--bs-navbar-color`, `--bs-sidebar-width`, `--bs-navbar-height` |

### Files to Create (Future Phases)

| File | Purpose | Priority |
|------|---------|----------|
| `resources/views/components/modal.blade.php` | Reusable modal wrapper | Medium |
| `resources/views/components/toast.blade.php` | Toast notification component | Medium |
| `resources/views/components/empty-state.blade.php` | Empty state component | Low |
| `resources/views/components/skeleton.blade.php` | Loading skeleton component | Low |

---

*This document is the single authoritative design system reference. Update it whenever colors, typography, spacing, or component standards change.*