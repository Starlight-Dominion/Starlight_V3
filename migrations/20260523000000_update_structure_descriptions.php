<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateStructureDescriptions extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("UPDATE structures SET description = 'The Capital of your Dominion, the last line of defense.' WHERE slug = 'foundation'");
        $this->execute("UPDATE structures SET description = 'The Economic center of your dominion.' WHERE slug = 'economy'");
        $this->execute("UPDATE structures SET description = 'Advanced military research and development facility for tactical equipment.' WHERE slug = 'armory'");
    }

    public function down(): void
    {
        $this->execute("UPDATE structures SET description = 'Core integrity.' WHERE slug = 'foundation'");
        $this->execute("UPDATE structures SET description = 'Credit generation and trade networks.' WHERE slug = 'economy'");
        $this->execute("UPDATE structures SET description = 'Advanced tactical gear unlocking.' WHERE slug = 'armory'");
    }
}
