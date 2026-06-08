# 🛡️ High Command: Admin Suite User Guide

Welcome to the **Starlight Dominion Command Center**. This terminal provides High Command with total oversight and granular control over every sector, evolutionary strain, and tactical parameter within the Dominion.

---

## 🔐 Authorization & Access

Access to the Command Center is strictly restricted to authorized High Command personnel.

### 1. Environment-Based Override (Preferred)
For local development or emergency oversight, you can grant yourself administrative privileges via the `.env` file:
```bash
ADMIN_USERNAME=your_username
```

### 2. Database Authorization
To manually authorize an account via the database:
```bash
docker exec sdo_db mariadb -u sdo_admin -ppassword sdo -e "UPDATE users SET is_admin = 1 WHERE username = 'TARGET_USERNAME';"
```

### 3. Navigation
Once authorized, a **"High Command"** block will appear at the bottom of your **Tactical Sidebar**. Click **"Access Command Center"** to initialize the terminal.

---

## ◈ Command Overview

The Command Overview provides a real-time telemetry snapshot of the entire Dominion.
- **Total Sovereigns:** Total registered user accounts.
- **Active Sectors:** Total initialized dominions.
- **Total Wealth:** Aggregate Credits (CP) across all sectors.
- **Total Population:** Aggregate Citizens across all sectors.
- **System Status:** Heartbeat synchronization and database connectivity status.

---

## ⚙️ Global Mechanics

Adjust the baseline parameters that govern the Dominion's economy and growth.
- **Baseline Citizens/Tick:** Standard population growth rate.
- **Baseline Credits/Tick:** Standard income rate before multipliers.
- **Starting Resources:** Initial Credits/Citizens for new sectors.
- **Tick Interval:** Global heartbeat frequency in seconds.
- **Global Broadcast:** Transmit a high-priority message to all commanders via the UI.

---

## ⚔️ Battle Doctrine Calibration

Tune the mathematical foundations of the Dominion's war engine.
- **Atk Turns Soft Exp/Max Mult:** Adjust scaling and caps for massive turn-expenditure attacks.
- **Underdog Min Ratio:** Threshold for weaker attackers to breach defenses.
- **Random Noise:** Fog of war variance applied to all strikes.
- **Loot Caps:** Configure anti-farm thresholds for repeated engagements.

---

## 👁️ Sovereign Oversight & Impersonation

Manage player accounts and investigate sectors directly.

### 1. Search & Search Results
Use the targeting array to find kingdoms by ID, name, or handle.
- **Impersonate:** Initiate a **Neural Link** to view the game from that commander's perspective. This is used for direct audit and troubleshooting.
- **Sovereign Inspector:** Launch a deep-dive modal for granular sector modification.

### 2. The Sovereign Inspector
- **Core Identity:** Manage account metadata (username, email) and security clearance (Admin/Bot flags).
- **Dominion Stats:** Direct adjustment of Credits, XP, Turns, Population, and Attribute points.
- **Military:** Adjust specific unit quantities (Fielded vs. Stabled).
- **Structures:** Set the exact rank of any building in the sector.
- **Armory:** Manage item inventory and equipment status.

---

## 👥 War Room (Units)

Full lifecycle management of the Dominion's military assets.
- **Enlist New Class:** Create entirely new unit archetypes.
- **Tactical Calibration:**
    - **Combat Power:** Set Offense, Defense, Spy Offense, and Spy Defense.
    - **Requisition Costs:** Set Credits, Citizens, and Turns required for training.
    - **Economic Yield:** Set the Credits produced per unit per tick.
    - **Prerequisites:** Configure Foundation level or specific tech requirements.

---

## 🛠️ Armory Forge

Granular control over all military equipment.
- **Calibrate Asset:**
    - **Identity:** Edit names and slugs.
    - **Combat Bonuses:** Adjust raw ATK/DEF multipliers provided to the user.
    - **Costs:** Requisition price in CP.
    - **Tech Requirements:** Manage rank requirements or item dependencies.

---

## 🏛️ Structural Engineering

Manage the blueprints and evolution paths for all Dominion buildings.
- **Commission New Blueprint:** Create new structure types.
- **Rank Evolution Matrix:** For each structure, define the cost, HP buffs, economic multipliers, and capacity bonuses for every rank.

---

## 🤖 Automation Suite

Neural processing unit for automated sectors. Manage bot behaviors and action frequencies.
- **Automation Profiles:** Create and calibrate behavioral templates (e.g., "Warmonger", "Builder").
- **Weighted Decision Matrix:** Use sliders to adjust the probability of specific actions (Attack, Build, Train, Explore) per profile.
- **Action Frequency:** Define how often bots under a specific profile will execute an action.
- **Assignment:** Link bots to specific automation profiles via the **Sovereign Inspector**.

---

## 🧬 Evolutionary Strains (Barracks)

Manage the genetic traits of the various races in the Dominion.
- **Genetic Calibration:** Edit race descriptions and their specific neural multipliers (e.g., population growth bonuses, combat power boosts).

---

## 📡 Neural API Gate

Secure external access management for third-party tools and telemetry.
- **Key Matrix:** Manage active access tokens, rate limits (RPM), and scopes.
- **Pending Requests:** Review and Approve/Reject applications from developers.
- **Audit Trail:** Real-time log of every API interaction, including status codes and response times.

---

## 🕵️ Audit Trail

A centralized record of every administrative directive issued within the Command Center.
- **Directive Logs:** Every change is logged with the Admin's ID, the operation type, and detailed metadata.
- **Neural Export:** Download the entire audit trail as a JSON file for external processing.

---

## 📝 Documentation

Real-time management of the Dominion's public-facing protocols.
- **Official Rules:** A full-scale Markdown editor for the public `/rules` page.
- **Commit Protocol:** Changes are synchronized with the public sector immediately upon "Commit".

---

## 📜 Battle Records

A live audit of all tactical engagements across the Dominion.
- **Telemetry:** Attacker/Defender identities, outcome, and total CP plundered.

---

> **⚠️ WARNING:** Changes made within this terminal affect the live production database immediately. Unauthorized modifications constitute sector-level treason.
