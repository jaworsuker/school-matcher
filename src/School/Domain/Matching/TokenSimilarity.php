<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

/**
 * Podobieństwo dwóch pojedynczych słów w skali 0..1.
 * Obsługuje odmianę ("Mickiewicz" / "Mickiewicza") oraz literówki ("Zeromskego").
 */
final class TokenSimilarity
{
    private const MIN_FUZZY_LENGTH = 4;
    private const INFLECTION_SCORE = 0.95;
    private const MAX_INFLECTION_SUFFIX = 3;
    private const MIN_TYPO_SCORE = 0.8;

    public function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        // Numery i krótkie skróty (TI, TM, LO) muszą zgadzać się dokładnie.
        if (NormalizedName::isNumberToken($a) || NormalizedName::isNumberToken($b)) {
            return 0.0;
        }

        $minLength = min(\strlen($a), \strlen($b));
        if ($minLength < self::MIN_FUZZY_LENGTH) {
            return 0.0;
        }

        if ($this->commonPrefixLength($a, $b) >= max(self::MIN_FUZZY_LENGTH, $minLength - self::MAX_INFLECTION_SUFFIX + 1)) {
            return self::INFLECTION_SCORE;
        }

        $score = 1 - $this->editDistance($a, $b) / max(\strlen($a), \strlen($b));

        return $score >= self::MIN_TYPO_SCORE ? $score : 0.0;
    }

    /**
     * Odległość Damerau-Levenshteina (wariant OSA): przestawienie sąsiednich liter liczy się jako jeden błąd.
     */
    private function editDistance(string $a, string $b): int
    {
        $lengthA = \strlen($a);
        $lengthB = \strlen($b);
        $d = [];

        for ($i = 0; $i <= $lengthA; ++$i) {
            $d[$i][0] = $i;
        }
        for ($j = 0; $j <= $lengthB; ++$j) {
            $d[0][$j] = $j;
        }

        for ($i = 1; $i <= $lengthA; ++$i) {
            for ($j = 1; $j <= $lengthB; ++$j) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $d[$i][$j] = min($d[$i - 1][$j] + 1, $d[$i][$j - 1] + 1, $d[$i - 1][$j - 1] + $cost);

                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $d[$i][$j] = min($d[$i][$j], $d[$i - 2][$j - 2] + 1);
                }
            }
        }

        return $d[$lengthA][$lengthB];
    }

    private function commonPrefixLength(string $a, string $b): int
    {
        $length = min(\strlen($a), \strlen($b));
        $i = 0;
        while ($i < $length && $a[$i] === $b[$i]) {
            ++$i;
        }

        return $i;
    }
}
