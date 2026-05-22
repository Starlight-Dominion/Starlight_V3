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

## 🛠️ Endpoints

### 📡 Connectivity (Ping)
Verify your neural link and API status.

**`GET /api/v1/ping`**

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Neural link active.",
  "timestamp": "2026-05-22 02:30:15",
  "version": "v1.0.0"
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
