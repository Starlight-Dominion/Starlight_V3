<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class StarlightDominionCore extends AbstractMigration
{
    public function up(): void
    {
        // Force a clean state before building
        $this->down();

        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Races (Evolutionary Strains)
        $this->table('races')
            ->addColumn('name', 'string', ['limit' => 30])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('bonus_type', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('bonus_value', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 0.00])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // 2. Users (Commanders)
        $this->table('users')
            ->addColumn('username', 'string', ['limit' => 50])
            ->addColumn('email', 'string', ['limit' => 100])
            ->addColumn('password', 'string')
            ->addColumn('avatar_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('is_bot', 'boolean', ['default' => false])
            ->addColumn('is_admin', 'boolean', ['default' => false])
            ->addColumn('stasis_until', 'datetime', ['null' => true])
            ->addColumn('handle_last_changed', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();

        // 3. Dominions (The core state being inserted during Enlist)
        $this->table('dominions')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('race_id', 'integer', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('credits', 'biginteger', ['default' => 10000])
            ->addColumn('credits_banked', 'biginteger', ['default' => 0])
            ->addColumn('citizens', 'integer', ['default' => 500])
            ->addColumn('turns', 'integer', ['default' => 100])
            ->addColumn('xp', 'integer', ['default' => 0])
            ->addColumn('foundation_hp', 'biginteger', ['default' => 1000])
            ->addColumn('foundation_max_hp', 'biginteger', ['default' => 1000])
            ->addColumn('last_tick', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE'])
            ->addForeignKey('race_id', 'races', 'id', ['delete'=> 'RESTRICT'])
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // 4. Units
        $this->table('units')
            ->addColumn('slug', 'string', ['limit' => 30])
            ->addColumn('name', 'string', ['limit' => 50])
            ->addColumn('description', 'text')
            ->addColumn('cost_credits', 'integer')
            ->addColumn('cost_citizens', 'integer')
            ->addColumn('cost_turns', 'integer')
            ->addColumn('power_offense', 'integer')
            ->addColumn('power_defense', 'integer')
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        // 5. Dominion Manpower
        $this->table('dominion_manpower', ['id' => false, 'primary_key' => ['dominion_id', 'unit_id']])
            ->addColumn('dominion_id', 'integer', ['signed' => false])
            ->addColumn('unit_id', 'integer', ['signed' => false])
            ->addColumn('total_quantity', 'integer', ['default' => 0])
            ->addColumn('stabled_quantity', 'integer', ['default' => 0])
            ->addForeignKey('dominion_id', 'dominions', 'id', ['delete'=> 'CASCADE'])
            ->addForeignKey('unit_id', 'units', 'id', ['delete'=> 'CASCADE'])
            ->create();

        // 6. Structures
        $this->table('structures')
            ->addColumn('slug', 'string', ['limit' => 30])
            ->addColumn('name', 'string', ['limit' => 50])
            ->addColumn('description', 'text')
            ->addColumn('max_level', 'integer', ['default' => 20])
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        // 7. Structure Levels
        $this->table('structure_levels', ['id' => false, 'primary_key' => ['structure_id', 'level']])
            ->addColumn('structure_id', 'integer', ['signed' => false])
            ->addColumn('level', 'integer')
            ->addColumn('cost', 'biginteger')
            ->addColumn('buff_hp', 'biginteger', ['default' => 0])
            ->addColumn('player_level_req', 'integer', ['default' => 1])
            ->addColumn('buff_name', 'string', ['limit' => 100])
            ->addForeignKey('structure_id', 'structures', 'id', ['delete'=> 'CASCADE'])
            ->create();

        // 8. Dominion Structures
        $this->table('dominion_structures', ['id' => false, 'primary_key' => ['dominion_id', 'structure_id']])
            ->addColumn('dominion_id', 'integer', ['signed' => false])
            ->addColumn('structure_id', 'integer', ['signed' => false])
            ->addColumn('level', 'integer', ['default' => 0])
            ->addColumn('mod_slot_1', 'string', ['limit' => 50, 'null' => true])
            ->addForeignKey('dominion_id', 'dominions', 'id', ['delete'=> 'CASCADE'])
            ->addForeignKey('structure_id', 'structures', 'id', ['delete'=> 'CASCADE'])
            ->create();

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        // Execute a forceful drop of all tables to guarantee a clean slate
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        
        $tables = [
            'dominion_structures',
            'structure_levels',
            'structures',
            'dominion_manpower',
            'units',
            'dominions',
            'users',
            'races',
            // Legacy tables just in case they survived
            'kingdoms', 
            'bank_transactions',
            'battle_logs'
        ];

        foreach ($tables as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}