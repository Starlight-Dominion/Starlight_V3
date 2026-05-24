<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Services\BankService;
use sdo\Services\LogService;
use sdo\Models\Dominion;
use sdo\Models\BankTransaction;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class BankServiceTest extends TestCase
{
    private $logServiceMock;
    private BankService $bankService;

    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->createTables();

        $this->logServiceMock = $this->createMock(LogService::class);
        $this->bankService = new BankService($this->logServiceMock);
    }

    private function createTables(): void
    {
        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->default(1);
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(10000);
            $table->bigInteger('credits_banked')->default(0);
            $table->integer('deposits_today')->default(0);
            $table->integer('armory_level')->default(0);
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(0);
            $table->integer('housing_level')->default(0);
            $table->integer('mercenary_market_level')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->dateTime('last_untrained')->nullable();
            $table->dateTime('last_deposit_timestamp')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('bank_transactions', function ($table) {
            $table->increments('id');
            $table->integer('kingdom_id'); // Legacy name in model probably, check model
            $table->string('transaction_type');
            $table->bigInteger('amount');
            $table->timestamps();
        });
    }

    public function testSuccessfulDeposit(): void
    {
        $dom = Dominion::create(['name' => 'BankTest', 'credits' => 10000]);

        $this->logServiceMock->expects($this->once())->method('log');

        $result = $this->bankService->deposit($dom->id, 5000);
        
        $this->assertTrue($result['success']);
        
        $updated = Dominion::find($dom->id);
        $this->assertEquals(5000, $updated->credits);
        $this->assertEquals(5000, $updated->credits_banked);
        $this->assertEquals(1, $updated->deposits_today);
    }

    public function testSuccessfulWithdrawal(): void
    {
        $dom = Dominion::create(['name' => 'WithdrawTest', 'credits' => 0, 'credits_banked' => 5000]);

        $this->logServiceMock->expects($this->once())->method('log');

        $result = $this->bankService->withdraw($dom->id, 2000);

        $this->assertTrue($result['success']);
        
        $updated = Dominion::find($dom->id);
        $this->assertEquals(2000, $updated->credits);
        $this->assertEquals(3000, $updated->credits_banked);
    }

    public function testDepositFailsWhenOverEightyPercent(): void
    {
        $dom = Dominion::create(['name' => 'EightyPercentTest', 'credits' => 1000]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Security Protocol: Cannot deposit more than 80% of liquid assets.");

        $this->bankService->deposit($dom->id, 801);
    }

    public function testDepositFailsWhenDepositLimitExceeded(): void
    {
        $dom = Dominion::create([
            'name' => 'LimitTest', 
            'credits' => 10000, 
            'deposits_today' => 6, 
            'last_deposit_timestamp' => new \DateTime()
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Bank Vault Locked: Daily deposit frequency exceeded. Cooldown active.");

        $this->bankService->deposit($dom->id, 1000);
    }
    
    public function testWithdrawFailsOnInsufficientFunds(): void
    {
        $dom = Dominion::create(['name' => 'InsufficientTest', 'credits_banked' => 500]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Insufficient vault reserves.");

        $this->bankService->withdraw($dom->id, 501);
    }
}
