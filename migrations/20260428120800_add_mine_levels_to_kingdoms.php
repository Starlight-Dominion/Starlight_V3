<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMineLevelsToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('base_gold_per_tick', 'integer', ['default' => 100, 'after' => 'xp']);

        for ($i = 1; $i <= 10; $i++) {
            $table->addColumn("mine_{$i}_level", 'integer', ['default' => 0, 'after' => 'base_gold_per_tick']);
        }

        // Set Mine 1 to level 1 for all existing kingdoms
        $table->update();
        $this->execute("UPDATE kingdoms SET mine_1_level = 1");
    }
}
