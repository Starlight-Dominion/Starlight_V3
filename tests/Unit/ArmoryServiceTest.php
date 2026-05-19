<?php

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use sdo\Services\ArmoryService;

class ArmoryServiceTest extends TestCase
{
    private PDO $db;
    private ArmoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec("CREATE TABLE kingdoms (
            id INTEGER PRIMARY KEY,
            foundation_level INTEGER DEFAULT 1,
            gold INTEGER DEFAULT 0
        )");

        $this->db->exec("CREATE TABLE armory_items (
            id INTEGER PRIMARY KEY,
            name TEXT,
            type TEXT,
            unit_type TEXT,
            tier INTEGER,
            cost INTEGER,
            attack_bonus INTEGER DEFAULT 0,
            defense_bonus INTEGER DEFAULT 0
        )");

        $this->db->exec("CREATE TABLE kingdom_armory_items (
            id INTEGER PRIMARY KEY,
            kingdom_id INTEGER,
            item_id INTEGER,
            quantity INTEGER DEFAULT 0,
            UNIQUE(kingdom_id, item_id)
        )");

        // Insert test armory items
        $items = [
            "(1, 'Iron Helmet', 'head', 'guards', 1, 100, 5, 0)",
            "(2, 'Iron Sword', 'primary', 'guards', 1, 150, 10, 0)",
            "(3, 'Steel Armor', 'body', 'soldiers', 2, 300, 0, 15)",
            "(4, 'Mithril Dagger', 'secondary', 'spies', 3, 500, 20, 0)",
        ];

        foreach ($items as $item) {
            $this->db->exec("INSERT INTO armory_items VALUES {$item}");
        }

        $this->service = new ArmoryService($this->db);
    }

    public function testGetArmoryData(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, foundation_level) VALUES (1, 1)");

        $result = $this->service->getArmoryData(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('unlocked_tiers', $result);
        $this->assertArrayHasKey('foundation_level', $result);
    }

    public function testGetArmoryDataFiltersByFoundation(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, foundation_level) VALUES (1, 1)"); // Only tier 1 unlocked

        $result = $this->service->getArmoryData(1);

        // Should only have tier 1 items
        foreach ($result['items'] as $unitType => $types) {
            foreach ($types as $type => $items) {
                foreach ($items as $item) {
                    $this->assertEquals(1, $item['tier']);
                }
            }
        }
    }

    public function testGetArmoryDataHigherFoundation(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, foundation_level) VALUES (1, 2)"); // Tier 2 unlocked

        $result = $this->service->getArmoryData(1);

        $this->assertArrayHasKey('unlocked_tiers', $result);
        $this->assertArrayHasKey(1, $result['unlocked_tiers']);
        $this->assertArrayHasKey(2, $result['unlocked_tiers']);
    }

    public function testBuyItem(): void
    {
        // Insert kingdom with gold and armory item (use id 10 to avoid conflict with setUp items)
        $this->db->exec("INSERT INTO kingdoms (id, foundation_level, gold) VALUES (1, 1, 1000)");
        $this->db->exec("INSERT INTO armory_items (id, name, type, unit_type, tier, cost) VALUES (10, 'Iron Sword', 'primary', 'guards', 1, 100)");

        $result = $this->service->buyItem(1, 10, 1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Successfully purchased', $result['message']);
    }

    public function testBuyItemInsufficientGold(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, foundation_level, gold) VALUES (1, 1, 50)");
        $this->db->exec("INSERT INTO armory_items (id, name, type, unit_type, tier, cost) VALUES (10, 'Iron Sword', 'primary', 'guards', 1, 100)");

        $result = $this->service->buyItem(1, 10, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient gold', $result['message']);
    }

    public function testSellItem(): void
    {
        // Insert kingdom, armory item, and kingdom_armory_item
        $this->db->exec("INSERT INTO kingdoms (id, foundation_level, gold) VALUES (1, 1, 1000)");
        $this->db->exec("INSERT INTO armory_items (id, name, type, unit_type, tier, cost) VALUES (10, 'Iron Sword', 'primary', 'guards', 1, 100)");
        $this->db->exec("INSERT INTO kingdom_armory_items (kingdom_id, item_id, quantity) VALUES (1, 10, 3)");

        $result = $this->service->sellItem(1, 10, 1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Successfully sold', $result['message']);
    }

    public function testSellItemNotFound(): void
    {
        $this->db->exec("INSERT INTO kingdoms (id, foundation_level) VALUES (1, 1)");

        $result = $this->service->sellItem(1, 999, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testGetArmoryDataNoItems(): void
    {
        $this->db->exec("DELETE FROM armory_items");
        $this->db->exec("INSERT INTO kingdoms (id, foundation_level) VALUES (1, 1)");

        $result = $this->service->getArmoryData(1);

        $this->assertEmpty($result['items']);
    }

    public function testGetArmoryDataKingdomNotFound(): void
    {
        $result = $this->service->getArmoryData(999);

        // The method returns false for non-existent kingdom
        $this->assertFalse($result['foundation_level'] ?? false);
    }
}
