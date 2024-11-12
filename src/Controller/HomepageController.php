<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        return $this->render('homepage/index.html.twig', [
            'category' => $categoryRepository->findAll(),
            'products' => $productRepository->findAll(),
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

    #[Route('/product/{id}', name: 'app_product')]
    public function product(int $id, CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        // Récupère le produit correspondant à l'id donné
        $product = $productRepository->find($id);

        // Vérifie si le produit existe
        if (!$product) {
            throw $this->createNotFoundException('Le produit demandé n\'existe pas.');
        }

        return $this->render('product/index.html.twig', [
            'category' => $categoryRepository->findAll(),
            'product' => $product, // envoie le produit unique, pas tous les produits
        ]);
    }

    #[Route('/collection/all', name: 'app_collection_all')]
    public function collection(ProductRepository $productRepository): Response
    {
        return $this->render('collection/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/drop/{name}', name: 'app_drop')]
    public function drop(string $name, CategoryRepository $categoryRepository): Response
    {
        // Récupérer la catégorie par son nom
        $category = $categoryRepository->findOneBy(['Name' => $name]);
        if (!$category) {
            throw $this->createNotFoundException('La catégorie demandée n\'existe pas.');
        }

        $products = $category->getProducts(); // Utilisation de la relation ManyToMany

        return $this->render('drop/index.html.twig', [
            'products' => $products,
            'category' => $category,
            
        ]);
    }




}