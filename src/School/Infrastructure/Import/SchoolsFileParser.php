<?php

declare(strict_types=1);

namespace App\School\Infrastructure\Import;

use App\School\Application\Import\SchoolData;
use App\School\Domain\Model\SchoolType;

/**
 * Parser pliku w formacie: Oficjalna nazwa | Alias1, Alias2 | Miasto | Typ
 * Puste linie i linie zaczynające się od "#" są pomijane.
 */
final class SchoolsFileParser
{
    /**
     * @return list<SchoolData>
     *
     * @throws InvalidSchoolsFileException
     */
    public function parseFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidSchoolsFileException(\sprintf('Nie można odczytać pliku "%s".', $path));
        }

        return $this->parse((string) file_get_contents($path));
    }

    /**
     * @return list<SchoolData>
     *
     * @throws InvalidSchoolsFileException
     */
    public function parse(string $content): array
    {
        $schools = [];

        foreach (preg_split('/\R/u', $content) ?: [] as $lineNumber => $line) {
            $line = trim($line);
            if ('' === $line || str_starts_with($line, '#')) {
                continue;
            }

            $columns = array_map('trim', explode('|', $line));
            if (4 !== \count($columns)) {
                throw new InvalidSchoolsFileException(\sprintf('Linia %d: oczekiwano 4 kolumn, znaleziono %d.', $lineNumber + 1, \count($columns)));
            }

            [$name, $aliases, $city, $type] = $columns;
            $schoolType = SchoolType::tryFrom(mb_strtolower($type));

            if ('' === $name || '' === $city || null === $schoolType) {
                throw new InvalidSchoolsFileException(\sprintf('Linia %d: pusta nazwa/miasto lub nieznany typ szkoły "%s".', $lineNumber + 1, $type));
            }

            $schools[] = new SchoolData(
                $name,
                array_values(array_filter(array_map('trim', explode(',', $aliases)), static fn (string $alias) => '' !== $alias)),
                $city,
                $schoolType,
            );
        }

        return $schools;
    }
}
