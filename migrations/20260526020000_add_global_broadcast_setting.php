<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddGlobalBroadcastSetting extends AbstractMigration
{
    public function up(): void
    {
        $rows = [
            [
                'setting_key' => 'global_broadcast',
                'setting_value' => '',
                'description' => 'A global message or announcement displayed to all commanders.'
            ]
        ];

        $this->table('game_settings')->insert($rows)->save();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM game_settings WHERE setting_key = 'global_broadcast'");
    }
}
