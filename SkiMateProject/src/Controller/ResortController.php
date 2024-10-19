<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ResortData;


class ResortController extends AbstractController
{
    public function __construct(private readonly ResortData $resortData)
    {
    }

    #[Route('/api/resortData', name: 'app_api')]
    public function index(): Response
    {
        $skiResort = $this->resortData->getResortData();
        $skiResortData = [];

        foreach ($skiResort["docs"] as $resort) {
            $skiResortData[] = [
                'name' => $resort['NOM'],
                'coordinates' => $resort['geometry']['coordinates'],
                'type' => $resort['TYPE']
            ];
        }
        return $this->json($skiResortData);
    }
}