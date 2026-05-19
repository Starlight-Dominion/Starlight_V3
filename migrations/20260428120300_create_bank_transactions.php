<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBankTransactions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('bank_transactions');
        $table->addColumn('kingdom_id', 'integer', ['signed' => false])
              ->addColumn('transaction_type', 'enum', ['values' => ['deposit', 'withdraw']])
              ->addColumn('amount', 'biginteger')
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('kingdom_id', 'kingdoms', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
