<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHousingAndMercenaryMarketLevelsToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('housing_level', 'integer', ['default' => 1, 'after' => 'foundation_upgrade_slot_1'])
              ->addColumn('mercenary_market_level', 'integer', ['default' => 0, 'after' => 'housing_level'])
              ->update();
    }
}
