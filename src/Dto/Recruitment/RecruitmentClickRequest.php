<?php

declare(strict_types=1);

namespace sdo\Dto\Recruitment;

use sdo\Infrastructure\BaseDto;

class RecruitmentClickRequest extends BaseDto
{
    public int $session_id;

    protected function rules(): array
    {
        return [
            'session_id' => 'required|integer'
        ];
    }
}
