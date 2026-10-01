<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\School\Domain\Model\School;
use App\School\Domain\Repository\SchoolRepositoryInterface;

final class InMemorySchoolRepository implements SchoolRepositoryInterface
{
    /** @var list<School> */
    private array $schools = [];

    public function findAll(): array
    {
        return $this->schools;
    }

    public function findById(int $id): ?School
    {
        foreach ($this->schools as $school) {
            if ($school->getId() === $id) {
                return $school;
            }
        }

        return null;
    }

    public function findByOfficialNameAndCity(string $officialName, string $city): ?School
    {
        foreach ($this->schools as $school) {
            if ($school->getOfficialName() === $officialName && $school->getCity() === $city) {
                return $school;
            }
        }

        return null;
    }

    public function save(School $school): void
    {
        if (!\in_array($school, $this->schools, true)) {
            $this->schools[] = $school;
        }
    }
}
