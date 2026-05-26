<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProductionCreditsToUnits extends AbstractMigration
{
    public function up(): void
    {
        $this->table('units')
             ->addColumn('production_credits', 'integer', ['default' => 0, 'after' => 'power_defense'])
             ->update();
    }

    public function down(): void
    {
        $this->table('units')
             ->removeColumn('production_credits')
             ->update();
    }
}
