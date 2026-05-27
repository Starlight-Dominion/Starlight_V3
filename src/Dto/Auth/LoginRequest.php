<?php

declare(strict_types=1);

namespace sdo\Dto\Auth;

use sdo\Infrastructure\BaseDto;

class LoginRequest extends BaseDto
{
    public string $username;
    public string $password;

    protected function rules(): array
    {
        return [
            'username' => 'required',
            'password' => 'required',
        ];
    }
}
