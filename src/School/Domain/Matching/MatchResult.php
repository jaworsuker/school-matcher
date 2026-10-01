<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

use App\School\Domain\Model\School;

final readonly class MatchResult
{
    /**
     * @param list<MatchCandidate> $candidates posortowani malejąco po wyniku
     */
    public function __construct(
        public string $input,
        public MatchStatus $status,
        public array $candidates = [],
        public ?string $city = null,
    ) {
    }

    public static function unmatched(string $input, ?string $city = null): self
    {
        return new self($input, MatchStatus::Unmatched, [], $city);
    }

    /**
     * Szkoła przypisana automatycznie - tylko przy pewnym dopasowaniu.
     */
    public function matchedSchool(): ?School
    {
        return MatchStatus::Matched === $this->status ? $this->candidates[0]->school : null;
    }

    public function bestScore(): float
    {
        return $this->candidates[0]->score ?? 0.0;
    }
}
