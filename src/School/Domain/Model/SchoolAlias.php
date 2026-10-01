<?php

declare(strict_types=1);

namespace App\School\Domain\Model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'school_alias')]
class SchoolAlias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: School::class, inversedBy: 'aliases')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private School $school,
        #[ORM\Column(length: 255)]
        private string $name,
    ) {
    }

    public function getSchool(): School
    {
        return $this->school;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
