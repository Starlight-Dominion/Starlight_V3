<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBattleLogs extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('battle_logs');
        $table->addColumn('attacker_kingdom_id', 'integer', ['signed' => false])
              ->addColumn('defender_kingdom_id', 'integer', ['signed' => false])
              ->addColumn('attacker_units', 'json', ['null' => true])
              ->addColumn('defender_units', 'json', ['null' => true])
              ->addColumn('result', 'string', ['limit' => 20])
              ->addColumn('attacker_loss_percent', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true])
              ->addColumn('defender_loss_percent', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true])
              ->addColumn('gold_looted', 'biginteger', ['default' => 0])
              ->addColumn('turns_spent', 'integer', ['default' => 1])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['attacker_kingdom_id'])
              ->addIndex(['defender_kingdom_id'])
              ->addIndex(['created_at'])
              ->create();
    }
}
