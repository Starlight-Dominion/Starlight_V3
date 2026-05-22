<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBattleFormulaSettings extends AbstractMigration
{
    public function up(): void
    {
        $rows = [
            [
                'setting_key' => 'battle_atk_turns_soft_exp',
                'setting_value' => '0.50',
                'description' => 'Soft exponent for turns multiplier in attack formula.'
            ],
            [
                'setting_key' => 'battle_atk_turns_max_mult',
                'setting_value' => '1.35',
                'description' => 'Maximum damage multiplier granted by high turn counts.'
            ],
            [
                'setting_key' => 'battle_underdog_min_ratio',
                'setting_value' => '0.985',
                'description' => 'Minimum power ratio required for an attacker to potentially win.'
            ],
            [
                'setting_key' => 'battle_random_noise_min',
                'setting_value' => '0.98',
                'description' => 'Minimum random variance applied to attack/defense power.'
            ],
            [
                'setting_key' => 'battle_random_noise_max',
                'setting_value' => '1.02',
                'description' => 'Maximum random variance applied to attack/defense power.'
            ],
            [
                'setting_key' => 'battle_guard_floor',
                'setting_value' => '20000',
                'description' => 'Minimum guard count protected from casualty calculations.'
            ],
            [
                'setting_key' => 'battle_hourly_full_loot_cap',
                'setting_value' => '5',
                'description' => 'Number of attacks on the same target before loot begins to diminish.'
            ],
            [
                'setting_key' => 'battle_hourly_reduced_loot_max',
                'setting_value' => '10',
                'description' => 'Maximum attacks on the same target before loot is completely negated.'
            ]
        ];

        $this->table('game_settings')->insert($rows)->save();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM game_settings WHERE setting_key LIKE 'battle_%'");
    }
}
