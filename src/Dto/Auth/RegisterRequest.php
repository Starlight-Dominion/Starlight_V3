<?php

declare(strict_types=1);

namespace sdo\Dto\Auth;

use sdo\Infrastructure\BaseDto;

class RegisterRequest extends BaseDto
{
    public string $username;
    public string $email;
    public string $password;
    public string $password_confirmation;
    public string $dominion_name;
    public string $race;

    protected function rules(): array
    {
        return [
            'username' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
            'dominion_name' => 'required|min:3',
            'race' => 'required'
        ];
    }
}
