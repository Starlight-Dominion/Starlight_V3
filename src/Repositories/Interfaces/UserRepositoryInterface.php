<?php

declare(strict_types=1);

namespace sdo\Repositories\Interfaces;

use sdo\Models\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByUsername(string $username): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(int $id, array $data): bool;
}