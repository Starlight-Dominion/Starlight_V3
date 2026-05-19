<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersAndKingdoms extends AbstractMigration
{
    public function change(): void
    {
        // Users Table
        $table = $this->table('users');
        $table->addColumn('username', 'string', ['limit' => 50])
              ->addColumn('email', 'string', ['limit' => 100])
              ->addColumn('password', 'string')
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['null' => true])
              ->addIndex(['username'], ['unique' => true])
              ->addIndex(['email'], ['unique' => true])
              ->create();

        // Kingdoms Table
        $kingdoms = $this->table('kingdoms');
        $kingdoms->addColumn('user_id', 'integer', ['signed' => false])
                 ->addColumn('kingdom_name', 'string', ['limit' => 100])
                 ->addColumn('gold', 'biginteger', ['default' => 10000])
                 ->addColumn('citizens', 'integer', ['default' => 500])
                 ->addColumn('turns', 'integer', ['default' => 100])
                 ->addColumn('last_tick', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                 ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                 ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                 ->addIndex(['user_id'], ['unique' => true])
                 ->create();
    }
}
