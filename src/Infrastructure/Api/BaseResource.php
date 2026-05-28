<?php

declare(strict_types=1);

namespace sdo\Infrastructure\Api;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseResource
{
    public function __construct(protected mixed $resource) {}

    /**
     * Transform the resource into an array.
     */
    abstract public function toArray(): array;

    /**
     * Static helper to initialize the resource.
     */
    public static function make(mixed $resource): static
    {
        return new static($resource);
    }

    /**
     * Handle collections of resources.
     */
    public static function collection(mixed $resources): array
    {
        if ($resources instanceof Collection || is_array($resources)) {
            $data = [];
            foreach ($resources as $res) {
                $data[] = (new static($res))->toArray();
            }
            return $data;
        }

        return [];
    }
}
