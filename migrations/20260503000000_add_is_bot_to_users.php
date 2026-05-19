<?php

use Phinx\Migration\AbstractMigration;

class AddIsBotToUsers extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users');
        $table->addColumn('is_bot', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->update();
    }
}
