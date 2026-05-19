<?php

declare(strict_types=1);

namespace sdo\Repositories\Eloquent;

use sdo\Models\User;
use sdo\Repositories\Interfaces\UserRepositoryInterface;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        // Explicitly defining creation fields for security
        return User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_bot' => $data['is_bot'] ?? false
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $user = User::find($id);
        if (!$user) return false;
        
        return $user->update($data);
    }
}