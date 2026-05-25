<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddQueryPerformanceIndexes extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('battle_logs')) {
            $battleLogs = $this->table('battle_logs');
            if (!$battleLogs->hasIndex(['attacker_id', 'defender_id', 'battle_time'])) {
                $battleLogs->addIndex(['attacker_id', 'defender_id', 'battle_time']);
            }
            $battleLogs->update();
        }

        if ($this->hasTable('bank_transactions')) {
            $bankTransactions = $this->table('bank_transactions');
            if (!$bankTransactions->hasIndex(['kingdom_id', 'created_at'])) {
                $bankTransactions->addIndex(['kingdom_id', 'created_at']);
            }
            $bankTransactions->update();
        }

        if ($this->hasTable('game_logs')) {
            $gameLogs = $this->table('game_logs');
            if (!$gameLogs->hasIndex(['dominion_id', 'created_at'])) {
                $gameLogs->addIndex(['dominion_id', 'created_at']);
            }
            $gameLogs->update();
        }

        if ($this->hasTable('api_logs')) {
            $apiLogs = $this->table('api_logs');
            if (!$apiLogs->hasIndex(['api_key_id', 'created_at'])) {
                $apiLogs->addIndex(['api_key_id', 'created_at']);
            }
            $apiLogs->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('api_logs')) {
            $apiLogs = $this->table('api_logs');
            if ($apiLogs->hasIndex(['api_key_id', 'created_at'])) {
                $apiLogs->removeIndex(['api_key_id', 'created_at']);
            }
            $apiLogs->update();
        }

        if ($this->hasTable('game_logs')) {
            $gameLogs = $this->table('game_logs');
            if ($gameLogs->hasIndex(['dominion_id', 'created_at'])) {
                $gameLogs->removeIndex(['dominion_id', 'created_at']);
            }
            $gameLogs->update();
        }

        if ($this->hasTable('bank_transactions')) {
            $bankTransactions = $this->table('bank_transactions');
            if ($bankTransactions->hasIndex(['kingdom_id', 'created_at'])) {
                $bankTransactions->removeIndex(['kingdom_id', 'created_at']);
            }
            $bankTransactions->update();
        }

        if ($this->hasTable('battle_logs')) {
            $battleLogs = $this->table('battle_logs');
            if ($battleLogs->hasIndex(['attacker_id', 'defender_id', 'battle_time'])) {
                $battleLogs->removeIndex(['attacker_id', 'defender_id', 'battle_time']);
            }
            $battleLogs->update();
        }
    }
}
