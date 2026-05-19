<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RefactorMinerManagement extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('kingdoms');

        // Drop unit_miners if it exists (from previous, possibly failed, migration)
        if ($table->hasColumn('unit_miners')) {
            $table->removeColumn('unit_miners');
        }

        // Add or ensure 'miners' column exists
        if (!$table->hasColumn('miners')) {
            $table->addColumn('miners', 'integer', ['default' => 0, 'after' => 'citizens']);
        } else {
            // Ensure type is correct if it already existed (e.g., from an older, failed migration)
            // This part might need manual adjustment if there's existing data of incompatible type
            // For now, we assume it's compatible or a fresh install.
            // $table->changeColumn('miners', 'integer', ['default' => 0, 'after' => 'citizens']);
        }

        // Add held_citizens and last_untrained columns
        if (!$table->hasColumn('held_citizens')) {
            $table->addColumn('held_citizens', 'integer', ['default' => 0, 'after' => 'miners']);
        }
        if (!$table->hasColumn('last_untrained')) {
            $table->addColumn('last_untrained', 'datetime', ['null' => true, 'after' => 'held_citizens']);
        }

        $table->update();
    }
}
