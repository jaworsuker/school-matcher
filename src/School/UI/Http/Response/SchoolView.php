<?php

declare(strict_types=1);

namespace App\School\UI\Http\Response;

use App\School\Domain\Model\School;

final readonly class SchoolView
{
    /**
     * @param list<string> $aliases
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public string $city,
        public string $type,
        public array $aliases,
    ) {
    }

    public static function fromSchool(School $school): self
    {
        return new self($school->getId(), $school->getOfficialName(), $school->getCity(), $school->getType()->value, $school->getAliases());
    }
}
