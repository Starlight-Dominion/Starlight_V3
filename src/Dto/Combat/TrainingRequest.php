<?php

declare(strict_types=1);

namespace sdo\Dto\Combat;

use sdo\Infrastructure\BaseDto;

class TrainingRequest extends BaseDto
{
    public string $unit_type;
    public int $quantity;

    protected function rules(): array
    {
        return [
            'unit_type' => 'required|string',
            'quantity' => 'required|integer|min:1'
        ];
    }
}
