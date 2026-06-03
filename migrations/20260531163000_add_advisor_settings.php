<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAdvisorSettings extends AbstractMigration
{
    public function up(): void
    {
        $rows = [
            [
                'setting_key' => 'ai_advisor_messages',
                'setting_value' => "Your Treasury grows with each tick. Consider investing in infrastructure to boost your income.\nA large population is the backbone of a strong army. Protect your Citizens.\nTurns are your most valuable resource. Spend them wisely to outmaneuver your rivals.\nScouting your neighbors can reveal valuable information before you commit to an attack.\nDon't neglect your defenses. A strong wall can repel a weak-willed invader.",
                'description' => 'Curated messages for the A.I. Advisor. One message per line.'
            ],
            [
                'setting_key' => 'dominion_news',
                'setting_value' => 'The Sector remains stable under High Command oversight. Tactical synchronization at 100%.',
                'description' => 'Global news broadcast displayed in the advisor panel.'
            ],
            [
                'setting_key' => 'ai_advisor_pulse_enabled',
                'setting_value' => '1',
                'description' => 'Toggle the blinking UI pulse on the advisor panel (1 = On, 0 = Off).'
            ]
        ];

        $this->table('game_settings')->insert($rows)->save();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM game_settings WHERE setting_key IN ('ai_advisor_messages', 'dominion_news', 'ai_advisor_pulse_enabled')");
    }
}
