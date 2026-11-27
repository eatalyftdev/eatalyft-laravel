# EataLyft Design System

## UI Stack Boundaries

### Legacy Admin (/admin/* Blade views)
- Maintain existing Bootstrap 5 / Sneat
- Do NOT add new Bootstrap pages
- Existing Blade views remain functional

### Customer, Agency, Station Teller, Reseller, SeatMap, Boarding Pass UIs
- Strictly Inertia + React + TypeScript + Tailwind CSS + Framer Motion
- Located in resources/js/
- No Bootstrap, no jQuery

## Brand Tokens
- Primary Crimson: #DC2626
- Deep Ink: #0A0A0A
- Crisp White: #FFFFFF
- Neutral Slate Light: #F8FAFC
- Neutral Slate Dark: #171717

## Typography
- Headings/Tickets/Codes: Poppins (400, 600, 700)
- UI/Tables: Inter (400, 500, 600)

## Forbidden
- Purple/indigo/violet gradients
- Generic glowing neon blobs
- Cluttered glassmorphism
- Cookie-cutter templates
## Unslop Workflow (Mandatory for ALL UI)

Every new or modified page, component, modal, ticket, or dashboard MUST undergo the unslop workflow before task completion:
1. Audit: Identify AI-UI tells (generic pills, symmetrical grids, vague copy, over-rounded cards, blobs)
2. Direction: Define aesthetic direction (Swiss-precision transit & fintech for Cameroon)
3. 9 Ordered Passes: spacing, color, icons, copy, typography, layout, details, motion, final
4. Eliminate ALL AI tells before completion

Reference: .claude/skills/unslop/SKILL.md

