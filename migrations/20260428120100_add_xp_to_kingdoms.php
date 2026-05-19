<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddXpToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('xp', 'integer', [
            'default' => 0,
            'after' => 'kingdom_name',
            'comment' => 'Player experience points'
        ])
        ->update();
    }
}
