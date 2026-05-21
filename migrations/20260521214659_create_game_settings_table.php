<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGameSettingsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('game_settings', ['id' => false, 'primary_key' => 'setting_key'])
            ->addColumn('setting_key', 'string', ['limit' => 100])
            ->addColumn('setting_value', 'text')
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->create();

        // Seed Initial Baseline Values
        $rows = [
            [
                'setting_key' => 'baseline_citizens_per_tick',
                'setting_value' => '50',
                'description' => 'Number of citizens granted to each dominion every game tick.'
            ],
            [
                'setting_key' => 'baseline_credits_per_tick',
                'setting_value' => '100',
                'description' => 'Baseline credits granted to each dominion every game tick (before multipliers).'
            ],
            [
                'setting_key' => 'starting_credits',
                'setting_value' => '10000',
                'description' => 'Amount of credits a new player starts with.'
            ],
            [
                'setting_key' => 'starting_citizens',
                'setting_value' => '500',
                'description' => 'Amount of citizens a new player starts with.'
            ],
            [
                'setting_key' => 'tick_interval_seconds',
                'setting_value' => '900',
                'description' => 'Frequency of the global game tick in seconds (900 = 15 mins).'
            ]
        ];

        $this->table('game_settings')->insert($rows)->save();
    }

    public function down(): void
    {
        $this->table('game_settings')->drop()->save();
    }
}
