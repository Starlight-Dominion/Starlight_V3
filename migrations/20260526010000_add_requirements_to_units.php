<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRequirementsToUnits extends AbstractMigration
{
    public function up(): void
    {
        $this->table('units')
             ->addColumn('requirement_slug', 'string', ['limit' => 50, 'null' => true, 'after' => 'production_credits'])
             ->addColumn('foundation_level_req', 'integer', ['default' => 0, 'after' => 'requirement_slug'])
             ->update();
    }

    public function down(): void
    {
        $this->table('units')
             ->removeColumn('requirement_slug')
             ->removeColumn('foundation_level_req')
             ->update();
    }
}
