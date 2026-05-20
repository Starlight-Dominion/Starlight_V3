<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EstablishDominionArchitecture extends AbstractMigration
{
    public function change(): void
    {
        // 1. Evolutionary Strains (Races) Lookup
        $races = $this->table('races');
        $races->addColumn('name', 'string', ['limit' => 30])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('bonus_type', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('bonus_value', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 0.00])
              ->addIndex(['name'], ['unique' => true])
              ->create();

        // 2. Dominion Persistence (Core Game State)
        $dominions = $this->table('dominions');
        $dominions->addColumn('user_id', 'integer', ['signed' => false])
                  ->addColumn('race_id', 'integer', ['signed' => false])
                  ->addColumn('name', 'string', ['limit' => 100])
                  ->addColumn('gold', 'biginteger', ['default' => 10000])
                  ->addColumn('citizens', 'integer', ['default' => 500])
                  ->addColumn('turns', 'integer', ['default' => 100])
                  ->addColumn('xp', 'integer', ['default' => 0])
                  ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  ->addForeignKey('race_id', 'races', 'id', ['delete'=> 'RESTRICT', 'update'=> 'NO_ACTION'])
                  ->addIndex(['user_id'], ['unique' => true])
                  ->addIndex(['race_id'])
                  ->addIndex(['name'], ['unique' => true])
                  ->create();

        // 3. Drop legacy Kingdoms if necessary (Handled manually or via separate migration)
    }
}