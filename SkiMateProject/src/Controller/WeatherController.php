<?php

namespace App\Controller;


use App\Service\WeatherApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WeatherController extends AbstractController
{
    #[Route('/weather', name: 'weather_snow')]
    public function getSnow(WeatherApiService $snowWeatherService): JsonResponse
    {
        $latitude = '45.5057'; // Latitude de La Plagne
        $longitude = '6.6803'; // Longitude de La Plagne
        $snowHeight = $snowWeatherService->getWeeklyWeather($latitude, $longitude);

        return $this->json([
            'location' => 'La Plagne',
            'meteo' => $snowHeight,
        ]);
    }
}
