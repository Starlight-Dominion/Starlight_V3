<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTickLogsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('tick_logs')
            ->addColumn('tick_time', 'datetime')
            ->addColumn('total_sectors', 'integer')
            ->addColumn('total_credits_granted', 'biginteger')
            ->addColumn('total_citizens_born', 'integer')
            ->addColumn('total_turns_granted', 'integer')
            ->addColumn('execution_time_ms', 'float')
            ->addColumn('metadata', 'json', ['null' => true])
            ->addIndex(['tick_time'])
            ->create();
    }

    public function down(): void
    {
        $this->table('tick_logs')->drop()->save();
    }
}
