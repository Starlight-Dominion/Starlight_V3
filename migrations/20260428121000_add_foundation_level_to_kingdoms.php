<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFoundationLevelToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('foundation_level', 'integer', ['default' => 1, 'after' => 'base_gold_per_tick'])
              ->update();
    }
}
