<?php

declare(strict_types=1);

namespace sdo\Infrastructure\Api;

class StructureResource extends BaseResource
{
    public function toArray(): array
    {
        return [
            'slug' => $this->resource->structure->slug,
            'name' => $this->resource->structure->name,
            'current_level' => (int)$this->resource->level,
            'buffs' => [
                'economy' => (int)($this->resource->levelData->buff_economy ?? 0),
                'offense' => (int)($this->resource->levelData->buff_offense ?? 0),
                'defense' => (int)($this->resource->levelData->buff_defense ?? 0),
                'capacity' => (int)($this->resource->levelData->capacity ?? 0),
            ]
        ];
    }
}
