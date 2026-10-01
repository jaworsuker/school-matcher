<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

use App\School\Domain\Model\School;

/**
 * @internal używany przez SchoolMatcher
 */
final readonly class SchoolIndexEntry
{
    /**
     * @param array<string, NormalizedName> $variants oryginalny zapis wariantu => postać znormalizowana
     */
    public function __construct(
        public School $school,
        public array $variants,
        public ?int $number,
    ) {
    }

    public static function fromSchool(School $school, SchoolNameNormalizer $normalizer): self
    {
        $variants = [];
        foreach ($school->getNameVariants() as $variant) {
            $variants[$variant] = $normalizer->normalize($variant);
        }

        $acronym = $normalizer->acronym($school->getOfficialName());
        if (null !== $acronym && !isset($variants[$acronym])) {
            $variants[$acronym] = new NormalizedName([$acronym]);
        }

        // Numer szkoły bierzemy z oficjalnej nazwy, a gdy jej brak - z pierwszego aliasu, który go zawiera.
        $number = null;
        foreach ($variants as $variant) {
            $number ??= $variant->number;
        }

        return new self($school, $variants, $number);
    }

    /**
     * @return list<string>
     */
    public function allTokens(): array
    {
        $tokens = [];
        foreach ($this->variants as $variant) {
            array_push($tokens, ...$variant->tokens);
        }

        return array_values(array_unique($tokens));
    }
}
