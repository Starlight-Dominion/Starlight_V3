<?php

declare(strict_types=1);

namespace sdo\Dto\Settings;

use sdo\Infrastructure\BaseDto;

class UpdateCipherRequest extends BaseDto
{
    public string $current_password;
    public string $new_password;
    public string $confirm_password;

    protected function rules(): array
    {
        return [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required'
        ];
    }
}
