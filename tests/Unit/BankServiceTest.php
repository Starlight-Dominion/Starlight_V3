<?php

namespace Tests\Unit;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use sdo\Services\BankService;
use Exception;

class BankServiceTest extends TestCase
{
    private $pdoMock;
    private $stmtMock;
    private BankService $bankService;

    protected function setUp(): void
    {
        // Create mocks for PDO and PDOStatement
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        
        // Instantiate the service with the mock database connection
        $this->bankService = new BankService($this->pdoMock);
    }

    public function testSuccessfulDeposit()
    {
        $kingdomData = [
            'id' => 1,
            'gold' => 10000,
            'gold_in_bank' => 0,
            'deposits_today' => 0,
            'last_deposit_recharge' => null
        ];
        
        // Mock the database fetching the kingdom
        $this->stmtMock->method('fetch')->willReturn($kingdomData);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->stmtMock->expects($this->atLeastOnce())->method('execute');
        $this->pdoMock->expects($this->once())->method('commit');

        $result = $this->bankService->deposit(1, 5000);
        
        $this->assertTrue($result['success']);
    }

    public function testSuccessfulWithdrawal()
    {
        $kingdomData = ['gold_in_bank' => 5000];

        $this->stmtMock->method('fetchColumn')->willReturn($kingdomData['gold_in_bank']);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->stmtMock->expects($this->atLeastOnce())->method('execute');
        $this->pdoMock->expects($this->once())->method('commit');

        $result = $this->bankService->withdraw(1, 2000);

        $this->assertTrue($result['success']);
    }

    public function testDepositFailsWhenOverEightyPercent()
    {
        $kingdomData = ['id' => 1, 'gold' => 1000, 'deposits_today' => 0, 'last_deposit_recharge' => null];
        
        $this->stmtMock->method('fetch')->willReturn($kingdomData);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->bankService->deposit(1, 801); // 80.1%

        $this->assertFalse($result['success']);
        $this->assertEquals("You cannot deposit more than 80% of your on-hand gold.", $result['message']);
    }

    public function testDepositFailsWhenDepositLimitExceeded()
    {
        $kingdomData = ['id' => 1, 'gold' => 10000, 'deposits_today' => 4, 'last_deposit_recharge' => date('Y-m-d H:i:s')];
        
        $this->stmtMock->method('fetch')->willReturn($kingdomData);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->bankService->deposit(1, 1000);

        $this->assertFalse($result['success']);
        $this->assertEquals("You have no available deposits. They recharge 6 hours after use.", $result['message']);
    }
    
    public function testWithdrawFailsOnInsufficientFunds()
    {
        $this->stmtMock->method('fetchColumn')->willReturn(500); // 500 gold in bank
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('rollBack');

        $result = $this->bankService->withdraw(1, 501); // Trying to withdraw 501

        $this->assertFalse($result['success']);
        $this->assertEquals("Insufficient funds in bank.", $result['message']);
    }
}
