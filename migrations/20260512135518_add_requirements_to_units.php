<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRequirementsToUnits extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('units');
        $table->addColumn('requirement_slug', 'string', ['limit' => 32, 'null' => true, 'after' => 'power_defense'])
              ->addColumn('foundation_level_req', 'integer', ['default' => 0, 'after' => 'requirement_slug'])
              ->addColumn('stable_level_req', 'integer', ['default' => 0, 'after' => 'foundation_level_req'])
              ->addColumn('armory_level_req', 'integer', ['default' => 0, 'after' => 'stable_level_req'])
              ->update();
    }
}
