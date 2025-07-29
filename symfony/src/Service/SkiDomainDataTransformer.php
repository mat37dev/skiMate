<?php

namespace App\Service;

use App\Document\Station;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SkiDomainDataTransformer
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    /**
     * @param HttpClientInterface $httpClient
     * @param LoggerInterface $logger
     */
    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }


    public function transformStation(string $domainName, array $stationElement, array $stationsData): Station
    {
        $station = new Station();
        $station->setDomain($domainName);
        $station->setOsmId((string)$stationElement['id']);
        $station->setName($stationElement['tags']['name'] ?? 'Station sans nom');
        $station->setTags($stationElement['tags'] ?? []);
        $station->setWebsite($stationElement['tags']['website'] ?? null);
        $station->setEmergencyPhone($stationElement['tags']['emergencyPhone'] ?? '');

        // Extraire et normaliser la géométrie
        $coords = $this->extractWayGeometry($stationElement, $stationsData);
        $station->setGeometry([
            'type' => 'LineString',
            'coordinates' => $coords
        ]);
        return $station;
    }

    public function transformAllFeatures(array $featuresData): array
    {
        $features = [];
        foreach ($featuresData['elements'] as $element) {
            $feature = $this->transformFeature($element, $featuresData);
            if ($feature !== null) {
                $features[] = $feature;
            }
        }
        return $features;
    }

    private function transformFeature(array $element, array $data): ?array
    {
        $tags = $element['tags'] ?? [];
        $category = $this->determineCategory($tags);

        if (!$category) {
            return null;
        }

        $geometry = $this->extractGeometry($element, $data);

        // Ajouter un champ d'orientation calculé
        if (in_array($category, ['run', 'lift'])) {
            $orientation = $this->calculateOrientation($geometry);
        } else {
            $orientation = 'unknown';
        }

        return [
            "type" => "Feature",
            "geometry" => $geometry,
            "properties" => [
                "source" => "osm",
                "category" => $category,
                "name" => $tags['name'] ?? 'Sans nom',
                "difficulty" => $tags['piste:difficulty'] ?? null,
                "tags" => $tags,
                "orientation" => $orientation // Ajout de l'orientation
            ]
        ];
    }

    private function determineCategory(array $tags): ?string
    {
        if (isset($tags['piste:type']) && $tags['piste:type'] === 'downhill') {
            return 'run';
        }
        if (isset($tags['aerialway'])) {
            return 'lift';
        }
        return null;
    }

    private function extractWayGeometry(array $wayElement, array $data): array
    {
        $nodes = $wayElement['nodes'] ?? [];
        $coords = [];

        // Indexer les nœuds par leur ID
        $nodesById = [];
        foreach ($data['elements'] as $el) {
            if ($el['type'] === 'node') {
                $nodesById[$el['id']] = $el;
            }
        }

        // Construire les coordonnées dans l'ordre des nœuds
        foreach ($nodes as $nodeId) {
            if (isset($nodesById[$nodeId])) {
                $coords[] = [$nodesById[$nodeId]['lon'], $nodesById[$nodeId]['lat']];
            }
        }

        // Normaliser l'ordre pour s'assurer que le point le plus haut est en premier
        if (count($coords) > 1) {
            $highestPointIndex = array_search(max(array_column($coords, 1)), array_column($coords, 1));
            $lowestPointIndex = array_search(min(array_column($coords, 1)), array_column($coords, 1));

            if ($highestPointIndex > $lowestPointIndex) {
                $coords = array_reverse($coords);
            }
        }

        return $coords;
    }

    private function extractGeometry(array $element, array $data): array
    {
        if ($element['type'] === 'node') {
            return [
                'type' => 'Point',
                'coordinates' => [$element['lon'], $element['lat']]
            ];
        } elseif ($element['type'] === 'way') {
            return [
                'type' => 'LineString',
                'coordinates' => $this->extractWayGeometry($element, $data)
            ];
        }

        return ['type' => 'GeometryCollection', 'coordinates' => []];
    }

    public function calculateOrientation(array $geometry): string
    {
        set_time_limit(300);
        // Vérifier que la géométrie est de type LineString et possède au moins deux points
        if ($geometry['type'] !== 'LineString' || count($geometry['coordinates']) < 2) {
            return 'unknown';
        }

        $firstPoint = $geometry['coordinates'][0];
        $lastPoint = $geometry['coordinates'][count($geometry['coordinates']) - 1];

        // Récupérer l'altitude pour le premier et le dernier point en une seule requête
        $altitudes = $this->getAltitudesForCoordinates([$firstPoint, $lastPoint]);

        if (count($altitudes) < 2) {
            return 'unknown';
        }

        $firstAlt = $altitudes[0];
        $lastAlt = $altitudes[1];

        if ($firstAlt < $lastAlt) {
            return 'asc';
        } elseif ($firstAlt > $lastAlt) {
            return 'desc';
        } else {
            return 'flat';
        }
    }

    private function getAltitudesForCoordinates(array $coordinates): array
    {
        set_time_limit(300);
        // On s'attend à recevoir un tableau de coordonnées, chaque élément étant un tableau [lon, lat]
        $lons = [];
        $lats = [];
        foreach ($coordinates as $coord) {
            $lons[] = $coord[0];
            $lats[] = $coord[1];
        }

        // Construit les paramètres en séparant les valeurs par "|"
        $delimiter = '|';
        $lonParam = implode($delimiter, $lons);
        $latParam = implode($delimiter, $lats);

        $url = 'https://data.geopf.fr/altimetrie/1.0/calcul/alti/rest/elevation.json';
        try {
            $response = $this->httpClient->request('GET', $url, [
                'query' => [
                    'lon'       => $lonParam,
                    'lat'       => $latParam,
                    'resource'  => 'ign_rge_alti_wld', // Spécifie la ressource altimétrique
                    'delimiter' => $delimiter,         // Spécifie le délimiteur utilisé
                    'zonly'     => 'true'              // Pour obtenir une réponse simple: {"elevations": [alt1, alt2]}
                ]
            ]);
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                if (isset($data['elevations']) && is_array($data['elevations'])) {
                    return $data['elevations'];
                }
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des altitudes pour les points ($latParam / $lonParam) : " . $e->getMessage());
        }

        return [];
    }
}
