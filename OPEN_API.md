# 📡 Starlight Dominion Open API (v1.0)

The Starlight Dominion Open API provides secure, high-performance access to tactical telemetry and sector data for external integrations.

---

## 🔐 Authentication

All requests to the Open API require a valid **Bearer Token** issued by the High Command.

### Headers
| Header | Value |
| :--- | :--- |
| `Authorization` | `Bearer <YOUR_API_TOKEN>` |
| `Accept` | `application/json` |

---

## ⏳ Rate Limiting

The API enforces tunable rate limits on a per-key basis. The standard default is **60 requests per minute (RPM)**. 

If you exceed your authorized threshold, the server will return a `429 Too Many Requests` status code. You can view your current limits and usage in the **Command Center** (Admin Suite).

---

## 🛠️ Endpoints Reference

### 1. Connectivity (Ping)
Verify your neural link and API status.

**`GET /api/v1/ping`**

#### Example Call:
```bash
curl -X GET "http://localhost:8080/api/v1/ping" \
     -H "Authorization: Bearer <TOKEN>" \
     -H "Accept: application/json"
```

#### Success Response:
```json
{
  "success": true,
  "message": "Neural link active.",
  "timestamp": "2026-05-22 02:30:15",
  "version": "v1.0.0"
}
```

---

### 2. Sector Status
Retrieve the current vitals and resource levels for your dominion.

**`GET /api/v1/sector/status`**

#### Example Call:
```bash
curl -X GET "http://localhost:8080/api/v1/sector/status" \
     -H "Authorization: Bearer <TOKEN>"
```

#### Success Response:
```json
{
  "success": true,
  "data": {
    "name": "Alpha Sector",
    "credits": 10500,
    "banked": 5000,
    "citizens": 1250,
    "turns": 100,
    "xp": 2500,
    "level": 6,
    "integrity": {
      "current": 1000,
      "max": 1000
    },
    "last_tick": "2026-05-22 02:15:00"
  }
}
```

---

### 3. Sector Manpower
List the detailed unit counts for all military divisions in your sector.

**`GET /api/v1/sector/manpower`**

#### Example Call:
```bash
curl -X GET "http://localhost:8080/api/v1/sector/manpower" \
     -H "Authorization: Bearer <TOKEN>"
```

#### Success Response:
```json
{
  "success": true,
  "data": [
    { "name": "Guards", "slug": "guards", "total_quantity": 5000 },
    { "name": "Soldiers", "slug": "soldiers", "total_quantity": 1200 },
    { "name": "Spies", "slug": "spies", "total_quantity": 50 }
  ]
}
```

---

### 4. Sector Structures
List all initialized structures and their current operational ranks.

**`GET /api/v1/sector/structures`**

#### Example Call:
```bash
curl -X GET "http://localhost:8080/api/v1/sector/structures" \
     -H "Authorization: Bearer <TOKEN>"
```

#### Success Response:
```json
{
  "success": true,
  "data": [
    { "slug": "foundation", "name": "Planetary Foundation", "current_level": 5, "max_level": 20 },
    { "slug": "economy", "name": "Economic Hub", "current_level": 3, "max_level": 20 },
    { "slug": "armory", "name": "Sector Armory", "current_level": 2, "max_level": 20 }
  ]
}
```

---

### 5. Battlefield Intelligence
Retrieve the public tactical list of all active Dominion sectors within operational range.

**`GET /api/v1/battlefield`**

#### Example Call:
```bash
curl -X GET "http://localhost:8080/api/v1/battlefield" \
     -H "Authorization: Bearer <TOKEN>"
```

#### Success Response:
```json
{
  "success": true,
  "data": [
    { "kingdom_id": 1, "name": "Alpha Core", "username": "Mungus", "gold": 15000, "level": 10 },
    { "kingdom_id": 2, "name": "Bravo Base", "username": "CommanderX", "gold": 2500, "level": 4 }
  ]
}
```

---

## 📜 Error Codes

| Status | Description |
| :--- | :--- |
| `200` | Success. The request was processed successfully. |
| `401` | Unauthorized. Invalid or missing Bearer token. |
| `429` | Too Many Requests. You have exceeded your rate limit. |
| `500` | Internal Server Error. Neural link unstable. |

---

## 📝 Developer Guidelines

1.  **Cache Frequently:** Tactical data changes every tick (15 minutes). Avoid polling faster than necessary.
2.  **Handle 429s:** Implement exponential backoff when hitting rate limits.
3.  **Security:** Never expose your API token in client-side code or public repositories.
