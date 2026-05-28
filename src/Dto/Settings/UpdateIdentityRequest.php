<?php

declare(strict_types=1);

namespace sdo\Dto\Settings;

use sdo\Infrastructure\BaseDto;

class UpdateIdentityRequest extends BaseDto
{
    public string $username;
    public string $email;

    protected function rules(): array
    {
        return [
            'username' => 'required|alpha_num|min:3|max:24',
            'email' => 'required|email'
        ];
    }
}
