<?php

namespace App\Controller;

use App\Repository\GalaxyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class CarouselController extends AbstractController
{
    #[Route('/carousel', name: 'app_carousel')]
    #[Cache(maxage: 3600, smaxage: 3600, public: true)]
    public function index(GalaxyRepository $galaxyRepository): Response
    {
        $galaxies = $galaxyRepository->findAllWithRelations();
        
        return $this->render('carousel/index.html.twig', [
            'carousel' => $galaxies
        ]);
    }
}
