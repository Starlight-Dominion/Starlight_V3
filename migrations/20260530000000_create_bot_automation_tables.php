<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBotAutomationTables extends AbstractMigration
{
    public function up(): void
    {
        // 1. Create bot_profiles table
        $this->table('bot_profiles', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('action_frequency_minutes', 'integer', ['default' => 60])
            ->addColumn('weight_attack', 'integer', ['default' => 25])
            ->addColumn('weight_build', 'integer', ['default' => 25])
            ->addColumn('weight_train', 'integer', ['default' => 25])
            ->addColumn('weight_explore', 'integer', ['default' => 25])
            ->addTimestamps()
            ->create();

        // 2. Update users table
        $this->table('users')
            ->addColumn('bot_profile_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'is_admin'])
            ->addColumn('last_bot_action_at', 'timestamp', ['null' => true, 'after' => 'bot_profile_id'])
            ->addForeignKey('bot_profile_id', 'bot_profiles', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
            ->save();
    }

    public function down(): void
    {
        $this->table('users')
            ->dropForeignKey('bot_profile_id')
            ->removeColumn('bot_profile_id')
            ->removeColumn('last_bot_action_at')
            ->save();

        $this->table('bot_profiles')->drop()->save();
    }
}
