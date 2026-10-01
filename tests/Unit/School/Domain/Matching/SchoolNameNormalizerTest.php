<?php

declare(strict_types=1);

namespace App\Tests\Unit\School\Domain\Matching;

use App\School\Domain\Matching\SchoolNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SchoolNameNormalizerTest extends TestCase
{
    private SchoolNameNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new SchoolNameNormalizer();
    }

    /**
     * @param list<string> $expectedTokens
     */
    #[DataProvider('names')]
    public function testNormalizesName(string $input, array $expectedTokens, ?int $expectedNumber): void
    {
        $name = $this->normalizer->normalize($input);

        self::assertSame($expectedTokens, $name->tokens);
        self::assertSame($expectedNumber, $name->number);
    }

    /**
     * @return iterable<string, array{string, list<string>, ?int}>
     */
    public static function names(): iterable
    {
        yield 'pełna nazwa z polskimi znakami' => ['XIV Liceum Ogólnokształcące im. Stanisława Staszica', ['#14', 'liceum', 'ogolnoksztalcace', 'stanislawa', 'staszica'], 14];
        yield 'skrót LO i liczba arabska' => ['14 LO', ['#14', 'liceum', 'ogolnoksztalcace'], 14];
        yield 'numer po "nr"' => ['Liceum Ogólnokształcące nr 5', ['liceum', 'ogolnoksztalcace', '#5'], 5];
        yield 'numer rzymski na końcu' => ['LO V', ['liceum', 'ogolnoksztalcace', '#5'], 5];
        yield 'liczebnik słowny' => ['Pierwsze LO', ['#1', 'liceum', 'ogolnoksztalcace'], 1];
        yield 'liczba sklejona z tekstem' => ['LO5', ['liceum', 'ogolnoksztalcace', '#5'], 5];
        yield 'spójnik "i" w środku nie jest numerem' => ['Zespół Szkół Elektronicznych i Informatycznych', ['zespol', 'szkol', 'elektronicznych', 'informatycznych'], null];
        yield 'interpunkcja i wielkość liter' => ['  ŻEROMSKI!!!  ', ['zeromski'], null];
        yield 'pusty napis' => ['', [], null];
        yield 'same znaki specjalne' => ['!!! ???', [], null];
    }

    public function testBuildsAcronymFromMultiWordName(): void
    {
        self::assertSame('zseii', $this->normalizer->acronym('Zespół Szkół Elektronicznych i Informatycznych'));
        self::assertSame('zstio', $this->normalizer->acronym('Zespół Szkół Technicznych i Ogólnokształcących'));
    }

    public function testDoesNotBuildAcronymFromShortName(): void
    {
        self::assertNull($this->normalizer->acronym('Technikum Mechatroniczne'));
    }
}
