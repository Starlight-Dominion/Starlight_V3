<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class PurgeStableSystem extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Remove stabled_quantity from manpower tracking
        $table = $this->table('dominion_manpower');
        if ($table->hasColumn('stabled_quantity')) {
            $table->removeColumn('stabled_quantity')->update();
        }

        // 2. Remove stable-related structural data
        $stable = $this->fetchRow("SELECT id FROM structures WHERE slug = 'stable'");
        if ($stable) {
            $this->execute("DELETE FROM dominion_structures WHERE structure_id = {$stable['id']}");
            $this->execute("DELETE FROM structure_levels WHERE structure_id = {$stable['id']}");
            $this->execute("DELETE FROM structures WHERE id = {$stable['id']}");
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        // No logical reason to revert to a redundant system.
    }
}