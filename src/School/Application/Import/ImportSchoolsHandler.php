<?php

declare(strict_types=1);

namespace App\School\Application\Import;

use App\School\Domain\Model\School;
use App\School\Domain\Repository\SchoolRepositoryInterface;

/**
 * Import jest idempotentny: szkoła identyfikowana jest po (oficjalna nazwa, miasto),
 * istniejące rekordy są aktualizowane zamiast duplikowane.
 */
final readonly class ImportSchoolsHandler
{
    public function __construct(
        private SchoolRepositoryInterface $schools,
    ) {
    }

    /**
     * @param iterable<SchoolData> $rows
     */
    public function handle(iterable $rows): ImportSummary
    {
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $school = $this->schools->findByOfficialNameAndCity($row->officialName, $row->city);

            if (null === $school) {
                $school = new School($row->officialName, $row->city, $row->type, $row->aliases);
                ++$created;
            } else {
                $school->changeType($row->type);
                $school->replaceAliases($row->aliases);
                ++$updated;
            }

            $this->schools->save($school);
        }

        return new ImportSummary($created, $updated);
    }
}
