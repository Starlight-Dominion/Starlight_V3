<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRecruitmentLogsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('recruitment_logs');
        $table->addColumn('dominion_id', 'integer')
              ->addColumn('action', 'string', ['limit' => 100])
              ->addColumn('description', 'text')
              ->addColumn('amount', 'integer', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['dominion_id'])
              ->addIndex(['action'])
              ->create();
    }
}
