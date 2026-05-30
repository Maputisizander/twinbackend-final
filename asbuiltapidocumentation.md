# AsBuilt IQ — API Documentation

**Version:** v1  
**Backend:** TwinBackend (Laravel 11)  
**Base URL:** `https://7a33-112-210-248-33.ngrok-free.app/api/v1`

---

## Authentication

| Header | Value |
|--------|-------|
| `X-AsBuilt-Key` | `asbuilt-iq-secret-key-2026` |
| `ngrok-skip-browser-warning` | `1` |

No user login required. API key only.

---

## Terminology

| AsBuilt IQ | Backend Table / Column |
|------------|----------------------|
| **Site / Area** | `skycable_areas` (NCR, North Luzon, South Luzon, Visayas, Mindanao) |
| **Node Identifier** | `skycable_nodes.node_id` — VARCHAR string, e.g. `"TY1401"` |
| **Node Name** | `skycable_nodes.name` — human name, e.g. `"MONTEVISTA SUBD."` |
| **Pole** | `poles` + `skycable_poles` |
| **Span** | `skycable_spans` |

---

## How It Works

```
1. Fetch all sites / areas
         GET /asbuilt/sites
         → [ NCR, North Luzon, South Luzon, Visayas, Mindanao ]

2. Display all areas on the screen using forEach

3. User selects a site / area
         GET /asbuilt/sites/{areaId}/nodes
         → [ { node_id: "TY1401", name: "MONTEVISTA SUBD." }, ... ]

4. User can choose either:
         Option A: Select an existing node
         Option B: Add node manually

5. If user selects an existing node:
         Use selected node_id and name

6. If user adds node manually:
         Display a form input for node_id, node_name, region, province, city,
         barangay_code, and barangay_name

7. User uploads poles and spans

8. On POST:
         All poles and spans are posted to the selected/manual node_id

9. After POST:
         Display all uploaded poles and spans under that specific node
```

---

## ForEach Pattern

```js
// Step 1 — Load all sites / areas
const areas = await GET('/asbuilt/sites')

// Step 2 — Display every area
areas.forEach(area => {
  displayAreaCard({
    id: area.id,
    name: area.name,
    node_count: area.node_count
  })
})

// Step 3 — User picks an area
const selectedArea = area

// Step 4 — Load all nodes under selected area
const { nodes } = await GET(`/asbuilt/sites/${selectedArea.id}/nodes`)

// Step 5 — Display every existing node
nodes.forEach(node => {
  displayNodeOption({
    id: node.id,
    node_id: node.node_id,
    name: node.name,
    pole_count: node.pole_count,
    source_file: node.source_file
  })
})
```

---

## Endpoints

---

### 1. List Sites / Areas

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

---

### 2. List Nodes by Site / Area

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
      "node_id":     "TY1401",
      "name":        "MONTEVISTA SUBD.",
      "full_label":  "MONTEVISTA SUBD.",
      "status":      "pending",
      "report_type": null,
      "source_file": null,
      "pole_count":  0
    },
    {
      "id":          11,
      "node_id":     "TY1402",
      "name":        "BRGY. DILA",
      "full_label":  "BRGY. DILA",
      "status":      "in_progress",
      "report_type": "full_report",
      "source_file": "asbuilt",
      "pole_count":  15
    }
  ]
}
```

| Field | Description |
|-------|-------------|
| `id` | Integer database ID of the node |
| `node_id` | **VARCHAR string identifier** e.g. `"TY1401"` — use this in the import payload |
| `name` | Node name saved in `skycable_nodes.name` |
| `source_file` | `"asbuilt"` if previously imported via AsBuilt IQ |
| `pole_count` | Number of poles already enrolled in this node |

---

### 3. Import JSON Body

```
POST /api/v1/asbuilt/import
Content-Type: application/json
```

Use this endpoint for both:

1. Existing node import  
2. Manual node create-or-update import  

If the `node_id` does not exist yet inside the selected `area_id`, the backend creates the node automatically.  
If the `node_id` already exists inside the selected `area_id`, the backend updates that node.

---

## Existing Node Import Example

Use this when the user selects an existing node from the node list.

```json
{
  "node_id":   "TY1401",
  "node_name": "MONTEVISTA SUBD.",
  "area_id":   1,
  "region":    "CALABARZON",
  "province":  "LAGUNA",
  "city":      "STA. ROSA",
  "barangay_code": "043428001",
  "barangay_name": "Balibago",
  "poles": [
    {
      "pole_code":     "PL-001",
      "latitude":      14.539770,
      "longitude":     121.109219,
      "region":        "CALABARZON",
      "province":      "LAGUNA",
      "city":          "STA. ROSA",
      "barangay_code": "043428001",
      "barangay_name": "Balibago"
    },
    {
      "pole_code":     "PL-002",
      "latitude":      14.540100,
      "longitude":     121.109800,
      "region":        "CALABARZON",
      "province":      "LAGUNA",
      "city":          "STA. ROSA",
      "barangay_code": "043428001",
      "barangay_name": "Balibago"
    },
    {
      "pole_code":     "PL-003",
      "latitude":      14.540750,
      "longitude":     121.110300,
      "region":        "CALABARZON",
      "province":      "LAGUNA",
      "city":          "STA. ROSA",
      "barangay_code": "043428002",
      "barangay_name": "Tagapo"
    }
  ],
  "spans": [
    {
      "from_pole_code": "PL-001",
      "to_pole_code":   "PL-002",
      "strand_length":  50.5,
      "number_of_runs": 1,
      "components": {
        "node": 2,
        "amplifier": 1,
        "extender": 0,
        "tsc": 1,
        "powersupply": 0,
        "ps_housing": 0
      }
    },
    {
      "from_pole_code": "PL-002",
      "to_pole_code":   "PL-003",
      "strand_length":  45.0,
      "number_of_runs": 2,
      "components": {
        "node": 1,
        "amplifier": 0,
        "extender": 1,
        "tsc": 0,
        "powersupply": 1,
        "ps_housing": 1
      }
    }
  ]
}
```

---

## Manual Node Creation Flow

Use this flow when the user wants to manually create a node under a selected area.

---

### Step 1 — Get All Areas

```
GET /api/v1/asbuilt/sites
```

Display all areas on the screen.

```js
const areas = await GET('/asbuilt/sites')

areas.forEach(area => {
  displayAreaCard({
    id: area.id,
    name: area.name,
    node_count: area.node_count
  })
})
```

Example UI:

```txt
NCR
North Luzon
South Luzon
Visayas
Mindanao
```

---

### Step 2 — User Clicks an Area

After the user clicks one area, get all existing nodes under that area.

```
GET /api/v1/asbuilt/sites/{areaId}/nodes
```

```js
const selectedArea = area

const response = await GET(`/asbuilt/sites/${selectedArea.id}/nodes`)

const nodes = response.nodes

nodes.forEach(node => {
  displayNodeOption({
    id: node.id,
    node_id: node.node_id,
    name: node.name,
    pole_count: node.pole_count,
    source_file: node.source_file
  })
})
```

---

### Step 3 — Show Two Options

After selecting an area, the user must have two options:

```txt
Option 1: Select Existing Node
Option 2: Add Node Manually
```

---

## Option 1 — Select Existing Node

If the user clicks an existing node, save the selected node data.

```js
const selectedNode = {
  id: node.id,
  node_id: node.node_id,
  name: node.name
}
```

This selected `node_id` will be used when posting poles and spans.

---

## Option 2 — Add Node Manually

If the user clicks **Add Node Manually**, display a form input.

### Manual Node Form Fields

```txt
Node ID
Node Name
Region
Province
City
Barangay Code
Barangay Name
```

Example form value:

```json
{
  "node_id": "TY1501",
  "node_name": "BRGY. BALIBAGO NODE",
  "region": "CALABARZON",
  "province": "LAGUNA",
  "city": "STA. ROSA",
  "barangay_code": "043428001",
  "barangay_name": "Balibago"
}
```

Frontend form state:

```js
const manualNode = {
  node_id: form.node_id,
  name: form.node_name,
  region: form.region,
  province: form.province,
  city: form.city,
  barangay_code: form.barangay_code,
  barangay_name: form.barangay_name
}
```

Important:

```txt
Manual node creation uses the same POST /asbuilt/import endpoint.

If the node_id does not exist yet inside the selected area_id,
the backend will create the node automatically.

If the node_id already exists inside the selected area_id,
the backend will update that node.
```

---

## Post Data After Selecting or Creating a Node

After the user selects an existing node or creates a node manually, the user can click **Post**.

When they click **Post**, all uploaded poles and spans must be posted to that specific `node_id`.

```http
POST /api/v1/asbuilt/import
Content-Type: application/json
```

---

### Frontend Payload Builder

```js
const targetNode = selectedNode || manualNode

const areaData = {
  region: manualNode?.region || selectedArea.region,
  province: manualNode?.province || selectedArea.province,
  city: manualNode?.city || selectedArea.city,
  barangay_code: manualNode?.barangay_code || selectedArea.barangay_code,
  barangay_name: manualNode?.barangay_name || selectedArea.barangay_name
}

const payload = {
  node_id: targetNode.node_id,
  node_name: targetNode.name,

  area_id: selectedArea.id,

  region: areaData.region,
  province: areaData.province,
  city: areaData.city,
  barangay_code: areaData.barangay_code,
  barangay_name: areaData.barangay_name,

  poles: uploadedPoles.map(pole => ({
    pole_code: pole.pole_code,
    latitude: pole.latitude,
    longitude: pole.longitude,

    // Automatically attach area data to every pole
    region: pole.region || areaData.region,
    province: pole.province || areaData.province,
    city: pole.city || areaData.city,
    barangay_code: pole.barangay_code || areaData.barangay_code,
    barangay_name: pole.barangay_name || areaData.barangay_name
  })),

  spans: uploadedSpans.map(span => ({
    from_pole_code: span.from_pole_code,
    to_pole_code: span.to_pole_code,
    strand_length: span.strand_length,
    number_of_runs: span.number_of_runs || 1,
    components: {
      node: span.components?.node || 0,
      amplifier: span.components?.amplifier || 0,
      extender: span.components?.extender || 0,
      tsc: span.components?.tsc || 0,
      powersupply: span.components?.powersupply || 0,
      ps_housing: span.components?.ps_housing || 0
    }
  }))
}

await POST('/asbuilt/import', payload)
```

---

## Automatic Pole Area Data

Every uploaded pole must automatically include the area data from the selected area or manual node form.

Required area fields to include in every pole:

```txt
barangay_code
region
province
city
barangay_name
```

Example pole data after automatic area mapping:

```json
{
  "pole_code": "PL-001",
  "latitude": 14.539770,
  "longitude": 121.109219,
  "region": "CALABARZON",
  "province": "LAGUNA",
  "city": "STA. ROSA",
  "barangay_code": "043428001",
  "barangay_name": "Balibago"
}
```

Tagalog note:

```txt
Pag upload ng poles, dapat kita agad yung area data.

Kung anong barangay yung selected area or manual node form,
automatic yun na ang barangay ng poles.

Kung majority ng uploaded poles ay nasa isang barangay,
automatic yun ang magiging barangay_name ng node.
```

---

## Barangay Majority Logic

The node's `barangay_name` is automatically set to the most frequently appearing `barangay_name` across all poles in the import.

Example:

```txt
PL-001 → Balibago
PL-002 → Balibago
PL-003 → Tagapo
```

Result:

```txt
Balibago appears 2 times
Tagapo appears 1 time

Node barangay_name = Balibago
```

---

## Display Uploaded Poles and Spans After Post

After successful import, use the integer database node ID from the import response.

```js
const importResponse = await POST('/asbuilt/import', payload)

const nodeDatabaseId = importResponse.data.node.id
```

Then verify and display the uploaded data.

```http
GET /api/v1/asbuilt/node/{nodeId}
```

```js
const nodeState = await GET(`/asbuilt/node/${nodeDatabaseId}`)

displayNodeDetails(nodeState.node)
displayPoles(nodeState.poles)
displaySpans(nodeState.spans)
```

The verify endpoint returns:

```txt
Node details
Uploaded poles
Uploaded spans
```

---

## Final UI Flow

```txt
1. GET all areas
2. Display all areas using forEach
3. User clicks an area
4. GET all nodes under selected area
5. Display existing nodes
6. User chooses either:
      A. Select existing node
      B. Add node manually
7. If manual, display node form:
      - node_id
      - node_name
      - region
      - province
      - city
      - barangay_code
      - barangay_name
8. User uploads poles and spans
9. System automatically adds area data to every pole:
      - barangay_code
      - region
      - province
      - city
      - barangay_name
10. User clicks Post
11. POST data to /asbuilt/import using the selected/manual node_id
12. Backend creates or updates the node
13. Backend saves poles and spans under that specific node_id
14. GET /asbuilt/node/{nodeId}
15. Display all uploaded poles and spans under that node
```

---

## Import — Fields Reference

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `node_id` | string | ✅ | VARCHAR identifier e.g. `"TY1401"` — maps to `skycable_nodes.node_id` |
| `node_name` | string | ✅ | Saved to `skycable_nodes.name`, e.g. `"MONTEVISTA SUBD."` |
| `area_id` | integer | ✅ | From `GET /asbuilt/sites` |
| `region` | string | ❌ | e.g. `"CALABARZON"` — saved to node |
| `province` | string | ❌ | e.g. `"LAGUNA"` — saved to node |
| `city` | string | ❌ | e.g. `"STA. ROSA"` — saved to node |
| `barangay_code` | string | ❌ | Area barangay code |
| `barangay_name` | string | ❌ | Node barangay name, can also be computed from majority pole barangay |
| `poles[].pole_code` | string | ✅ | Stored UPPERCASE |
| `poles[].latitude` | decimal | ❌ | −90 to 90 — retained as-is |
| `poles[].longitude` | decimal | ❌ | −180 to 180 — retained as-is |
| `poles[].region` | string | ❌ | Automatically copied from selected/manual area data |
| `poles[].province` | string | ❌ | Automatically copied from selected/manual area data |
| `poles[].city` | string | ❌ | Automatically copied from selected/manual area data |
| `poles[].barangay_code` | string | ❌ | Automatically copied from selected/manual area data |
| `poles[].barangay_name` | string | ❌ | Per-pole barangay; node gets the majority value |
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

> `expected_cable` = `strand_length × number_of_runs` — auto-computed and saved to `skycable_span_summaries`.

---

## Import — Response `201`

```json
{
  "message": "AsBuilt import completed.",
  "data": {
    "node": {
      "id":          10,
      "node_id":     "TY1401",
      "name":        "MONTEVISTA SUBD.",
      "region":      "CALABARZON",
      "province":    "LAGUNA",
      "city":        "STA. ROSA",
      "barangay_code": "043428001",
      "barangay_name": "Balibago",
      "report_type": "full_report",
      "source_file": "asbuilt"
    },
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

### 4. Import File Upload

```
POST /api/v1/asbuilt/import
Content-Type: multipart/form-data
```

Upload the export as a `.json` file. The file must contain the same structure as the JSON body above.

| Field | Type | Description |
|-------|------|-------------|
| `file` | `.json` file | Must include all required fields: `node_id`, `node_name`, `area_id`, `poles`, `spans` |

---

### 5. Verify Node State

```
GET /api/v1/asbuilt/node/{nodeId}
```

Check what was imported. `{nodeId}` is the **integer database ID** from `skycable_nodes.id`.

**Response `200`:**

```json
{
  "node": {
    "id":          10,
    "node_id":     "TY1401",
    "name":        "MONTEVISTA SUBD.",
    "area":        "NCR",
    "region":      "CALABARZON",
    "province":    "LAGUNA",
    "city":        "STA. ROSA",
    "barangay_code": "043428001",
    "barangay_name": "Balibago",
    "report_type": "full_report",
    "source_file": "asbuilt",
    "status":      "pending"
  },
  "poles": [
    {
      "skycable_pole_id": 1,
      "pole_id":          1,
      "pole_code":        "PL-001",
      "sequence":         1,
      "latitude":         14.539770,
      "longitude":        121.109219,
      "region":           "CALABARZON",
      "province":         "LAGUNA",
      "city":             "STA. ROSA",
      "barangay_code":    "043428001",
      "barangay_name":    "Balibago",
      "status":           "pending",
      "date_start":       null,
      "finished_at":      null,
      "duration":         null
    }
  ],
  "spans": [
    {
      "span_id":        1,
      "from_pole_code": "PL-001",
      "to_pole_code":   "PL-002",
      "strand_length":  50.5,
      "number_of_runs": 1,
      "expected_cable": 50.5,
      "status":         "pending",
      "components": {
        "node": 2,
        "amplifier": 1,
        "extender": 0,
        "tsc": 1,
        "powersupply": 0,
        "ps_housing": 0
      }
    }
  ]
}
```

---

## All Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/asbuilt/sites` | List all areas |
| `GET` | `/asbuilt/sites/{areaId}/nodes` | List nodes under an area |
| `POST` | `/asbuilt/import` | Bulk import, manual node create-or-update, JSON body, or file upload |
| `GET` | `/asbuilt/node/{nodeId}` | Verify node state after import |

---

## cURL Examples

```bash
BASE="https://7a33-112-210-248-33.ngrok-free.app/api/v1"
KEY="asbuilt-iq-secret-key-2026"
NGROK="-H \"ngrok-skip-browser-warning: 1\""

# 1 — List sites / areas
curl "$BASE/asbuilt/sites" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "ngrok-skip-browser-warning: 1"

# 2 — List nodes for NCR, area id = 1
curl "$BASE/asbuilt/sites/1/nodes" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "ngrok-skip-browser-warning: 1"

# 3 — Import via JSON body
curl -X POST "$BASE/asbuilt/import" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "ngrok-skip-browser-warning: 1" \
  -H "Content-Type: application/json" \
  -d '{
    "node_id": "TY1401",
    "node_name": "MONTEVISTA SUBD.",
    "area_id": 1,
    "region": "CALABARZON",
    "province": "LAGUNA",
    "city": "STA. ROSA",
    "barangay_code": "043428001",
    "barangay_name": "Balibago",
    "poles": [
      {
        "pole_code": "PL-001",
        "latitude": 14.53977,
        "longitude": 121.10921,
        "region": "CALABARZON",
        "province": "LAGUNA",
        "city": "STA. ROSA",
        "barangay_code": "043428001",
        "barangay_name": "Balibago"
      }
    ],
    "spans": []
  }'

# 4 — Import via file upload
curl -X POST "$BASE/asbuilt/import" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "ngrok-skip-browser-warning: 1" \
  -F "file=@export.json"

# 5 — Verify node state
# Use the integer database id from the import response
curl "$BASE/asbuilt/node/10" \
  -H "X-AsBuilt-Key: $KEY" \
  -H "ngrok-skip-browser-warning: 1"
```