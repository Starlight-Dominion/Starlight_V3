<?php
declare(strict_types=1);

namespace sdo\Models;

class User extends Model
{
    protected $table = 'users';

    protected $hidden = ['password'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_bot' => 'boolean',
        'is_admin' => 'boolean',
    ];

    /**
     * Relationship to the core game state.
     * Theming calls it a "Dominion", but the structural architecture remains "Kingdom" 
     * to ensure seamless integration with the core game loop.
     */
    public function kingdom()
    {
        return $this->hasOne(Kingdom::class, 'user_id');
    }

    /**
     * Mutator to automatically hash passwords before they are stored in the database.
     */
    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
    }
}