<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class SchoolApiTest extends ApiTestCase
{
    public function testMatchesSchoolWithoutPersistingAnything(): void
    {
        $data = $this->postJson('/api/schools/match', ['name' => 'Konopnickiej', 'city' => 'Gdańsk']);

        self::assertResponseIsSuccessful();
        self::assertSame('matched', $data['status']);
        self::assertSame('II Liceum Ogólnokształcące im. Marii Konopnickiej', $data['school']['name']);
        self::assertSame($data['school'], $data['candidates'][0]['school']);
    }

    public function testRequiresName(): void
    {
        $this->postJson('/api/schools/match', ['city' => 'Gdańsk']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testListsImportedSchools(): void
    {
        $this->client->request('GET', '/api/schools');

        self::assertResponseIsSuccessful();
        self::assertCount(12, $this->responseJson());
    }
}
