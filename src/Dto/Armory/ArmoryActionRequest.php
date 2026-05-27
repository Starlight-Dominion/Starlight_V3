<?php

declare(strict_types=1);

namespace sdo\Dto\Armory;

use sdo\Infrastructure\BaseDto;

class ArmoryActionRequest extends BaseDto
{
    public int $item_id;
    public int $quantity;

    protected function rules(): array
    {
        return [
            'item_id' => 'required|integer',
            'quantity' => 'required|integer|min:1'
        ];
    }
}
