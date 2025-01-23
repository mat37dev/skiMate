<?php

namespace App\Controller;

use App\Document\Station;
use App\Repository\SkiLevelRepository;
use App\Service\SkiDomainDataFetcher;
use App\Service\SkiDomainDataTransformer;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
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
                    $lat = $stationElement['center']['lat'] ?? null;
                    $long = $stationElement['center']['lon'] ?? null;

                    // Transformer la station
                    $station = $transformer->transformStation($domainName, $stationElement, $stationsData);

                    // Vérifier si cette station existe déjà
                    $existingStation = $documentManager->getRepository(Station::class)
                        ->findOneBy(['osmId' => $station->getOsmId()]);

                    if ($existingStation) {
                        $documentManager->remove($existingStation);
                        $documentManager->flush();
                    }

                    // Persister la station "vide"
                    $documentManager->persist($station);
                    $documentManager->flush();

                    // Récupérer les features pour cette station
                    $featuresData = $domainDataFetcher->fetchFeaturesForStation($wayId);

                    // Transformer les features en Features GeoJSON
                    $features = $transformer->transformAllFeatures($featuresData);
                    $station->setFeatures($features);

                    $station->setLongitude($long);
                    $station->setLatitude($lat);
                    // Mettre à jour la station avec ses features
                    $documentManager->persist($station);
                    $documentManager->flush();

                    $imported[] = $station->getName();
                }
            }
        }

        $featureCollection = [
            "type" => "FeatureCollection",
            "features" => $features ?? []
        ];

        return $this->json([
            'message' => 'Import terminé',
            'stations_importées' => $imported,
            'last_features_data' => $featureCollection
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
            // Ajouter un Feature pour la station (sa géométrie de type Polygon)
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

            // Ajouter les Features propres à la station (pistes, lifts, etc.)
            // Désormais, $station->getFeatures() retourne des Features déjà conformes à GeoJSON
            foreach ($station->getFeatures() as $feature) {
                // $feature doit déjà être un tableau avec keys 'type', 'geometry', 'properties'.
                // Si c'est déjà formaté correctement, on peut l'ajouter directement :
                $features[] = $feature;
            }
        }

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        return new JsonResponse($geoJson);
    }

    #[Route('/stations', name: 'app_get_stations', methods: ['GET'])]
    public function getStationList(DocumentManager $documentManager, Request $request): JsonResponse
    {
        // Paramètre de recherche "q"
        $searchTerm = $request->query->get('q', '');

        // Repository par défaut pour la classe Station
        $stationRepository = $documentManager->getRepository(Station::class);

        if (!empty($searchTerm)) {
            // Recherche partielle insensible à la casse via un Regex
            $regex = new Regex($searchTerm, 'i');

            // On construit la requête
            $stationsCursor = $stationRepository
                ->createQueryBuilder()
                ->field('name')->equals($regex)
                ->getQuery()
                ->execute();

            // On convertit le cursor en tableau
            $stations = $stationsCursor->toArray();
        } else {
            // Si pas de terme de recherche, on récupère tout
            $stations = $stationRepository->findAll();
        }

        // On formate le résultat
        $results = [];
        foreach ($stations as $station) {
            $results[] = [
                'name'  => $station->getName(),
                'osmId' => $station->getOsmId(),
            ];
        }

        return new JsonResponse($results);
    }
}
