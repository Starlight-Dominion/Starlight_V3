<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBankFieldsToKingdoms extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');
        $table->addColumn('gold_in_bank', 'biginteger', ['default' => 0, 'after' => 'gold'])
              ->addColumn('deposits_today', 'integer', ['default' => 0, 'after' => 'last_tick'])
              ->addColumn('last_deposit_recharge', 'datetime', ['null' => true, 'after' => 'deposits_today'])
              ->update();
    }
}
