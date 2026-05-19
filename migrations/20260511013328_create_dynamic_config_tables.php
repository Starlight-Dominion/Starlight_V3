<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDynamicConfigTables extends AbstractMigration
{
    public function change(): void
    {
        // Units Table
        $this->table('units')
            ->addColumn('slug', 'string', ['limit' => 32])
            ->addColumn('name', 'string', ['limit' => 64])
            ->addColumn('description', 'text')
            ->addColumn('cost_gold', 'integer')
            ->addColumn('cost_citizens', 'integer')
            ->addColumn('cost_turns', 'integer')
            ->addColumn('power_offense', 'integer')
            ->addColumn('power_defense', 'integer')
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        // Foundation Tiers Table
        $this->table('foundation_tiers', ['id' => false, 'primary_key' => ['level']])
            ->addColumn('level', 'integer')
            ->addColumn('name', 'string', ['limit' => 64])
            ->addColumn('description', 'text')
            ->addColumn('hp', 'biginteger')
            ->addColumn('cost', 'biginteger')
            ->addColumn('player_level_req', 'integer')
            ->create();

        // Stable Levels Table
        $this->table('stable_levels', ['id' => false, 'primary_key' => ['level']])
            ->addColumn('level', 'integer')
            ->addColumn('cost', 'biginteger')
            ->addColumn('capacity', 'integer')
            ->create();

        // Armory Upgrades Table
        $this->table('armory_upgrades', ['id' => false, 'primary_key' => ['level']])
            ->addColumn('level', 'integer')
            ->addColumn('cost', 'biginteger')
            ->create();
    }
}
