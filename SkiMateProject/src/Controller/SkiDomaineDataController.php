<?php

namespace App\Controller;

use App\Document\Station;
use App\Service\SkiDomainDataFetcher;
use App\Service\SkiDomainDataTransformer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class SkiDomaineDataController extends AbstractController
{
    #[Route('/admin/domaine-data', name: 'app_admin_domaine_data', methods: ['POST'])]
    public function index(
        Request $request,
        SkiDomainDataFetcher $domainDataFetcher,
        SkiDomainDataTransformer $transformer,
        DocumentManager $documentManager
    ): Response {
        $requestData = json_decode($request->getContent(), true);
        $domainName = $requestData['domaine'] ?? null;

        if (!$domainName) {
            return $this->json(['error' => 'Le champ "domaine" est requis.'], 400);
        }

        $stationsData = $domainDataFetcher->fetchStationsForDomain($domainName);
        $imported = [];
        $featuresData = null;

        foreach ($stationsData['elements'] as $stationElement) {
            if ($stationElement['type'] === 'way') {
                $stationName = $stationElement['tags']['name'] ?? null;
                if ($stationName && $stationName !== $domainName) {
                    $wayId = $stationElement['id'];

                    // Transformer la station
                    $station = $transformer->transformStation($domainName, $stationElement, $stationsData);

                    // Vérifier si cette station existe déjà (par exemple, via osmId)
                    $existingStation = $documentManager->getRepository(Station::class)
                        ->findOneBy(['osmId' => $station->getOsmId()]);

                    if ($existingStation) {
                        // Supprimer l'ancienne version
                        $documentManager->remove($existingStation);
                        $documentManager->flush();
                    }

                    // Persister la station "vide" (sans items)
                    $documentManager->persist($station);
                    $documentManager->flush();

                    // Récupérer les features pour cette station
                    $featuresData = $domainDataFetcher->fetchFeaturesForStation($wayId);

                    // Transformer les features en items
                    $items = $transformer->transformAllItems($featuresData);
                    $station->setItems($items);

                    // Mettre à jour la station avec ses items
                    $documentManager->persist($station);
                    $documentManager->flush();

                    $imported[] = $station->getName();
                }
            }
        }

        return $this->json([
            'message' => 'Import terminé',
            'stations_importées' => $imported,
            'last_features_data' => $featuresData
        ]);
    }


    #[Route('/get-ski-domain', name: 'app_get_ski_domain', methods: ['POST'])]
    public function getSkiDomain(Request $request, DocumentManager $documentManager): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);
        $domain = $requestData['domaine'] ?? null;

        if (!$domain) {
            return $this->json(['error' => 'Le champ "domaine" est requis.'], 400);
        }

        // Récupérer toutes les stations pour ce domaine
        $stationRepository = $documentManager->getRepository(Station::class);
        $stations = $stationRepository->findBy(['domain' => $domain]);

        $features = [];

        foreach ($stations as $station) {
            // Ajouter un Feature pour la station
            $features[] = [
                'type' => 'Feature',
                'geometry' => $station->getGeometry(),
                'properties' => [
                    'type' => 'station',
                    'name' => $station->getName(),
                    'osmId' => $station->getOsmId(),
                    'tags' => $station->getTags(),
                    'validated' => $station->isValidated()
                ]
            ];

            // Ajouter un Feature pour chaque item de la station
            foreach ($station->getItems() as $item) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => $item['geometry'],
                    'properties' => array_merge(
                        [
                            'type' => $item['properties']['category'] ?? 'unknown',
                            'source' => $item['properties']['source'] ?? 'osm',
                            'validated' => $item['validated'] ?? false
                        ],
                        // On fusionne les tags pour avoir direct les infos
                        // Sinon on peut juste 'tags' => $item['properties']['tags']
                        ['tags' => $item['properties']['tags'] ?? []]
                    )
                ];
            }
        }

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        return new JsonResponse($geoJson);
    }
}
