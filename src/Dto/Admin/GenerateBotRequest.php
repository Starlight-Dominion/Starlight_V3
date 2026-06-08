<?php
declare(strict_types=1);

namespace sdo\Dto\Admin;

use sdo\Infrastructure\BaseDto;

class GenerateBotRequest extends BaseDto
{
    public string $name;
    public ?int $bot_profile_id;
    public int $race_id;
    public int $starting_credits;
    public int $starting_citizens;
    public int $starting_level;

    protected function rules(): array
    {
        return [
            'name' => 'required|min:3|max:255',
            'bot_profile_id' => 'nullable|integer',
            'race_id' => 'required|integer',
            'starting_credits' => 'required|integer|min:0',
            'starting_citizens' => 'required|integer|min:10',
            'starting_level' => 'required|integer|min:1',
        ];
    }
}
