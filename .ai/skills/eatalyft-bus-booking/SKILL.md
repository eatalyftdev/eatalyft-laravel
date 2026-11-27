# EataLyft Bus Booking Skill

## Overview
Bus booking engine for Cameroon inter-city transport.

## Key Components
- SeatMap.tsx: Interactive seat selection with real-time Reverb sync
- BookBusTripAction: Atomic booking with DB::transaction() + lockForUpdate()
- 5 seat layouts: 75, 65, 50, 30, 18

## Booking Flow
1. Search trips by origin city, destination, date, bus class
2. Select seats on interactive heatmap
3. Enter passenger details (name, phone +237, CNI)
4. Payment (wallet or counter cash)
5. Ticket issuance with QR code

## Concurrency
- Seat holds: 5-minute temporary lock
- Atomic seat booking with lockForUpdate()
- Idempotency keys on all bookings