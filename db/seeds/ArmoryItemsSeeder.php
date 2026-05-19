<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class ArmoryItemsSeeder extends AbstractSeed
{
    public function run(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0');
        $this->table('kingdom_armory_items')->truncate();
        $this->table('hidden_armory_items')->truncate();
        $this->table('armory_items')->truncate();
        $this->table('armory_categories')->truncate();
        $this->table('armory_unit_types')->truncate();
        $this->execute('SET FOREIGN_KEY_CHECKS=1');
        
        $config = require __DIR__ . '/../../config/armory.php';
        
        foreach ($config['loadouts'] as $unitSlug => $loadout) {
            // 1. Insert Unit Type
            $this->table('armory_unit_types')->insert([
                [
                    'slug' => $unitSlug,
                    'name' => ucfirst($unitSlug),
                    'title' => $loadout['title']
                ]
            ])->saveData();

            $unitType = $this->fetchRow("SELECT id FROM armory_unit_types WHERE slug = '{$unitSlug}'");
            $unitTypeId = $unitType['id'];

            foreach ($loadout['categories'] as $catSlug => $category) {
                // 2. Insert Category
                $this->table('armory_categories')->insert([
                    [
                        'unit_type_id' => $unitTypeId,
                        'slug' => $catSlug,
                        'name' => $category['title'],
                        'slots' => $category['slots'] ?? 1
                    ]
                ])->saveData();

                $dbCategory = $this->fetchRow("SELECT id FROM armory_categories WHERE slug = '{$catSlug}'");
                $categoryId = $dbCategory['id'];

                foreach ($category['items'] as $itemSlug => $details) {
                    // 3. Insert Item
                    $this->table('armory_items')->insert([
                        [
                            'category_id' => $categoryId,
                            'slug' => $itemSlug,
                            'name' => $details['name'],
                            'cost' => $details['cost'],
                            'attack_bonus' => $details['attack'] ?? 0,
                            'defense_bonus' => $details['defense'] ?? 0,
                            'unit_type' => $unitSlug, 
                            'requirement_slug' => $details['requires'] ?? null,
                            'armory_level_req' => $details['armory_level_req'] ?? 0
                        ]
                    ])->saveData();
                }
            }
        }
    }
}
