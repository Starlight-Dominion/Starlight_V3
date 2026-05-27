<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SyncStructureSchema extends AbstractMigration
{
    public function up(): void
    {
        // 1. Update structures table
        $structures = $this->table('structures');
        if (!$structures->hasColumn('upgrade_slots')) {
            $structures->addColumn('upgrade_slots', 'integer', ['default' => 1, 'after' => 'description'])
                      ->update();
        }

        // 2. Update structure_levels table
        $levels = $this->table('structure_levels');
        $columnsToAdd = [
            'buff_offense' => 'integer',
            'buff_defense' => 'integer',
            'buff_economy' => 'integer',
            'capacity' => 'integer'
        ];

        foreach ($columnsToAdd as $col => $type) {
            if (!$levels->hasColumn($col)) {
                $levels->addColumn($col, $type, ['default' => 0])
                       ->update();
            }
        }
    }

    public function down(): void
    {
        $structures = $this->table('structures');
        if ($structures->hasColumn('upgrade_slots')) {
            $structures->removeColumn('upgrade_slots')->update();
        }

        $levels = $this->table('structure_levels');
        $columnsToRemove = ['buff_offense', 'buff_defense', 'buff_economy', 'capacity'];
        foreach ($columnsToRemove as $col) {
            if ($levels->hasColumn($col)) {
                $levels->removeColumn($col)->update();
            }
        }
    }
}
