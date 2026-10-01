<?php

declare(strict_types=1);

namespace App\School\Infrastructure\Doctrine;

use App\School\Domain\Model\School;
use App\School\Domain\Repository\SchoolRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSchoolRepository implements SchoolRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s', 'a')
            ->from(School::class, 's')
            ->leftJoin('s.aliases', 'a')
            ->orderBy('s.id')
            ->getQuery()
            ->getResult();
    }

    public function findById(int $id): ?School
    {
        return $this->entityManager->find(School::class, $id);
    }

    public function findByOfficialNameAndCity(string $officialName, string $city): ?School
    {
        return $this->entityManager->getRepository(School::class)->findOneBy([
            'officialName' => $officialName,
            'city' => $city,
        ]);
    }

    public function save(School $school): void
    {
        $this->entityManager->persist($school);
        $this->entityManager->flush();
    }
}
