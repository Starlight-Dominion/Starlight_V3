<?php

declare(strict_types=1);

namespace sdo\Infrastructure\Api;

class DominionResource extends BaseResource
{
    public function toArray(): array
    {
        return [
            'id' => (int)$this->resource->id,
            'name' => $this->resource->name,
            'commander' => $this->resource->user->username ?? 'Unknown',
            'race' => $this->resource->race->name ?? 'Unknown',
            'resources' => [
                'credits' => (int)$this->resource->credits,
                'citizens' => (int)$this->resource->citizens,
                'turns' => (int)$this->resource->turns,
            ],
            'stats' => [
                'xp' => (int)$this->resource->xp,
                'level' => (int)floor(sqrt((int)$this->resource->xp / 100)) + 1,
                'hp' => (int)$this->resource->foundation_hp,
                'max_hp' => (int)$this->resource->foundation_max_hp,
            ],
            'last_tick' => $this->resource->last_tick?->format('Y-m-d H:i:s'),
        ];
    }
}
