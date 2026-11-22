# Phase 03 Asset Catalog — EataLyft Photorealistic Image Library

**Date:** 2026-11-22  
**Project:** `D:\Git Projects\eatalyft-laravel`  
**Status:** Complete (placeholder layer)  
**Asset Root:** `public/assets/eatalyft/`

---

## 1. Asset Organization

```
public/assets/eatalyft/
├── branding/
│   ├── logo.svg
│   └── favicon.ico (placeholder — 0 bytes, needs replacement)
├── buses/
│   ├── coaches/
│   │   └── coach-premium.svg (placeholder)
│   ├── interiors/
│   │   └── coach-interior.svg (placeholder)
│   ├── seats/
│   │   └── passenger-seats.svg (placeholder)
│   └── terminals/
│       └── transport-terminal.svg (placeholder)
├── tickets/
│   ├── ticket-previews/
│   │   └── digital-ticket.svg (placeholder)
│   └── boarding-passes/
│       └── boarding-pass.svg (placeholder)
├── esim/
│   ├── devices/
│   │   └── smartphone.svg (placeholder)
│   ├── activation/
│   │   └── activation.svg (placeholder)
│   ├── destinations/
│   │   └── connectivity-map.svg (placeholder)
│   └── packages/
│       └── data-package.svg (placeholder)
├── games/
│   ├── featured/
│   │   └── featured-game.svg (placeholder)
│   └── categories/
│       └── game-category.svg (placeholder)
├── destinations/
│   └── cameroon-highlands.svg (placeholder)
├── banners/
│   └── travel-banner.svg (placeholder)
├── backgrounds/
│   └── hero-bg.svg (placeholder)
├── placeholders/
│   ├── avatar.svg (placeholder — user avatar fallback)
│   ├── operator-logo.svg (placeholder — agency/operator logo fallback)
│   └── destination.svg (placeholder — destination image fallback)
└── icons/ (existing Phase 02 SVGs)
    ├── bus.svg, seat.svg, clock.svg, check.svg, search.svg, location-marker.svg
```

---

## 2. Asset Catalog

### Branding

| Filename | Category | Purpose | Source | License | Dimensions | Format | Intended Screens |
|----------|----------|---------|--------|-------|------------|--------|------------------|
| `branding/logo.svg` | Branding | EataLyft wordmark + bus logomark | Original (Phase 02) | EataLyft original | 200×64 | SVG | Nav, footer, auth pages |
| `branding/favicon.ico` | Branding | Browser favicon | — | — | 32×32 | ICO | All pages (browser tab) |
| `placeholders/avatar.svg` | Placeholder | User profile avatar fallback | Original (Phase 03) | EataLyft original | 150×150 | SVG | Profile, navbar dropdown |
| `placeholders/operator-logo.svg` | Placeholder | Agency/operator logo fallback | Original (Phase 03) | EataLyft original | 64×64 | SVG | Agencies page, trip cards |
| `placeholders/destination.svg` | Placeholder | Destination image fallback | Original (Phase 03) | EataLyft original | 400×300 | SVG | Bus trips, destination cards |

### Buses

| Filename | Category | Purpose | Source | License | Dimensions | Format | Intended Screens |
|----------|----------|---------|--------|-------|------------|--------|------------------|
| `buses/coaches/coach-premium.svg` | Buses | Premium intercity coach exterior | Original (Phase 03) | EataLyft original | 800×450 | SVG | Hero, bus discovery, trip details |
| `buses/interiors/coach-interior.svg` | Buses | Coach interior seating | Original (Phase 03) | EataLyft original | 800×450 | SVG | Trip details, seat selection |
| `buses/seats/passenger-seats.svg` | Buses | Passenger seat close-up | Original (Phase 03) | EataLyft original | 600×400 | SVG | Seat selection summary |
| `buses/terminals/transport-terminal.svg` | Buses | Transport terminal/station | Original (Phase 03) | EataLyft original | 800×450 | SVG | Agencies, trip details |

### Tickets

| Filename | Category | Purpose | Source | License | Dimensions | Format | Intended Screens |
|----------|----------|---------|--------|-------|------------|--------|------------------|
| `tickets/ticket-previews/digital-ticket.svg` | Tickets | Digital ticket preview | Original (Phase 02) | EataLyft original | 400×300 | SVG | Showcase, booking |
| `tickets/boarding-passes/boarding-pass.svg` | Tickets | Boarding pass layout | Original (Phase 03) | EataLyft original | 400×300 | SVG | Booking confirmation |

### eSIM

| Filename | Category | Purpose | Source | License | Dimensions | Format | Intended Screens |
|----------|----------|---------|--------|-------|------------|--------|------------------|
| `esim/devices/smartphone.svg` | eSIM | Smartphone device | Original (Phase 03) | EataLyft original | 400×600 | SVG | eSIM plans (future) |
| `esim/activation/activation.svg` | eSIM | Digital SIM activation | Original (Phase 03) | EataLyft original | 600×400 | SVG | eSIM activation (future) |
| `esim/destinations/connectivity-map.svg` | eSIM | Travel connectivity map | Original (Phase 03) | EataLyft original | 800×450 | SVG | eSIM destinations (future) |
| `esim/packages/data-package.svg` | eSIM | Data package visualization | Original (Phase 03) | EataLyft original | 400×300 | SVG | eSIM plans (future) |

### Games

| Filename | Category | Purpose | Source | License | Dimensions | Format | Intended Screens |
|----------|----------|---------|--------|-------|------------|--------|------------------|
| `games/featured/featured-game.svg` | Games | Featured game artwork | Original (Phase 03) | EataLyft original | 600×400 | SVG | Games catalog (future) |
| `games/categories/game-category.svg` | Games | Game category thumbnail | Original (Phase 03) | EataLyft original | 300×200 | SVG | Games categories (future) |

### Destinations

| Filename | Category | Purpose | Source | License | Dimensions | Format | Intended Screens |
|----------|----------|---------|--------|-------|------------|--------|------------------|
| `destinations/cameroon-highlands.svg` | Destinations | Cameroon travel destination | Original (Phase 03) | EataLyft original | 800×450 | SVG | Home showcase, trip details |

### Banners & Backgrounds

| Filename | Category | Purpose | Source | License | Dimensions | Format | Intended Screens |
|----------|----------|---------|--------|-------|------------|--------|------------------|
| `banners/travel-banner.svg` | Banners | Travel promotional banner | Original (Phase 03) | EataLyft original | 1200×400 | SVG | Home hero, promotions |
| `backgrounds/hero-bg.svg` | Backgrounds | Hero section background | Original (Phase 03) | EataLyft original | 1920×1080 | SVG | Home hero |

---

## 3. Licensing & Provenance Status

| Asset | Provenance | License Status | Notes |
|-------|-----------|----------------|-------|
| All Phase 02 SVGs | EataLyft original | EataLyft owns | Created in Phase 02 |
| All Phase 03 placeholder SVGs | EataLyft original | EataLyft owns | Created in Phase 03 as fallbacks |
| `branding/favicon.ico` | Empty (0 bytes) | — | Needs replacement with valid favicon |
| External Dicebear URLs (removed) | Third-party | — | Replaced with local fallback (Phase 03) |

**Important:** No third-party stock photography has been downloaded. All placeholder assets are original EataLyft SVG illustrations. Where photorealistic WebP photography is required for production, it must be sourced from properly licensed providers (Unsplash, Pexels, or original photography) with usage rights verified before deployment. Do not claim placeholder SVGs are licensed stock photography.

**No fake partner logos, operator logos, or game cover artwork have been created.** Placeholder SVGs are neutral and do not represent any real company, game, or service.

---

## 4. Asset → Screen Mapping

| Screen | Route | Assets Used | Fallback |
|--------|-------|-------------|----------|
| Home (hero) | `/` | `buses/coaches/coach-premium.svg`, `banners/travel-banner.svg` | `backgrounds/hero-bg.svg` |
| Home (showcase) | `/` | `tickets/ticket-previews/digital-ticket.svg` | `placeholders/destination.svg` |
| Home (benefits/icons) | `/` | Phase 02 icons | — |
| Agencies listing | `/agencies` | `agencies.logo_url` (dynamic) | `placeholders/operator-logo.svg` |
| Trip detail | Bus API | `agency.logo_url` (dynamic), `buses/interiors/coach-interior.svg` | `placeholders/operator-logo.svg` |
| Seat selection | Bus API | `buses/seats/passenger-seats.svg` | `placeholders/destination.svg` |
| Profile | `/profile` | `users.photo_url` (dynamic) | `placeholders/avatar.svg` |
| Navbar avatar | All (auth) | `users.photo_url` (dynamic) | `placeholders/avatar.svg` |
| eSIM plans | `/esim/plans` | `esim/devices/smartphone.svg`, `esim/packages/data-package.svg` | `placeholders/destination.svg` |
| Games catalog | Future | `games/featured/featured-game.svg`, `games/categories/game-category.svg` | `placeholders/destination.svg` |

---

## 5. Files to Create

| File | Purpose |
|------|---------|
| `config/eatalyft_assets.php` | Centralized asset manifest |
| `resources/views/components/eatalyft/image.blade.php` | Reusable image component |
| `public/assets/eatalyft/buses/coaches/coach-premium.svg` | Coach exterior placeholder |
| `public/assets/eatalyft/buses/interiors/coach-interior.svg` | Coach interior placeholder |
| `public/assets/eatalyft/buses/seats/passenger-seats.svg` | Seats placeholder |
| `public/assets/eatalyft/buses/terminals/transport-terminal.svg` | Terminal placeholder |
| `public/assets/eatalyft/tickets/boarding-passes/boarding-pass.svg` | Boarding pass placeholder |
| `public/assets/eatalyft/esim/devices/smartphone.svg` | Smartphone placeholder |
| `public/assets/eatalyft/esim/activation/activation.svg` | Activation placeholder |
| `public/assets/eatalyft/esim/destinations/connectivity-map.svg` | Connectivity map placeholder |
| `public/assets/eatalyft/esim/packages/data-package.svg` | Data package placeholder |
| `public/assets/eatalyft/games/featured/featured-game.svg` | Featured game placeholder |
| `public/assets/eatalyft/games/categories/game-category.svg` | Game category placeholder |
| `public/assets/eatalyft/destinations/cameroon-highlands.svg` | Destination placeholder |
| `public/assets/eatalyft/banners/travel-banner.svg` | Travel banner placeholder |
| `public/assets/eatalyft/backgrounds/hero-bg.svg` | Hero background placeholder |
| `public/assets/eatalyft/placeholders/avatar.svg` | User avatar fallback |
| `public/assets/eatalyft/placeholders/operator-logo.svg` | Operator logo fallback |
| `public/assets/eatalyft/placeholders/destination.svg` | Destination fallback |

---

*This document is the asset catalog. Update it whenever assets are added, replaced, or licensed photography is deployed.*