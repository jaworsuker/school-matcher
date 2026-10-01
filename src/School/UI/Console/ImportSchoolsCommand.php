<?php

declare(strict_types=1);

namespace App\School\UI\Console;

use App\School\Application\Import\ImportSchoolsHandler;
use App\School\Infrastructure\Import\InvalidSchoolsFileException;
use App\School\Infrastructure\Import\SchoolsFileParser;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:schools:import', description: 'Importuje listę szkół z pliku tekstowego (idempotentnie)')]
final readonly class ImportSchoolsCommand
{
    public function __construct(
        private SchoolsFileParser $parser,
        private ImportSchoolsHandler $handler,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Ścieżka do pliku (format: nazwa | aliasy | miasto | typ)')] string $file = 'docs/schools.txt',
    ): int {
        try {
            $rows = $this->parser->parseFile($file);
        } catch (InvalidSchoolsFileException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $summary = $this->handler->handle($rows);
        $io->success(\sprintf('Zaimportowano szkoły: %d nowych, %d zaktualizowanych.', $summary->created, $summary->updated));

        return Command::SUCCESS;
    }
}
