<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CallApiService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client) // Corrected here
    {
        $this->client = $client;
    }

    public function getCallApi()
    {
        $response = $this->client->request(
            'GET',
            'https://opendata.nicecotedazur.org/data/storage/f/2015-10-20T16%3A01%3A37.952Z/sign-ig-ig-base-localisation-selection.json'
        );
        return $response->toArray();
    }
}
