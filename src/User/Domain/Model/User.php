<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

use App\School\Domain\Matching\MatchResult;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToOne(targetEntity: SchoolAssignment::class, mappedBy: 'user', cascade: ['persist'])]
    private ?SchoolAssignment $schoolAssignment = null;

    private function __construct(
        #[ORM\Column(length: 180, unique: true)]
        private string $email,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Rejestracja użytkownika razem z wynikiem dopasowania szkoły podanej w formularzu.
     */
    public static function register(string $email, MatchResult $schoolMatch): self
    {
        $user = new self(self::normalizeEmail($email));
        $user->schoolAssignment = SchoolAssignment::fromMatchResult($user, $schoolMatch);

        return $user;
    }

    /**
     * Adres e-mail porównujemy bez względu na wielkość liter.
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSchoolAssignment(): ?SchoolAssignment
    {
        return $this->schoolAssignment;
    }
}
