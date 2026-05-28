<?php

declare(strict_types=1);

namespace sdo\Dto\Combat;

use sdo\Infrastructure\BaseDto;

class AttackRequest extends BaseDto
{
    public int $target_id;
    public int $turns;

    protected function rules(): array
    {
        return [
            'target_id' => 'required|integer',
            'turns' => 'required|integer|min:1'
        ];
    }
}
