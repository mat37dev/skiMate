<?php

namespace App\Tests\Unit\Service;

use App\Service\WeatherApiService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use ReflectionClass;

class WeatherApiServiceTest extends TestCase
{
    private WeatherApiService $service;
    private ReflectionClass $refClass;

    protected function setUp(): void
    {
        // On stubbe simplement les dépendances sans comportement
        $httpClient = $this->createMock(HttpClientInterface::class);
        $dm = $this->createMock(DocumentManager::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->service = new WeatherApiService($httpClient, $dm, $logger);
        $this->refClass = new ReflectionClass(WeatherApiService::class);
    }

    private function invoke(string $method, array $args = [])
    {
        $m = $this->refClass->getMethod($method);
        return $m->invokeArgs($this->service, $args);
    }

    public function testCalculateModeWithValues(): void
    {
        $values = [1,2,2,3,1,2];
        $mode = $this->invoke('calculateMode', [$values]);
        $this->assertSame(2, $mode);
    }

    public function testCalculateModeEmpty(): void
    {
        $mode = $this->invoke('calculateMode', [[]]);
        $this->assertNull($mode);
    }

    public function testInterpretWeatherCodeKnown(): void
    {
        $result = $this->invoke('interpretWeatherCode', [2]);
        $this->assertSame(['Partiellement nuageux','nuageux'], $result);
    }

    public function testInterpretWeatherCodeUnknown(): void
    {
        $result = $this->invoke('interpretWeatherCode', [999]);
        $this->assertSame(['Inconnu','inconnu'], $result);
    }

    public function testInterpretWeatherCodeNull(): void
    {
        $result = $this->invoke('interpretWeatherCode', [null]);
        $this->assertNull($result);
    }

    public function testAggregatePeriodDataEmpty(): void
    {
        $data = $this->invoke('aggregatePeriodData', [[]]);
        $this->assertEquals([
            'temperature_2m' => null,
            'snowfall'       => null,
            'snow_depth'     => null,
            'weather_code'   => null,
            'wind_speed_10m' => null,
        ], $data);
    }

    public function testAggregatePeriodDataWithValues(): void
    {
        $period = [
            [
                'temperature_2m' => 10.0,
                'snowfall'       => 1.0,
                'snow_depth'     => 5.0,
                'weather_code'   => 1,
                'wind_speed_10m' => 5.0,
            ],
            [
                'temperature_2m' => 20.0,
                'snowfall'       => 3.0,
                'snow_depth'     => 7.0,
                'weather_code'   => 2,
                'wind_speed_10m' => 15.0,
            ],
            [
                'temperature_2m' => 30.0,
                'snowfall'       => 5.0,
                'snow_depth'     => 9.0,
                'weather_code'   => 2,
                'wind_speed_10m' => 25.0,
            ],
        ];

        $data = $this->invoke('aggregatePeriodData', [$period]);

        $this->assertEqualsWithDelta(20.0, $data['temperature_2m'], 0.0);
        $this->assertEqualsWithDelta(3.00, $data['snowfall'], 0.01);
        $this->assertEqualsWithDelta(7.00, $data['snow_depth'], 0.01);
        $this->assertEquals(['Partiellement nuageux','nuageux'], $data['weather_code']);
        $this->assertEqualsWithDelta(15.0, $data['wind_speed_10m'], 0.0);
    }
}
