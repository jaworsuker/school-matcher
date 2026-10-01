<?php

declare(strict_types=1);

namespace App\Tests\Unit\School\Domain\Matching;

use App\School\Application\Import\ImportSchoolsHandler;
use App\School\Domain\Matching\MatchCandidate;
use App\School\Domain\Matching\MatchStatus;
use App\School\Domain\Matching\SchoolMatcher;
use App\School\Domain\Matching\SchoolNameNormalizer;
use App\School\Domain\Matching\TokenSimilarity;
use App\School\Infrastructure\Import\SchoolsFileParser;
use App\Tests\Double\InMemorySchoolRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Testy na prawdziwej liście szkół z docs/schools.txt.
 */
final class SchoolMatcherTest extends TestCase
{
    private SchoolMatcher $matcher;

    protected function setUp(): void
    {
        $repository = new InMemorySchoolRepository();
        $schools = (new SchoolsFileParser())->parseFile(__DIR__.'/../../../../../docs/schools.txt');
        (new ImportSchoolsHandler($repository))->handle($schools);

        $this->matcher = new SchoolMatcher($repository, new SchoolNameNormalizer(), new TokenSimilarity());
    }

    #[DataProvider('confidentMatches')]
    public function testMatchesSchool(string $input, string $expectedSchool, ?string $city = null): void
    {
        $result = $this->matcher->match($input, $city);

        self::assertSame(MatchStatus::Matched, $result->status, $this->describe($result->candidates));
        self::assertSame($expectedSchool, $result->matchedSchool()?->getOfficialName());
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2?: string}>
     */
    public static function confidentMatches(): iterable
    {
        $staszic = 'XIV Liceum Ogólnokształcące im. Stanisława Staszica';
        $mickiewicz = 'I Liceum Ogólnokształcące im. Adama Mickiewicza';
        $zsei = 'Zespół Szkół Elektronicznych i Informatycznych';

        yield 'pełna oficjalna nazwa' => [$staszic, $staszic];
        yield 'alias' => ['Staszic', $staszic];
        yield 'alias małymi literami' => ['staszic', $staszic];
        yield 'numer arabski zamiast rzymskiego' => ['14 LO', $staszic];
        yield 'mieszany skrót' => ['XIV LO Staszica', $staszic];
        yield 'liczebnik słowny' => ['Pierwsze liceum', $mickiewicz];
        yield 'nazwisko patrona w dopełniaczu' => ['Liceum Mickiewicza', $mickiewicz];
        yield 'literówka - przestawione litery' => ['Mickiewciza', $mickiewicz];
        yield 'literówka - brak litery' => ['Zeromskego', 'Liceum Ogólnokształcące im. Stefana Żeromskiego'];
        yield 'skrótowiec' => ['ZSEiI', $zsei];
        yield 'skrótowiec bez wielkich liter' => ['zsei', $zsei];
        yield 'alias potoczny z miastem w nazwie' => ['Elektronik Warszawa', $zsei];
        yield 'miasto w odmianie' => ['Konopnickiej w Gdańsku', 'II Liceum Ogólnokształcące im. Marii Konopnickiej'];
        yield 'numer po "nr"' => ['LO nr 5', 'Liceum Ogólnokształcące nr 5 im. Józefa Wybickiego'];
        yield 'niepełna nazwa' => ['Zespół Szkół Technicznych', 'Zespół Szkół Technicznych i Ogólnokształcących'];
        yield 'skrócony rdzeń' => ['Mechatronik', 'Technikum Mechatroniczne'];
        yield 'zgodne miasto jako osobne pole' => ['Sienkiewicz', 'Liceum Ogólnokształcące im. Henryka Sienkiewicza', 'Katowice'];
    }

    #[DataProvider('ambiguousInputs')]
    public function testRequiresReviewWhenUncertain(string $input, ?string $city = null): void
    {
        $result = $this->matcher->match($input, $city);

        self::assertSame(MatchStatus::NeedsReview, $result->status, $this->describe($result->candidates));
        self::assertNull($result->matchedSchool());
        self::assertNotEmpty($result->candidates);
    }

    /**
     * @return iterable<string, array{0: string, 1?: string}>
     */
    public static function ambiguousInputs(): iterable
    {
        yield 'samo "LO" pasuje do wielu szkół' => ['LO'];
        yield 'dwa licea w tym samym mieście' => ['LO w Krakowie'];
        yield 'dwa zespoły szkół' => ['Zespół Szkół'];
        yield 'sprzeczne dane: numer jednej szkoły, patron innej' => ['II LO Kopernika'];
        yield 'szkoła z innego miasta niż podane' => ['Mickiewicz', 'Kraków'];
    }

    #[DataProvider('unknownInputs')]
    public function testDoesNotMatchUnknownSchool(string $input): void
    {
        $result = $this->matcher->match($input);

        self::assertSame(MatchStatus::Unmatched, $result->status, $this->describe($result->candidates));
        self::assertSame([], $result->candidates);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownInputs(): iterable
    {
        yield 'szkoła spoza listy' => ['Szkoła Podstawowa nr 3'];
        yield 'nieistniejący numer liceum' => ['IV LO'];
        yield 'przypadkowy tekst' => ['asdf'];
        yield 'pusty napis' => [''];
        yield 'same znaki specjalne' => ['!!!'];
        yield 'samo miasto' => ['Warszawa'];
    }

    public function testDoesNotConfuseSchoolsWithSimilarNumbers(): void
    {
        self::assertSame('II Liceum Ogólnokształcące im. Marii Konopnickiej', $this->matcher->match('II LO')->matchedSchool()?->getOfficialName());
        self::assertSame('III Liceum Ogólnokształcące im. Juliusza Słowackiego', $this->matcher->match('III LO')->matchedSchool()?->getOfficialName());
    }

    #[DataProvider('conflictingNumberAndPatron')]
    public function testOffersBothSchoolsWhenNumberContradictsPatron(string $input, string $byNumber, string $byPatron): void
    {
        $result = $this->matcher->match($input);
        $names = array_map(static fn (MatchCandidate $c) => $c->school->getOfficialName(), $result->candidates);

        self::assertSame(MatchStatus::NeedsReview, $result->status, $this->describe($result->candidates));
        self::assertContains($byNumber, $names);
        self::assertContains($byPatron, $names);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function conflictingNumberAndPatron(): iterable
    {
        $konopnicka = 'II Liceum Ogólnokształcące im. Marii Konopnickiej';

        yield 'V LO + patron II LO' => ['V LO Konopnickiej', 'Liceum Ogólnokształcące nr 5 im. Józefa Wybickiego', $konopnicka];
        yield 'XIV LO + patron II LO' => ['XIV LO Konopnickiej', 'XIV Liceum Ogólnokształcące im. Stanisława Staszica', $konopnicka];
        yield 'III LO + patron I LO' => ['III LO Mickiewicza', 'III Liceum Ogólnokształcące im. Juliusza Słowackiego', 'I Liceum Ogólnokształcące im. Adama Mickiewicza'];
    }

    public function testDetectsCityFromInput(): void
    {
        self::assertSame('Gdynia', $this->matcher->match('Technikum Mechatroniczne Gdynia')->city);
    }

    public function testReturnsCandidatesSortedByScore(): void
    {
        $scores = array_map(static fn ($candidate) => $candidate->score, $this->matcher->match('LO')->candidates);
        $sorted = $scores;
        rsort($sorted);

        self::assertLessThanOrEqual(SchoolMatcher::MAX_CANDIDATES, \count($scores));
        self::assertSame($sorted, $scores);
    }

    /**
     * @param list<MatchCandidate> $candidates
     */
    private function describe(array $candidates): string
    {
        return 'Kandydaci: '.implode('; ', array_map(
            static fn ($c) => \sprintf('%s (%s) %.3f', $c->school->getOfficialName(), $c->school->getCity(), $c->score),
            $candidates,
        ));
    }
}
