<?php

use PHPUnit\Framework\TestCase;
use sdo\Services\AdvisorService;

class AdvisorServiceTest extends TestCase
{
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

    public function testGetContextualAdviceFromKingdom_Early()
    {
        $advisor = new AdvisorService();
        $kingdom = ['xp' => 0];
        $advice = $advisor->getContextualAdviceFromKingdom($kingdom);
        $this->assertEquals("You are just starting to grow. Focus on steady resource collection and expand housing to support population growth.", $advice);
    }

    public function testGetContextualAdviceFromKingdom_Mid()
    {
        $advisor = new AdvisorService();
        $kingdom = ['xp' => 150]; // level 2, progress ~16.7%
        $advice = $advisor->getContextualAdviceFromKingdom($kingdom);
        // With <20% progress, early message is returned
        $this->assertEquals("You are just starting to grow. Focus on steady resource collection and expand housing to support population growth.", $advice);
    }

    public function testGetContextualAdviceFromKingdom_Null()
    {
        $advisor = new AdvisorService();
        $advice = $advisor->getContextualAdviceFromKingdom(null);
        $this->assertIsString($advice);
    }
}
