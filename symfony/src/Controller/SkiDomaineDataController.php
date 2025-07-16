<?php

namespace App\Controller;

use App\Document\Station;
use App\Service\SkiDomainDataFetcher;
use App\Service\SkiDomainDataTransformer;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
            return $this->json(['errors' => 'Le champ "domaine" est requis.'], 400);
        }

        $stationsData = $domainDataFetcher->fetchStationsForDomain($domainName);
        $imported = [];

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

                    try {
                        $hamlets = $domainDataFetcher->fetchHamletsForStation($stationName);
                    } catch (\Exception $e) {
                        // Si la requête échoue (timeout Overpass, etc.), on continue sans hamlets
                        $hamlets = [];
                    }
                    // -> on suppose que l’entité Station a une méthode setCities() /par setHamlets()
                    $station->setCity($hamlets);

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
            'stations_importees' => $imported,
            'last_features_data' => $featureCollection
        ]);
    }

    #[Route('/admin/skiResort-data', name: 'app_admin_skiResort_data', methods: ['POST'])]
    public function getSkiResortData(Request $request, SkiDomainDataFetcher $domainDataFetcher,
                                     SkiDomainDataTransformer $transformer, DocumentManager $documentManager): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $skiResortName = $requestData['skiResort'] ?? null;

        if (!$skiResortName) {
            return $this->json(['errors' => 'Le champ "skiResort" est requis.'], 400);
        }
        $stationRepository = $documentManager->getRepository(Station::class);
        $station = $stationRepository->findOneBy(['name' => $skiResortName]);
        if($station){
            return $this->json(['errors' => 'Une station porte déjà ce nom.'], 400);
        }

        $stationsData = $domainDataFetcher->fetchStationsForDomain($skiResortName);
        $imported = [];

        foreach ($stationsData['elements'] as $stationElement) {
            if ($stationElement['type'] === 'way') {
                $stationName = $stationElement['tags']['name'] ?? null;
                if ($stationName == $skiResortName) {
                    $wayId = $stationElement['id'];
                    $lat = $stationElement['center']['lat'] ?? null;
                    $long = $stationElement['center']['lon'] ?? null;

                    // Transformer la station
                    $station = $transformer->transformStation($skiResortName, $stationElement, $stationsData);

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
            'stations_importees' => $imported,
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
                    'validated' => $station->isValidated(),
                    'city'=> $station->getCity(),
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
        // Récupération des paramètres de recherche
        $searchTerm = $request->query->get('q', '');
        $domainTerm = $request->query->get('domain', ''); // optionnel

        // Accès au repository
        $stationRepository = $documentManager->getRepository(Station::class);

        // Création du query builder
        $qb = $stationRepository->createQueryBuilder();

        // Si un terme de recherche est fourni, appliquer un filtre sur le nom (insensible à la casse)
        if (!empty($searchTerm)) {
            $regex = new Regex($searchTerm, 'i');
            $qb->field('name')->equals($regex);
        }

        // Si un domaine est fourni, on filtre sur le champ 'domain'
        if (!empty($domainTerm)) {
            // On peut par exemple convertir le domaine en minuscules pour être cohérent
            $qb->field('domain')->equals($domainTerm);
        }

        // Exécution de la requête
        $stationsCursor = $qb->getQuery()->execute();
        $stations = $stationsCursor->toArray();

        // Formattage du résultat
        $results = [];
        foreach ($stations as $station) {
            $results[] = [
                'name'  => $station->getName(),
                'osmId' => $station->getOsmId(),
                'logo'=> $station->getLogo(),
                'domain'=> $station->getDomain(),
                'website' => $station->getWebsite(),
                'emergencyPhone' => $station->getEmergencyPhone(),
                'altitudeMin'=> $station->getAltitudeMin(),
                'altitudeMax'=> $station->getAltitudeMax(),
                'latitude'=> $station->getLatitude(),
                'longitude'=> $station->getLongitude(),
                'distanceSlope'=> $station->getDistanceSlope(),
                'countEasy'=> $station->getCountEasy(),
                'countIntermediate'=> $station->getCountIntermediate(),
                'countAdvanced'=> $station->getCountAdvanced(),
                'countExpert'=> $station->getCountExpert(),
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/station/information', name: 'app_get_station_information', methods: ['POST'])]
    public function getStationInformations(DocumentManager $documentManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if(!isset($data["osmId"])){
            return $this->json(['error' => 'Vous devez renseigner une station.'], Response::HTTP_BAD_REQUEST);
        }
        $stationRepository = $documentManager->getRepository(Station::class);
        $station = $stationRepository->findOneBy(['osmId' => $data["osmId"]]);
        if (empty($station)) {
            return $this->json(['error' => "La station n'a pas été trouvé."], Response::HTTP_BAD_REQUEST);
        }


        $results = [
            'osmId' => $station->getOsmId(),
            'name' => $station->getName(),
            'domain' => $station->getDomain(),
            'website' => $station->getWebsite(),
            'emergencyPhone' => $station->getEmergencyPhone(),
            'altitudeMin'=> $station->getAltitudeMin(),
            'altitudeMax'=> $station->getAltitudeMax(),
            'latitude'=> $station->getLatitude(),
            'longitude'=> $station->getLongitude(),
            'distanceSlope'=> $station->getDistanceSlope(),
            'countEasy'=> $station->getCountEasy(),
            'countIntermediate'=> $station->getCountIntermediate(),
            'countAdvanced'=> $station->getCountAdvanced(),
            'countExpert'=> $station->getCountExpert(),
            'logo'=> $station->getLogo()
        ];
        return $this->json($results, 200);
    }

    #[Route('/admin/edit/station', name: 'app_edit_station', methods: ['POST'])]
    public function editStation(Request $request, DocumentManager $documentManager, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if(!isset($data["osmId"])){
            return $this->json(['error' => "Vous devez renseigner le osmId."], Response::HTTP_BAD_REQUEST);
        }
        $stationRepository = $documentManager->getRepository(Station::class);
        $station = $stationRepository->findOneBy(['osmId' => $data["osmId"]]);
        if (empty($station)) {
            return $this->json(['error' => "La station n'a pas été trouvé."], Response::HTTP_BAD_REQUEST);
        }

        if(isset($data["name"])){
            $station->setName($data["name"]);
        }
        else{
            return $this->json(['error' => "Vous devez fournir un nom de station."], Response::HTTP_BAD_REQUEST);
        }
        if(isset($data["domain"]))
        {
            $station->setDomain($data["domain"]);
        }
        else{
            return $this->json(['error' => "Vous devez fournir un nom de domaine."], Response::HTTP_BAD_REQUEST);
        }
        if(isset($data["website"])){
            $station->setWebsite($data["website"]);
        }
        if(isset($data["emergencyPhone"])){
            $station->setEmergencyPhone($data["emergencyPhone"]);
        }
        if(isset($data["altitudeMin"])){
            $station->setAltitudeMin($data["altitudeMin"]);
        }
        if(isset($data["altitudeMax"])){
            $station->setAltitudeMax($data["altitudeMax"]);
        }
        if(isset($data["latitude"])){
            $station->setLatitude($data["latitude"]);
        }
        else{
            return $this->json(['error' => "Vous devez fournir la latitude de la station."], Response::HTTP_BAD_REQUEST);
        }
        if(isset($data["longitude"])){
            $station->setLongitude($data["longitude"]);
        }
        else{
            return $this->json(['error' => "Vous devez fournir la longitude de la station."], Response::HTTP_BAD_REQUEST);
        }
        if(isset($data["distanceSlope"])){
            $station->setDistanceSlope($data["distanceSlope"]);
        }
        if(isset($data["countEasy"])){
            $station->setCountEasy($data["countEasy"]);
        }
        if(isset($data["countIntermediate"])){
            $station->setCountIntermediate($data["countIntermediate"]);
        }
        if(isset($data["countAdvanced"])){
            $station->setCountAdvanced($data["countAdvanced"]);
        }
        if(isset($data["countExpert"])){
            $station->setCountExpert($data["countExpert"]);
        }

        $errors = $validator->validate($station);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorsArray], Response::HTTP_BAD_REQUEST);
        }

        $documentManager->persist($station);
        $documentManager->flush();

        return new JsonResponse(['message' => 'Station mise à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/admin/delete/station', name: 'app_delete_station', methods: ['POST'])]
    public function deleteStation(Request $request, DocumentManager $documentManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if(!isset($data["osmId"])){
            return $this->json(['error' => "Vous devez renseigner le osmId."], Response::HTTP_BAD_REQUEST);
        }
        $stationRepository = $documentManager->getRepository(Station::class);
        $station = $stationRepository->findOneBy(['osmId' => $data["osmId"]]);
        if (empty($station)) {
            return $this->json(['error' => "La station n'a pas été trouvé."], Response::HTTP_BAD_REQUEST);
        }
        $documentManager->remove($station);
        $documentManager->flush();
        return new JsonResponse(['message' => 'Station supprimé avec succès'], Response::HTTP_OK);
    }

    #[Route('/admin/upload/logo', name: 'app_upload_logo', methods: ['POST'])]
    public function uploadLogo(Request $request, DocumentManager $dm, LoggerInterface $logger): JsonResponse
    {
        // 1) Récupération du fichier et de l’ID OSM
        /** @var UploadedFile|null $file */
        $file  = $request->files->get('logo');
        $osmId = $request->request->get('osmId');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier n’a été téléchargé.'], Response::HTTP_BAD_REQUEST);
        }
        if (!$osmId) {
            return $this->json(['error' => 'Vous devez renseigner le osmId.'], Response::HTTP_BAD_REQUEST);
        }

        // 2) Récupération de la station
        $station = $dm->getRepository(Station::class)->findOneBy(['osmId' => $osmId]);
        if (!$station) {
            return $this->json(['error' => 'La station n’a pas été trouvée.'], Response::HTTP_BAD_REQUEST);
        }

        // 3) Validation du type MIME
        $allowed = ['image/jpeg','image/png','image/gif'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return $this->json(['error' => 'Type de fichier non autorisé.'], Response::HTTP_BAD_REQUEST);
        }

        // 4) Suppression de l’ancien logo
        $old = $station->getLogo();
        if ($old) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logos';
            $oldFile   = $uploadDir . '/' . basename($old);
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        // 5) Préparation du dossier de stockage
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logos';
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                // Échec de la création du dossier
                $logger->error("Impossible de créer le répertoire $uploadDir");
                return $this->json(['error' => 'Erreur serveur interne.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // 6) Génération d’un nom de fichier « safe »
        $orig         = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safe         = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $orig);
        $newFilename  = sprintf('%s-%s.%s', $safe, uniqid(), $file->guessExtension());

        // 7) Déplacement du fichier
        try {
            $file->move($uploadDir, $newFilename);
        } catch (FileException $e) {
            $logger->error('Erreur lors du téléchargement du logo : '.$e->getMessage());
            return $this->json(['error' => 'Erreur lors du téléchargement du fichier.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 8) Mise à jour de l’entité et sauvegarde
        $url = '/uploads/logos/'.$newFilename;
        $station->setLogo($url);
        $dm->persist($station);
        $dm->flush();

        return $this->json([
            'message' => 'Logo enregistré avec succès.',
            'logoUrl' => $url,
        ], Response::HTTP_OK);
    }
}
