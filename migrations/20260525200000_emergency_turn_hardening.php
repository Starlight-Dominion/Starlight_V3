<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EmergencyTurnHardening extends AbstractMigration
{
    public function up(): void
    {
        // 1. Force a manual increment to break the 200 wall and verify DB mobility
        $this->execute("UPDATE dominions SET turns = turns + 10 WHERE turns >= 190;");

        // 2. Explicitly remove any theoretical constraints (MySQL/MariaDB 8.0+)
        // In MariaDB 10.2+, check constraints might exist.
        // We'll try to drop a theoretical constraint just in case it was manually added.
        try {
            $this->execute("ALTER TABLE dominions DROP CONSTRAINT IF EXISTS turns_cap;");
        } catch (\Exception $e) {
            // Ignore if not supported or not present
        }
    }

    public function down(): void
    {
        // No reverse needed for emergency hardening
    }
}
