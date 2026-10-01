<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

use App\School\Domain\Model\School;

/**
 * Znormalizowany katalog szkół przygotowany do dopasowywania: warianty nazw każdej szkoły
 * oraz wagi tokenów (rzadkie słowo = większa waga).
 *
 * @internal używany przez SchoolMatcher
 */
final readonly class SchoolIndex
{
    /**
     * @param list<SchoolIndexEntry>  $entries
     * @param array<string, float>    $weights
     * @param list<string>            $cities
     */
    private function __construct(
        private array $entries,
        private array $weights,
        private float $unknownTokenWeight,
        private array $cities,
    ) {
    }

    /**
     * @param list<School> $schools
     */
    public static function build(array $schools, SchoolNameNormalizer $normalizer): self
    {
        $entries = [];
        $documentFrequency = [];
        $cities = [];

        foreach ($schools as $school) {
            $entry = SchoolIndexEntry::fromSchool($school, $normalizer);
            $entries[] = $entry;
            $cities[$school->getCity()] = true;

            foreach ($entry->allTokens() as $token) {
                $documentFrequency[$token] = ($documentFrequency[$token] ?? 0) + 1;
            }
        }

        $count = max(1, \count($entries));
        $weights = array_map(static fn (int $df) => log(1 + $count / $df), $documentFrequency);

        return new self($entries, $weights, log(1 + $count), array_keys($cities));
    }

    /**
     * @return list<SchoolIndexEntry>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @return list<string>
     */
    public function cities(): array
    {
        return $this->cities;
    }

    /**
     * Słowa spoza katalogu (np. literówki) traktujemy jak najrzadsze - nie wolno ich pominąć przy liczeniu pokrycia.
     */
    public function weight(string $token): float
    {
        return $this->weights[$token] ?? $this->unknownTokenWeight;
    }
}
