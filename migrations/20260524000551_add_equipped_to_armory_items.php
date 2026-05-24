<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEquippedToArmoryItems extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdom_armory_items');
        if (!$table->hasColumn('is_equipped')) {
            $table->addColumn('is_equipped', 'boolean', ['default' => false])
                  ->update();
        }
    }
}
