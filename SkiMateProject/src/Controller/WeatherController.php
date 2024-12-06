<?php

namespace App\Controller;


use App\Document\WeatherForecast;
use App\Service\WeatherApiService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class WeatherController extends AbstractController
{
    #[Route('/weather', name: 'weather_snow', methods: ['POST'])]
    public function getSnow(Request $request, DocumentManager $documentManager): JsonResponse
    {
        // Récupérer le body de la requête POST (attend un JSON contenant 'location')
        $data = json_decode($request->getContent(), true);
        $location = $data['location'] ?? null;

        if (!$location) {
            return $this->json(['error' => 'La location est requise.'], 400);
        }
        // Rechercher les données météo dans la base de données pour la location
        $weatherData = $documentManager->getRepository(WeatherForecast::class)->findOneBy(['location' => strtolower($location)]);

        if (!$weatherData) {
            return $this->json(['error' => "Aucune donnée météo trouvée pour la location $location."], 404);
        }

        // Retourner les prévisions
        return $this->json([
            'location' => $weatherData->getLocation(),
            'date' => $weatherData->getDate(),
            'forecasts' => $weatherData->getForecasts(),
        ]);
    }

}
