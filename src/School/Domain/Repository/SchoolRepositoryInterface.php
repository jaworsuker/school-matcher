<?php

declare(strict_types=1);

namespace App\School\Domain\Repository;

use App\School\Domain\Model\School;

interface SchoolRepositoryInterface
{
    /**
     * @return list<School>
     */
    public function findAll(): array;

    public function findById(int $id): ?School;

    public function findByOfficialNameAndCity(string $officialName, string $city): ?School;

    public function save(School $school): void;
}
