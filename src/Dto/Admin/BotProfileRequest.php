<?php
declare(strict_types=1);

namespace sdo\Dto\Admin;

use sdo\Infrastructure\BaseDto;

class BotProfileRequest extends BaseDto
{
    public string $name;
    public ?string $description;
    public int $action_frequency_minutes;
    public int $weight_attack;
    public int $weight_build;
    public int $weight_train;
    public int $weight_explore;

    protected function rules(): array
    {
        return [
            'name' => 'required|min:3|max:255',
            'description' => 'nullable|string',
            'action_frequency_minutes' => 'required|integer|min:1',
            'weight_attack' => 'required|integer|min:0|max:100',
            'weight_build' => 'required|integer|min:0|max:100',
            'weight_train' => 'required|integer|min:0|max:100',
            'weight_explore' => 'required|integer|min:0|max:100',
        ];
    }
}
