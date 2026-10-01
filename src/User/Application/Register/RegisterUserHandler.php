<?php

declare(strict_types=1);

namespace App\User\Application\Register;

use App\School\Domain\Matching\SchoolMatcher;
use App\User\Domain\Exception\EmailAlreadyRegisteredException;
use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private SchoolMatcher $schoolMatcher,
    ) {
    }

    /**
     * @throws EmailAlreadyRegisteredException
     */
    public function handle(RegisterUserCommand $command): User
    {
        if ($this->users->existsByEmail($command->email)) {
            throw EmailAlreadyRegisteredException::forEmail(User::normalizeEmail($command->email));
        }

        $match = $this->schoolMatcher->match($command->schoolName, $command->city);
        $user = User::register($command->email, $match);
        $this->users->save($user);

        return $user;
    }
}
