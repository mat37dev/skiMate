<?php
namespace App\Tests\Controller;

use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthTest extends WebTestCase
{
    public function testPasswordIsHashedOnRegister(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@test.com',
            'password' => 'MyPass123!',
            'confirmPassword' => 'MyPass123!',
            'firstName' => 'xxxxx',
            'lastName' => 'xxxxx',
            'phoneNumber' => '1234567890',
        ]));

        $this->assertResponseStatusCodeSame(201);

        $user = static::getContainer()->get(UsersRepository::class)->findOneByEmail('test@test.com');
        $this->assertNotNull($user);

        // Le mot de passe ne doit pas être stocké en clair
        $this->assertNotEquals('MyPass123!', $user->getPassword());
        $this->assertStringStartsWith('$', $user->getPassword());
    }

    public function testRegisterFailsWithDuplicateEmail(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'dupe@example.com',
            'password' => 'DupPass123!',
            'confirmPassword'=>'DupPass123!',
            'firstName' => 'name',
            'lastName' => 'lastname',
            'phoneNumber' => '1234567890',
        ]));

        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'dupe@example.com',
            'password' => 'AnotherPass!',
            'confirmPassword'=>'AnotherPass!',
            'firstName' => 'name',
            'lastName' => 'lastname',
            'phoneNumber' => '1234567890',
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginReturnsValidJWT(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@test.com',
            'password' => 'MyPass123!',
        ]));

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);

        // décoder le JWT (sans vérifier la signature ici)
        $parts = explode('.', $data['token']);
        $payload = json_decode(base64_decode($parts[1]), true);

        $this->assertArrayHasKey('id', $payload);
        $this->assertNotEmpty($payload['id']);
        $this->assertArrayHasKey('roles', $payload);
        $this->assertContains('ROLE_USER', $payload['roles']);

    }

    public function testRegisterValidationErrors(): void
    {
        $client = static::createClient();

        // Cas 1 : Mots de passe différents
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'wrongmatch@test.com',
            'password' => 'MyPass123!',
            'confirmPassword' => 'NotTheSame123!',
            'firstname' => 'Test',
            'lastname' => 'User',
            'phoneNumber' => '0600000000'
        ]));

        $this->assertResponseStatusCodeSame(400);

        $response1 = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response1);
        $this->assertEquals('les mots de passe ne correspondent pas', $response1['errors']);

        // Cas 2 : mot de passe trop faible
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'weakpass@test.com',
            'password' => 'abc',
            'confirmPassword' => 'abc',
            'firstname' => 'Test',
            'lastname' => 'User',
            'phoneNumber' => '0600000000'
        ]));

        $this->assertResponseStatusCodeSame(400);

        $response2 = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response2);
        $this->assertStringContainsString(
            'Le mot de passe doit contenir au moins 8 caractères',
            $response2['errors']
        );
    }
}
