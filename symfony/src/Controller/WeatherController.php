<?php
namespace App\Controller;

use App\Document\Snowfall;
use App\Document\WeatherForecast;
use App\Service\WeatherApiService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class WeatherController extends AbstractController
{
    #[Route('/weather', name: 'app_weather', methods: ['POST'])]
    public function getSnow(Request $request, DocumentManager $documentManager): JsonResponse
    {
        // Récupérer le body de la requête POST (attend un JSON contenant 'location')
        $data = json_decode($request->getContent(), true);
        $location = $data['location'] ?? null;

        if (!$location) {
            return $this->json(['error' => 'La location est requise.'], Response::HTTP_BAD_REQUEST);
        }

        $normalizedLocation = strtolower($location);

        // Rechercher les données météo pour la location
        $weatherData = $documentManager->getRepository(WeatherForecast::class)->findOneBy(['location' => $normalizedLocation]);

        if (!$weatherData) {
            return $this->json(['error' => "Aucune donnée météo trouvée pour la location $location."], Response::HTTP_BAD_REQUEST);
        }

        $forecast = $weatherData->getForecasts();

        // Rechercher les informations sur les dernières chutes de neige pour la location
        $lastSnowfallData = $documentManager->getRepository(Snowfall::class)->findOneBy(['location' => $normalizedLocation]);
        $forecast['0']['lastSnowfall'] = $lastSnowfallData ? $lastSnowfallData->getSnowfallAmount(): null;
        $forecast['0']['lastSnowFallDate'] = $lastSnowfallData ? $lastSnowfallData->getLastSnowfallDate(): null;

        // Construire la réponse
        return $this->json([
            'location' => $weatherData->getLocation(),
            'date' => $weatherData->getDate(),
            'forecasts' => $forecast
        ]);
    }

    #[Route('/admin/refresh-weather', name: 'app_refresh_weather', methods: ['GET'])]
    public function refreshWeather(WeatherApiService $weatherApiService): JsonResponse
    {
        try {
            $weatherApiService->saveWeeklyWeatherForAllLocations();
            return new JsonResponse(['message' => ['Météo mis à jour avec succès']], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => "La mise à jour de la météo n'a pu être fait: ".$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}

