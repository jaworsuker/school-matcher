<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class UserApiTest extends ApiTestCase
{
    public function testRegistersUserAndAssignsMatchedSchool(): void
    {
        $data = $this->postJson('/api/users', ['email' => 'Ania@Example.com', 'schoolName' => 'XIV LO Staszica']);

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame('Location', '/api/users/'.$data['id']);
        self::assertSame('ania@example.com', $data['email']);
        self::assertSame('matched', $data['schoolAssignment']['status']);
        self::assertSame('XIV Liceum Ogólnokształcące im. Stanisława Staszica', $data['schoolAssignment']['school']['name']);
    }

    public function testStoresAmbiguousInputForReviewWithoutAssigningSchool(): void
    {
        $data = $this->postJson('/api/users', ['email' => 'ola@example.com', 'schoolName' => 'LO', 'city' => 'Kraków']);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('needs_review', $data['schoolAssignment']['status']);
        self::assertNull($data['schoolAssignment']['school']);
        self::assertSame('LO', $data['schoolAssignment']['rawInput']);
        self::assertNotEmpty($data['schoolAssignment']['candidates']);
    }

    public function testRegistersUserEvenWhenSchoolIsUnknown(): void
    {
        $data = $this->postJson('/api/users', ['email' => 'piotr@example.com', 'schoolName' => 'Szkoła Podstawowa nr 3']);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('unmatched', $data['schoolAssignment']['status']);
        self::assertSame([], $data['schoolAssignment']['candidates']);
    }

    public function testReturnsPersistedUser(): void
    {
        $created = $this->postJson('/api/users', ['email' => 'kasia@example.com', 'schoolName' => 'Zeromskego']);

        $this->client->request('GET', '/api/users/'.$created['id']);
        $data = $this->responseJson();

        self::assertResponseIsSuccessful();
        self::assertSame('matched', $data['schoolAssignment']['status']);
        self::assertSame('Szczecin', $data['schoolAssignment']['school']['city']);
    }

    public function testRejectsDuplicateEmailCaseInsensitively(): void
    {
        $this->postJson('/api/users', ['email' => 'jan@example.com', 'schoolName' => 'Staszic']);
        $data = $this->postJson('/api/users', ['email' => 'JAN@example.com', 'schoolName' => 'Kopernik']);

        self::assertResponseStatusCodeSame(409);
        self::assertStringContainsString('jan@example.com', $data['detail']);
    }

    public function testValidatesPayload(): void
    {
        $data = $this->postJson('/api/users', ['email' => 'not-an-email', 'schoolName' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertEqualsCanonicalizing(['email', 'schoolName'], array_column($data['violations'], 'propertyPath'));
    }

    public function testReturnsNotFoundForUnknownUser(): void
    {
        $this->client->request('GET', '/api/users/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
