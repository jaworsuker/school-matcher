<?php

declare(strict_types=1);

namespace App\School\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MatchSchoolRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',
        #[Assert\Length(max: 100)]
        public ?string $city = null,
    ) {
    }
}
