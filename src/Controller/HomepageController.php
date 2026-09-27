<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomepageController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    #[Route('/', name: 'home')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $drops = $categoryRepository->findBy([], ['id' => 'DESC']);

        return $this->render('homepage/index.html.twig', [
            'drops' => $drops,
            'heroDrop' => $categoryRepository->findOneBy(['Last' => true]) ?? ($drops[0] ?? null),
            'featuredProducts' => $this->latestProducts(4),
        ]);
    }

    #[Route('/product/{id}', name: 'app_product', requirements: ['id' => '\d+'])]
    public function product(Product $product): Response
    {
        return $this->render('product/index.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/collection/all', name: 'app_collection_all')]
    public function collection(): Response
    {
        return $this->render('collection/index.html.twig', [
            'products' => $this->productRepository->findBy([], ['add_date' => 'DESC', 'id' => 'DESC']),
        ]);
    }

    #[Route('/drop/{name}', name: 'app_drop')]
    public function drop(#[MapEntity(mapping: ['name' => 'Name'])] Category $category): Response
    {
        return $this->render('drop/index.html.twig', [
            'category' => $category,
            'products' => $category->getProducts(),
        ]);
    }

    // Pages éditoriales : seules les premières photos produits servent d'illustrations.

    #[Route('/campagne', name: 'app_campagne')]
    public function campagne(): Response
    {
        return $this->render('campagne/index.html.twig', ['products' => $this->latestProducts(3)]);
    }

    #[Route('/histoire', name: 'app_histoire')]
    public function histoire(): Response
    {
        return $this->render('histoire/index.html.twig', ['products' => $this->latestProducts(4)]);
    }

    // Pages légales.

    #[Route('/cgv/cgu', name: 'app_cgv_cgu')]
    public function cgu(): Response
    {
        return $this->render('cgv/cgu.html.twig');
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

    /** @return Product[] */
    private function latestProducts(int $limit): array
    {
        return $this->productRepository->findBy([], ['add_date' => 'DESC', 'id' => 'DESC'], $limit);
    }
}
