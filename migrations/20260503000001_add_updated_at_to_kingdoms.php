<?php

use Phinx\Migration\AbstractMigration;

class AddUpdatedAtToKingdoms extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('kingdoms');
        $table->addColumn('updated_at', 'datetime', [
            'null' => true,
        ])
        ->update();
    }
}
