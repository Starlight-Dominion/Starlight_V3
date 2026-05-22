<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateApiApplicationsTable extends AbstractMigration
{
    public function up(): void
    {
        // 1. API Applications Table
        $this->table('api_applications')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('project_name', 'string', ['limit' => 255])
            ->addColumn('justification', 'text')
            ->addColumn('status', 'enum', ['values' => ['pending', 'approved', 'rejected'], 'default' => 'pending'])
            ->addColumn('admin_notes', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE'])
            ->addIndex(['status'])
            ->create();

        // 2. Enhance API Logs Table
        $this->table('api_logs')
            ->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('payload', 'json', ['null' => true])
            ->addColumn('error_log', 'text', ['null' => true])
            ->update();
    }

    public function down(): void
    {
        $this->table('api_applications')->drop()->save();
        
        $this->table('api_logs')
            ->removeColumn('user_agent')
            ->removeColumn('payload')
            ->removeColumn('error_log')
            ->update();
    }
}
