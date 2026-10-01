<?php

declare(strict_types=1);

namespace App\School\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'school')]
#[ORM\UniqueConstraint(name: 'uniq_school_name_city', columns: ['official_name', 'city'])]
class School
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @var Collection<int, SchoolAlias> */
    #[ORM\OneToMany(targetEntity: SchoolAlias::class, mappedBy: 'school', cascade: ['persist'], orphanRemoval: true)]
    private Collection $aliases;

    /**
     * @param list<string> $aliases
     */
    public function __construct(
        #[ORM\Column(length: 255)]
        private string $officialName,
        #[ORM\Column(length: 100)]
        private string $city,
        #[ORM\Column(length: 20, enumType: SchoolType::class)]
        private SchoolType $type,
        array $aliases = [],
    ) {
        $this->aliases = new ArrayCollection();
        $this->replaceAliases($aliases);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOfficialName(): string
    {
        return $this->officialName;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getType(): SchoolType
    {
        return $this->type;
    }

    /**
     * @return list<string>
     */
    public function getAliases(): array
    {
        return array_values($this->aliases->map(static fn (SchoolAlias $alias) => $alias->getName())->toArray());
    }

    /**
     * Wszystkie znane nazwy szkoły: oficjalna + aliasy.
     *
     * @return list<string>
     */
    public function getNameVariants(): array
    {
        return [$this->officialName, ...$this->getAliases()];
    }

    public function changeType(SchoolType $type): void
    {
        $this->type = $type;
    }

    /**
     * @param list<string> $aliases
     */
    public function replaceAliases(array $aliases): void
    {
        $names = array_values(array_unique(array_filter(array_map('trim', $aliases), static fn (string $alias) => '' !== $alias)));

        foreach ($this->aliases as $alias) {
            if (!\in_array($alias->getName(), $names, true)) {
                $this->aliases->removeElement($alias);
            }
        }

        foreach (array_diff($names, $this->getAliases()) as $name) {
            $this->aliases->add(new SchoolAlias($this, $name));
        }
    }
}
