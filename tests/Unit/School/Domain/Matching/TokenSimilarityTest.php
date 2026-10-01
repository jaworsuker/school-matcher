<?php

declare(strict_types=1);

namespace App\Tests\Unit\School\Domain\Matching;

use App\School\Domain\Matching\TokenSimilarity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TokenSimilarityTest extends TestCase
{
    #[DataProvider('similarPairs')]
    public function testRecognizesSimilarTokens(string $a, string $b): void
    {
        self::assertGreaterThanOrEqual(0.8, (new TokenSimilarity())->similarity($a, $b));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function similarPairs(): iterable
    {
        yield 'identyczne' => ['staszic', 'staszic'];
        yield 'odmiana' => ['mickiewicz', 'mickiewicza'];
        yield 'odmiana miasta' => ['krakow', 'krakowie'];
        yield 'brakująca litera' => ['zeromskego', 'zeromskiego'];
        yield 'przestawione litery' => ['mickiewciza', 'mickiewicza'];
    }

    #[DataProvider('differentPairs')]
    public function testRejectsDifferentTokens(string $a, string $b): void
    {
        self::assertSame(0.0, (new TokenSimilarity())->similarity($a, $b));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function differentPairs(): iterable
    {
        yield 'różne nazwiska o wspólnym rdzeniu' => ['mickiewicza', 'sienkiewicza'];
        yield 'krótkie skróty bez tolerancji' => ['ti', 'tm'];
        yield 'różne numery' => ['#2', '#3'];
        yield 'technikum vs techniczne' => ['technikum', 'techniczne'];
    }
}
