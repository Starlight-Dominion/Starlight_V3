<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEconomyToStructures extends AbstractMigration
{
    public function up(): void
    {
        // 1. Add Buff columns to Structure Levels
        $table = $this->table('structure_levels');
        $table->addColumn('buff_economy', 'integer', ['default' => 0, 'after' => 'buff_hp'])
              ->addColumn('buff_offense', 'integer', ['default' => 0, 'after' => 'buff_economy'])
              ->addColumn('buff_defense', 'integer', ['default' => 0, 'after' => 'buff_offense'])
              ->addColumn('capacity', 'integer', ['null' => true, 'after' => 'buff_defense'])
              ->update();

        // 2. Remove legacy miners column from Dominions
        if ($this->table('dominions')->hasColumn('miners')) {
            $this->table('dominions')->removeColumn('miners')->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('structure_levels');
        $table->removeColumn('buff_economy')
              ->removeColumn('buff_offense')
              ->removeColumn('buff_defense')
              ->removeColumn('capacity')
              ->update();

        $this->table('dominions')->addColumn('miners', 'integer', ['default' => 0])->update();
    }
}