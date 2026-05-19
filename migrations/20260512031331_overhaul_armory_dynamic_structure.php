<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OverhaulArmoryDynamicStructure extends AbstractMigration
{
    public function change(): void
    {
        // 1. Create Unit Types Table
        $this->table('armory_unit_types')
            ->addColumn('slug', 'string', ['limit' => 32])
            ->addColumn('name', 'string', ['limit' => 64])
            ->addColumn('title', 'string', ['limit' => 128]) // e.g., Vanguard Offensive Loadout
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        // 2. Create Categories Table
        $this->table('armory_categories')
            ->addColumn('unit_type_id', 'integer', ['signed' => false])
            ->addColumn('slug', 'string', ['limit' => 32])
            ->addColumn('name', 'string', ['limit' => 64])
            ->addColumn('slots', 'integer', ['default' => 1])
            ->addForeignKey('unit_type_id', 'armory_unit_types', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        // 3. Update Armory Items
        $table = $this->table('armory_items');
        $table->addColumn('category_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'id'])
              ->addForeignKey('category_id', 'armory_categories', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->update();
    }
}
