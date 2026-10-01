<?php

declare(strict_types=1);

namespace App\Tests\Unit\School\Infrastructure\Import;

use App\School\Domain\Model\SchoolType;
use App\School\Infrastructure\Import\InvalidSchoolsFileException;
use App\School\Infrastructure\Import\SchoolsFileParser;
use PHPUnit\Framework\TestCase;

final class SchoolsFileParserTest extends TestCase
{
    public function testParsesLinesSkippingCommentsAndBlankLines(): void
    {
        $content = <<<TXT
            # komentarz

            Technikum Mechatroniczne | Mechatronika, TM ,  | Gdynia | Technikum
            TXT;

        $schools = (new SchoolsFileParser())->parse($content);

        self::assertCount(1, $schools);
        self::assertSame('Technikum Mechatroniczne', $schools[0]->officialName);
        self::assertSame(['Mechatronika', 'TM'], $schools[0]->aliases);
        self::assertSame('Gdynia', $schools[0]->city);
        self::assertSame(SchoolType::Technikum, $schools[0]->type);
    }

    public function testParsesProvidedSchoolsFile(): void
    {
        self::assertCount(12, (new SchoolsFileParser())->parseFile(__DIR__.'/../../../../../docs/schools.txt'));
    }

    public function testRejectsLineWithWrongNumberOfColumns(): void
    {
        $this->expectException(InvalidSchoolsFileException::class);
        $this->expectExceptionMessage('Linia 1');

        (new SchoolsFileParser())->parse('Technikum | Gdynia | technikum');
    }

    public function testRejectsUnknownSchoolType(): void
    {
        $this->expectException(InvalidSchoolsFileException::class);

        (new SchoolsFileParser())->parse('Szkoła | | Gdynia | podstawowa');
    }
}
