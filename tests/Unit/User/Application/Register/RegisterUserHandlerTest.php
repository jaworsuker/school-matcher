<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Register;

use App\School\Application\Import\ImportSchoolsHandler;
use App\School\Domain\Matching\MatchStatus;
use App\School\Domain\Matching\SchoolMatcher;
use App\School\Domain\Matching\SchoolNameNormalizer;
use App\School\Domain\Matching\TokenSimilarity;
use App\School\Infrastructure\Import\SchoolsFileParser;
use App\Tests\Double\InMemorySchoolRepository;
use App\Tests\Double\InMemoryUserRepository;
use App\User\Application\Register\RegisterUserCommand;
use App\User\Application\Register\RegisterUserHandler;
use App\User\Domain\Exception\EmailAlreadyRegisteredException;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $users;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $schools = new InMemorySchoolRepository();
        (new ImportSchoolsHandler($schools))->handle(
            (new SchoolsFileParser())->parseFile(__DIR__.'/../../../../../docs/schools.txt'),
        );

        $this->users = new InMemoryUserRepository();
        $this->handler = new RegisterUserHandler(
            $this->users,
            new SchoolMatcher($schools, new SchoolNameNormalizer(), new TokenSimilarity()),
        );
    }

    public function testRegistersUserWithMatchedSchool(): void
    {
        $user = $this->handler->handle(new RegisterUserCommand(' Ania@Example.com ', 'XIV LO Staszica'));
        $assignment = $user->getSchoolAssignment();

        self::assertSame([$user], $this->users->all());
        self::assertSame('ania@example.com', $user->getEmail());
        self::assertNotNull($assignment);
        self::assertSame(MatchStatus::Matched, $assignment->getStatus());
        self::assertSame('XIV Liceum Ogólnokształcące im. Stanisława Staszica', $assignment->getSchool()?->getOfficialName());
        self::assertSame('XIV LO Staszica', $assignment->getRawInput());
    }

    public function testRegistersUserWithoutSchoolWhenMatchNeedsReview(): void
    {
        $user = $this->handler->handle(new RegisterUserCommand('ola@example.com', 'LO', 'Kraków'));
        $assignment = $user->getSchoolAssignment();

        self::assertSame([$user], $this->users->all());
        self::assertNotNull($assignment);
        self::assertSame(MatchStatus::NeedsReview, $assignment->getStatus());
        self::assertNull($assignment->getSchool());
        self::assertSame('Kraków', $assignment->getCity());
        self::assertNotEmpty($assignment->getCandidates());
    }

    public function testRejectsDuplicateEmailRegardlessOfCase(): void
    {
        $this->handler->handle(new RegisterUserCommand('jan@example.com', 'Staszic'));

        try {
            $this->handler->handle(new RegisterUserCommand('JAN@Example.com', 'Kopernik'));
            self::fail('Oczekiwano EmailAlreadyRegisteredException.');
        } catch (EmailAlreadyRegisteredException $e) {
            self::assertStringContainsString('jan@example.com', $e->getMessage());
        }

        self::assertCount(1, $this->users->all());
    }
}
