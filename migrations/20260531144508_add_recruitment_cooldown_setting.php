<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRecruitmentCooldownSetting extends AbstractMigration
{
    public function up(): void
    {
        $rows = [
            [
                'setting_key'   => 'recruitment_click_cooldown_ms',
                'setting_value' => '500'
            ]
        ];

        $this->table('game_settings')->insert($rows)->save();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM game_settings WHERE setting_key = 'recruitment_click_cooldown_ms'");
    }
}
