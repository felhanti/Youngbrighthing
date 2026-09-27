<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * robots.txt et sitemap.xml générés à partir du catalogue, pour aider Google
 * à trouver chaque pièce dès sa mise en ligne.
 */
final class SeoController extends AbstractController
{
    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'])]
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            // Pages privées ou sans intérêt pour un moteur de recherche.
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /cart',
            'Disallow: /order',
            'Disallow: /orders',
            'Disallow: /reset-password',
            'Disallow: /liste-attente',
            '',
            'Sitemap: '.$this->generateUrl('app_sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        return new Response(implode("\n", $lines)."\n", headers: ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'])]
    public function sitemap(ProductRepository $products, CategoryRepository $categories): Response
    {
        $response = $this->render('seo/sitemap.xml.twig', [
            'products' => $products->findBy([], ['add_date' => 'DESC', 'id' => 'DESC']),
            'drops' => $categories->findAll(),
        ]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
