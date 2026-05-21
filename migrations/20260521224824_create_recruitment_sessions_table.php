<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRecruitmentSessionsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('recruitment_sessions')
            ->addColumn('dominion_id', 'integer', ['signed' => false])
            ->addColumn('clicks_count', 'integer', ['default' => 0])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('completed_at', 'timestamp', ['null' => true])
            ->addForeignKey('dominion_id', 'dominions', 'id', ['delete'=> 'CASCADE'])
            ->addIndex(['dominion_id', 'created_at'])
            ->create();

        // Add settings for recruitment
        $rows = [
            [
                'setting_key' => 'recruitment_clicks_per_session',
                'setting_value' => '150',
                'description' => 'Maximum clicks (citizens) allowed per recruitment session.'
            ],
            [
                'setting_key' => 'recruitment_sessions_per_day',
                'setting_value' => '2',
                'description' => 'Maximum number of recruitment sessions allowed in a 24-hour window.'
            ],
            [
                'setting_key' => 'recruitment_sessions_per_3days',
                'setting_value' => '5',
                'description' => 'Maximum number of recruitment sessions allowed in a 72-hour window.'
            ]
        ];

        $this->table('game_settings')->insert($rows)->save();
    }

    public function down(): void
    {
        $this->table('recruitment_sessions')->drop()->save();
        $this->execute("DELETE FROM game_settings WHERE setting_key LIKE 'recruitment_%'");
    }
}
