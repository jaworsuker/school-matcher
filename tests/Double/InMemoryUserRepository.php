<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var list<User> */
    private array $users = [];

    public function findById(int $id): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getId() === $id) {
                return $user;
            }
        }

        return null;
    }

    public function existsByEmail(string $email): bool
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === User::normalizeEmail($email)) {
                return true;
            }
        }

        return false;
    }

    public function save(User $user): void
    {
        if (!\in_array($user, $this->users, true)) {
            $this->users[] = $user;
        }
    }

    /**
     * @return list<User>
     */
    public function all(): array
    {
        return $this->users;
    }
}
