<?php
declare(strict_types=1);
use Phinx\Seed\AbstractSeed;

class RaceSeeder extends AbstractSeed {
    public function run(): void {
        $data = [
            ['name' => 'Human'],
            ['name' => 'Cyborg'],
            ['name' => 'Shade'],
            ['name' => 'Synthera']
        ];
        $this->table('races')->insert($data)->saveData();
    }
}