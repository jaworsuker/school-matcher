<?php

declare(strict_types=1);

namespace App\School\UI\Http\Response;

use App\School\Domain\Matching\MatchCandidate;
use App\School\Domain\Matching\MatchResult;

final readonly class MatchResultView
{
    /**
     * @param list<array{school: SchoolView, score: float, matchedVariant: string}> $candidates
     */
    public function __construct(
        public string $input,
        public ?string $city,
        public string $status,
        public ?SchoolView $school,
        public array $candidates,
    ) {
    }

    public static function fromResult(MatchResult $result): self
    {
        $school = $result->matchedSchool();

        return new self(
            $result->input,
            $result->city,
            $result->status->value,
            null !== $school ? SchoolView::fromSchool($school) : null,
            array_map(static fn (MatchCandidate $candidate) => [
                'school' => SchoolView::fromSchool($candidate->school),
                'score' => $candidate->score,
                'matchedVariant' => $candidate->matchedVariant,
            ], $result->candidates),
        );
    }
}
