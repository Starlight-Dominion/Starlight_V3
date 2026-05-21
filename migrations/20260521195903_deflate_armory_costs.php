<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DeflateArmoryCosts extends AbstractMigration
{
    public function up(): void
    {
        // Reduce costs by factor of 10,000. Floor at 1 credit.
        $this->execute("UPDATE armory_items SET cost = GREATEST(1, FLOOR(cost / 10000))");
    }

    public function down(): void
    {
        // Reverse by multiplying back (approximate reversal)
        $this->execute("UPDATE armory_items SET cost = cost * 10000");
    }
}
