<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddQueryPerformanceIndexes extends AbstractMigration
{
    private const BATTLE_LOGS_INDEX = 'idx_battle_logs_attacker_defender_time';
    private const BANK_TRANSACTIONS_INDEX = 'idx_bank_transactions_kingdom_created';
    private const GAME_LOGS_INDEX = 'idx_game_logs_dominion_created';
    private const API_LOGS_INDEX = 'idx_api_logs_api_key_created';

    public function up(): void
    {
        if ($this->hasTable('battle_logs')) {
            $battleLogs = $this->table('battle_logs');
            if (!$battleLogs->hasIndex(['attacker_id', 'defender_id', 'battle_time'])) {
                $battleLogs->addIndex(['attacker_id', 'defender_id', 'battle_time'], ['name' => self::BATTLE_LOGS_INDEX]);
            }
            $battleLogs->update();
        }

        if ($this->hasTable('bank_transactions')) {
            $bankTransactions = $this->table('bank_transactions');
            if (!$bankTransactions->hasIndex(['kingdom_id', 'created_at'])) {
                $bankTransactions->addIndex(['kingdom_id', 'created_at'], ['name' => self::BANK_TRANSACTIONS_INDEX]);
            }
            $bankTransactions->update();
        }

        if ($this->hasTable('game_logs')) {
            $gameLogs = $this->table('game_logs');
            if (!$gameLogs->hasIndex(['dominion_id', 'created_at'])) {
                $gameLogs->addIndex(['dominion_id', 'created_at'], ['name' => self::GAME_LOGS_INDEX]);
            }
            $gameLogs->update();
        }

        if ($this->hasTable('api_logs')) {
            $apiLogs = $this->table('api_logs');
            if (!$apiLogs->hasIndex(['api_key_id', 'created_at'])) {
                $apiLogs->addIndex(['api_key_id', 'created_at'], ['name' => self::API_LOGS_INDEX]);
            }
            $apiLogs->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('api_logs')) {
            $this->execute('ALTER TABLE `api_logs` DROP INDEX IF EXISTS `' . self::API_LOGS_INDEX . '`');
        }

        if ($this->hasTable('game_logs')) {
            $this->execute('ALTER TABLE `game_logs` DROP INDEX IF EXISTS `' . self::GAME_LOGS_INDEX . '`');
        }

        if ($this->hasTable('bank_transactions')) {
            $this->execute('ALTER TABLE `bank_transactions` DROP INDEX IF EXISTS `' . self::BANK_TRANSACTIONS_INDEX . '`');
        }

        if ($this->hasTable('battle_logs')) {
            $this->execute('ALTER TABLE `battle_logs` DROP INDEX IF EXISTS `' . self::BATTLE_LOGS_INDEX . '`');
        }
    }
}
