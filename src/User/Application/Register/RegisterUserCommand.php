<?php

declare(strict_types=1);

namespace App\User\Application\Register;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $schoolName,
        public ?string $city = null,
    ) {
    }
}
