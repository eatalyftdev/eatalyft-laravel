# RBAC Permission Matrix

## Roles (12 + bus_operator legacy)

| Role | Description | Scope |
|------|-------------|-------|
| `superadmin` | Platform owner, full access, can create custom sub-roles | Global |
| `admin` | Platform administrator, all permissions except `system.admin` | Global |
| `support_agent` | Customer support, read users/KYC/audit/bookings | Global read |
| `kyc_reviewer` | KYC verification only | KYC module |
| `listing_moderator` | Content moderation for listings | Listings module |
| `agency_manager` | Manages agency, branches, staff, bus, | Assigned agency |
| `branch_manager` | Manages single branch operations | Assigned branch |
| `ticket_agent` | Counter sales: issue/sell/scan tickets | Assigned branch |
| `ticket_scanner` | Scan tickets only (PWA camera) | Assigned branch |
| `hotel_manager` | Manages hotels, rooms, rates, bookings | Assigned hotels |
| `handyman_provider` | Service provider (deferred - schema only) | Own services |
| `customer` | End user: own profile, KYC, bookings, orders | Own data |
| `creator` | Content creator: content, analytics, payouts | Own content |

---

## Permission Categories

### System
- `system.admin` - Superadmin only
- `system.settings.read` / `write` - App settings
- `system.audit.read` - Audit logs
- `system.health` - Detailed health checks

### Users & KYC
- `users.read` / `write` / `delete`
- `users.kyc.read` / `write`
- `kyc.read` / `write` / `approve` / `reject`

### Roles
- `roles.read` / `write` / `delete`

### Notifications
- `notifications.read` / `write` / `send`

### Audit
- `audit.read`

### Agencies
- `agencies.read` / `write` / `delete`
- `agencies.branches.read` / `write` / `delete`
- `agencies.staff.read` / `write` / `delete`

### Bus
- `bus.routes.read` / `write` / `delete`
- `bus.trips.read` / `write` / `delete`
- `bus.inventory.read` / `write`
- `bus.tickets.read` / `write` / `cancel` / `scan`
- `bus.bookings.read` / `write` / `confirm` / `cancel`

### Hotels
- `hotels.read` / `write` / `delete`
- `hotels.rooms.read` / `write` / `delete`
- `hotels.rates.read` / `write`
- `hotels.availability.read` / `write`
- `hotels.bookings.read` / `write` / `confirm` / `cancel`

### Listings
- `listings.read` / `write` / `delete` / `publish` / `moderate`

### eSIM
- `esim.read` / `write` / `delete`
- `esim.plans.read` / `write`
- `esim.orders.read` / `write`

### Boxo
- `boxo.read` / `write`
- `boxo.miniapps.read` / `write`

### Integrations
- `integrations.read` / `write` / `delete`

### Currencies & FX
- `currencies.read` / `write` / `delete`
- `fx.rates.read` / `write`
- `fx.margins.read` / `write`
- `fx.history.read`

### Handyman (Deferred)
- `handyman.read` / `write` / `delete`
- `handyman.providers.read` / `write`
- `handyman.bookings.read` / `write`

### Creator
- `creator.content.read` / `write`
- `creator.analytics.read`
- `creator.payouts.read`

---

## Role Permissions Matrix

| Permission | superadmin | admin | support_agent | kyc_reviewer | listing_moderator | agency_manager | branch_manager | ticket_agent | ticket_scanner | hotel_manager | handyman_provider | customer | creator |
|------------|:----------:|:-----:|:-------------:|:------------:|:-----------------:|:--------------:|:--------------:|:------------:|:--------------:|:-------------:|:-----------------:|:--------:|:-------:|
| **System** |
| system.admin | ✓ | | | | | | | | | | | | |
| system.settings.read | ✓ | ✓ | | | | | | | | | | | |
| system.settings.write | ✓ | ✓ | | | | | | | | | | | |
| system.audit.read | ✓ | ✓ | ✓ | | | | | | | | | | |
| system.health | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Users & KYC** |
| users.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ | ✓ |
| users.write | ✓ | ✓ | | | | ✓ | | | | | | | |
| users.delete | ✓ | ✓ | | | | | | | | | | | |
| users.kyc.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ | ✓ |
| users.kyc.write | ✓ | ✓ | | ✓ | | | | | | | | ✓ | ✓ |
| kyc.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ | ✓ |
| kyc.write | ✓ | ✓ | ✓ | ✓ | | | | | | | | ✓ | ✓ |
| kyc.approve | ✓ | ✓ | | ✓ | | | | | | | | | |
| kyc.reject | ✓ | ✓ | | ✓ | | | | | | | | | |
| **Roles** |
| roles.read | ✓ | ✓ | | | | | | | | | | | |
| roles.write | ✓ | ✓ | | | | | | | | | | | |
| roles.delete | ✓ | ✓ | | | | | | | | | | | |
| **Notifications** |
| notifications.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ | ✓ |
| notifications.write | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | | ✓ | ✓ | | ✓ |
| notifications.send | ✓ | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ | | ✓ | ✓ | | ✓ |
| **Audit** |
| audit.read | ✓ | ✓ | ✓ | | | | | | | | | | |
| **Agencies** |
| agencies.read | ✓ | ✓ | | | | ✓ | | | | | | | |
| agencies.write | ✓ | ✓ | | | | ✓ | | | | | | | |
| agencies.delete | ✓ | ✓ | | | | | | | | | | | |
| agencies.branches.read | ✓ | ✓ | | | | ✓ | ✓ | | | | | | |
| agencies.branches.write | ✓ | ✓ | | | | ✓ | | | | | | | |
| agencies.branches.delete | ✓ | ✓ | | | | | | | | | | | |
| agencies.staff.read | ✓ | ✓ | | | | ✓ | | | | | | | |
| agencies.staff.write | ✓ | ✓ | | | | ✓ | | | | | | | |
| agencies.staff.delete | ✓ | ✓ | | | | | | | | | | | |
| **Bus** |
| bus.routes.read | ✓ | ✓ | | | | ✓ | ✓ | ✓ | | | | | |
| bus.routes.write | ✓ | ✓ | | | | ✓ | | | | | | | |
| bus.routes.delete | ✓ | ✓ | | | | | | | | | | | |
| bus.trips.read | ✓ | ✓ | | | | ✓ | ✓ | ✓ | ✓ | | | | |
| bus.trips.write | ✓ | ✓ | | | | ✓ | | | | | | | |
| bus.trips.delete | ✓ | ✓ | | | | | | | | | | | |
| bus.inventory.read | ✓ | ✓ | | | | ✓ | ✓ | ✓ | | | | | |
| bus.inventory.write | ✓ | ✓ | | | | ✓ | | | | | | | |
| bus.tickets.read | ✓ | ✓ | | | | ✓ | ✓ | ✓ | | | | ✓ | |
| bus.tickets.write | ✓ | ✓ | | | | ✓ | ✓ | ✓ | | | | | |
| bus.tickets.cancel | ✓ | ✓ | | | | ✓ | ✓ | ✓ | | | | | |
| bus.tickets.scan | ✓ | ✓ | | | | | ✓ | ✓ | ✓ | | | | |
| bus.bookings.read | ✓ | ✓ | ✓ | | | ✓ | ✓ | ✓ | | | | ✓ | |
| bus.bookings.write | ✓ | ✓ | | | | ✓ | ✓ | ✓ | | | | | |
| bus.bookings.confirm | ✓ | ✓ | | | | ✓ | ✓ | ✓ | | | | | |
| bus.bookings.cancel | ✓ | ✓ | | | | ✓ | ✓ | ✓ | | | | | |
| **Hotels** |
| hotels.read | ✓ | ✓ | | | | ✓ | ✓ | | | ✓ | | | |
| hotels.write | ✓ | ✓ | | | | ✓ | | | | ✓ | | | |
| hotels.delete | ✓ | ✓ | | | | | | | | | | | |
| hotels.rooms.read | ✓ | ✓ | | | | ✓ | ✓ | | | ✓ | | | |
| hotels.rooms.write | ✓ | ✓ | | | | ✓ | | | | ✓ | | | |
| hotels.rooms.delete | ✓ | ✓ | | | | | | | | | | | |
| hotels.rates.read | ✓ | ✓ | | | | ✓ | ✓ | | | ✓ | | | |
| hotels.rates.write | ✓ | ✓ | | | | ✓ | | | | ✓ | | | |
| hotels.availability.read | ✓ | ✓ | | | | ✓ | ✓ | | | ✓ | | | |
| hotels.availability.write | ✓ | ✓ | | | | ✓ | | | | ✓ | | | |
| hotels.bookings.read | ✓ | ✓ | ✓ | | | ✓ | ✓ | | | ✓ | | ✓ | |
| hotels.bookings.write | ✓ | ✓ | | | | ✓ | ✓ | | | ✓ | | | |
| hotels.bookings.confirm | ✓ | ✓ | | | | ✓ | ✓ | | | ✓ | | | |
| hotels.bookings.cancel | ✓ | ✓ | | | | ✓ | ✓ | | | ✓ | | | |
| **Listings** |
| listings.read | ✓ | ✓ | | | ✓ | | | | | | | | |
| listings.write | ✓ | ✓ | | | | | | | | | | | |
| listings.delete | ✓ | ✓ | | | | | | | | | | | |
| listings.publish | ✓ | ✓ | | | ✓ | | | | | | | | |
| listings.moderate | ✓ | ✓ | | | ✓ | | | | | | | | |
| **eSIM** |
| esim.read | ✓ | ✓ | | | | | | | | | | | |
| esim.write | ✓ | ✓ | | | | | | | | | | | |
| esim.delete | ✓ | ✓ | | | | | | | | | | | |
| esim.plans.read | ✓ | ✓ | | | | | | | | | | | |
| esim.plans.write | ✓ | ✓ | | | | | | | | | | | |
| esim.orders.read | ✓ | ✓ | | | | | | | | | | ✓ | |
| esim.orders.write | ✓ | ✓ | | | | | | | | | | | |
| **Boxo** |
| boxo.read | ✓ | ✓ | | | | | | | | | | | |
| boxo.write | ✓ | ✓ | | | | | | | | | | | |
| boxo.miniapps.read | ✓ | ✓ | | | | | | | | | | | |
| boxo.miniapps.write | ✓ | ✓ | | | | | | | | | | | |
| **Integrations** |
| integrations.read | ✓ | ✓ | | | | | | | | | | | |
| integrations.write | ✓ | ✓ | | | | | | | | | | | |
| integrations.delete | ✓ | ✓ | | | | | | | | | | | |
| **Currencies & FX** |
| currencies.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| currencies.write | ✓ | ✓ | | | | | | | | | | | |
| currencies.delete | ✓ | ✓ | | | | | | | | | | | |
| fx.rates.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| fx.rates.write | ✓ | ✓ | | | | | | | | | | | |
| fx.margins.read | ✓ | ✓ | | | | | | | | | | | |
| fx.margins.write | ✓ | ✓ | | | | | | | | | | | |
| fx.history.read | ✓ | ✓ | | | | | | | | | | | |
| **Handyman (Deferred)** |
| handyman.providers.read | ✓ | ✓ | | | | | | | | | ✓ | | |
| handyman.providers.write | ✓ | ✓ | | | | | | | | | ✓ | | |
| handyman.bookings.read | ✓ | ✓ | | | | | | | | | ✓ | | |
| handyman.bookings.write | ✓ | ✓ | | | | | | | | | ✓ | | |
| **Creator** |
| creator.content.read | ✓ | ✓ | | | | | | | | | | | ✓ |
| creator.content.write | ✓ | ✓ | | | | | | | | | | | ✓ |
| creator.analytics.read | ✓ | ✓ | | | | | | | | | | | ✓ |
| creator.payouts.read | ✓ | ✓ | | | | | | | | | | | ✓ |

---

## Ownership Scoping (Policies)

### AgencyManagerPolicy
- `viewAny` / `view` / `create` / `update` / `delete` → only agencies where `user.agency_id = agency.id`
- Branches → only branches of user's agency
- Staff → only staff of user's agency

### BranchManagerPolicy
- All bus/hotel actions → only resources where `branch_id = user.branch_id`
- Tickets → only tickets sold at user's branch
- Bookings → only bookings for trips/hotels at user's branch

### TicketAgentPolicy
- `bus.tickets.*` → only tickets where `sold_by_user_id = user.id` OR `branch_id = user.branch_id`
- `bus.bookings.*` → only bookings created by user or at user's branch

### TicketScannerPolicy
- `bus.tickets.scan` → only validate tickets for trips departing from user's branch

### HotelManagerPolicy
- Hotels → only hotels where `manager_id = user.id` OR user has explicit assignment
- Rooms/Rates/Availability/Bookings → scoped to user's hotels

### CustomerPolicy
- All resources → only own data (`user_id = auth()->id()`)

---

## Custom Sub-Roles (Superadmin Only)

Superadmin can create custom roles in the admin UI by:
1. Selecting from the full permission list (98 permissions)
2. Assigning a name and description
3. The role is created with `guard_name = 'web'`
4. Custom roles are marked with `is_custom = true` in a `roles` table extension (future migration)

---

## Testing Requirements

For each role, tests must verify:
1. **Allowed pages**: 200 OK for permitted routes
2. **Denied pages**: 403 Forbidden for non-permitted routes
3. **Cross-agency access**: 403 when agency_manager accesses another agency's data
4. **Cross-branch access**: 403 when branch_manager accesses another branch's data
5. **Own data only**: customer/creator see only their own records
