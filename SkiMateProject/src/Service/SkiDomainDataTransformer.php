<?php

namespace App\Service;

use App\Document\Station;

class SkiDomainDataTransformer
{
    public function transformStation(string $domainName, array $stationElement, array $stationsData): Station
    {
        $station = new Station();
        $station->setDomain($domainName);
        $station->setOsmId((string)$stationElement['id']);
        $station->setName($stationElement['tags']['name'] ?? 'Station sans nom');
        $station->setTags($stationElement['tags'] ?? []);

        // Extraire et normaliser la géométrie
        $coords = $this->extractWayGeometry($stationElement, $stationsData);
        $station->setGeometry([
            'type' => 'LineString',
            'coordinates' => $coords
        ]);

        $station->setLatitude($coords[0][1]);
        $station->setLongitude($coords[0][0]);
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
        $orientation = $this->calculateOrientation($geometry);

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

    private function calculateOrientation(array $geometry): float
    {
        if ($geometry['type'] === 'LineString' && count($geometry['coordinates']) > 1) {
            $firstPoint = $geometry['coordinates'][0];
            $lastPoint = $geometry['coordinates'][count($geometry['coordinates']) - 1];

            // Calculer l'angle entre le point de départ et d'arrivée
            $deltaX = $lastPoint[0] - $firstPoint[0];
            $deltaY = $lastPoint[1] - $firstPoint[1];
            $angle = rad2deg(atan2($deltaY, $deltaX));

            // Normaliser l'angle entre 0 et 360°
            return ($angle + 360) % 360;
        }

        return 0.0; // Angle par défaut pour des géométries invalides
    }

}
