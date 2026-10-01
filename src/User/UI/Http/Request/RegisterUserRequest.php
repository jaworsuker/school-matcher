<?php

declare(strict_types=1);

namespace App\User\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $schoolName = '',
        #[Assert\Length(max: 100)]
        public ?string $city = null,
    ) {
    }
}
