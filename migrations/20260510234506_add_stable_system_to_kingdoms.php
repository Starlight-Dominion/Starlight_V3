<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddStableSystemToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('stable_level', 'integer', ['default' => 0, 'after' => 'armory_level'])
              ->addColumn('stabled_unit_guards', 'integer', ['default' => 0, 'after' => 'unit_sentries'])
              ->addColumn('stabled_unit_soldiers', 'integer', ['default' => 0, 'after' => 'stabled_unit_guards'])
              ->addColumn('stabled_unit_spies', 'integer', ['default' => 0, 'after' => 'stabled_unit_soldiers'])
              ->addColumn('stabled_unit_sentries', 'integer', ['default' => 0, 'after' => 'stabled_unit_spies'])
              ->update();
    }
}
