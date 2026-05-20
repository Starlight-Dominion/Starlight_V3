<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateUserSettingsFields extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('avatar_path', 'string', ['limit' => 255, 'null' => true, 'after' => 'email'])
              ->addColumn('stasis_until', 'datetime', ['null' => true, 'after' => 'is_admin'])
              ->addColumn('handle_last_changed', 'datetime', ['null' => true, 'after' => 'stasis_until'])
              ->update();
    }
}