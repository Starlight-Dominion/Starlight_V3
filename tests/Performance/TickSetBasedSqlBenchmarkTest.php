<?php

declare(strict_types=1);

namespace Tests\Performance;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;
use sdo\Repositories\Eloquent\EloquentTickRepository;
use sdo\Services\TickService;

class TickSetBasedSqlBenchmarkTest extends TestCase
{
    private const USER_COUNT = 100000;
    private const BASE_CREDITS = 100;
    private const BASE_CITIZENS = 50;
    private const BASE_TURNS = 4;
    private const TICK_TIME = '2026-06-08 12:00:00';

    /** @var array<int, int> */
    private const ECONOMY_BUFFS = [
        1 => -30,
        2 => -10,
        3 => 0,
        4 => 15,
        5 => 40,
    ];

    /** @var array<int, int> */
    private const CITIZEN_BUFFS = [
        1 => -20,
        2 => 0,
        3 => 10,
        4 => 25,
        5 => 60,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::connection()->disableQueryLog();

        Capsule::schema()->create('users', function ($table): void {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
        });

        Capsule::schema()->create('dominions', function ($table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name')->unique();
            $table->bigInteger('credits')->default(0);
            $table->integer('citizens')->default(0);
            $table->integer('turns')->default(0);
            $table->datetime('last_tick')->nullable();
        });

        Capsule::schema()->create('structures', function ($table): void {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
        });

        Capsule::schema()->create('structure_levels', function ($table): void {
            $table->integer('structure_id')->unsigned();
            $table->integer('level');
            $table->integer('buff_economy')->default(0);
            $table->integer('buff_citizens_per_tick')->default(0);
            $table->primary(['structure_id', 'level']);
        });

        Capsule::schema()->create('dominion_structures', function ($table): void {
            $table->integer('dominion_id')->unsigned();
            $table->integer('structure_id')->unsigned();
            $table->integer('level')->default(0);
            $table->primary(['dominion_id', 'structure_id']);
        });

        Capsule::schema()->create('units', function ($table): void {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->integer('production_credits')->default(0);
        });

        Capsule::schema()->create('dominion_manpower', function ($table): void {
            $table->integer('dominion_id')->unsigned();
            $table->integer('unit_id')->unsigned();
            $table->integer('total_quantity')->default(0);
            $table->primary(['dominion_id', 'unit_id']);
        });

        Capsule::schema()->create('dominion_tick_summaries', function ($table): void {
            $table->integer('dominion_id')->unsigned()->primary();
            $table->integer('total_economy_buff')->default(0);
            $table->integer('total_citizen_buff')->default(0);
            $table->bigInteger('total_unit_production')->default(0);
        });
    }

    public function testSetBasedTickSqlHandlesOneHundredThousandDominionsExactly(): void
    {
        $this->requireHeavyBenchmarkFlag();

        $this->seedReferenceData();
        $this->seedDominionsWithStats();

        $repository = new EloquentTickRepository();
        $dominionIds = range(1, self::USER_COUNT);

        $startedAt = microtime(true);
        $metrics = $repository->applyTickResultsSetBased(
            $dominionIds,
            self::BASE_CREDITS,
            self::BASE_CITIZENS,
            self::BASE_TURNS,
            self::TICK_TIME
        );
        $elapsedMs = (microtime(true) - $startedAt) * 1000;

        $this->assertExactFinalStateAndMetrics($metrics);

        fwrite(STDOUT, sprintf(
            "\nTick SQL benchmark: updated %d dominions in %.2f ms\n",
            self::USER_COUNT,
            $elapsedMs
        ));

        $this->assertGreaterThan(0.0, $elapsedMs);
    }

    public function testLegacyTickLoopHandlesOneHundredThousandDominionsExactly(): void
    {
        $this->requireHeavyBenchmarkFlag();

        $this->seedReferenceData();
        $this->seedDominionsWithStats();

        $repository = new EloquentTickRepository();
        $dominionIds = range(1, self::USER_COUNT);

        $startedAt = microtime(true);
        $metrics = [
            'credits' => 0,
            'citizens' => 0,
            'turns' => 0,
        ];

        foreach (array_chunk($dominionIds, TickService::BATCH_SIZE) as $chunkIds) {
            $dominions = $repository->getTickData($chunkIds);

            foreach ($dominions as $dominion) {
                $multiplier = 1 + ((float) ($dominion->total_economy_buff ?? 0) / 100);
                $basePlusUnit = self::BASE_CREDITS + (int) ($dominion->total_unit_production ?? 0);
                $creditsGained = max(0, (int) floor($basePlusUnit * $multiplier));
                $citizensGained = max(0, self::BASE_CITIZENS + (int) ($dominion->total_citizen_buff ?? 0));

                $repository->applyTickResults(
                    (int) $dominion->id,
                    $creditsGained,
                    $citizensGained,
                    self::BASE_TURNS,
                    self::TICK_TIME
                );

                $metrics['credits'] += $creditsGained;
                $metrics['citizens'] += $citizensGained;
                $metrics['turns'] += self::BASE_TURNS;
            }
        }

        $elapsedMs = (microtime(true) - $startedAt) * 1000;

        $this->assertExactFinalStateAndMetrics($metrics);

        fwrite(STDOUT, sprintf(
            "\nLegacy tick benchmark (stream batch size %d): updated %d dominions in %.2f ms\n",
            TickService::BATCH_SIZE,
            self::USER_COUNT,
            $elapsedMs
        ));

        $this->assertGreaterThan(0.0, $elapsedMs);
    }

    private function requireHeavyBenchmarkFlag(): void
    {
        if ((int) getenv('RUN_HEAVY_TICK_BENCHMARK') !== 1) {
            $this->markTestSkipped('Set RUN_HEAVY_TICK_BENCHMARK=1 to run the 100,000-dominion benchmark.');
        }
    }

    private function assertExactFinalStateAndMetrics(array $metrics): void
    {
        $mismatchMessages = [];
        $expectedMetrics = [
            'credits' => 0,
            'citizens' => 0,
            'turns' => 0,
        ];

        Capsule::table('dominions')
            ->select(['id', 'credits', 'citizens', 'turns', 'last_tick'])
            ->orderBy('id')
            ->chunk(5000, function ($rows) use (&$mismatchMessages, &$expectedMetrics): void {
                foreach ($rows as $row) {
                    $expected = $this->expectedFinalStats((int) $row->id);
                    $expectedMetrics['credits'] += $expected['delta_credits'];
                    $expectedMetrics['citizens'] += $expected['delta_citizens'];
                    $expectedMetrics['turns'] += self::BASE_TURNS;

                    if (
                        (int) $row->credits !== $expected['credits'] ||
                        (int) $row->citizens !== $expected['citizens'] ||
                        (int) $row->turns !== $expected['turns'] ||
                        (string) $row->last_tick !== self::TICK_TIME
                    ) {
                        if (count($mismatchMessages) < 15) {
                            $mismatchMessages[] = sprintf(
                                'id=%d expected[c=%d,ci=%d,t=%d,last=%s] actual[c=%d,ci=%d,t=%d,last=%s]',
                                (int) $row->id,
                                $expected['credits'],
                                $expected['citizens'],
                                $expected['turns'],
                                self::TICK_TIME,
                                (int) $row->credits,
                                (int) $row->citizens,
                                (int) $row->turns,
                                (string) $row->last_tick
                            );
                        }
                    }
                }
            });

        $this->assertSame([], $mismatchMessages, implode("\n", $mismatchMessages));
        $this->assertSame($expectedMetrics, [
            'credits' => (int) ($metrics['credits'] ?? 0),
            'citizens' => (int) ($metrics['citizens'] ?? 0),
            'turns' => (int) ($metrics['turns'] ?? 0),
        ]);
    }

    private function seedReferenceData(): void
    {
        Capsule::table('structures')->insert([
            ['id' => 1, 'slug' => 'economy', 'name' => 'Economy'],
            ['id' => 2, 'slug' => 'housing', 'name' => 'Housing'],
        ]);

        $levels = [];
        foreach (range(1, 5) as $level) {
            $levels[] = [
                'structure_id' => 1,
                'level' => $level,
                'buff_economy' => self::ECONOMY_BUFFS[$level],
                'buff_citizens_per_tick' => 0,
            ];
            $levels[] = [
                'structure_id' => 2,
                'level' => $level,
                'buff_economy' => 0,
                'buff_citizens_per_tick' => self::CITIZEN_BUFFS[$level],
            ];
        }

        Capsule::table('structure_levels')->insert($levels);

        Capsule::table('units')->insert([
            ['id' => 1, 'slug' => 'u1', 'production_credits' => 1],
            ['id' => 2, 'slug' => 'u2', 'production_credits' => 3],
            ['id' => 3, 'slug' => 'u3', 'production_credits' => 7],
        ]);
    }

    private function seedDominionsWithStats(): void
    {
        $batchSize = 1000;

        for ($start = 1; $start <= self::USER_COUNT; $start += $batchSize) {
            $end = min(self::USER_COUNT, $start + $batchSize - 1);
            $users = [];
            $dominions = [];
            $dominionStructures = [];
            $manpower = [];
            $tickSummaries = [];

            for ($id = $start; $id <= $end; $id++) {
                $users[] = [
                    'id' => $id,
                    'username' => 'user' . $id,
                    'email' => 'user' . $id . '@example.test',
                    'password' => 'x',
                ];

                $dominions[] = [
                    'id' => $id,
                    'user_id' => $id,
                    'name' => 'Dominion ' . $id,
                    'credits' => $this->initialCredits($id),
                    'citizens' => $this->initialCitizens($id),
                    'turns' => $this->initialTurns($id),
                    'last_tick' => null,
                ];

                $economyLevel = $this->economyLevel($id);
                $economyBuff = $economyLevel > 0 ? self::ECONOMY_BUFFS[$economyLevel] : 0;
                if ($economyLevel > 0) {
                    $dominionStructures[] = [
                        'dominion_id' => $id,
                        'structure_id' => 1,
                        'level' => $economyLevel,
                    ];
                }

                $housingLevel = $this->housingLevel($id);
                $citizenBuff = $housingLevel > 0 ? self::CITIZEN_BUFFS[$housingLevel] : 0;
                if ($housingLevel > 0) {
                    $dominionStructures[] = [
                        'dominion_id' => $id,
                        'structure_id' => 2,
                        'level' => $housingLevel,
                    ];
                }

                $u1 = $id % 21;
                $u2 = ($id * 2) % 17;
                $u3 = ($id * 3) % 13;

                $manpower[] = [
                    'dominion_id' => $id,
                    'unit_id' => 1,
                    'total_quantity' => $u1,
                ];
                $manpower[] = [
                    'dominion_id' => $id,
                    'unit_id' => 2,
                    'total_quantity' => $u2,
                ];
                $manpower[] = [
                    'dominion_id' => $id,
                    'unit_id' => 3,
                    'total_quantity' => $u3,
                ];

                $tickSummaries[] = [
                    'dominion_id' => $id,
                    'total_economy_buff' => $economyBuff,
                    'total_citizen_buff' => $citizenBuff,
                    'total_unit_production' => $u1 + ($u2 * 3) + ($u3 * 7),
                ];
            }

            Capsule::table('users')->insert($users);
            Capsule::table('dominions')->insert($dominions);
            if (!empty($dominionStructures)) {
                Capsule::table('dominion_structures')->insert($dominionStructures);
            }
            Capsule::table('dominion_manpower')->insert($manpower);
            Capsule::table('dominion_tick_summaries')->insert($tickSummaries);
        }
    }

    /** @return array{credits:int,citizens:int,turns:int,delta_credits:int,delta_citizens:int} */
    private function expectedFinalStats(int $id): array
    {
        $unitProduction = ($id % 21) + ((($id * 2) % 17) * 3) + ((($id * 3) % 13) * 7);

        $economyLevel = $this->economyLevel($id);
        $economyBuff = $economyLevel > 0 ? self::ECONOMY_BUFFS[$economyLevel] : 0;
        $multiplier = 1 + ($economyBuff / 100);

        $creditsGain = max(0, (int) floor((self::BASE_CREDITS + $unitProduction) * $multiplier));

        $housingLevel = $this->housingLevel($id);
        $citizenBuff = $housingLevel > 0 ? self::CITIZEN_BUFFS[$housingLevel] : 0;
        $citizensGain = max(0, self::BASE_CITIZENS + $citizenBuff);

        return [
            'credits' => $this->initialCredits($id) + $creditsGain,
            'citizens' => $this->initialCitizens($id) + $citizensGain,
            'turns' => $this->initialTurns($id) + self::BASE_TURNS,
            'delta_credits' => $creditsGain,
            'delta_citizens' => $citizensGain,
        ];
    }

    private function economyLevel(int $id): int
    {
        return $id % 6;
    }

    private function housingLevel(int $id): int
    {
        return ($id + 2) % 6;
    }

    private function initialCredits(int $id): int
    {
        return ($id * 7) % 1000;
    }

    private function initialCitizens(int $id): int
    {
        return ($id * 11) % 500;
    }

    private function initialTurns(int $id): int
    {
        return ($id * 13) % 200;
    }
}
