<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSpyStatsToUnits extends AbstractMigration
{
    public function up(): void
    {
        $this->table('units')
             ->addColumn('power_spy_offense', 'integer', ['default' => 0, 'after' => 'power_defense'])
             ->addColumn('power_spy_defense', 'integer', ['default' => 0, 'after' => 'power_spy_offense'])
             ->update();
    }

    public function down(): void
    {
        $this->table('units')
             ->removeColumn('power_spy_offense')
             ->removeColumn('power_spy_defense')
             ->update();
    }
}
