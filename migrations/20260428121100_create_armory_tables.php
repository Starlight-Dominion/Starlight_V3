<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateArmoryTables extends AbstractMigration
{
    public function change(): void
    {
        // Armory Items Table
        $this->table('armory_items', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('type', 'enum', ['values' => ['head', 'primary', 'secondary']])
            ->addColumn('unit_type', 'string', ['limit' => 50])
            ->addColumn('tier', 'integer')
            ->addColumn('cost', 'integer')
            ->addColumn('attack_bonus', 'integer', ['default' => 0])
            ->addColumn('defense_bonus', 'integer', ['default' => 0])
            ->create();

        // Kingdom Armory Items Table (Join Table)
        $this->table('kingdom_armory_items', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('kingdom_id', 'integer', ['signed' => false])
            ->addColumn('item_id', 'integer')
            ->addColumn('quantity', 'integer', ['default' => 1])
            ->addForeignKey('kingdom_id', 'kingdoms', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->addForeignKey('item_id', 'armory_items', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->addIndex(['kingdom_id', 'item_id'], ['unique' => true])
            ->create();
    }
}
