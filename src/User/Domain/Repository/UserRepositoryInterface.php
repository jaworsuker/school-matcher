<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Model\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function existsByEmail(string $email): bool;

    public function save(User $user): void;
}
