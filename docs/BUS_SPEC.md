# Bus Module Specification

## Data Model
- All money in integer minor units + currency code (XAF default).
- Times stored in UTC, displayed in Africa/Douala.
- cities (EN/FR names, region, country; admin-managed).
- branches (agency_id, city_id, name, type office/terminal, phone, status).
- bus_classes, amenities (admin-managed catalog).
- buses (agency_id, plate, fleet_no, make/model/year, class, seat_layout_id, capacity computed, amenities, photos, status active/maintenance/retired).
- bus_documents (insurance, technical inspection, registration; expiry dates; private storage).
- maintenance_logs (date, type, odometer, notes, next_due_date).
- crew (driver/conductor: name, phone, licence number + expiry; NOT app logins).
- seat_layouts + seat_layout_cells (row, col, type seat/aisle/door/driver/blocked, label, class).
- routes (agency_id, origin, destination, distance, duration).
- route_stops (order, city/branch, offset_minutes).
- route_fares (from_stop, to_stop, bus_class, price_minor, currency).
- schedules (route, bus, departure time, days of week, valid dates, default crew).
- trips (route, bus, crew, departure_at, status, sales_cutoff_minutes, allowed channels).
- trip_seats (trip_id, seat_label, status, hold_expires_at, ticket_id).
- bus_bookings (booking_ref, customer_id nullable, channel, agency_id, branch_id, sold_by_user_id nullable, contact, status, hold_expires_at, total_minor, idempotency_key).
- bus_tickets (ticket_number, trip, seat, from/to stop, passenger, fare_minor, status, channel, branch_id, sold_by_user_id, sold_at, scanned_at, scanned_by).
- ticket_events (append-only audit).

## Seat Safety
- Atomic conditional updates inside transactions.
- Holds expire via scheduler.
- Trip never exceeds capacity.

## RBAC
- bus.* permissions with scope: ticket_agent=own sales, branch_manager=branch, agency_manager=agency, admin=all.
