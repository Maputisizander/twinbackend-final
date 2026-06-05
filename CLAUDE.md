# TwinBackend — Claude Context

## What this is
Laravel 11 API backend for a multi-company telco field operations platform. Serves three frontends:
- `C:/globe-app/globe-app/` — React/Vite web dashboard
- `C:/globe-mobileapp/` — Expo/React Native mobile app (Globe field staff)
- TelcoVantage admin (internal)

## Tunnel / Base URL
```
https://quack-useable-thesaurus.ngrok-free.dev   ← static domain (never changes)
http://192.168.1.17:8080                          ← local Wi-Fi
API prefix: /api/v1   (set in bootstrap/app.php → apiPrefix)
```
Start tunnel: `ngrok http --domain=quack-useable-thesaurus.ngrok-free.dev 8080`  
All routes in `routes/api.php` are automatically prefixed with `/api/v1`.  
ngrok — every request needs header: `ngrok-skip-browser-warning: 1`

## Auth
Laravel Sanctum (Bearer tokens). Each company has its own middleware:
- `auth:sanctum` + `company:skycable` → Skycable routes
- `auth:sanctum` + `company:globe` → Globe routes
- `auth:sanctum` + `company:meralco` → Meralco routes
- `auth:sanctum` + `company:telcovantage` → Admin routes
- `auth:sanctum` only → `/api/v1/teardown-logs` (cross-company mobile submission)

Login endpoints (no auth): `POST /api/v1/{company}/auth/login`

## Route file
`routes/api.php` — all routes are here. Grouped by company prefix + middleware.

**Key route ordering rule:** Static routes (`poles/code/{code}`, `poles/map`, `poles/all`) must be declared BEFORE the dynamic `poles/{pole}` route or Laravel will match "code"/"map"/"all" as a pole ID.

## Companies / modules
| Company | Prefix | Main modules |
|---|---|---|
| Skycable | `/skycable` | Areas, Sites, Nodes, Poles, Spans, Teardowns, Daily Reports, Warehouses |
| Globe | `/globe` | Poles, NAP Boxes, Ports, Surveys, Tickets, Teardowns, Daily Reports |
| Meralco | `/meralco` | Poles (read-only), Summary, Teardown Proof |
| Admin | `/admin` | Users, Subcontractors, Teams, Audit Logs |

## Key models and relationships

### Pole (shared across all companies)
- `poles` table — `pole_code` (globally unique), `lat`, `lng`, `skycable_status`, `globe_status`
- `SkycablePole` — junction: `skycable_poles(node_id, pole_id, sequence)` — links a Pole to a SkycableNode
- `PoleCableSlot` — `pole_cable_slots(pole_id, slot_label, occupied_by, status)` — auto-seeded C1–C5, DA on pole creation

### Skycable hierarchy
`SkycableArea` → `SkycableNode` (has `subcontractor_id`, `team_id`) → `SkycablePole` → `Pole`

### Spans
`SkycableSpan(node_id, from_pole_id, to_pole_id)` — `from_pole_id`/`to_pole_id` are `skycable_poles.id` (NOT `poles.id`)

### Teardown
`SkycableTeardownReport(span_id, team_id, lineman_id, status)` → `SkycableTeardownPhoto(teardown_report_id, photo_type, image_path)`

Photo types: `from_before`, `from_after`, `from_pole_tag`, `to_before`, `to_after`, `to_pole_tag`, `bunching`

Status flow: `pending` → `submitted` → `subcon_approved` → `backend_approved` (or `rejected` at any review step)

## Controllers (app/Http/Controllers/Api/)

### Skycable
- `AreaController` — CRUD for skycable areas
- `NodeController` — CRUD + `polePhotos`, `importJson`; update accepts `subcontractor_id`, `team_id`
- `PoleController` — CRUD + `showByCode`, `mapPins`, `allPoles`, `addSlot`
- `SpanController` — CRUD + `updateComponents`
- `TeardownController` — `start`, `submit`, `review`, `backendApprove`, **`storeDirect`** (mobile one-shot)
- `SiteController` — CRUD for skycable sites

### Globe
- `PoleController` — list/read globe poles + map pins
- `NapBoxController` — NAP box CRUD + ports
- `TicketController` — teardown ticket workflow
- `TeardownController` — per-ticket teardown store/approve

### Admin
- `SubcontractorController` — CRUD with teams/warehouses
- `TeamController` — CRUD + member management

## New endpoints added (not in original scaffold)

### GET `/api/v1/skycable/poles/code/{code}`
Look up pole by `pole_code` string. Returns pole + aggregated teardown photos from all spans it appears in.

### GET `/api/v1/skycable/poles/map`
All SkycablePoles with GPS. Returns flat array of pins for map rendering. Filters `lat IS NOT NULL AND lng IS NOT NULL`.

### GET `/api/v1/skycable/poles/all`
All SkycablePoles (with AND without GPS). Returns `has_gps: bool` flag. Used by web frontend sidebar to show all poles even without coordinates.

### POST `/api/v1/teardown-logs`
**Mobile app one-shot teardown submission.** No company middleware — any authenticated user can POST.  
Accepts `multipart/form-data` with field data + 7 photo files (`from_before`, `from_after`, `from_tag`, `to_before`, `to_after`, `to_tag`, `before_span`).  
Creates `SkycableTeardownReport` with status `submitted` + stores photos in `SkycableTeardownPhoto`.  
Required: `pole_span_id` (skycable_spans.id). Returns 409 if duplicate, 422 if validation fails.

## Photo storage
`StoresPhotos` trait in `app/Http/Concerns/StoresPhotos.php`. Call: `$this->storePhoto($file, 'teardown')` → returns relative path like `teardown/filename.jpg`. Serve via `GET /api/v1/files/{path}`.

## Subcontractor → Node assignment
`SkycableNode` has `subcontractor_id` + `team_id` (fillable). Update via `PATCH /api/v1/skycable/nodes/{id}` with `{ subcontractor_id, team_id }`.  
Admin subcontractors: `GET /api/v1/admin/subcontractors?per_page=100`  
Teams per subcontractor: `GET /api/v1/admin/teams?subcontractor_id={id}&per_page=100`

## Full API reference
See `api.md` in this directory.
