<?php

use PHPUnit\Framework\TestCase;
use sdo\Services\AdvisorService;
use sdo\Models\Dominion;
use sdo\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdvisorServiceTest extends TestCase
{
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

        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Capsule::schema()->create('dominions', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->integer('armory_level')->default(0);
            $table->integer('current_mine_tier')->default(1);
            $table->integer('current_mine_level')->default(0);
            $table->integer('housing_level')->default(0);
            $table->integer('mercenary_market_level')->default(0);
            $table->integer('held_citizens')->default(0);
            $table->dateTime('last_untrained')->nullable();
            $table->integer('xp')->default(0);
            $table->timestamps();
        });
    }

    public function testGetContextualAdvice_EarlyProgress()
    {
        $advisor = new AdvisorService();
        $level = 1;
        $xp = 0; // 0% progress
        $advice = $advisor->getContextualAdvice($level, $xp);

        $this->assertIsString($advice);
        $this->assertEquals("You are just starting to grow. Focus on steady resource collection and expand housing to support population growth.", $advice);
    }

    public function testGetContextualAdvice_MidProgress()
    {
        $advisor = new AdvisorService();
        $level = 3;
        $xp = 700; // ~60% progress toward next level
        $advice = $advisor->getContextualAdvice($level, $xp);
        $this->assertEquals("You are approaching a new tier. Prepare for upgrades that unlock higher production and defenses.", $advice);
    }

    public function testGetContextualAdvice_HighProgress()
    {
        $advisor = new AdvisorService();
        $level = 4;
        $xp = 1500; // ~85% progress toward next level
        $advice = $advisor->getContextualAdvice($level, $xp);
        $this->assertEquals("You are near a level-up. Invest in infrastructure and military upgrades to maximize the payoff of the next tick.", $advice);
    }

    public function testGetContextualAdvice_Baseline2()
    {
        $advisor = new AdvisorService();
        $level = 2;
        $xp = 100; // 0% progress for level 2
        $advice = $advisor->getContextualAdvice($level, $xp);
        $this->assertEquals("You are just starting to grow. Focus on steady resource collection and expand housing to support population growth.", $advice);
    }

    public function testGetContextualAdviceFromDominion_Early()
    {
        $advisor = new AdvisorService();
        $dominion = new Dominion(['xp' => 0]);
        $advice = $advisor->getContextualAdviceFromDominion($dominion);
        $this->assertEquals("You are just starting to grow. Focus on steady resource collection and expand housing to support population growth.", $advice);
    }

    public function testGetContextualAdviceFromDominion_Mid()
    {
        $advisor = new AdvisorService();
        $dominion = new Dominion(['xp' => 150]); // level 2, progress ~16.7%
        $advice = $advisor->getContextualAdviceFromDominion($dominion);
        // With <20% progress, early message is returned
        $this->assertEquals("You are just starting to grow. Focus on steady resource collection and expand housing to support population growth.", $advice);
    }

    public function testGetContextualAdviceFromDominion_Null()
    {
        $advisor = new AdvisorService();
        $advice = $advisor->getContextualAdviceFromDominion(null);
        $this->assertIsString($advice);
    }
}
