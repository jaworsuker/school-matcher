<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use App\User\Domain\Exception\EmailAlreadyRegisteredException;
use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(int $id): ?User
    {
        return $this->entityManager->find(User::class, $id);
    }

    public function existsByEmail(string $email): bool
    {
        return null !== $this->entityManager->getRepository(User::class)->findOneBy(['email' => User::normalizeEmail($email)]);
    }

    public function save(User $user): void
    {
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            // Dwie równoległe rejestracje tego samego adresu - sprawdzenie w handlerze nie wystarczy.
            throw EmailAlreadyRegisteredException::forEmail($user->getEmail());
        }
    }
}
