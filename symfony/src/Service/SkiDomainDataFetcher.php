<?php

namespace App\Service;

use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SkiDomainDataFetcher
{
    private HttpClientInterface $httpClient;
    private string $overpassUrl = 'https://overpass-api.de/api/interpreter';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Récupère les stations de ski (way avec landuse=winter_sports)
     * pour un domaine skiable.
     *
     * @param string $domainName Nom du domaine skiable (ex: "Paradiski")
     * @return array Données OSM brutes, contenant les ways représentant les stations
     */
    public function fetchStationsForDomain(string $domainName): array
    {
        $query = $this->buildStationsQuery($domainName);

        $response = $this->httpClient->request('POST', $this->overpassUrl, [
            'body' => ['data' => $query],
            'timeout' => 30,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Erreur lors de la récupération des stations pour le domaine skiable '$domainName'");
        }

        return json_decode($response->getContent(), true);
    }

    /**
     * Pour une station identifiée par l'id du way OSM (ex: way(id)),
     * récupère toutes les features (pistes, remontées, restaurants, toilettes,
     * aires de pique-nique, points de vue, informations touristiques)
     * présentes dans la zone de la station.
     *
     * @param int $wayId L'id du way OSM représentant la station
     * @return array Données OSM brutes pour les features
     */
    public function fetchFeaturesForStation(int $wayId): array
    {
        $query = $this->buildFeaturesQueryForStation($wayId);

        $response = $this->httpClient->request('POST', $this->overpassUrl, [
            'body' => ['data' => $query],
            'timeout' => 30,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Erreur lors de la récupération des features pour la station $wayId");
        }

        return json_decode($response->getContent(), true);
    }

    /**
     * Construit la requête Overpass pour récupérer les stations (ways avec landuse=winter_sports)
     * dans la zone du domaine.
     */
    private function buildStationsQuery(string $domainName): string
    {
        return <<<OVERPASS
[out:json][timeout:25];
area["name"="$domainName"]->.searchArea;
way(area.searchArea)["landuse"="winter_sports"];
out body center;
>;
out skel qt;
OVERPASS;
    }

    /**
     * Construit la requête Overpass pour récupérer les features d'une station.
     * On part du way représentant la station, on utilise map_to_area, puis on recherche
     * les différentes catégories (pistes, remontées, restaurants, etc.) dans cette area.
     */
    private function buildFeaturesQueryForStation(int $wayId): string
    {
        // Ici, on part du principe que le way représente la station.
        // On le transforme en area, puis on récupère :
        // - Pistes (piste:type=downhill)
        // - Remontées (aerialway)
        // - Restaurants (amenity=restaurant)
        // - Toilettes (amenity=toilets)
        // - Aires de pique-nique (amenity=picnic_site)
        // - Points de vue (tourism=viewpoint)
        // - Informations touristiques (tourism=information)

        return <<<OVERPASS
[out:json][timeout:25];
way($wayId);
map_to_area->.stationArea;
(
  node(area.stationArea)["piste:type"="downhill"];
  way(area.stationArea)["piste:type"="downhill"]["area"!="yes"];
  
  node(area.stationArea)["aerialway"];
  way(area.stationArea)["aerialway"]["area"!="yes"];

  node(area.stationArea)["amenity"="restaurant"];
  way(area.stationArea)["amenity"="restaurant"]["area"!="yes"];

  node(area.stationArea)["amenity"="toilets"];
  way(area.stationArea)["amenity"="toilets"]["area"!="yes"];

  node(area.stationArea)["amenity"="picnic_site"];
  way(area.stationArea)["amenity"="picnic_site"]["area"!="yes"];

  node(area.stationArea)["tourism"="viewpoint"];
  way(area.stationArea)["tourism"="viewpoint"]["area"!="yes"];

  node(area.stationArea)["tourism"="information"];
  way(area.stationArea)["tourism"="information"]["area"!="yes"];
);
out body;
>;
out skel qt;

OVERPASS;
    }

    /**
     * Récupère les “hamlets” (villes ou quartiers) associés à une station.
     *
     * @param string $stationName Nom de la station (ex : "La Plagne")
     * @return array Tableau de hameaux, chacun contenant ['id' => <osmId>, 'name' => <nom>, 'lat' => <latitude>, 'lon' => <longitude>]
     * @throws Exception en cas d’échec de la requête Overpass
     */
    public function fetchHamletsForStation(string $stationName): array
    {
        // On construit la requête Overpass : on cherche tous les nodes "place=hamlet"
        // dans l’aire correspondant à la station (way avec name="$stationName").
        $query = <<<OVERPASS
[out:json][timeout:25];
area["name"="$stationName"]->.searchArea;
node(area.searchArea)["place"="hamlet"];
out body;
>;
out skel qt;
OVERPASS;

        $response = $this->httpClient->request('POST', $this->overpassUrl, [
            'body'    => ['data' => $query],
            'timeout' => 30,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Erreur lors de la récupération des hamlets pour la station '$stationName'");
        }

        $data = json_decode($response->getContent(), true);
        $hamlets = [];

        if (isset($data['elements']) && is_array($data['elements'])) {
            foreach ($data['elements'] as $el) {
                // On ne garde que les nodes avec un tag "name" (et présumément "place"="hamlet")
                if ($el['type'] === 'node'
                    && isset($el['tags']['name'])
                    && isset($el['lat'], $el['lon'])
                ) {
                    $hamlets[] = [
                        'id'   => (string) $el['id'],
                        'name' => $el['tags']['name'],
                        'lat'  => $el['lat'],
                        'lon'  => $el['lon'],
                    ];
                }
            }
        }

        return $hamlets;
    }
}

