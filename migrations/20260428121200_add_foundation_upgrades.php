<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFoundationUpgrades extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('foundation_hp', 'integer', ['default' => 100, 'after' => 'foundation_level'])
              ->addColumn('foundation_upgrade_slot_1', 'string', ['limit' => 50, 'null' => true, 'after' => 'foundation_hp'])
              ->update();
    }
}
