<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\School\Application\Import\ImportSchoolsHandler;
use App\School\Infrastructure\Import\SchoolsFileParser;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Baza testowa (school_matcher_test) jest czyszczona i zasilana listą szkół przed każdym testem.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $connection = $container->get(Connection::class);
        foreach (['school_assignment', '`user`', 'school_alias', 'school'] as $table) {
            $connection->executeStatement('DELETE FROM '.$table);
        }

        $container->get(ImportSchoolsHandler::class)->handle(
            $container->get(SchoolsFileParser::class)->parseFile(\dirname(__DIR__, 2).'/docs/schools.txt'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    protected function postJson(string $uri, array $payload): array
    {
        $this->client->jsonRequest('POST', $uri, $payload);

        return $this->responseJson();
    }

    /**
     * @return array<string, mixed>
     */
    protected function responseJson(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }
}
