# 🛡️ High Command: Admin Suite User Guide

Welcome to the **Starlight Dominion Command Center**. This guide details the operational procedures for managing the game's core mechanics, economic balance, and sovereign oversight.

---

## 🔐 Authorization & Access

Access to the Command Center is restricted to authorized High Command personnel only.

### 1. Environment-Based Override (Preferred)
For local development or emergency oversight, you can grant yourself administrative privileges via the `.env` file:
```bash
ADMIN_USERNAME=your_username
```
*Note: Restart your Docker containers (`docker-compose up -d`) after modifying the `.env` file.*

### 2. Database Authorization
To manually authorize an account via the database:
```bash
docker exec sdo_db mariadb -u sdo_admin -ppassword sdo -e "UPDATE users SET is_admin = 1 WHERE username = 'TARGET_USERNAME';"
```

### 3. Navigation
Once authorized, a **"High Command"** block will appear at the bottom of your **Tactical Sidebar**. Click **"Access Command Center"** to initialize the terminal.

---

## ⚙️ Global Mechanics

The **Global Mechanics** module allows for real-time tuning of the game's baseline parameters. Changes here affect all sectors immediately.

*   **Baseline Citizens/Tick:** Sets the standard population growth rate (Default: 50).
*   **Baseline Credits/Tick:** Sets the standard income rate before building multipliers (Default: 100).
*   **Starting Resources:** Configures the `Credits` and `Citizens` a new commander receives upon sector initialization.
*   **Tick Interval:** Adjusts the global heartbeat frequency in seconds (900 = 15 minutes).
*   **Recruitment Limits:** Configure the frequency and magnitude of the Neural Recruitment tool (Sessions per day/3-days and clicks per session).

**Operational Note:** Click **"UPDATE"** on any row to synchronize the new value with the live environment.

---

## ⚔️ Armory Forge

The **Armory Forge** provides granular control over all military equipment.

*   **Item Identity:** Edit names and internal slugs for all equipment.
*   **Stat Modification:** Adjust the **Offense (ATK)** and **Defense (DEF)** bonuses provided by each item.
*   **Requisition Cost:** Set the purchase price in Credits (CP).
*   **Prerequisites:** Manage technical dependencies:
    *   **NO PREREQ:** The item is available immediately.
    *   **ITEM PREREQ:** Requires another specific item to be owned first.
    *   **RANK REQ:** Requires a specific Armory Tech Rank (Structures) to unlock.

---

## 👥 Sovereign Oversight

Search and manage individual player sectors.

1.  **Search:** Use the targeting array to find kingdoms by name or commander handle.
2.  **Resource Adjustment:** Manually adjust a commander's **Credits, XP, Turns,** or **Citizens**.
3.  **Commit Directives:** Click **"SAVE DIRECTIVES"** to apply changes to the live sector.

---

## 🏛️ Structural Engineering

Manage the archetypes of all dominion buildings and their evolution paths.

*   **Building Designation:** Edit names and descriptions of structures (Foundation, Armory, Mines, etc.).
*   **Rank Evolution Matrix:** For each structure rank, you can define:
    *   **Cost:** Price to upgrade to this rank.
    *   **Integrity (HP):** Maximum HP provided (primarily for Foundation).
    *   **Buffs:** Offensive/Defensive power multipliers or Economic percentage bonuses.
    *   **Capacity:** Citizen housing or resource storage limits.

---

## 📜 Battle Records

View a real-time audit trail of all tactical engagements across the Dominion.
*   **Engagement:** Identities of the Attacker and Defender.
*   **Outcome:** Victory or Repel status.
*   **Credits Siphoned:** Total loot plundered during the strike.

---

> **⚠️ WARNING:** Changes made within this terminal affect the live production database immediately. Unauthorized modifications constitute sector-level treason.
