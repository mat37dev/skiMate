<?php

namespace App\Service;

use App\Document\Snowfall;
use App\Document\WeatherForecast;
use App\Entity\SkiResort;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherApiService
{
    private HttpClientInterface $httpClient;
    private DocumentManager $documentManager;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, DocumentManager $documentManager, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->documentManager = $documentManager;
        $this->logger = $logger;
    }

    public function saveWeeklyWeatherForAllLocations(): void
    {
        // Récupérer toutes les stations (avec coordonnées) depuis la base de données
        //Attention, il faudra récupérer toutes les stations et mettre le try dans une boucle!

        //Données de test
        $location = 'La Plagne';
        $location = strtolower($location);
        // Appeler la méthode pour chaque station
        try {
            $this->saveWeeklyWeather('45.5057', '6.6803', $location);
        } catch (\Exception $e) {
            // Log en cas d'échec pour une station spécifique
            $this->logger->error("Échec de la mise à jour pour la station $location : {$e->getMessage()}");
        }
    }

    public function saveWeeklyWeather(float $latitude, float $longitude, string $location): void
    {
        $data = $this->getWeeklyWeather($latitude, $longitude);

        $existingData = $this->documentManager->getRepository(WeatherForecast::class)->findOneBy(['location' => $location]);
        if ($existingData) {
            $this->documentManager->remove($existingData);
        }
        $meteo = new WeatherForecast();
        $meteo->setLocation($location)
            ->setDate(new \DateTime())
            ->setForecasts($data);

        $this->documentManager->persist($meteo);
        $this->documentManager->flush();

        // Chercher les prévisions pour aujourd'hui pour mettre à jour les dernières tombées de neiges
        $today = (new \DateTime())->format('Y-m-d');
        $todayForecast = array_filter($data, function ($forecast) use ($today) {
            return isset($forecast['day']) && $forecast['day'] === $today;
        });

        if (!empty($todayForecast)) {
            $todayForecast = reset($todayForecast);

            $morningSnowfall = $todayForecast['morning']['snowfall'] ?? 0;
            $afternoonSnowfall = $todayForecast['afternoon']['snowfall'] ?? 0;

            $totalSnowfall = $morningSnowfall + $afternoonSnowfall;

            if ($totalSnowfall > 0) {
                $this->updateLastSnowfall($location, [
                    'snowfall' => $totalSnowfall,
                ]);
            } else {
                $this->logger->info("Aucune chute de neige aujourd'hui pour $location.");
            }
        } else {
            $this->logger->info("Aucune prévision trouvée pour aujourd'hui pour $location.");
        }
    }

    public function getWeeklyWeather(float $latitude, float $longitude): array
    {
        $url = 'https://api.open-meteo.com/v1/forecast';
        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'hourly' => 'temperature_2m,snowfall,snow_depth,weather_code,wind_speed_10m',
            'timezone' => 'Europe/Paris',
        ];

        $response = $this->httpClient->request('GET', $url, ['query' => $params]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('Erreur lors de la récupération des données météo.');
        }

        $data = $response->toArray();

        $hourlyData = $data['hourly'] ?? [];
        if (empty($hourlyData)) {
            throw new Exception('Aucune donnée horaire disponible.');
        }

        $times = array_map(fn($time) => new \DateTimeImmutable($time), $hourlyData['time']);
        $temperature = $hourlyData['temperature_2m'] ?? [];
        $snowfall = $hourlyData['snowfall'] ?? [];
        $snowDepth = $hourlyData['snow_depth'] ?? [];
        $weatherCode = $hourlyData['weather_code'] ?? [];
        $windSpeed = $hourlyData['wind_speed_10m'] ?? [];

        $dailyData = [];
        foreach ($times as $index => $time) {
            $day = $time->format('Y-m-d');
            $hour = (int) $time->format('H');

            if (!isset($dailyData[$day])) {
                $dailyData[$day] = [
                    'morning' => [],
                    'afternoon' => []
                ];
            }

            $dataPoint = [
                'temperature_2m' => $temperature[$index] ?? null,
                'snowfall' => $snowfall[$index] ?? null,
                'snow_depth' => $snowDepth[$index] ?? null,
                'weather_code' => $weatherCode[$index] ?? null,
                'wind_speed_10m' => $windSpeed[$index] ?? null,
            ];

            if ($hour >= 6 && $hour < 12) {
                $dailyData[$day]['morning'][] = $dataPoint;
            } elseif ($hour >= 12 && $hour < 18) {
                $dailyData[$day]['afternoon'][] = $dataPoint;
            }
        }

        $weeklyData = [];
        foreach (array_slice($dailyData, 0, 7) as $day => $periods) {
            $weeklyData[] = [
                'day' => $day,
                'morning' => $this->aggregatePeriodData($periods['morning']),
                'afternoon' => $this->aggregatePeriodData($periods['afternoon']),
            ];
        }

        return $weeklyData;
    }

    private function aggregatePeriodData(array $periodData): array
    {
        if (empty($periodData)) {
            return [
                'temperature_2m' => null,
                'snowfall' => null,
                'snow_depth' => null,
                'weather_code' => null,
                'wind_speed_10m' => null,
            ];
        }

        $total = count($periodData);
        $sum = [
            'temperature_2m' => 0,
            'snowfall' => 0,
            'snow_depth' => 0,
            'wind_speed_10m' => 0,
        ];
        $weatherCodes = [];

        foreach ($periodData as $data) {
            $sum['temperature_2m'] += $data['temperature_2m'] ?? 0;
            $sum['snowfall'] += $data['snowfall'] ?? 0;
            $sum['snow_depth'] += $data['snow_depth'] ?? 0;
            $sum['wind_speed_10m'] += $data['wind_speed_10m'] ?? 0;

            if (!empty($data['weather_code'])) {
                $weatherCodes[] = $data['weather_code'];
            }
        }

        // Calcul du mode pour weather_code
        $weatherCodeMode = $this->calculateMode($weatherCodes);

        return [
            'temperature_2m' => round($sum['temperature_2m'] / $total, 1),
            'snowfall' => round($sum['snowfall'] / $total, 2),
            'snow_depth' => round($sum['snow_depth'] / $total, 2),
            'weather_code' => $this->interpretWeatherCode($weatherCodeMode),
            'wind_speed_10m' => round($sum['wind_speed_10m'] / $total, 1),
        ];
    }

    private function calculateMode(array $values): ?int
    {
        if (empty($values)) {
            return null;
        }

        $counts = array_count_values($values);
        arsort($counts); // Trier par ordre décroissant
        return array_key_first($counts); // Retourne la valeur la plus fréquente
    }

    private function interpretWeatherCode(?int $code): ?array
    {
        if ($code === null) {
            return null;
        }

        return match ($code) {
            0 => ['Clair', 'ensoleille'],
            1 => ['Principalement clair', 'ensoleille'],
            2 => ['Partiellement nuageux', 'nuageux'],
            3 => ['Couvert', 'nuageux'],
            45 => ['Brouillard', 'brouillard'],
            48 => ['Brouillard givrant', 'brouillard'],
            51 => ['Bruine légère', 'pluvieux'],
            53 => ['Bruine modérée', 'pluvieux'],
            55 => ['Bruine dense', 'pluvieux'],
            56 => ['Bruine verglaçante légère', 'pluvieux'],
            57 => ['Bruine verglaçante dense', 'pluvieux'],
            61 => ['Pluie légère', 'pluvieux'],
            63 => ['Pluie modérée', 'pluvieux'],
            65 => ['Pluie forte', 'pluvieux'],
            66 => ['Pluie verglaçante légère', 'pluvieux'],
            67 => ['Pluie verglaçante forte', 'pluvieux'],
            71 => ['Neige légère', 'neigeux'],
            73 => ['Neige modérée', 'neigeux'],
            75 => ['Neige forte', 'neigeux'],
            77 => ['Grains de neige', 'neigeux'],
            80 => ['Averses de pluie légères', 'pluvieux'],
            81 => ['Averses de pluie modérées', 'pluvieux'],
            82 => ['Averses de pluie violentes', 'pluvieux'],
            85 => ['Averses de neige légères', 'neigeux'],
            86 => ['Averses de neige fortes', 'neigeux'],
            95 => ['Orage léger ou modéré', 'pluvieux'],
            96 => ['Orage avec grêle légère', 'pluvieux'],
            99 => ['Orage avec grêle forte', 'pluvieux'],
            default => ['Inconnu', 'inconnu'],
        };
    }

    public function updateLastSnowfall(string $location, array $dailyWeatherData): void
    {
        // Recherche de la station
        $snowfallRepository = $this->documentManager->getRepository(Snowfall::class);
        $snowfall = $snowfallRepository->findOneBy(['location' => $location]);

        // Vérifier si une chute de neige est prévue aujourd'hui
        $today = new \DateTime();
        $todaySnowfall = $dailyWeatherData['snowfall']; // Exemple : données de chute de neige pour aujourd'hui (en cm)

        if ($todaySnowfall > 0) {
            if (!$snowfall) {
                // Si la station n'existe pas encore, la créer
                $snowfall = new Snowfall();
                $snowfall->setLocation($location);
            }

            // Mise à jour de la dernière chute de neige
            $snowfall->setLastSnowfallDate($today)
                ->setSnowfallAmount($todaySnowfall);

            $this->documentManager->persist($snowfall);
            $this->logger->info("Dernière chute de neige mise à jour pour $location : $todaySnowfall cm");
        } else {
            $this->logger->info("Pas de chute de neige aujourd'hui pour $location.");
        }

        // Sauvegarder les changements dans MongoDB
        $this->documentManager->flush();
    }

}
