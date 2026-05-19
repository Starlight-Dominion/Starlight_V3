<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMinersToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('miners', 'integer', ['default' => 0, 'after' => 'citizens'])
              ->update();
    }
}
