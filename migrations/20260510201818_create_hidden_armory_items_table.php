<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHiddenArmoryItemsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('hidden_armory_items', ['id' => false, 'primary_key' => ['kingdom_id', 'item_id']])
            ->addColumn('kingdom_id', 'integer', ['signed' => false])
            ->addColumn('item_id', 'integer')
            ->addForeignKey('kingdom_id', 'kingdoms', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->addForeignKey('item_id', 'armory_items', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->create();
    }
}
