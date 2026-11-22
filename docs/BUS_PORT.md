# Bus Port Notes

## Legacy Source
- Legacy SQL schema in legacy/schema/ (bus-related tables).
- Legacy Next.js agency dashboard in legacy UI (UNKNOWN path).

## Ported Items
- City/branch/agency structure ported to Eloquent models.
- Seat layouts use grid cells (row/col/type).

## UNKNOWN Items
- Legacy SMS provider: UNKNOWN (not identified in legacy code).
- Exact legacy field validation rules: UNKNOWN (legacy code not accessible in this session).
- Legacy fare calculation formulas: UNKNOWN.

## Differences
- Money stored as integer minor units (not decimal).
- Times stored in UTC (legacy may have used local).
- No payment processing in new system (counter sales confirm immediately).
