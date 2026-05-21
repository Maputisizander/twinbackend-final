# AsBuilt IQ — API Documentation

**Version:** v1  
**Backend:** TwinBackend (Laravel 11)  
**Base URL:** `https://disguisedly-enarthrodial-kristi.ngrok-free.dev/api/v1`  
**Required header (all requests):** `ngrok-skip-browser-warning: 1`

---

## Authentication

| Header | Value |
|--------|-------|
| `X-AsBuilt-Key` | `asbuilt-iq-secret-key-2026` |

No user login required. API key only.

---

## Terminology

| AsBuilt IQ | Backend Table |
|------------|---------------|
| **Site** | `skycable_areas` (NCR, North Luzon, South Luzon, Visayas, Mindanao) |
| **Node** | `skycable_nodes` |
| **Pole** | `poles` + `skycable_poles` |
| **Span** | `skycable_spans` |

---

## How It Works

```
1. Fetch all sites (skycable_areas)
         GET /asbuilt/sites
         → [ NCR, North Luzon, South Luzon, Visayas, Mindanao ]

2. User selects a site (area)
         GET /asbuilt/sites/{areaId}/nodes
         → [ Node-A, Node-B, Node-C, … ]

3. User selects a node
         → node_id is now known

4. Push node_id into the export payload and submit
         POST /asbuilt/import
         Body: { "node_id": 10, "poles": [...], "spans": [...] }
```

---

## ForEach Pattern

```js
// Step 1 — Load all sites (areas)
const sites = await GET('/asbuilt/sites')
// → [{ id:1, name:"NCR" }, { id:2, name:"North Luzon" }, ...]

// Step 2 — User picks a site, load its nodes
const { nodes } = await GET(`/asbuilt/sites/${selectedSite.id}/nodes`)
// → [{ id:10, name:"Node-A" }, { id:11, name:"Node-B" }, ...]

// Step 3 — User picks a node, push node_id into payload and submit
const payload = {
  node_id: selectedNode.id,   // ← pushed here
  poles: [ ...sitemapPoles ],
  spans: [ ...sitemapSpans ],
}
await POST('/asbuilt/import', payload)
```

---

## Endpoints

---

### 1. List Sites (skycable_areas)

```
GET /api/v1/asbuilt/sites
```

Returns all areas. These are the **Sites** in AsBuilt IQ.

**Response `200`:**
```json
[
  { "id": 1, "name": "NCR",         "node_count": 12 },
  { "id": 2, "name": "North Luzon", "node_count": 8  },
  { "id": 3, "name": "South Luzon", "node_count": 6  },
  { "id": 4, "name": "Visayas",     "node_count": 4  },
  { "id": 5, "name": "Mindanao",    "node_count": 3  }
]
```

| Field | Description |
|-------|-------------|
| `id` | Use as `areaId` in the next call |
| `name` | Display in site dropdown |
| `node_count` | Number of nodes in this area |

---

### 2. List Nodes by Site

```
GET /api/v1/asbuilt/sites/{areaId}/nodes
```

Returns all nodes under the selected area.

**Response `200`:**
```json
{
  "site": {
    "id":   1,
    "name": "NCR"
  },
  "nodes": [
    {
      "id":          10,
      "name":        "Node-A",
      "full_label":  "NCR-NODE-A",
      "status":      "pending",
      "report_type": null,
      "pole_count":  0
    },
    {
      "id":          11,
      "name":        "Node-B",
      "full_label":  "NCR-NODE-B",
      "status":      "in_progress",
      "report_type": "full_report",
      "pole_count":  15
    }
  ]
}
```

| Field | Description |
|-------|-------------|
| `nodes[].id` | **This is the `node_id` you push into the import payload** |
| `nodes[].report_type` | `full_report` = previously imported; re-import will update |
| `nodes[].pole_count` | Poles already enrolled in this node |

---

### 3. Import (JSON Body)

```
POST /api/v1/asbuilt/import
Content-Type: application/json
```

Push the sitemap data with the selected `node_id`.

**Request Body:**
```json
{
  "node_id": 10,
  "poles": [
    { "pole_code": "PL-001", "latitude": 14.599512, "longitude": 120.984219 },
    { "pole_code": "PL-002", "latitude": 14.600100, "longitude": 120.984800 },
    { "pole_code": "PL-003", "latitude": 14.600750, "longitude": 120.985300 }
  ],
  "spans": [
    {
      "from_pole_code": "PL-001",
      "to_pole_code":   "PL-002",
      "strand_length":  50.5,
      "number_of_runs": 1,
      "components": {
        "node": 2, "amplifier": 1, "extender": 0,
        "tsc": 1, "powersupply": 0, "ps_housing": 0
      }
    },
    {
      "from_pole_code": "PL-002",
      "to_pole_code":   "PL-003",
      "strand_length":  45.0,
      "number_of_runs": 2,
      "components": {
        "node": 1, "amplifier": 0, "extender": 1,
        "tsc": 0, "powersupply": 1, "ps_housing": 1
      }
    }
  ]
}
```

---

### 4. Import (File Upload)

```
POST /api/v1/asbuilt/import
Content-Type: multipart/form-data
```

Upload the export as a `.json` file. The file must contain the same structure as above with `node_id` already inside.

| Field | Type | Description |
|-------|------|-------------|
| `file` | `.json` file | Must include `node_id`, `poles`, `spans` |

**Example file `export.json`:**
```json
{
  "node_id": 10,
  "poles": [
    { "pole_code": "PL-001", "latitude": 14.5995, "longitude": 120.9842 }
  ],
  "spans": [
    {
      "from_pole_code": "PL-001",
      "to_pole_code":   "PL-002",
      "strand_length":  50.5,
      "number_of_runs": 1,
      "components": { "node": 2, "amplifier": 1, "extender": 0, "tsc": 1, "powersupply": 0, "ps_housing": 0 }
    }
  ]
}
```

---

### Import — Fields Reference

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `node_id` | integer | ✅ | From `GET /asbuilt/sites/{areaId}/nodes` |
| `poles[].pole_code` | string | ✅ | Stored UPPERCASE |
| `poles[].latitude` | decimal | ❌ | −90 to 90 |
| `poles[].longitude` | decimal | ❌ | −180 to 180 |
| `spans[].from_pole_code` | string | ✅ | Must be in the `poles` list |
| `spans[].to_pole_code` | string | ✅ | Must be in the `poles` list |
| `spans[].strand_length` | decimal | ❌ | Meters |
| `spans[].number_of_runs` | integer | ❌ | Default: 1 |
| `spans[].components.node` | integer | ❌ | Default: 0 |
| `spans[].components.amplifier` | integer | ❌ | Default: 0 |
| `spans[].components.extender` | integer | ❌ | Default: 0 |
| `spans[].components.tsc` | integer | ❌ | Default: 0 |
| `spans[].components.powersupply` | integer | ❌ | Default: 0 |
| `spans[].components.ps_housing` | integer | ❌ | Default: 0 |

> `expected_cable` = `strand_length × number_of_runs` — auto-computed, saved to `skycable_span_summaries`.

---

### Import — Response `201`

```json
{
  "message": "AsBuilt import completed.",
  "data": {
    "node":          { "id": 10, "name": "Node-A", "report_type": "full_report" },
    "poles_created": ["PL-001", "PL-002", "PL-003"],
    "poles_updated": [],
    "spans_created": ["PL-001 → PL-002", "PL-002 → PL-003"],
    "spans_updated": [],
    "total_poles":   3,
    "total_spans":   2,
    "errors":        []
  }
}
```

---

### 5. Verify Node State

```
GET /api/v1/asbuilt/node/{nodeId}
```

Check what was imported.

**Response `200`:**
```json
{
  "node": { "id": 10, "name": "Node-A", "report_type": "full_report", "status": "pending" },
  "poles": [
    {
      "pole_id": 1, "pole_code": "PL-001", "sequence": 1,
      "latitude": 14.599512, "longitude": 120.984219,
      "status": "pending", "date_start": null, "finished_at": null, "duration": null
    }
  ],
  "spans": [
    {
      "span_id": 1, "from_pole_code": "PL-001", "to_pole_code": "PL-002",
      "strand_length": 50.5, "number_of_runs": 1, "expected_cable": 50.5,
      "status": "pending",
      "components": { "node": 2, "amplifier": 1, "extender": 0, "tsc": 1, "powersupply": 0, "ps_housing": 0 }
    }
  ]
}
```

---

## All Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/asbuilt/sites` | List all areas (NCR, North Luzon, …) |
| `GET` | `/asbuilt/sites/{areaId}/nodes` | List nodes under an area |
| `POST` | `/asbuilt/import` | Bulk import (JSON body or file upload) |
| `GET` | `/asbuilt/node/{nodeId}` | Verify node state after import |

---

## cURL Examples

```bash
BASE="https://disguisedly-enarthrodial-kristi.ngrok-free.dev/api/v1"
KEY="asbuilt-iq-secret-key-2026"

# 1 — List sites (areas)
curl "$BASE/asbuilt/sites" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "ngrok-skip-browser-warning: 1"

# 2 — List nodes for NCR (area id=1)
curl "$BASE/asbuilt/sites/1/nodes" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "ngrok-skip-browser-warning: 1"

# 3 — Import via JSON body (node_id pushed in)
curl -X POST "$BASE/asbuilt/import" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "Content-Type: application/json" \
  -H "ngrok-skip-browser-warning: 1" \
  -d '{ "node_id": 10, "poles": [...], "spans": [...] }'

# 4 — Import via file upload (node_id must be inside the json file)
curl -X POST "$BASE/asbuilt/import" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "ngrok-skip-browser-warning: 1" \
  -F "file=@export.json"

# 5 — Verify
curl "$BASE/asbuilt/node/10" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "ngrok-skip-browser-warning: 1"
```
