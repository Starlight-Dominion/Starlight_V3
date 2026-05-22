<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateOfficialRulesAndManual extends AbstractMigration
{
    public function up(): void
    {
        $content = <<<MARKDOWN
# Official Protocols & Commander's Manual

Welcome, Commander. This document serves as your definitive operational reference for Starlight Dominion. Adhere to these protocols or face sector-level decommissioning.

---

## ⚖️ Section I: Legal & Tactical Conduct

### 1. Authorization & Software
- **Unauthorized Third Party Software:** The use of scripts, macros, or external automation tools is strictly forbidden. Any account found utilizing such software is subject to immediate termination without warning.
- **Self-Harm Protocols:** Direct kinetic engagement against one's own sector (attacking oneself) is hard-locked by the neural link and prohibited.

### 2. Fair Play & Sportsmanship
- **Conduct:** Unsportsmanlike behavior, including exploitation of unforeseen system vulnerabilities, will not be tolerated. 
- **Retroactive Correction:** The High Command reserves the right to retroactively adjust player accounts that benefit from irregular gameplay. Actions are determined on a case-by-case basis.
- **Harassment:** Respect your fellow Sovereigns. Malicious signal interference or harassment is monitored and punishable.

### 3. Economic Stability
- **Vault Security:** The Interstellar Bank enforces safety limits on deposits (80% on-hand max) to ensure regional liquidity. Frequent or suspicious patterns are subject to audit.

---

## 🛰️ Section II: Core Operational Loop

The Dominion operates on a 15-minute **Sector Pulse (Tick)**. Every pulse generates resources and processes queued structural evolutions. Your goal is to expand your population, secure your treasury, and dominate the targeting array.

### 1. Civilian Recruitment (The Uplink)
Before you can train a military, you must have a civilian base.
- **Neural Recruitment:** Use the **Recruitment** tool to manually oversee civilian enlistment.
- **Manual Labor:** Each successful pulse in the recruitment uplink grants 1 Untrained Citizen.
- **Authorization Limits:** You are authorized for 150 civilian processes per session. You may initialize 2 sessions per 24 hours, and 5 sessions per 72-hour window.

### 2. Military Enlistment (Training)
Convert your civilian population into specialized tactical divisions via the **Training Grounds**.
- **Division Classes:**
    - **Guards:** Defensive specialists vital for protecting your credits.
    - **Soldiers:** Your primary expeditionary force for plundering rivals.
    - **Spies:** Essential for breaching target neural links (Intel).
    - **Sentries:** Specialized units designed to intercept enemy spies.
- **Requisition:** Enlistment requires **Credits (CP)**, **Citizens**, and **Tactical Turns**. 

### 3. Tactical Procurement (The Armory)
A soldier without gear is a liability. Starlight Dominion enforces a **1:1 Unit-to-Item** military power mandate.
- **Maximum Efficiency:** To reach 100% tactical output, every unit in a division must be equipped with the corresponding item (e.g., 500 Soldiers require 500 Kinetic Rifles).
- **The Forge:** Buy equipment in bulk or salvage old gear for a 50% credit refund.
- **Tech Requirements:** Advanced gear requires specific **Armory Tech Ranks** or the ownership of prerequisite lower-tier equipment.

### 4. Structural Command (Upgrades)
Evolving your infrastructure is the only way to scale your Dominion.
- **Planetary Foundation:** Increases the total integrity (HP) of your sector, shielding you from permanent damage during assaults.
- **Economic Hub:** Provides a percentage multiplier to your credit generation every tick.
- **Civilian Housing:** Increases the baseline number of citizens that join your sector automatically every tick.
- **Sector Armory:** Unlocks higher tiers of military equipment in the Forge.
- **Mercenary Market:** Grants immediate unit reinforcements upon reaching higher ranks.

### 5. The Battlefield (War Room)
Engagement is the primary method of XP acquisition and wealth redistribution.
- **Targeting Array:** View all active sectors in operational range.
- **Neural Intel:** Use spies to scan targets for manpower and liquidity data. Attacks launched without intel are high-risk.
- **Strike Magnitude:** Adjust the number of **Turns** used in a strike. Higher magnitudes increase damage and plunder but consume your capacity faster.
- **Plunder:** Victorious strikes siphon credits directly from the target's liquid assets.

---

## 💡 Strategic Tips
- **Secure Your Bank:** Liquid credits on hand are vulnerable to 20% plunder. Keep your treasury in the **Secure Bank** where it is protected and generates interest.
- **Maintain Integrity:** If your Foundation HP drops below 100%, structural upgrades are blocked. Use the **Nano-Repair** function in Structural Command to restore integrity.
- **Observe the Pulse:** Plan your big expenditures and attacks just before the 15-minute cycle refresh to maximize your momentum.

**End of Transmission.**
MARKDOWN;

        $content = addslashes($content);
        $this->execute("UPDATE game_settings SET setting_value = '$content' WHERE setting_key = 'official_rules'");
    }

    public function down(): void
    {
    }
}
