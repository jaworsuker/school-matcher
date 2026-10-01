<?php

declare(strict_types=1);

namespace App\User\UI\Http\Response;

use App\School\UI\Http\Response\SchoolView;
use App\User\Domain\Model\User;

final readonly class UserView
{
    /**
     * @param array<string, mixed>|null $schoolAssignment
     */
    public function __construct(
        public ?int $id,
        public string $email,
        public string $createdAt,
        public ?array $schoolAssignment,
    ) {
    }

    public static function fromUser(User $user): self
    {
        $assignment = $user->getSchoolAssignment();
        $school = $assignment?->getSchool();

        return new self(
            $user->getId(),
            $user->getEmail(),
            $user->getCreatedAt()->format(\DATE_ATOM),
            null === $assignment ? null : [
                'status' => $assignment->getStatus()->value,
                'rawInput' => $assignment->getRawInput(),
                'city' => $assignment->getCity(),
                'score' => $assignment->getScore(),
                'school' => null !== $school ? SchoolView::fromSchool($school) : null,
                'candidates' => $assignment->getCandidates(),
            ],
        );
    }
}
