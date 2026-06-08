<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MakeRecruitmentIndefinite extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("UPDATE game_settings SET setting_value = '1000' WHERE setting_key = 'recruitment_clicks_per_session'");
        $this->execute("UPDATE game_settings SET setting_value = '999' WHERE setting_key = 'recruitment_sessions_per_day'");
        $this->execute("UPDATE game_settings SET setting_value = '9999' WHERE setting_key = 'recruitment_sessions_per_3days'");
    }

    public function down(): void
    {
        $this->execute("UPDATE game_settings SET setting_value = '150' WHERE setting_key = 'recruitment_clicks_per_session'");
        $this->execute("UPDATE game_settings SET setting_value = '2' WHERE setting_key = 'recruitment_sessions_per_day'");
        $this->execute("UPDATE game_settings SET setting_value = '5' WHERE setting_key = 'recruitment_sessions_per_3days'");
    }
}
