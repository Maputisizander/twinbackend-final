# Skycable — Design & API Plan

Skycable is shutting down. Goal: complete teardown and collection of all equipment and cables from poles.

---

## Business Context

- Skycable wires/equipment are attached to Meralco-owned poles
- All components must be physically removed and collected
- Field linemen report via **mobile app** (React Native)
- Back office monitors via **dashboard** (React Vite)
- Reports needed for audit: before/after photos, cable expected vs actual, time tracking

---

## Data Hierarchy

```
Skycable Area (NCR / North Luzon / South Luzon / Visayas / Mindanao)
  └── Province (PSGC)
        └── City / Municipality (PSGC)
              └── Barangay (PSGC)
                    └── Node (e.g. "Node 1" → auto-labels as "1-A", "1-B" if multiple)
                          └── Pole (linked to shared poles table)
                                └── Span (pole_from → pole_to pair)
                                      ├── Collectable Components
                                      └── Teardown Report
```

---

## Tables

### `skycable_areas`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | NCR, North Luzon, South Luzon, Visayas, Mindanao |

### `skycable_nodes`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| area_id | bigint | FK → skycable_areas |
| barangay_code | string | FK → psgc_barangays |
| name | string | e.g. "Node 1" |
| label | string | auto: "A", "B", "C" — if multiple nodes same name+barangay |
| full_label | string | computed: "Node 1-A" |
| subcontractor_id | bigint | FK → subcontractors (required for Skycable) |
| team_id | bigint | nullable, FK → teams (must belong to the subcontractor) |
| status | enum | pending / in_progress / completed |
| data_source | enum | manual / json_import / ai_scanner |
| source_file | string | nullable — filename of JSON or AI scan used |
| timestamps | | |

**Auto-label logic:** When a new node is created with the same `name` in the same `barangay_code`, the system automatically assigns the next letter label (A → B → C...).

**Node ID sources:**
- `manual` — back office manually types in the node data
- `json_import` — bulk upload via JSON file, backend parses and creates nodes
- `ai_scanner` — Python AI sitemap scanner auto-detects and sends node data to API

**JSON import format (expected):**
```json
[
  {
    "node_id": "Node 1",
    "area": "NCR",
    "barangay_code": "PH137404057",
    "poles": [
      { "pole_code": "PL-001", "lat": 14.5995, "lng": 120.9842 }
    ]
  }
]
```

**AI scanner** — Python tool scans sitemaps, generates JSON, posts to:
`POST /api/v1/skycable/nodes/import` with `data_source: ai_scanner`

### `skycable_poles`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| node_id | bigint | FK → skycable_nodes |
| pole_id | bigint | FK → shared poles table |
| sequence | integer | order within the node map |
| timestamps | | |

### `skycable_spans`
*(A span = connection between two poles, where cables and equipment are installed)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| pole_from_id | bigint | FK → skycable_poles |
| pole_to_id | bigint | FK → skycable_poles |
| status | enum | pending / in_progress / completed |
| timestamps | | |

**Example:** Pole 1 as center can have multiple spans:
- Span 1→2
- Span 1→3
- Span 1→4

### `skycable_span_components`
*(Collectable equipment per span)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| span_id | bigint | FK → skycable_spans |
| component_type | enum | node / amplifier / extender / tsc / wire |
| expected_count | decimal | from map data |
| actual_count | decimal | reported by lineman |
| unit | string | pcs / meters |
| timestamps | | |

### `skycable_teardown_reports`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| span_id | bigint | FK → skycable_spans |
| team_id | bigint | FK → teams |
| lineman_id | bigint | FK → users |
| start_time | timestamp | when lineman started |
| end_time | timestamp | when lineman finished |
| duration_minutes | integer | auto-computed (end - start) |
| expected_cable | decimal | meters, from map |
| actual_cable | decimal | meters, reported by lineman |
| before_photo | string | file path / URL |
| after_photo | string | file path / URL |
| pole_tag_photo | string | file path / URL |
| bunching_photo | string | file path / URL — re-bundled remaining wires |
| notes | text | nullable |
| status | enum | pending / submitted / verified |
| timestamps | | |

### `skycable_teardown_report_slots`
*(Which cable slots on the pole were removed — part of the teardown report)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| teardown_report_id | bigint | FK → skycable_teardown_reports |
| pole_id | bigint | FK → poles |
| pole_cable_slot_id | bigint | FK → pole_cable_slots |
| slot_label | string | free-form text — lineman inputs the slot label from the physical pole |

**Slot info is part of the teardown report** — submitted together with:
- `before_photo` — photo before wire removal (slot visible)
- `after_photo` — photo after wire removal (slot now free)
- `pole_tag_photo` — pole ID tag photo

**On teardown report submission:** linked slot records auto-updated to `free`.

---

## Mobile App Workflow (Lineman)

1. Login → see assigned node (from team assignment)
2. Select node → view pole list
3. Select pole → view available spans (e.g. 1→2, 1→3, 1→4)
4. Select span → start teardown
   - `start_time` recorded automatically
   - Fill in actual components collected vs expected
   - Fill in actual cable vs expected
   - Upload photos: before, after, pole tag, bunching
   - Submit → `end_time` recorded, `duration_minutes` computed
5. Repeat per span until node complete

---

## Dashboard (Back Office)

- View all areas → nodes → poles → spans
- Filter by: area, province, city, barangay, status
- See team assignments per node
- Reassign teams to nodes
- View teardown reports with photos and audit times
- Export reports

---

## API Endpoints

### Areas & Nodes
- `GET  /api/v1/skycable/areas`
- `GET  /api/v1/skycable/areas/{id}/nodes`
- `POST /api/v1/skycable/nodes` *(back office)*
- `GET  /api/v1/skycable/nodes/{id}`
- `GET  /api/v1/skycable/nodes/{id}/poles`

### Spans
- `GET  /api/v1/skycable/poles/{id}/spans`
- `GET  /api/v1/skycable/spans/{id}`
- `GET  /api/v1/skycable/spans/{id}/components`

### Teardown Reports
- `POST /api/v1/skycable/spans/{id}/teardown/start` — records start_time
- `POST /api/v1/skycable/spans/{id}/teardown/submit` — records end_time, components, photos
- `GET  /api/v1/skycable/teardowns` — back office list
- `GET  /api/v1/skycable/teardowns/{id}`

### Team Assignments
- `GET  /api/v1/skycable/nodes/{id}/team`
- `POST /api/v1/skycable/nodes/{id}/assign-team`

---

## Status Flows

### Node Status
`pending` → `in_progress` → `completed`

### Span Status
`pending` → `in_progress` (start_time recorded) → `completed` (report submitted)

### Teardown Report Status
`pending` → `submitted` → `verified` (back office review)

---

## Warehouse & Delivery Staging System

### Overview — Staging Flow

```
Pole Teardown (collected items)
    ↓
Subcon Warehouse  ← warehouseman approves receipt
    ↓ (request pickup when full or ready)
Pickup Request → approved by warehouseman
    ↓
Staging Warehouse(s) ← arrival + acceptance documented at each hop
    ↓
TelcoVantage Main Warehouse ← final arrival + acceptance
    ↓
Pull Out Declaration (for sale / for delivery)
    → approved by authorized user
    → arrival + acceptance at destination documented
```

---

### `warehouses`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | warehouse name |
| type | enum | subcon / staging / main |
| subcontractor_id | bigint | nullable FK → subcontractors (null if staging or main) |
| address | text | nullable |
| sqm | decimal | floor area in square meters |
| status | enum | active / inactive |
| timestamps | | |

**Rules:**
- Auto-create a `subcon` warehouse when a subcontractor is added (named after them)
- Subcon can have multiple warehouses; selection logic: 1 = auto-select, multiple = dropdown
- `staging` and `main` warehouses are created by TelcoVantage admin
- `main` = TelcoVantage's final receiving warehouse

---

### `warehouse_stocks`
*(Running inventory per warehouse)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| warehouse_id | bigint | FK → warehouses |
| item_type | enum | node / amplifier / extender / tsc / cable |
| quantity | decimal | current stock count or meters |
| unit | string | pcs / meters |
| timestamps | | |

---

### `warehouse_receipts`
*(Items received INTO a warehouse — applies at every stage)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| warehouse_id | bigint | FK → warehouses (destination) |
| from_delivery_id | bigint | nullable FK → deliveries |
| node_id | bigint | nullable FK → skycable_nodes |
| received_by | bigint | FK → users (warehouseman) |
| receipt_date | date | |
| approved_by | bigint | nullable FK → users |
| approved_at | timestamp | nullable |
| status | enum | pending / approved / rejected |
| notes | text | nullable |
| timestamps | | |

### `warehouse_receipt_items`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| receipt_id | bigint | FK → warehouse_receipts |
| item_type | enum | node / amplifier / extender / tsc / cable |
| quantity | decimal | |
| unit | string | pcs / meters |

---

### `pickup_requests`
*(Subcon requests pickup from their warehouse when full/ready)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| from_warehouse_id | bigint | FK → warehouses (subcon warehouse) |
| to_warehouse_id | bigint | FK → warehouses (staging or main) |
| requested_by | bigint | FK → users |
| approved_by | bigint | nullable FK → users (warehouseman) |
| approved_at | timestamp | nullable |
| status | enum | pending / approved / rejected / dispatched |
| notes | text | nullable |
| timestamps | | |

---

### `deliveries`
*(Movement of items between warehouses — one delivery per hop)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| pickup_request_id | bigint | nullable FK → pickup_requests |
| from_warehouse_id | bigint | FK → warehouses |
| to_warehouse_id | bigint | FK → warehouses |
| dispatched_by | bigint | FK → users |
| dispatched_at | timestamp | |
| arrived_at | timestamp | nullable |
| accepted_by | bigint | nullable FK → users |
| accepted_at | timestamp | nullable |
| status | enum | pending / in_transit / arrived / accepted / rejected |
| notes | text | nullable |
| timestamps | | |

### `delivery_items`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| delivery_id | bigint | FK → deliveries |
| item_type | enum | node / amplifier / extender / tsc / cable |
| quantity | decimal | |
| unit | string | pcs / meters |

---

### `pull_out_requests`
*(Items leaving TelcoVantage main warehouse — for sale or delivery)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| warehouse_id | bigint | FK → warehouses (main) |
| purpose | enum | for_sale / for_delivery |
| declared_by | bigint | FK → users |
| approved_by | bigint | nullable FK → users |
| approved_at | timestamp | nullable |
| destination | string | buyer / delivery address |
| arrival_confirmed_by | bigint | nullable FK → users |
| arrival_confirmed_at | timestamp | nullable |
| status | enum | pending / approved / rejected / dispatched / delivered |
| notes | text | nullable |
| timestamps | | |

### `pull_out_items`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| pull_out_id | bigint | FK → pull_out_requests |
| item_type | enum | node / amplifier / extender / tsc / cable |
| quantity | decimal | |
| unit | string | pcs / meters |

---

## Daily Reports

### Node Assignment Flow
```
TelcoVantage backend assigns Node → Subcontractor
  → Subcontractor assigns Team to Node
  OR
  → TelcoVantage backend assigns Team directly
```

### Teardown Report Approval Flow
```
Lineman submits teardown report / daily report
  → Subcontractor Project Manager reviews
      → system flags MISSING IMAGES (before/after/pole tag/bunching)
      → lineman uploads missing images
  → Subcontractor PM approves
  → TelcoVantage Backend Team gives FINAL approval
  → Report visible on main dashboard
```

### `skycable_daily_reports`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| node_id | bigint | FK → skycable_nodes |
| team_id | bigint | FK → teams |
| subcontractor_id | bigint | FK → subcontractors |
| submitted_by | bigint | FK → users (lineman) |
| report_date | date | |
| status | enum | draft / submitted / subcon_reviewing / subcon_approved / backend_approved / rejected |
| subcon_reviewed_by | bigint | nullable FK → users (Subcontractor PM) |
| subcon_reviewed_at | timestamp | nullable |
| backend_approved_by | bigint | nullable FK → users (TelcoVantage backend team) |
| backend_approved_at | timestamp | nullable |
| rejection_reason | text | nullable |
| notes | text | nullable |
| timestamps | | |

### `skycable_daily_report_logs`
*(Which teardown logs are included in this daily report)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| daily_report_id | bigint | FK → skycable_daily_reports |
| teardown_report_id | bigint | FK → skycable_teardown_reports |

### Missing Image Validation
When Skycable admin opens a daily report for review, the API returns a **missing images summary**:

```json
{
  "report_id": 1,
  "missing_images": [
    {
      "teardown_report_id": 12,
      "span": "Pole 1 → Pole 2",
      "missing": ["after_photo", "bunching_photo"]
    },
    {
      "teardown_report_id": 15,
      "span": "Pole 3 → Pole 4",
      "missing": ["pole_tag_photo"]
    }
  ]
}
```

- Admin sees pop-up list of incomplete teardown logs
- Lineman is notified to upload missing images
- Admin cannot approve until all required images are present
- Required images: `before_photo`, `after_photo`, `pole_tag_photo` (bunching optional if no rebundling done)

### Status Flow
```
draft
  → submitted          (lineman submits)
  → subcon_reviewing   (Subcontractor PM reviews — checks missing images)
  → subcon_approved    (Subcontractor PM approves)
  → backend_approved   (TelcoVantage final approval — visible on main dashboard)
  → rejected           (subcon PM or TelcoVantage rejects → back to lineman)
```

### Skycable Notifications

| Trigger | Recipients |
|---|---|
| Node assigned to subcontractor | Subcontractor PM notified |
| Team assigned to node | All team members (linemen) notified |
| Node re-assigned to different subcon/team | Old + new subcon/team notified |
| Lineman submits teardown/daily report | Subcontractor PM notified |
| Missing images flagged | Lineman notified |
| Subcontractor PM approves report | TelcoVantage backend team notified |
| TelcoVantage approves report | Lineman + Subcontractor PM notified |
| TelcoVantage rejects report | Lineman + Subcontractor PM notified |

---

## TODO
- [ ] Migrations: skycable_areas, skycable_nodes, skycable_poles, skycable_spans, skycable_span_components, skycable_teardown_reports
- [ ] Subcontractor assigned to node (validation: team must belong to subcon)
- [ ] Auto-label logic for nodes (A/B/C)
- [ ] Photo upload handling (storage disk config)
- [ ] API controllers + routes
- [ ] Mobile: span selection UI (pole as center, multiple span options)
- [ ] Dashboard: filter by area + PSGC hierarchy
- [ ] Export teardown reports (CSV/PDF)
- [ ] Export: Pole List Report (TBD — format to be shown)
- [ ] Export: RTD Report (TBD — format to be shown)
- [ ] Export: Vicinity Map (TBD — format to be shown)
- [ ] Warehouse auto-create on subcon registration
- [ ] Warehouse selection logic (1 = auto-select, multiple = dropdown)
- [ ] Warehouse stock ledger (running inventory per item type)
- [ ] Pickup request flow (subcon → approval → dispatch)
- [ ] Delivery hop tracking (from_warehouse → to_warehouse, arrival + acceptance)
- [ ] Pull out request (for_sale / for_delivery, approval + arrival confirmation)
- [ ] Stock auto-update on receipt approval and delivery acceptance
- [ ] Daily report submission + approval flow (TBD)
