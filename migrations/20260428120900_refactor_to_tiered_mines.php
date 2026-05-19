<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RefactorToTieredMines extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');

        // Remove old mine level columns if they exist
        for ($i = 1; $i <= 10; $i++) {
            if ($table->hasColumn("mine_{$i}_level")) {
                $table->removeColumn("mine_{$i}_level");
            }
        }
        
        // Add new tiered columns
        $table->addColumn('current_mine_tier', 'integer', ['default' => 1, 'after' => 'base_gold_per_tick'])
              ->addColumn('current_mine_level', 'integer', ['default' => 1, 'after' => 'current_mine_tier'])
              ->update();
    }
}
