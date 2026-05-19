<?php

namespace sdo\Models;

class User extends Model
{
    protected $table = 'users';

    protected $hidden = ['password'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_bot' => 'boolean',
    ];

    public function kingdom()
    {
        return $this->hasOne(Kingdom::class, 'user_id');
    }

    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
    }
}
