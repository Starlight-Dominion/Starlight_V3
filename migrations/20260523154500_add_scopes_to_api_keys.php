<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddScopesToApiKeys extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('api_keys');
        if (!$table->hasColumn('scopes')) {
            $table
                ->addColumn('scopes', 'string', ['limit' => 255, 'default' => '*', 'after' => 'rate_limit_per_minute'])
                ->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('api_keys');
        if ($table->hasColumn('scopes')) {
            $table
                ->removeColumn('scopes')
                ->update();
        }
    }
}
