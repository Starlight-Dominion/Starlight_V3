<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIsAdminToUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('is_admin', 'boolean', ['default' => false, 'after' => 'is_bot'])
              ->update();
    }
}
