<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddArmoryLevelToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('armory_level', 'integer', ['default' => 0, 'after' => 'foundation_level'])
              ->update();
    }
}
