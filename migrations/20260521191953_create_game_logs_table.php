<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGameLogsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('game_logs')
            ->addColumn('dominion_id', 'integer', ['signed' => false])
            ->addColumn('action', 'string', ['limit' => 50])
            ->addColumn('description', 'text')
            ->addColumn('amount', 'biginteger', ['null' => true])
            ->addColumn('metadata', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('dominion_id', 'dominions', 'id', ['delete'=> 'CASCADE'])
            ->addIndex(['dominion_id', 'action'])
            ->addIndex(['created_at'])
            ->create();
    }

    public function down(): void
    {
        $this->table('game_logs')->drop()->save();
    }
}
