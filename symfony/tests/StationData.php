<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccessDataStation extends WebTestCase
{
    public function testStationReturnsSuccess(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/get-ski-domain',[],[],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode([
            "domaine"=>"La Plagne"
        ])
    );
        $this->assertResponseIsSuccessful();
    }
}