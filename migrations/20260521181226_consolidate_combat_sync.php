<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ConsolidateCombatSync extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Update Dominions Table (Attributes & Bank telemetry)
        $domTable = $this->table('dominions');
        
        $columnsToAdd = [
            ['strength_points', 'integer', ['default' => 0]],
            ['constitution_points', 'integer', ['default' => 0]],
            ['dexterity_points', 'integer', ['default' => 0]],
            ['charisma_points', 'integer', ['default' => 0]],
            ['deposits_today', 'integer', ['default' => 0]],
            ['last_deposit_timestamp', 'timestamp', ['null' => true]]
        ];

        foreach ($columnsToAdd as $col) {
            if (!$domTable->hasColumn($col[0])) {
                $domTable->addColumn($col[0], $col[1], $col[2]);
            }
        }
        $domTable->update();

        // 2. Armory Tables
        if (!$this->hasTable('armory_unit_types')) {
            $this->table('armory_unit_types')
                ->addColumn('slug', 'string', ['limit' => 30])
                ->addColumn('name', 'string', ['limit' => 50])
                ->addColumn('title', 'string', ['limit' => 100])
                ->addIndex(['slug'], ['unique' => true])
                ->create();
        }

        if (!$this->hasTable('armory_categories')) {
            $this->table('armory_categories')
                ->addColumn('unit_type_id', 'integer', ['signed' => false])
                ->addColumn('slug', 'string', ['limit' => 50])
                ->addColumn('name', 'string', ['limit' => 100])
                ->addColumn('slots', 'integer', ['default' => 1])
                ->addForeignKey('unit_type_id', 'armory_unit_types', 'id', ['delete'=> 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('armory_items')) {
            $this->table('armory_items')
                ->addColumn('category_id', 'integer', ['signed' => false])
                ->addColumn('slug', 'string', ['limit' => 50])
                ->addColumn('name', 'string', ['limit' => 100])
                ->addColumn('unit_type', 'string', ['limit' => 30])
                ->addColumn('attack_bonus', 'integer', ['default' => 0])
                ->addColumn('defense_bonus', 'integer', ['default' => 0])
                ->addColumn('cost', 'integer')
                ->addColumn('requirement_slug', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('armory_level_req', 'integer', ['default' => 0])
                ->addColumn('notes', 'string', ['limit' => 255, 'null' => true])
                ->addForeignKey('category_id', 'armory_categories', 'id', ['delete'=> 'CASCADE'])
                ->addIndex(['slug'], ['unique' => true])
                ->create();
        }

        if (!$this->hasTable('kingdom_armory_items')) {
            $this->table('kingdom_armory_items', ['id' => false, 'primary_key' => ['kingdom_id', 'item_id']])
                ->addColumn('kingdom_id', 'integer', ['signed' => false])
                ->addColumn('item_id', 'integer', ['signed' => false])
                ->addColumn('quantity', 'integer', ['default' => 0])
                ->addForeignKey('kingdom_id', 'dominions', 'id', ['delete'=> 'CASCADE'])
                ->addForeignKey('item_id', 'armory_items', 'id', ['delete'=> 'CASCADE'])
                ->create();
        }

        // 3. Bank Transactions
        if (!$this->hasTable('bank_transactions')) {
            $this->table('bank_transactions')
                ->addColumn('kingdom_id', 'integer', ['signed' => false])
                ->addColumn('transaction_type', 'enum', ['values' => ['deposit', 'withdraw']])
                ->addColumn('amount', 'biginteger')
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('kingdom_id', 'dominions', 'id', ['delete'=> 'CASCADE'])
                ->create();
        }

        // 4. Overhauled Battle Logs
        if ($this->hasTable('battle_logs')) {
            $this->table('battle_logs')->drop()->save();
        }
        $this->table('battle_logs')
            ->addColumn('attacker_id', 'integer', ['signed' => false])
            ->addColumn('defender_id', 'integer', ['signed' => false])
            ->addColumn('attacker_name', 'string', ['limit' => 100])
            ->addColumn('defender_name', 'string', ['limit' => 100])
            ->addColumn('outcome', 'enum', ['values' => ['victory', 'defeat']])
            ->addColumn('credits_stolen', 'biginteger', ['default' => 0])
            ->addColumn('turns_used', 'integer')
            ->addColumn('attacker_damage', 'biginteger')
            ->addColumn('defender_damage', 'biginteger')
            ->addColumn('attacker_xp_gained', 'integer', ['default' => 0])
            ->addColumn('defender_xp_gained', 'integer', ['default' => 0])
            ->addColumn('guards_lost', 'integer', ['default' => 0])
            ->addColumn('attacker_soldiers_lost', 'integer', ['default' => 0])
            ->addColumn('structure_damage', 'biginteger', ['default' => 0])
            ->addColumn('loot_factor', 'decimal', ['precision' => 3, 'scale' => 2, 'default' => 1.00])
            ->addColumn('battle_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['attacker_id', 'defender_id'])
            ->create();

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void {}
}