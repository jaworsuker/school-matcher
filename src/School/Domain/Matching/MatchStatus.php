<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

enum MatchStatus: string
{
    /** Pewne dopasowanie - szkołę można przypisać automatycznie. */
    case Matched = 'matched';

    /** Niepewne lub niejednoznaczne - są kandydaci, ale decyzję powinien podjąć człowiek. */
    case NeedsReview = 'needs_review';

    /** Brak sensownych kandydatów. */
    case Unmatched = 'unmatched';
}
