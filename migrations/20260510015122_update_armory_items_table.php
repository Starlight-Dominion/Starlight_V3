<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateArmoryItemsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('armory_items');
        
        // Remove old columns not in the new spec
        if ($table->hasColumn('type')) {
            $table->removeColumn('type');
        }
        if ($table->hasColumn('tier')) {
            $table->removeColumn('tier');
        }

        $table->addColumn('slug', 'string', ['limit' => 64, 'after' => 'id'])
              ->addColumn('category', 'string', ['limit' => 64, 'after' => 'name'])
              ->addColumn('requirement_slug', 'string', ['limit' => 64, 'null' => true, 'after' => 'cost'])
              ->addColumn('armory_level_req', 'integer', ['default' => 0, 'after' => 'requirement_slug'])
              ->addIndex(['slug'], ['unique' => true])
              ->update();
    }
}