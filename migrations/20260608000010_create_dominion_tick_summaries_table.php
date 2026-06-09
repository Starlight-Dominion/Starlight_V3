<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDominionTickSummariesTable extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('dominion_tick_summaries')) {
            $this->table('dominion_tick_summaries', ['id' => false, 'primary_key' => ['dominion_id']])
                ->addColumn('dominion_id', 'integer', ['signed' => false])
                ->addColumn('total_economy_buff', 'integer', ['default' => 0])
                ->addColumn('total_citizen_buff', 'integer', ['default' => 0])
                ->addColumn('total_unit_production', 'biginteger', ['default' => 0])
                ->addForeignKey('dominion_id', 'dominions', 'id', ['delete' => 'CASCADE'])
                ->create();
        }

        $this->execute(
            'INSERT INTO dominion_tick_summaries (dominion_id, total_economy_buff, total_citizen_buff, total_unit_production)
             SELECT d.id,
                    COALESCE(sa.total_economy_buff, 0) as total_economy_buff,
                    COALESCE(sa.total_citizen_buff, 0) as total_citizen_buff,
                    COALESCE(ma.total_unit_production, 0) as total_unit_production
             FROM dominions d
             LEFT JOIN (
                SELECT ds.dominion_id,
                       COALESCE(SUM(sl.buff_economy), 0) as total_economy_buff,
                       COALESCE(SUM(sl.buff_citizens_per_tick), 0) as total_citizen_buff
                FROM dominion_structures ds
                JOIN structure_levels sl
                  ON ds.structure_id = sl.structure_id
                 AND ds.level = sl.level
                GROUP BY ds.dominion_id
             ) sa ON sa.dominion_id = d.id
             LEFT JOIN (
                SELECT dm.dominion_id,
                       COALESCE(SUM(dm.total_quantity * u.production_credits), 0) as total_unit_production
                FROM dominion_manpower dm
                JOIN units u ON dm.unit_id = u.id
                GROUP BY dm.dominion_id
             ) ma ON ma.dominion_id = d.id
             ON DUPLICATE KEY UPDATE
                total_economy_buff = VALUES(total_economy_buff),
                total_citizen_buff = VALUES(total_citizen_buff),
                total_unit_production = VALUES(total_unit_production)'
        );
    }

    public function down(): void
    {
        if ($this->hasTable('dominion_tick_summaries')) {
            $this->table('dominion_tick_summaries')->drop()->save();
        }
    }
}
