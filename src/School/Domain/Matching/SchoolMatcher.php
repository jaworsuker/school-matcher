<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

use App\School\Domain\Model\School;
use App\School\Domain\Repository\SchoolRepositoryInterface;

/**
 * Dopasowuje wpisaną przez użytkownika nazwę do szkoły z katalogu.
 *
 * Każda szkoła ma kilka wariantów nazwy (oficjalna, aliasy, skrótowiec). Dla każdego wariantu liczony jest
 * ważony stopień pokrycia tokenów. Wagi działają jak IDF: słowo występujące w wielu szkołach ("liceum")
 * waży mało, słowo charakterystyczne ("staszica", "#14") - dużo.
 */
final class SchoolMatcher
{
    public const MATCH_THRESHOLD = 0.8;
    public const REVIEW_THRESHOLD = 0.5;
    public const AMBIGUITY_MARGIN = 0.1;
    public const MAX_CANDIDATES = 5;

    /** Ile z wyniku daje pokrycie tego, co wpisał użytkownik, a ile pokrycie nazwy szkoły. */
    private const INPUT_COVERAGE_WEIGHT = 0.75;

    /** Kara, gdy podane miasto nie zgadza się z miastem szkoły. */
    private const CITY_MISMATCH_FACTOR = 0.7;

    private const CITY_SIMILARITY_THRESHOLD = 0.9;

    public function __construct(
        private readonly SchoolRepositoryInterface $schools,
        private readonly SchoolNameNormalizer $normalizer,
        private readonly TokenSimilarity $similarity,
    ) {
    }

    public function match(string $input, ?string $city = null): MatchResult
    {
        $city = null !== $city && '' !== trim($city) ? trim($city) : null;
        $name = $this->normalizer->normalize($input);
        $index = SchoolIndex::build($this->schools->findAll(), $this->normalizer);

        // Miasto wpisane w nazwie ("Staszic Warszawa", "LO w Krakowie") traktujemy jak podpowiedź, nie część nazwy.
        foreach ($index->cities() as $cityName) {
            $cityTokens = $this->normalizer->normalize($cityName)->tokens;
            $found = $this->findTokens($cityTokens, $name->tokens);

            if (null !== $found) {
                $name = $name->withoutTokens($found);
                $city ??= $cityName;
            }
        }

        if ($name->isEmpty()) {
            return MatchResult::unmatched($input, $city);
        }

        $candidates = [];
        foreach ($index->entries() as $entry) {
            if (null !== $name->number && null !== $entry->number && $name->number !== $entry->number) {
                continue;
            }

            $best = null;
            foreach ($entry->variants as $variant => $variantName) {
                $score = $this->score($name->tokens, $variantName->tokens, $index);
                if (null === $best || $score > $best[0]) {
                    $best = [$score, (string) $variant];
                }
            }

            if (null === $best) {
                continue;
            }

            [$score, $variant] = $best;
            if (null !== $city && !$this->isSameCity($city, $entry->school)) {
                $score *= self::CITY_MISMATCH_FACTOR;
            }

            if ($score >= self::REVIEW_THRESHOLD) {
                $candidates[] = new MatchCandidate($entry->school, round($score, 3), $variant);
            }
        }

        usort($candidates, static fn (MatchCandidate $a, MatchCandidate $b) => $b->score <=> $a->score);
        $candidates = \array_slice($candidates, 0, self::MAX_CANDIDATES);

        return new MatchResult($input, $this->resolveStatus($candidates), $candidates, $city);
    }

    /**
     * @param list<MatchCandidate> $candidates
     */
    private function resolveStatus(array $candidates): MatchStatus
    {
        if ([] === $candidates) {
            return MatchStatus::Unmatched;
        }

        $best = $candidates[0]->score;
        $second = $candidates[1]->score ?? 0.0;

        if ($best >= self::MATCH_THRESHOLD && $best - $second >= self::AMBIGUITY_MARGIN) {
            return MatchStatus::Matched;
        }

        return MatchStatus::NeedsReview;
    }

    /**
     * @param list<string> $input
     * @param list<string> $variant
     */
    private function score(array $input, array $variant, SchoolIndex $index): float
    {
        $inputCoverage = $this->coverage($input, $variant, $index);
        $variantCoverage = $this->coverage($variant, $input, $index);

        return self::INPUT_COVERAGE_WEIGHT * $inputCoverage + (1 - self::INPUT_COVERAGE_WEIGHT) * $variantCoverage;
    }

    /**
     * Jaka (ważona) część tokenów $source ma swój odpowiednik w $target.
     *
     * @param list<string> $source
     * @param list<string> $target
     */
    private function coverage(array $source, array $target, SchoolIndex $index): float
    {
        $total = 0.0;
        $covered = 0.0;

        foreach ($source as $token) {
            $weight = $index->weight($token);
            $total += $weight;
            $covered += $weight * $this->bestSimilarity($token, $target);
        }

        return $total > 0 ? $covered / $total : 0.0;
    }

    /**
     * @param list<string> $candidates
     */
    private function bestSimilarity(string $token, array $candidates): float
    {
        $best = 0.0;
        foreach ($candidates as $candidate) {
            $best = max($best, $this->similarity->similarity($token, $candidate));
        }

        return $best;
    }

    private function isSameCity(string $city, School $school): bool
    {
        $given = $this->normalizer->normalize($city)->tokens;
        $actual = $this->normalizer->normalize($school->getCity())->tokens;

        return null !== $this->findTokens($actual, $given) && \count($given) === \count($actual);
    }

    /**
     * Zwraca tokeny z $haystack odpowiadające wszystkim $needles (z tolerancją na odmianę) albo null.
     *
     * @param list<string> $needles
     * @param list<string> $haystack
     *
     * @return list<string>|null
     */
    private function findTokens(array $needles, array $haystack): ?array
    {
        if ([] === $needles) {
            return null;
        }

        $found = [];
        foreach ($needles as $needle) {
            $match = null;
            foreach ($haystack as $token) {
                if ($this->similarity->similarity($needle, $token) >= self::CITY_SIMILARITY_THRESHOLD) {
                    $match = $token;
                    break;
                }
            }

            if (null === $match) {
                return null;
            }
            $found[] = $match;
        }

        return $found;
    }
}
