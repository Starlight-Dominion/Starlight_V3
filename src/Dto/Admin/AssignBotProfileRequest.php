<?php
declare(strict_types=1);

namespace sdo\Dto\Admin;

use sdo\Infrastructure\BaseDto;

class AssignBotProfileRequest extends BaseDto
{
    public int $user_id;
    public ?int $bot_profile_id;

    protected function rules(): array
    {
        return [
            'user_id' => 'required|integer',
            'bot_profile_id' => 'nullable|integer',
        ];
    }
}
