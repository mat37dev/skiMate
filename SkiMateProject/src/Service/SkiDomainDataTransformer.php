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

        // Construire la geometry (Polygon)
        $coords = $this->extractWayGeometry($stationElement, $stationsData);
        $station->setGeometry([
            'type' => 'Polygon',
            'coordinates' => [$coords]
        ]);

        return $station;
    }

    public function transformAllItems(array $featuresData): array
    {
        $items = [];
        foreach ($featuresData['elements'] as $element) {
            $item = $this->transformItem($element, $featuresData);
            if ($item !== null) {
                $items[] = $item;
            }
        }
        return $items;
    }

    private function transformItem(array $element, array $data): ?array
    {
        $tags = $element['tags'] ?? [];
        $category = $this->determineCategory($tags);
        if (!$category) {
            return null;
        }

        $geometry = $this->extractGeometry($element, $data);

        return [
            "type" => "Item",
            "geometry" => $geometry,
            "properties" => [
                "source" => "osm",
                "category" => $category,
                "tags" => $tags
            ],
            "validated" => false
        ];
    }

    private function determineCategory(array $tags): ?string
    {
        if (isset($tags['piste:type']) && $tags['piste:type'] === 'downhill') {
            return 'piste';
        }
        if (isset($tags['aerialway'])) {
            return 'lift';
        }
        if (isset($tags['amenity'])) {
            if ($tags['amenity'] === 'restaurant') return 'restaurant';
            if ($tags['amenity'] === 'toilets') return 'wc';
            if ($tags['amenity'] === 'picnic_site') return 'picnic';
        }
        if (isset($tags['tourism'])) {
            if ($tags['tourism'] === 'viewpoint') return 'viewpoint';
            if ($tags['tourism'] === 'information') return 'information';
        }
        return null;
    }

    private function extractWayGeometry(array $wayElement, array $data): array
    {
        $nodes = $wayElement['nodes'] ?? [];
        $coords = [];

        // Indexer tous les node par leur id
        $nodesById = [];
        foreach ($data['elements'] as $el) {
            if ($el['type'] === 'node') {
                $nodesById[$el['id']] = $el;
            }
        }

        foreach ($nodes as $nodeId) {
            if (isset($nodesById[$nodeId])) {
                $coords[] = [$nodesById[$nodeId]['lon'], $nodesById[$nodeId]['lat']];
            }
        }
        return $coords;
    }

    private function extractGeometry(array $element, array $data): array
    {
        if ($element['type'] === 'node') {
            $lon = $element['lon'];
            $lat = $element['lat'];
            return [
                'type' => 'Point',
                'coordinates' => [$lon, $lat]
            ];
        } elseif ($element['type'] === 'way') {
            // pour une piste (LineString)
            $coords = $this->extractWayGeometry($element, $data);
            return [
                'type' => 'LineString',
                'coordinates' => $coords
            ];
        }

        // Par défaut, on renvoie un type vide.
        return ['type' => 'GeometryCollection', 'coordinates' => []];
    }
}
