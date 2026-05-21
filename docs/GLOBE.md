# Globe — Design & API Plan

Globe telco is removing fiber cables from Meralco-owned poles. NAP box inventory must also be maintained.

---

## Business Context

- Globe wires are attached to Meralco-owned poles
- Only the **fiber cable wire** is being removed (not the NAP boxes)
- **NAP boxes remain** on poles — inventory tracked per port (active/inactive/free)
- **Back office (backend team) creates tickets** — linemen cannot create tickets
- Tickets appear on the mobile app of linemen belonging to the assigned team
- A lineman can only pick up a ticket assigned to **their team**
- No warehouse system needed for Globe

---

## Data Hierarchy

```
Region (PSGC)
  └── Province
        └── City / Municipality
              └── Barangay
                    └── Pole (shared poles table)
                          ├── NAP Box (8 or 16 ports)
                          │     └── NAP Ports (per port status)
                          └── Ticket (teardown work order)
                                └── Teardown Report
```

---

## Tables

### `globe_nap_boxes`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| pole_id | bigint | FK → shared poles table |
| nap_code | string | unique identifier of the NAP box |
| port_count | enum | 8 / 12 / 16 / 32 |
| status | enum | active / inactive / for_removal |
| timestamps | | |

### `globe_nap_ports`
*(One row per port in a NAP box — auto-generated on NAP box creation)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| nap_box_id | bigint | FK → globe_nap_boxes |
| port_number | integer | 1 to 8, 12, 16, or 32 |
| status | enum | active / inactive / free |
| subscriber_id | string | nullable — subscriber identifier |
| subscriber_name | string | nullable |
| account_number | string | nullable — subscriber account number |
| surveyed_by | bigint | nullable FK → users |
| surveyed_at | timestamp | nullable — when this port was last surveyed |
| updated_by | bigint | nullable FK → users |
| timestamps | | |

**Auto-generate:** When a NAP box is created, system creates port rows equal to `port_count` (all `free`). Options: **8, 12, 16, 32**.

**Visual UI (Dashboard):**
- Displayed as a **clickable grid of colored buttons** per NAP box
- 🔴 Red = `active` (occupied, paying subscriber)
- 🟠 Orange = `inactive` (subscriber disconnected, wire still there)
- 🟢 Green = `free` (available slot)
- Click a port → view/edit: subscriber_id, account_number, subscriber_name, status

**Inventory summary per NAP box:** total ports, active count, inactive count, free slots.

---

### NAP Box Survey

Field person visits the physical NAP box and records per-port subscriber info.

### `globe_nap_surveys`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| nap_box_id | bigint | FK → globe_nap_boxes |
| surveyed_by | bigint | FK → users |
| surveyed_at | timestamp | |
| notes | text | nullable |
| status | enum | pending / partial / complete |
| timestamps | | |

### `globe_nap_survey_items`
*(Per-port findings during the survey)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| survey_id | bigint | FK → globe_nap_surveys |
| port_number | integer | which port slot |
| subscriber_id | string | nullable |
| account_number | string | nullable |
| subscriber_name | string | nullable |
| status | enum | active / inactive / free |
| notes | text | nullable |

**Survey flow:**
1. Field person opens NAP box in mobile app
2. Taps each port slot → enters subscriber_id, account_number, status
3. Submits survey → updates `globe_nap_ports` with latest data
4. Back office reviews survey → identifies inactive ports with wire still attached
5. Creates teardown ticket for those ports

### `globe_tickets`
*(Created by back office, executed by lineman)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| ticket_number | string | unique, auto-generated |
| subcontractor_id | bigint | nullable FK → subcontractors (if team is under a subcon) |
| team_id | bigint | FK → teams |
| pole_id | bigint | FK → shared poles table |
| nap_box_id | bigint | nullable, FK → globe_nap_boxes |
| created_by | bigint | FK → users (back office) |
| assigned_at | timestamp | |
| status | enum | pending / in_progress / completed / cancelled |
| notes | text | nullable |
| timestamps | | |

**Team options for Globe:**
- **Direct team** — `subcontractor_id` is null, team belongs directly to Globe
- **Subcon team** — `subcontractor_id` set, team belongs to that subcontractor

**Ticket rule:** Only members of the assigned `team_id` can claim and execute the ticket.

### `globe_teardown_reports`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| ticket_id | bigint | FK → globe_tickets |
| lineman_id | bigint | FK → users |
| wire_status | enum | pending / removed |
| teardown_date | date | |
| before_photo | string | file path / URL |
| after_photo | string | file path / URL |
| pole_tag_photo | string | file path / URL |
| notes | text | nullable |
| status | enum | submitted / approved / rejected |
| approved_by | bigint | nullable FK → users |
| approved_at | timestamp | nullable |
| rejection_reason | text | nullable |
| timestamps | | |

### `globe_teardown_report_slots`
*(Which cable slots on the pole Globe's wire was removed from)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| teardown_report_id | bigint | FK → globe_teardown_reports |
| pole_id | bigint | FK → poles |
| pole_cable_slot_id | bigint | FK → pole_cable_slots |
| slot_label | string | free-form — as labeled on physical pole |

**On teardown approval:** Globe's slot(s) auto-updated to `free` in `pole_cable_slots`.

---

## Mobile App Workflow (Lineman)

1. Login → see tickets assigned to my team
2. Select ticket → view pole info + NAP box details
3. Claim ticket → status changes to `in_progress`
4. Perform teardown:
   - Upload before photo
   - Remove fiber cable wire
   - Upload after photo + pole tag photo
   - Set `wire_status` = removed
   - Set `teardown_date`
5. Submit report → ticket status → `completed`

---

## NAP Box Inventory Workflow (Back Office / Dashboard)

1. View poles on map or list
2. Select pole → see attached NAP boxes
3. Per NAP box: view all ports with status (active / inactive / free)
4. Update port status if a subscriber is disconnected or reconnected
5. See summary: X occupied, Y free out of 8 or 16 ports

---

## Dashboard (Back Office)

- Create and manage tickets
- Assign tickets to teams
- View ticket status (pending / in_progress / completed)
- View teardown reports with photos
- NAP box inventory per pole (filter by area, province, city, barangay)
- Monitor lineman GPS location

---

## API Endpoints

### NAP Boxes
- `GET  /api/v1/globe/poles/{id}/nap-boxes`
- `POST /api/v1/globe/nap-boxes` *(back office)*
- `GET  /api/v1/globe/nap-boxes/{id}`
- `GET  /api/v1/globe/nap-boxes/{id}/ports`
- `PUT  /api/v1/globe/nap-boxes/{id}/ports/{port_number}` — update port status

### Tickets
- `GET  /api/v1/globe/tickets` — back office: all; lineman: team's tickets
- `POST /api/v1/globe/tickets` *(back office only)*
- `GET  /api/v1/globe/tickets/{id}`
- `POST /api/v1/globe/tickets/{id}/claim` *(lineman, must be team member)*
- `PUT  /api/v1/globe/tickets/{id}/status`

### Teardown Reports
- `POST /api/v1/globe/tickets/{id}/teardown` — submit teardown report + photos
- `GET  /api/v1/globe/teardowns` — back office list
- `GET  /api/v1/globe/teardowns/{id}`

### Team Assignments
- `GET  /api/v1/globe/tickets/{id}/team`
- `POST /api/v1/globe/tickets/{id}/assign-team` *(back office)*

---

## Ticket Number Format

Auto-generated on creation. Suggested format:
`GLB-YYYYMMDD-XXXX` (e.g. `GLB-20260422-0001`)

---

## Status Flows

### Ticket Status
```
pending
  → in_progress  (lineman claims ticket)
  → for_approval (lineman submits teardown report)
  → completed    (back office approves — ticket officially closed)
  → rejected     (back office rejects — lineman must resubmit)
```

### Teardown Report Status
```
submitted → approved (back office) → ticket closed
         → rejected  (back office) → lineman resubmits
```

### Wire Status
`pending` → `removed`

---

## Ticket Approval (Back Office)

When a lineman submits a teardown report, the ticket moves to `for_approval`.

Back office sees a **summary view** per ticket:
- Pole info + NAP box
- Wire status (removed / pending)
- Before / after / pole tag photos
- Lineman who submitted + date

Actions:
- **Approve** → `teardown_reports.status = approved`, `ticket.status = completed`
- **Reject** → `teardown_reports.status = rejected`, `ticket.status = in_progress` (lineman notified to resubmit)

Add to `globe_teardown_reports`:
| Column | Type | Notes |
|---|---|---|
| approved_by | bigint | nullable FK → users (back office) |
| approved_at | timestamp | nullable |
| rejection_reason | text | nullable |

---

## Daily Reports

Linemen submit a daily summary of their teardown work. Back office reviews and approves.

### `globe_daily_reports`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| team_id | bigint | FK → teams |
| submitted_by | bigint | FK → users (lineman) |
| report_date | date | |
| total_tickets_completed | integer | auto-computed |
| total_wire_removed | integer | count of wires removed |
| status | enum | draft / submitted / approved / rejected |
| approved_by | bigint | nullable FK → users (back office) |
| approved_at | timestamp | nullable |
| rejection_reason | text | nullable |
| notes | text | nullable |
| timestamps | | |

### `globe_daily_report_tickets`
*(Which tickets are included in this daily report)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| daily_report_id | bigint | FK → globe_daily_reports |
| ticket_id | bigint | FK → globe_tickets |

**Daily report flow:**
```
Lineman completes tickets throughout the day
  → Submits daily report (groups completed tickets)
  → Back office reviews + approves
  → Report closed
```

---

## Notifications

| Trigger | Recipients | Message |
|---|---|---|
| Back office creates + assigns ticket to team | All team members | "New ticket {ticket_number} assigned to your team" |
| Lineman claims ticket | Back office | "Ticket {ticket_number} claimed by {lineman}" |
| Lineman submits teardown report | Back office | "Ticket {ticket_number} submitted for approval" |
| Back office approves ticket | Lineman who submitted | "Ticket {ticket_number} approved and closed" |
| Back office rejects ticket | Lineman who submitted | "Ticket {ticket_number} rejected — {reason}" |
| Lineman submits daily report | Back office | "Daily report submitted by {team} for {date}" |
| Back office approves daily report | Team linemen | "Daily report for {date} approved" |

**Implementation:** Laravel notifications via database + push (FCM for mobile)

---

## TODO
- [ ] Migrations: globe_nap_boxes, globe_nap_ports, globe_tickets, globe_teardown_reports, globe_daily_reports
- [ ] Ticket number auto-generation logic (GLB-YYYYMMDD-XXXX)
- [ ] Team membership check middleware (lineman can only claim team's ticket)
- [ ] Back office only can create tickets (role middleware)
- [ ] Photo upload handling
- [ ] API controllers + routes
- [ ] NAP inventory summary endpoint (occupied/free count per NAP box)
- [ ] Dashboard: filter by PSGC hierarchy
- [ ] Notification system (database + FCM push)
- [ ] Daily report submission + approval flow
