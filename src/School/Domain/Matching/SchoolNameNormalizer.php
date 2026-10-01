<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

/**
 * Sprowadza dowolny zapis nazwy szkoły do postaci kanonicznej:
 * - małe litery, bez polskich znaków i interpunkcji,
 * - numer szkoły (arabski, rzymski, słowny: "14", "XIV", "czternaste") jako token "#14",
 * - rozwinięte skróty ("LO" -> "liceum ogolnoksztalcace"),
 * - usunięte słowa bez znaczenia ("im.", "nr", "i").
 */
final class SchoolNameNormalizer
{
    private const TRANSLITERATION = [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
    ];

    private const NOISE = ['im', 'imienia', 'nr', 'numer', 'i', 'w', 'we', 'z', 'ze'];

    private const NUMBER_PREFIXES = ['nr', 'numer'];

    private const ABBREVIATIONS = [
        'lo' => ['liceum', 'ogolnoksztalcace'],
        'ogolniak' => ['liceum', 'ogolnoksztalcace'],
        'tech' => ['technikum'],
        'zs' => ['zespol', 'szkol'],
    ];

    private const ORDINAL_STEMS = [
        'pierwsz' => 1, 'drug' => 2, 'trzec' => 3, 'czwart' => 4, 'piat' => 5,
        'szost' => 6, 'siodm' => 7, 'osm' => 8, 'dziewiat' => 9, 'dziesiat' => 10,
        'jedenast' => 11, 'dwunast' => 12, 'trzynast' => 13, 'czternast' => 14, 'pietnast' => 15,
    ];

    private const ORDINAL_ENDINGS = ['e', 'a', 'y', 'i', 'ego', 'ej'];

    public function normalize(string $name): NormalizedName
    {
        $words = $this->words($name);
        $lastIndex = \count($words) - 1;
        $tokens = [];
        $number = null;

        foreach ($words as $i => $word) {
            $value = $this->parseNumber($word, $i, $lastIndex, $words[$i - 1] ?? null);

            if (null !== $value) {
                $number ??= $value;
                $tokens[] = NormalizedName::numberToken($value);
                continue;
            }

            if (\in_array($word, self::NOISE, true)) {
                continue;
            }

            array_push($tokens, ...(self::ABBREVIATIONS[$word] ?? [$word]));
        }

        return new NormalizedName(array_values(array_unique($tokens)), $number);
    }

    /**
     * Skrótowiec z pierwszych liter słów, np. "Zespół Szkół Elektronicznych i Informatycznych" -> "zseii".
     */
    public function acronym(string $name): ?string
    {
        $words = array_filter($this->words($name), static fn (string $word) => !ctype_digit($word));

        if (\count($words) < 3) {
            return null;
        }

        return implode('', array_map(static fn (string $word) => $word[0], $words));
    }

    /**
     * @return list<string>
     */
    private function words(string $name): array
    {
        $name = strtr(mb_strtolower($name), self::TRANSLITERATION);
        $name = (string) preg_replace('/[^a-z0-9]+/', ' ', $name);
        $name = (string) preg_replace('/(\d+)/', ' $1 ', $name);

        return array_values(array_filter(explode(' ', $name), static fn (string $word) => '' !== $word));
    }

    private function parseNumber(string $word, int $index, int $lastIndex, ?string $previous): ?int
    {
        if (ctype_digit($word)) {
            return (int) $word;
        }

        foreach (self::ORDINAL_STEMS as $stem => $value) {
            foreach (self::ORDINAL_ENDINGS as $ending) {
                if ($word === $stem.$ending) {
                    return $value;
                }
            }
        }

        // Liczba rzymska tylko tam, gdzie zwykle stoi numer szkoły - inaczej "i" to spójnik.
        $isNumberPosition = 0 === $index || $index === $lastIndex || \in_array($previous, self::NUMBER_PREFIXES, true);

        return $isNumberPosition ? $this->parseRoman($word) : null;
    }

    private function parseRoman(string $word): ?int
    {
        if (!preg_match('/^(x{0,3})(ix|iv|v?i{0,3})$/', $word, $m) || '' === $word) {
            return null;
        }

        $units = ['' => 0, 'i' => 1, 'ii' => 2, 'iii' => 3, 'iv' => 4, 'v' => 5, 'vi' => 6, 'vii' => 7, 'viii' => 8, 'ix' => 9];

        return 10 * \strlen($m[1]) + $units[$m[2]];
    }
}
