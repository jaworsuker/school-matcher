<?php

declare(strict_types=1);

namespace App\School\Application\Import;

final readonly class ImportSummary
{
    public function __construct(
        public int $created,
        public int $updated,
    ) {
    }
}
