<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUnitMinersAndCitizenHold extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('unit_miners', 'integer', ['default' => 0, 'after' => 'unit_sentries'])
              ->addColumn('held_citizens', 'integer', ['default' => 0, 'after' => 'unit_miners'])
              ->addColumn('last_untrained', 'datetime', ['null' => true, 'after' => 'held_citizens'])
              ->update();
    }
}
