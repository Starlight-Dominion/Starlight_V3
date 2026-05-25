<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDiscordLinkTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('discord_account_links')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('discord_user_id', 'string', ['limit' => 32])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('linked_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('unlinked_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['discord_user_id'], ['unique' => true])
            ->create();

        $this->table('discord_link_challenges')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('code_hash', 'string', ['limit' => 64])
            ->addColumn('expires_at', 'timestamp')
            ->addColumn('consumed_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->addIndex(['code_hash'], ['unique' => true])
            ->addIndex(['expires_at'])
            ->addIndex(['consumed_at'])
            ->create();
    }

    public function down(): void
    {
        $this->table('discord_link_challenges')->drop()->save();
        $this->table('discord_account_links')->drop()->save();
    }
}
