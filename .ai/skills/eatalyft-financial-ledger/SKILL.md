# EataLyft Financial Ledger Skill

## Overview
Fintech-grade double-entry ledger for transport payments.

## Rules
- ALL monetary values in BIGINT minor units (XAF integers)
- NEVER use FLOAT/DOUBLE for money
- Currency ISO codes: XAF, USD, EUR, GBP, JPY
- Every debit/credit inside DB::transaction() with lockForUpdate()
- Idempotency keys on all financial operations

## Commission Split
- Platform Commission
- Agency Net Settlement
- Sub-Branch/Station attribution

## Payment Modes
1. Internal Wallet Settlement (instant atomic)
2. Counter Cash / Mobile Money (pending gateway)