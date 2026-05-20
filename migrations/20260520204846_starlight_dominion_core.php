<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class StarlightDominionCore extends AbstractMigration
{
    /**
     * Idempotent migration to establish the Starlight Dominion core.
     * Checks for table existence before creation to ensure a clean run.
     */
    public function change(): void
    {
        // 0. Cleanup Legacy Artifacts if they exist
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        if ($this->hasTable('kingdoms')) { $this->table('kingdoms')->drop()->save(); }
        if ($this->hasTable('bank_transactions')) { $this->table('bank_transactions')->drop()->save(); }
        if ($this->hasTable('battle_logs')) { $this->table('battle_logs')->drop()->save(); }
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Evolutionary Strains (Races)
        if (!$this->hasTable('races')) {
            $races = $this->table('races');
            $races->addColumn('name', 'string', ['limit' => 30])
                  ->addColumn('description', 'text', ['null' => true])
                  ->addColumn('bonus_type', 'string', ['limit' => 30, 'null' => true])
                  ->addColumn('bonus_value', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 0.00])
                  ->addIndex(['name'], ['unique' => true])
                  ->create();
        }

        // 2. Commander Identities (Users)
        if (!$this->hasTable('users')) {
            $users = $this->table('users');
            $users->addColumn('username', 'string', ['limit' => 50])
                  ->addColumn('email', 'string', ['limit' => 100])
                  ->addColumn('password', 'string')
                  ->addColumn('avatar_path', 'string', ['limit' => 255, 'null' => true])
                  ->addColumn('is_bot', 'boolean', ['default' => false])
                  ->addColumn('is_admin', 'boolean', ['default' => false])
                  ->addColumn('stasis_until', 'datetime', ['null' => true])
                  ->addColumn('handle_last_changed', 'datetime', ['null' => true])
                  ->addTimestamps() // Standard Eloquent: created_at, updated_at
                  ->addIndex(['username'], ['unique' => true])
                  ->addIndex(['email'], ['unique' => true])
                  ->create();
        }

        // 3. Sector Dominions (Main Game State)
        if (!$this->hasTable('dominions')) {
            $dominions = $this->table('dominions');
            $dominions->addColumn('user_id', 'integer', ['signed' => false])
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
                      ->addTimestamps() // Standard Eloquent: created_at, updated_at
                      ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE'])
                      ->addForeignKey('race_id', 'races', 'id', ['delete'=> 'RESTRICT'])
                      ->addIndex(['user_id'], ['unique' => true])
                      ->addIndex(['name'], ['unique' => true])
                      ->create();
        }

        // 4. Military Unit Definitions
        if (!$this->hasTable('units')) {
            $units = $this->table('units');
            $units->addColumn('slug', 'string', ['limit' => 30])
                  ->addColumn('name', 'string', ['limit' => 50])
                  ->addColumn('description', 'text')
                  ->addColumn('cost_credits', 'integer')
                  ->addColumn('cost_citizens', 'integer')
                  ->addColumn('cost_turns', 'integer')
                  ->addColumn('power_offense', 'integer')
                  ->addColumn('power_defense', 'integer')
                  ->addIndex(['slug'], ['unique' => true])
                  ->create();
        }

        // 5. Dominion Manpower (Instance Tracking)
        if (!$this->hasTable('dominion_manpower')) {
            $manpower = $this->table('dominion_manpower', ['id' => false, 'primary_key' => ['dominion_id', 'unit_id']]);
            $manpower->addColumn('dominion_id', 'integer', ['signed' => false])
                     ->addColumn('unit_id', 'integer', ['signed' => false])
                     ->addColumn('total_quantity', 'integer', ['default' => 0])
                     ->addColumn('stabled_quantity', 'integer', ['default' => 0])
                     ->addForeignKey('dominion_id', 'dominions', 'id', ['delete'=> 'CASCADE'])
                     ->addForeignKey('unit_id', 'units', 'id', ['delete'=> 'CASCADE'])
                     ->create();
        }

        // 6. Structural Blueprints
        if (!$this->hasTable('structures')) {
            $structures = $this->table('structures');
            $structures->addColumn('slug', 'string', ['limit' => 30])
                       ->addColumn('name', 'string', ['limit' => 50])
                       ->addColumn('description', 'text')
                       ->addColumn('max_level', 'integer', ['default' => 20])
                       ->addIndex(['slug'], ['unique' => true])
                       ->create();
        }

        // 7. Structural Evolution (Levels)
        if (!$this->hasTable('structure_levels')) {
            $levels = $this->table('structure_levels', ['id' => false, 'primary_key' => ['structure_id', 'level']]);
            $levels->addColumn('structure_id', 'integer', ['signed' => false])
                   ->addColumn('level', 'integer')
                   ->addColumn('cost', 'biginteger')
                   ->addColumn('buff_hp', 'biginteger', ['default' => 0])
                   ->addColumn('player_level_req', 'integer', ['default' => 1])
                   ->addColumn('buff_name', 'string', ['limit' => 100])
                   ->addForeignKey('structure_id', 'structures', 'id', ['delete'=> 'CASCADE'])
                   ->create();
        }

        // 8. Dominion Structural Status
        if (!$this->hasTable('dominion_structures')) {
            $domStructures = $this->table('dominion_structures', ['id' => false, 'primary_key' => ['dominion_id', 'structure_id']]);
            $domStructures->addColumn('dominion_id', 'integer', ['signed' => false])
                          ->addColumn('structure_id', 'integer', ['signed' => false])
                          ->addColumn('level', 'integer', ['default' => 0])
                          ->addColumn('mod_slot_1', 'string', ['limit' => 50, 'null' => true])
                          ->addForeignKey('dominion_id', 'dominions', 'id', ['delete'=> 'CASCADE'])
                          ->addForeignKey('structure_id', 'structures', 'id', ['delete'=> 'CASCADE'])
                          ->create();
        }
    }
}