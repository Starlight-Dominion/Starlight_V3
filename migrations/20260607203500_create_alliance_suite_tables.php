<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAllianceSuiteTables extends AbstractMigration
{
    public function change(): void
    {
        // 1. Alliances Table
        $alliances = $this->table('alliances');
        $alliances->addColumn('name', 'string', ['limit' => 100])
                  ->addColumn('tag', 'string', ['limit' => 5])
                  ->addColumn('description', 'text', ['null' => true])
                  ->addColumn('avatar_path', 'string', ['limit' => 255, 'null' => true])
                  ->addColumn('leader_id', 'integer', ['signed' => false])
                  ->addColumn('bank_credits', 'biginteger', ['default' => 0])
                  ->addColumn('war_prestige', 'integer', ['default' => 0])
                  ->addColumn('is_joinable', 'boolean', ['default' => true])
                  ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addIndex(['tag'], ['unique' => true])
                  ->create();

        // 2. Alliance Roles Table
        $roles = $this->table('alliance_roles');
        $roles->addColumn('alliance_id', 'integer', ['signed' => false])
              ->addColumn('name', 'string', ['limit' => 50])
              ->addColumn('order', 'integer', ['default' => 0])
              ->addColumn('can_invite', 'boolean', ['default' => false])
              ->addColumn('can_kick', 'boolean', ['default' => false])
              ->addColumn('can_manage_roles', 'boolean', ['default' => false])
              ->addColumn('can_moderate_forum', 'boolean', ['default' => false])
              ->addColumn('can_bank_withdraw', 'boolean', ['default' => false])
              ->addColumn('can_purchase_structures', 'boolean', ['default' => false])
              ->addForeignKey('alliance_id', 'alliances', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();

        // 3. Update Users Table
        $users = $this->table('users');
        $users->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'id'])
              ->addColumn('alliance_role_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'alliance_id'])
              ->addForeignKey('alliance_id', 'alliances', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->addForeignKey('alliance_role_id', 'alliance_roles', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
              ->update();

        // 4. Alliance Applications Table
        $apps = $this->table('alliance_applications');
        $apps->addColumn('alliance_id', 'integer', ['signed' => false])
             ->addColumn('user_id', 'integer', ['signed' => false])
             ->addColumn('message', 'text', ['null' => true])
             ->addColumn('status', 'enum', ['values' => ['pending', 'accepted', 'rejected'], 'default' => 'pending'])
             ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
             ->addForeignKey('alliance_id', 'alliances', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
             ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
             ->create();

        // 5. Alliance Invitations Table
        $invites = $this->table('alliance_invitations');
        $invites->addColumn('alliance_id', 'integer', ['signed' => false])
                ->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('message', 'text', ['null' => true])
                ->addColumn('status', 'enum', ['values' => ['pending', 'accepted', 'rejected', 'expired'], 'default' => 'pending'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('alliance_id', 'alliances', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                ->create();

        // 6. Alliance Bank Logs Table
        $bankLogs = $this->table('alliance_bank_logs');
        $bankLogs->addColumn('alliance_id', 'integer', ['signed' => false])
                 ->addColumn('user_id', 'integer', ['signed' => false])
                 ->addColumn('amount', 'biginteger')
                 ->addColumn('action_type', 'enum', ['values' => ['deposit', 'withdrawal']])
                 ->addColumn('comment', 'string', ['limit' => 255, 'null' => true])
                 ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                 ->addForeignKey('alliance_id', 'alliances', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                 ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                 ->create();

        // 7. Alliance Structures Table
        $structures = $this->table('alliance_structures');
        $structures->addColumn('alliance_id', 'integer', ['signed' => false])
                   ->addColumn('structure_key', 'string', ['limit' => 50])
                   ->addColumn('level', 'integer', ['default' => 0])
                   ->addForeignKey('alliance_id', 'alliances', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                   ->create();

        // 8. Forum Threads Table
        $threads = $this->table('forum_threads');
        $threads->addColumn('alliance_id', 'integer', ['signed' => false])
                ->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('title', 'string', ['limit' => 150])
                ->addColumn('is_stickied', 'boolean', ['default' => false])
                ->addColumn('is_locked', 'boolean', ['default' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('alliance_id', 'alliances', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                ->create();

        // 9. Forum Posts Table
        $posts = $this->table('forum_posts');
        $posts->addColumn('thread_id', 'integer', ['signed' => false])
              ->addColumn('user_id', 'integer', ['signed' => false])
              ->addColumn('content', 'text')
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('thread_id', 'forum_threads', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();
    }
}
