<?php

declare(strict_types=1);

namespace sdo\Infrastructure\Api;

class ManpowerResource extends BaseResource
{
    public function toArray(): array
    {
        return [
            'slug' => $this->resource->unit->slug,
            'name' => $this->resource->unit->name,
            'quantity' => (int)$this->resource->total_quantity,
            'stabled' => (int)$this->resource->stabled_quantity,
        ];
    }
}
