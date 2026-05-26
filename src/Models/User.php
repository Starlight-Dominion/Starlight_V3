<?php
declare(strict_types=1);

namespace sdo\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    protected $table = 'users';
    
    public $timestamps = true;

    protected $hidden = ['password'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_bot' => 'boolean',
        'is_admin' => 'boolean',
        'stasis_until' => 'datetime',
        'handle_last_changed' => 'datetime'
    ];

    public function dominion(): HasOne
    {
        return $this->hasOne(Dominion::class, 'user_id');
    }

    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
    }
}