# Meralco — Design & API Plan

Meralco is a **TelcoVantage client** — view-only access to their pole data. TelcoVantage manages their user accounts.

---

## Business Context

- Meralco owns all poles (shared infrastructure)
- Telcos (Skycable, Globe) pay Meralco fees to attach wires to their poles
- Disconnected telco subscribers whose wires are **still attached** cost Meralco money
- Meralco needs to see: which poles still have active/pending telco wires
- Once wires are removed, they need **proof** (photos from teardown reports)
- Meralco has **no mobile app** — dashboard only (React Vite)
- Meralco is **read-only** — they do not create, edit, or delete anything
- **TelcoVantage manages Meralco user accounts** — Meralco cannot manage their own users
- All Meralco users have the same view-only access level — no role distinction

---

## Data Hierarchy

```
Region (PSGC)
  └── Province
        └── City / Municipality
              └── Barangay
                    └── Pole (shared poles table)
                          ├── Skycable spans attached (status)
                          └── Globe NAP boxes + wires (status)
```

---

## What Meralco Sees Per Pole

| Info | Source |
|---|---|
| Pole code | shared poles table |
| Location (region → barangay) | PSGC via barangay_code |
| Skycable status | skycable_spans + teardown reports |
| Globe wire status | globe_teardown_reports |
| Globe NAP box status | globe_nap_boxes |
| Teardown photos (proof) | skycable/globe teardown reports |
| Subscribers still attached | cross-reference teardown status |

---

## No Dedicated Meralco Tables

Meralco reads from shared and cross-company data:
- `poles` — owned by Meralco
- `psgc_*` — location hierarchy
- `skycable_spans`, `skycable_teardown_reports`
- `globe_nap_boxes`, `globe_nap_ports`, `globe_teardown_reports`

---

## Dashboard Views

### 1. Pole List / Map View
- Filter by: region, province, city, barangay
- Per pole: show status indicators
  - Skycable: pending / in_progress / cleared
  - Globe: pending / in_progress / cleared
- Color-coded: red (still attached) / yellow (in progress) / green (cleared)

### 2. Pole Detail View
- Pole info (code, location, coordinates)
- Skycable section:
  - Which node/spans are attached
  - Teardown status per span
  - Before/after photos
- Globe section:
  - NAP boxes on this pole
  - Wire removal status
  - Before/after photos
- Overall pole status: fully cleared when both Skycable + Globe = cleared

### 3. Reports / Summary
- Total poles in area
- Poles fully cleared
- Poles with pending teardown (Skycable / Globe / both)
- Filter by area, date range

---

## API Endpoints

### Poles
- `GET /api/v1/meralco/poles` — list with filters (region, province, city, barangay, status)
- `GET /api/v1/meralco/poles/{id}` — pole detail with Skycable + Globe status

### Summary / Reports
- `GET /api/v1/meralco/summary` — aggregate counts (cleared / pending / in_progress)
- `GET /api/v1/meralco/summary?barangay_code=` — filter by location

### Teardown Proof (Photos)
- `GET /api/v1/meralco/poles/{id}/teardown-proof` — all photos from Skycable + Globe reports for this pole

---

## Pole Status Logic (Computed)

```
Pole is "Cleared" when:
  - All Skycable spans on this pole = completed teardown (all slots freed)
  - All Globe wires on this pole = removed
  
Pole is "Partial" when:
  - One company cleared, other still pending
  (e.g. Skycable cleared but Globe wire still attached)

Pole is "In Progress" when:
  - At least one Skycable span or Globe wire is in_progress
  
Pole is "Pending" when:
  - No teardown started yet
```

## Billing Clearance — Skycable

When all Skycable teardown reports for a pole are `backend_approved`:
- All `pole_cable_slots` occupied by Skycable on that pole → auto-set to `free`
- Pole record updated: `skycable_status = cleared`
- **Meralco dashboard shows:** "Skycable wire removed — billing can stop"
- Timestamp recorded: `skycable_cleared_at`

This gives Meralco documented **proof with timestamp** that Skycable wire was removed — basis for stopping the pole attachment fee.

Add to `poles` table:
| Column | Type | Notes |
|---|---|---|
| skycable_status | enum | pending / in_progress / cleared |
| skycable_cleared_at | timestamp | nullable — when all Skycable teardown approved |
| globe_status | enum | pending / in_progress / cleared |
| globe_cleared_at | timestamp | nullable — when all Globe wire removal approved |

---

## TODO
- [ ] Meralco-specific API controllers (read-only, no write permissions)
- [ ] Pole status computation logic (aggregate Skycable + Globe status)
- [ ] Teardown proof endpoint (compile photos from both telcos)
- [ ] Dashboard filter by PSGC hierarchy
- [ ] Summary/reporting endpoints
- [ ] Role middleware: meralco users cannot POST/PUT/DELETE anything
