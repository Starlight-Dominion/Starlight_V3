<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddOfficialRulesSetting extends AbstractMigration
{
    public function up(): void
    {
        $rows = [
            [
                'setting_key' => 'official_rules',
                'setting_value' => "# Official Rules of Starlight Dominion\n\nWelcome Commander. Adhere to these protocols or face sector-level decommissioning.\n\n### 1. Tactical Conduct\n- Automation scripts are strictly prohibited.\n- Self-harm protocols (attacking oneself) are blocked by the neural link.\n\n### 2. Economic Stability\n- The Bank of the Dominion enforces safety limits on deposits to ensure liquidity.\n\n### 3. Diplomatic Relations\n- Respect your fellow Sovereigns. Signal interference (harassment) is monitored.",
                'description' => 'Markdown-supported content for the Official Rules page.'
            ]
        ];

        $this->table('game_settings')->insert($rows)->save();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM game_settings WHERE setting_key = 'official_rules'");
    }
}
