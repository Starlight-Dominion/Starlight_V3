<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdminLogsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('admin_logs');
        $table->addColumn('admin_id', 'integer')
              ->addColumn('action', 'string', ['limit' => 100])
              ->addColumn('description', 'text')
              ->addColumn('metadata', 'json', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['admin_id'])
              ->addIndex(['action'])
              ->create();
    }
}
