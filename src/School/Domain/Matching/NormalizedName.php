<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

/**
 * Nazwa szkoły sprowadzona do porównywalnej postaci: lista tokenów + wyłuskany numer szkoły.
 * Numer występuje też w tokenach jako "#<n>", dzięki czemu bierze udział w ważeniu jak zwykłe słowo.
 */
final readonly class NormalizedName
{
    /**
     * @param list<string> $tokens
     */
    public function __construct(
        public array $tokens,
        public ?int $number = null,
    ) {
    }

    public static function numberToken(int $number): string
    {
        return '#'.$number;
    }

    public static function isNumberToken(string $token): bool
    {
        return str_starts_with($token, '#');
    }

    public function isEmpty(): bool
    {
        return [] === $this->tokens;
    }

    /**
     * @param list<string> $tokens
     */
    public function withoutTokens(array $tokens): self
    {
        return new self(array_values(array_diff($this->tokens, $tokens)), $this->number);
    }
}
