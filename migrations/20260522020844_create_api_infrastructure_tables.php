<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateApiInfrastructureTables extends AbstractMigration
{
    public function up(): void
    {
        // 1. API Keys Table
        $this->table('api_keys')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('api_token', 'string', ['limit' => 64])
            ->addColumn('rate_limit_per_minute', 'integer', ['default' => 60])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE'])
            ->addIndex(['api_token'], ['unique' => true])
            ->create();

        // 2. API Logs Table
        $this->table('api_logs')
            ->addColumn('api_key_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('endpoint', 'string', ['limit' => 255])
            ->addColumn('method', 'string', ['limit' => 10])
            ->addColumn('ip_address', 'string', ['limit' => 45])
            ->addColumn('status_code', 'integer')
            ->addColumn('response_time_ms', 'integer')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('api_key_id', 'api_keys', 'id', ['delete'=> 'SET_NULL'])
            ->addIndex(['created_at'])
            ->addIndex(['api_key_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('api_logs')->drop()->save();
        $this->table('api_keys')->drop()->save();
    }
}
