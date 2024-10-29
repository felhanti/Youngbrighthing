<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('homepage/index.html.twig', [
            'controller_name' => 'HomepageController',
        ]);
    }

    #[Route('/cgv/cgu', name: 'app_cgv_cgu')]
    public function cgu(): Response
    {
        return $this->render('cgv/cgu.html.twig', [
            'controller_name' => 'HomepageController',
        ]);

    }

    #[Route('/cgv/confidentialite', name: 'app_cgv_confidentialite')]
    public function confidentialite(): Response
    {
        return $this->render('cgv/confidentialite.html.twig');
    }

    #[Route('/cgv/mentions-legales', name: 'app_cgv_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('cgv/mentions_legales.html.twig');
    }

    #[Route('/cgv/retour', name: 'app_cgv_retour')]
    public function retour(): Response
    {
        return $this->render('cgv/retour.html.twig');
    }
}