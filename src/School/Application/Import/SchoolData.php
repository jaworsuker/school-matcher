<?php

declare(strict_types=1);

namespace App\School\Application\Import;

use App\School\Domain\Model\SchoolType;

final readonly class SchoolData
{
    /**
     * @param list<string> $aliases
     */
    public function __construct(
        public string $officialName,
        public array $aliases,
        public string $city,
        public SchoolType $type,
    ) {
    }
}
