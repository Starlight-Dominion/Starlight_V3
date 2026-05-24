<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SyncDominionTableColumns extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('dominions');
        
        $cols = [
            ['current_mine_tier', 'integer', ['default' => 1]],
            ['current_mine_level', 'integer', ['default' => 0]],
            ['housing_level', 'integer', ['default' => 0]],
            ['mercenary_market_level', 'integer', ['default' => 0]],
            ['held_citizens', 'integer', ['default' => 0]],
            ['last_untrained', 'datetime', ['null' => true]],
            ['armory_level', 'integer', ['default' => 0]],
        ];

        foreach ($cols as $col) {
            if (!$table->hasColumn($col[0])) {
                $table->addColumn($col[0], $col[1], $col[2]);
            }
        }
        
        $table->update();
    }
}
