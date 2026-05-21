# TwinBackend — Main API Planner

Laravel API-only backend serving 3 separate client apps.

---

## Apps Overview

| Company  | Dashboard     | Mobile App     | Role             |
|----------|--------------|----------------|------------------|
| Skycable | React Vite   | React Native   | Teardown + Collect |
| Globe    | React Vite   | React Native   | Teardown + NAP Inventory |
| Meralco  | React Vite   | None           | Read-only monitoring |

---

## Architecture

- **Framework:** Laravel (API only — no Blade views)
- **Auth:** Laravel Sanctum (token-based login)
- **Database:** SQLite (upgradeable to MySQL)
- **API prefix:** `/api/v1/`
- **File storage:** Local disk (`storage/app/public`) — Docker volume mounted, switchable to S3 via `.env`
- **Image processing:** Intervention Image v3 (`intervention/image-laravel`)
  - Scale down to **≤ 1280px** on longest side (never upscale)
  - Convert to JPEG at **75% quality**
  - Applied to: from_before/from_after/from_pole_tag/to_before/to_after/to_pole_tag/bunching (Skycable), before/after/pole_tag (Globe)
  - Handled via shared `StoresPhotos` trait in `App\Http\Concerns\StoresPhotos`

---

## GPS Tracking

- **Mobile app only** — only linemen (React Native) send GPS coordinates
- Dashboard/web users do NOT send GPS
- Mobile app sends `gps_lat` + `gps_lng` on **every authenticated API request**
- Backend middleware auto-updates `users.current_gps_lat` + `users.current_gps_lng` on each mobile request
- `last_login` updated on every successful login (all platforms)

## Active Users Monitoring

Visible on dashboard for all three apps — shows who is currently online/active.

| Field | Notes |
|---|---|
| `last_seen_at` | timestamp — updated on every API request (mobile + dashboard) |
| `is_online` | computed: `last_seen_at` within last 5 minutes = online |
| `current_gps_lat/lng` | mobile users only — shows on map |

**Dashboard displays:**
- List of active linemen with GPS pin on map (mobile users)
- List of active back office / admin users (dashboard users, no GPS)
- Per company: Skycable active field team, Globe active field team
- TelcoVantage admin sees active users across ALL companies

**API endpoints:**
- `GET /api/v1/skycable/active-users` — Skycable active linemen + GPS
- `GET /api/v1/globe/active-users` — Globe active linemen + GPS
- `GET /api/v1/admin/active-users` — all active users across all companies

---

## Offline Mode (Mobile)

Linemen may lose signal in the field. Mobile app must support offline submission.

**How it works:**
- Mobile app stores teardown data locally (AsyncStorage / SQLite on device) when offline
- When internet is restored → app auto-syncs queued submissions to backend
- Backend tracks sync state per submission:

| Field | Notes |
|---|---|
| `offline_mode` | boolean — was this submitted while offline? |
| `captured_at_device` | timestamp when lineman submitted on device |
| `received_at_server` | timestamp when server received the sync |
| `synced_at_server` | timestamp when server fully processed it |

- Applied to: **Skycable teardown reports**, **Globe teardown reports**
- Photos are queued and uploaded when connection is restored
- GPS coordinates captured at time of field submission (device time), not sync time

---

## IT Support Ticketing System

Clients (Skycable, Globe, Meralco) can submit support tickets to TelcoVantage IT team.

### `support_tickets`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| ticket_number | string | auto-generated e.g. `SUP-20260422-0001` |
| company | enum | skycable / globe / meralco |
| submitted_by | bigint | FK → users (client user) |
| assigned_to | bigint | nullable FK → users (TelcoVantage IT/admin) |
| subject | string | |
| description | text | |
| priority | enum | low / medium / high / urgent |
| status | enum | open / in_progress / resolved / closed |
| resolved_at | timestamp | nullable |
| closed_at | timestamp | nullable |
| timestamps | | |

### `support_ticket_messages`
*(Conversation thread per ticket)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| ticket_id | bigint | FK → support_tickets |
| sender_id | bigint | FK → users |
| message | text | |
| attachment | string | nullable — file path |
| timestamps | | |

### `support_ticket_attachments`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| ticket_id | bigint | FK → support_tickets |
| message_id | bigint | nullable FK → support_ticket_messages |
| file_path | string | |
| file_name | string | |
| timestamps | | |

**Flow:**
```
Client submits ticket (subject + description + priority)
  → TelcoVantage IT sees new ticket
  → IT assigns to a team member
  → Thread conversation (client ↔ IT)
  → IT marks resolved → client confirms → closed
```

**Notifications:**
- New ticket submitted → TelcoVantage IT team notified
- IT replies → client notified
- Ticket resolved → client notified to confirm
- Ticket closed → both parties notified

**API endpoints:**
- `POST /api/v1/{company}/support/tickets` — client creates ticket
- `GET  /api/v1/{company}/support/tickets` — client sees their tickets
- `GET  /api/v1/{company}/support/tickets/{id}` — ticket detail + thread
- `POST /api/v1/{company}/support/tickets/{id}/messages` — reply
- `GET  /api/v1/admin/support/tickets` — IT sees all tickets across companies
- `PUT  /api/v1/admin/support/tickets/{id}/assign`
- `PUT  /api/v1/admin/support/tickets/{id}/status`

---

## Export Features

Available for back office and admin roles.

| Report | Format | Who |
|---|---|---|
| Skycable teardown reports per node | CSV / PDF | Skycable back office, TelcoVantage admin |
| Skycable daily reports | CSV / PDF | Skycable back office, TelcoVantage admin |
| Globe ticket reports | CSV / PDF | Globe back office, TelcoVantage admin |
| Globe daily reports | CSV / PDF | Globe back office, TelcoVantage admin |
| Globe NAP box inventory | CSV | Globe back office, TelcoVantage admin |
| Pole status summary (all companies) | CSV / PDF | TelcoVantage admin, Meralco (view) |

**Export endpoints:**
- `GET /api/v1/skycable/export/teardowns?format=csv&node_id=`
- `GET /api/v1/skycable/export/daily-reports?format=pdf`
- `GET /api/v1/globe/export/tickets?format=csv`
- `GET /api/v1/globe/export/nap-inventory?format=csv`
- `GET /api/v1/meralco/export/poles?format=csv`

---

---

## Shared Tables (All Companies)

### Roles & Permissions

| Role | Scope | Access |
|---|---|---|
| `admin` | TelcoVantage internal | Full access — only one who can **hard delete** (final delete after 120-day retention) |
| `executive` | TelcoVantage internal | View/read everything across all companies. No create/edit/delete |
| `project_manager` | Assigned per project (Globe or Skycable) | View + edit everything in assigned project. Can **soft delete** (120-day retention before admin hard deletes) |
| `back_office` | Per company (Globe / Skycable) | Manage their company data — create tickets (Globe), manage nodes/assignments (Skycable) |
| `subcon_pm` | Per subcontractor | View and manage their subcontractor's teams, linemen, and reports |
| `lineman` | Per team | Mobile app only — submit teardown reports, upload photos |

**Delete policy:**
- All user deletions → **soft delete** (`deleted_at` timestamp, 30-day retention)
- After 30 days → admin can **hard delete** (permanent)
- `project_manager` deletes project data → **soft delete** (120-day retention)
- `admin` final deletes project data → **hard delete** (after 120 days or on demand)

**Active / Inactive:**
- `is_active = false` → user cannot login, gets `403` on all endpoints
- Inactive ≠ deleted — data is preserved, account just suspended
- Admin or back_office can toggle active/inactive per user

### `users`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| company | enum | telcovantage / skycable / globe / meralco |
| role | enum | admin / executive / project_manager / back_office / subcon_pm / lineman |
| project_access | json | nullable — for project_manager: ["skycable", "globe"] |
| subcontractor_id | bigint | nullable FK → subcontractors (for subcon_pm and lineman) |
| team_id | bigint | nullable FK → teams (for lineman) |
| first_name | string | |
| last_name | string | |
| email | string | unique |
| password | hashed | |
| cellphone | string | nullable |
| address | text | nullable |
| profile_photo | string | nullable — compressed via Intervention Image v4 |
| current_gps_lat | decimal | nullable — mobile only, updated per API request |
| current_gps_lng | decimal | nullable — mobile only, updated per API request |
| last_seen_at | timestamp | nullable — updated every API request (online detection) |
| last_login | timestamp | nullable |
| status | enum | active / inactive / on_hold — default active |
| password_reset_required | boolean | default false — forced change on admin reset |
| temp_password_set_at | timestamp | nullable |
| deleted_at | timestamp | nullable — soft delete (30-day retention) |
| timestamps | | created_at, updated_at |

**User-editable (self):** `first_name`, `last_name`, `cellphone`, `address`, `profile_photo`, `email`, `password` (current password required)
**User CANNOT:** delete their own account
**System-managed (PM / Admin only):** `role`, `company`, `status`, `subcontractor_id`, `team_id`

### User Status
| Status | Meaning | Who can set |
|---|---|---|
| `active` | Normal access | Admin / PM |
| `inactive` | Cannot login — deactivated | Admin / PM / Subcon PM |
| `on_hold` | Cannot login — temporarily suspended | Admin / PM / Subcon PM |

- `inactive` / `on_hold` → returns `403` on login attempt
- Account data preserved — not deleted
- Only **admin** can permanently delete (soft delete, 30-day retention)

---

## Audit Logs

Every edit and delete action across the system is recorded — who did it, what changed, old vs new value.

### `audit_logs`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| user_id | bigint | FK → users (who performed the action) |
| company | enum | telcovantage / skycable / globe / meralco |
| action | enum | created / updated / deleted |
| model_type | string | e.g. "User", "Node", "GlobeTicket", "TeardownReport" |
| model_id | bigint | ID of the affected record |
| old_values | json | nullable — values before the change |
| new_values | json | nullable — values after the change |
| ip_address | string | nullable |
| user_agent | string | nullable |
| timestamps | | created_at only |

**Example log entry (edit):**
```json
{
  "user_id": 5,
  "action": "updated",
  "model_type": "User",
  "model_id": 12,
  "old_values": { "first_name": "Juan", "email": "juan@old.com" },
  "new_values": { "first_name": "Juan Carlos", "email": "juan@new.com" }
}
```

**Example log entry (delete):**
```json
{
  "user_id": 1,
  "action": "deleted",
  "model_type": "SkycableNode",
  "model_id": 7,
  "old_values": { "name": "Node 1", "status": "pending" },
  "new_values": null
}
```

**Tracked across:** Users, Nodes, Poles, Spans, Teams, Subcontractors, Tickets, Teardown Reports, NAP Boxes, Daily Reports, Warehouses

**Dashboard view (admin only):**
- Filter by: company, user, model type, action, date range
- Shows: who, what, when, old → new values

**API endpoints:**
- `GET /api/v1/admin/audit-logs` — TelcoVantage admin sees all
- `GET /api/v1/skycable/audit-logs` — Skycable back office sees their company logs
- `GET /api/v1/globe/audit-logs` — Globe back office sees their company logs

### Pole Data Import
- Poles can be **imported from survey data** (CSV/Excel upload)
- Back office or admin uploads the survey file → system creates pole records in bulk
- Import maps: pole_code, location, lat/lng, barangay_code (PSGC)

### `poles` *(Meralco-owned shared infrastructure)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| pole_code | string | unique identifier |
| barangay_code | string | FK → psgc_barangays |
| lat | decimal | |
| lng | decimal | |
| status | enum | active / for_teardown / cleared |
| timestamps | | |

### `pole_cable_slots` *(Physical cable attachment slots per pole)*
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| pole_id | bigint | FK → poles |
| slot_label | string | Standard: `C1`, `C2`, `C3`, `C4`, `C5`, `DA` — custom labels also supported |
| occupied_by | enum | `skycable` / `globe` / `meralco` / `others` / `free` — **nullable** |
| status | enum | `occupied` / `pending_teardown` / `free` |
| timestamps | | |

**Rules:**
- **Standard slots C1–C5 and DA are auto-created (as `free`) when a pole is added** — no manual seeding needed
- Custom labels can still be added via `POST /poles/{id}/slots`
- Once a slot is assigned to a telco → **reserved exclusively for them** — no other telco can use it
- `status: pending_teardown` — a teardown report has been submitted but not yet backend-approved
- On Skycable teardown backend-approval → slot auto-set to `status: free, occupied_by: free`
- On Globe teardown approval → slot auto-set to `status: free, occupied_by: free`
- **Meralco dashboard per pole:** "C3 — Globe | C4 — Skycable | C5 — Free | DA — Meralco"
- A freed slot becomes available for future use by any telco
- Run `php artisan poles:seed-slots` to backfill standard slots on existing poles

### PSGC Location Tables
- `psgc_regions` — code, name
- `psgc_provinces` — code, name, region_code
- `psgc_cities` — code, name, province_code
- `psgc_barangays` — code, name, city_code
- **Seeded once** from PSA PSGC API during setup

### `subcontractors`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| company | enum | skycable / globe |
| name | string | subcontractor company name |
| contact_person | string | nullable |
| contact_number | string | nullable |
| address | text | nullable |
| status | enum | active / inactive |
| timestamps | | |

**Skycable:** subcontractor is always required — all teams belong to a subcon.
**Globe:** subcontractor is optional — teams can be direct or under a subcon.

### `teams`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| company | enum | skycable / globe |
| subcontractor_id | bigint | nullable FK → subcontractors (required for Skycable, optional for Globe) |
| name | string | team name |
| status | enum | active / inactive |
| timestamps | | |

### `team_members`
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| team_id | bigint | FK → teams |
| user_id | bigint | FK → users |
| role | string | lineman / team_leader / supervisor |
| timestamps | | |

---

## Build Order

### Phase 1 — Foundation ✅ DONE
- [x] Configure Laravel as API-only (remove web routes, add Sanctum)
- [x] PSGC seeder (fetch from PSA API, seed regions/provinces/cities/barangays)
- [x] Users table + auth endpoints (login, logout, profile edit)
- [x] Poles table (with auto-seeded C1–C5 + DA cable slots on create)
- [x] `pole_cable_slots` with `pending_teardown` status, nullable `occupied_by`
- [x] Subcontractors table
- [x] Teams + team_members tables (with role per member)

### Phase 2 — Skycable ✅ DONE
- See [SKYCABLE.md](./SKYCABLE.md)
- [x] skycable_areas
- [x] skycable_nodes (with auto-label A/B/C, expected cable, site/subcon/team assignment)
- [x] skycable_poles (junction: node ↔ shared poles, ordered by sequence)
- [x] skycable_spans (from/to pole, strand length, runs, actual cable)
- [x] skycable_span_components (node/amplifier/extender/tsc/cable/powersupply)
- [x] skycable_teardown_reports (start/submit/review/backend-approve flow)
- [x] skycable_teardown_photos (per-pole photo types: from_before/from_after/from_pole_tag, to_before/to_after/to_pole_tag, bunching)
- [x] skycable_teardown_report_slots (pole × slot cleared per teardown)
- [x] skycable_daily_reports
- [x] Intervention Image v3 photo resize (≤1280px, JPEG 75%) on all uploads
- [x] `GET /nodes/{node}/pole-photos` — aggregated pole photo list for reports
- [x] API endpoints (mobile + dashboard)

### Phase 3 — Globe ✅ DONE
- See [GLOBE.md](./GLOBE.md)
- [x] globe_nap_boxes (auto-creates ports on creation)
- [x] globe_nap_ports
- [x] globe_nap_surveys + globe_nap_survey_items
- [x] globe_tickets (auto-generated ticket_number)
- [x] globe_teardown_reports (with slots + photos)
- [x] globe_daily_reports
- [x] API endpoints (mobile + dashboard)

### Phase 4 — Meralco ✅ DONE
- See [MERALCO.md](./MERALCO.md)
- [x] Read-only pole list + detail (cableSlots + napBoxes included)
- [x] `GET /poles/{id}/teardown-proof` — Skycable + Globe clearance proof per pole
- [x] `GET /summary` — cross-company pole clearance overview

### Phase 5 — Planner 🔄 PARTIAL
- [x] Team assigned to node/ticket at creation or update
- [x] Lineman belongs to team (team_id on user)
- [ ] Lineman filtered to only see their team's assigned jobs (endpoint filtering)
- [ ] Back office bulk reassignment endpoints
- [ ] Active users / GPS tracking middleware (planned — see MAIN-PLANNER.md GPS section)
- [ ] Push notifications (new teardown submitted, approved, rejected)
- [ ] Export endpoints (CSV/PDF per node/daily report)

---

## API Endpoints (Planned)

### Auth (Separate per company for security)
- `POST /api/v1/admin/auth/login`     — TelcoVantage team (admin, executive, project_manager) — sees ALL companies
- `POST /api/v1/skycable/auth/login`  — Skycable users only
- `POST /api/v1/globe/auth/login`     — Globe users only
- `POST /api/v1/meralco/auth/login`   — Meralco users only
- `POST /api/v1/skycable/auth/logout`
- `POST /api/v1/globe/auth/logout`
- `POST /api/v1/meralco/auth/logout`
- `GET  /api/v1/skycable/auth/me`
- `GET  /api/v1/globe/auth/me`
- `GET  /api/v1/meralco/auth/me`
- `PUT  /api/v1/skycable/auth/profile`
- `PUT  /api/v1/globe/auth/profile`
- `PUT  /api/v1/meralco/auth/profile`
- `POST /api/v1/skycable/auth/forgot-password`
- `POST /api/v1/globe/auth/forgot-password`
- `POST /api/v1/meralco/auth/forgot-password`

**Security rules:**
- Each login endpoint validates `users.company` matches the endpoint's company
- A Globe token cannot access Skycable or Meralco endpoints (middleware enforced)
- Tokens are named by company: `createToken('globe-mobile')`, `createToken('skycable-mobile')`
- Cross-company login attempts return `403 Forbidden`

---

## Password Reset Flows

### Flow 1 — Forgot Password (self-service)
```
User clicks "Forgot Password"
  → Enters email
  → System sends reset link to email
  → User clicks link → enters new password
  → Password updated → redirect to login
```

### Flow 2 — Admin Reset (forced change)
```
Admin resets user password
  → System generates temporary random password (e.g. "Tw!n#7482")
  → Admin sees/copies the temp password to share with user
  → User logs in with temp password
  → System detects password_reset_required = true
  → Force shows "Change Password" screen (cannot skip, cannot access anything else)
  → User sets new permanent password
  → password_reset_required = false → normal access restored
```

**Add to `users` table:**
| Column | Type | Notes |
|---|---|---|
| `password_reset_required` | boolean | default false — true when admin resets |
| `temp_password_set_at` | timestamp | nullable — when temp password was set |

**API endpoints:**
- `POST /api/v1/{company}/auth/forgot-password` — sends reset email
- `POST /api/v1/{company}/auth/reset-password` — confirm reset via token
- `POST /api/v1/admin/users/{id}/reset-password` — admin resets, returns temp password
- `POST /api/v1/{company}/auth/change-password` — user sets new password (required if flag is true)

### Locations (PSGC)
- `GET /api/v1/locations/regions`
- `GET /api/v1/locations/provinces?region_code=`
- `GET /api/v1/locations/cities?province_code=`
- `GET /api/v1/locations/barangays?city_code=`

### Teams / Planner
- `GET    /api/v1/teams`
- `POST   /api/v1/teams`
- `PUT    /api/v1/teams/{id}/members`
- `DELETE /api/v1/teams/{id}/members/{user_id}`
- `GET    /api/v1/teams/{id}/assignments`
- `POST   /api/v1/teams/{id}/assign`

### Skycable — see SKYCABLE.md
### Globe — see GLOBE.md
### Meralco — see MERALCO.md

---

## Notes
- All responses in JSON
- All protected routes require `Authorization: Bearer {token}` header
- GPS coordinates updated by mobile app on each authenticated request
- `last_login` updated on every successful login
