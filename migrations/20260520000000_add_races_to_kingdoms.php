<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRacesToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        // 1. Create Normalized Races Table
        if (!$this->hasTable('races')) {
            $races = $this->table('races');
            $races->addColumn('name', 'string', ['limit' => 30])
                  ->addColumn('description', 'text', ['null' => true])
                  ->addColumn('bonus_type', 'string', ['limit' => 20, 'null' => true])
                  ->addColumn('bonus_value', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 0.00])
                  ->addIndex(['name'], ['unique' => true])
                  ->create();
            
            // Auto-seed to ensure Foreign Keys don't break during migration
            $races->insert([
                ['name' => 'Human', 'description' => 'Highly adaptable. Standard baseline.'],
                ['name' => 'Cyborg', 'description' => 'Cybernetically enhanced for superior logic.'],
                ['name' => 'Shade', 'description' => 'Masters of stealth and covert operations.'],
                ['name' => 'Synthera', 'description' => 'Artificial intelligence collective.']
            ])->saveData();
        }

        // 2. Link Races to existing Kingdoms (Dominions)
        $kingdoms = $this->table('kingdoms');
        if (!$kingdoms->hasColumn('race_id')) {
            // Add column allowing null initially to prevent crashes on existing rows
            $kingdoms->addColumn('race_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'user_id'])
                     ->update();

            // Set existing users to Human (ID 1)
            $this->execute('UPDATE kingdoms SET race_id = 1 WHERE race_id IS NULL');

            // Enforce scaling constraints and indexes
            $kingdoms->changeColumn('race_id', 'integer', ['signed' => false, 'null' => false])
                     ->addIndex(['race_id'])
                     ->addForeignKey('race_id', 'races', 'id', ['delete'=> 'RESTRICT', 'update'=> 'NO_ACTION'])
                     ->update();
        }
    }
}