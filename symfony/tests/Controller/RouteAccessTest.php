<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RouteAccessTest extends WebTestCase
{
    private function login($client, string $email, string $password): string
    {
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);

        return $data['token'];
    }

    public function testAdminAccessDeniedForRegularUser(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'test@test.com', 'MyPass123!');

        $client->request('GET', '/api/admin/utilisateurs', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminAccessGrantedForAdminUser(): void
    {
        $client = static::createClient();

        $token = $this->login($client, 'mathieu.crosnier15@outlook.fr', '1234');

        $client->request('GET', '/api/admin/utilisateurs', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
    }


    public function testAdminAccessRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/admin/utilisateurs');

        $this->assertResponseStatusCodeSame(401);
    }
}
