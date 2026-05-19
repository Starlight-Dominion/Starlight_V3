<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUnitColumnsToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('unit_guards', 'integer', ['default' => 0, 'after' => 'xp'])
              ->addColumn('unit_soldiers', 'integer', ['default' => 0, 'after' => 'unit_guards'])
              ->addColumn('unit_spies', 'integer', ['default' => 0, 'after' => 'unit_soldiers'])
              ->addColumn('unit_sentries', 'integer', ['default' => 0, 'after' => 'unit_spies'])
              ->update();
    }
}
