<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

use App\School\Domain\Matching\MatchCandidate;
use App\School\Domain\Matching\MatchResult;
use App\School\Domain\Matching\MatchStatus;
use App\School\Domain\Model\School;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Powiązanie użytkownika ze szkołą. Zapisywane zawsze - także gdy dopasowanie się nie udało -
 * żeby oryginalny wpis i lista kandydatów były dostępne do późniejszej, ręcznej weryfikacji.
 */
#[ORM\Entity]
#[ORM\Table(name: 'school_assignment')]
#[ORM\Index(name: 'idx_school_assignment_status', columns: ['status'])]
class SchoolAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @param list<array{schoolId: int|null, name: string, city: string, score: float}> $candidates
     */
    private function __construct(
        #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'schoolAssignment')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Column(length: 255)]
        private string $rawInput,
        #[ORM\Column(length: 100, nullable: true)]
        private ?string $city,
        #[ORM\Column(length: 20, enumType: MatchStatus::class)]
        private MatchStatus $status,
        #[ORM\ManyToOne(targetEntity: School::class)]
        #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
        private ?School $school,
        #[ORM\Column]
        private float $score,
        #[ORM\Column(type: Types::JSON)]
        private array $candidates,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function fromMatchResult(User $user, MatchResult $result): self
    {
        return new self(
            $user,
            $result->input,
            $result->city,
            $result->status,
            $result->matchedSchool(),
            $result->bestScore(),
            array_map(static fn (MatchCandidate $candidate) => [
                'schoolId' => $candidate->school->getId(),
                'name' => $candidate->school->getOfficialName(),
                'city' => $candidate->school->getCity(),
                'score' => $candidate->score,
            ], $result->candidates),
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRawInput(): string
    {
        return $this->rawInput;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getStatus(): MatchStatus
    {
        return $this->status;
    }

    public function getSchool(): ?School
    {
        return $this->school;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * Migawka kandydatów z chwili dopasowania.
     *
     * @return list<array{schoolId: int|null, name: string, city: string, score: float}>
     */
    public function getCandidates(): array
    {
        return $this->candidates;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
