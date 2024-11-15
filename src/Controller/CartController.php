<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addToCart(int $id, EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
    {
        // Récupérer le produit par son ID
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException("Produit non trouvé.");
        }

        // Récupérer l'utilisateur connecté
        $connectedUser = $this->getUser();

        // Récupérer ou créer le panier de l'utilisateur
        $carts = $connectedUser->getCarts();
        if (count($carts) > 0) {
            $cart = $carts[0]; // On suppose que l'utilisateur n'a qu'un seul panier
        } else {
            $cart = new Cart();
            $cart->setUser($connectedUser);
        }

        // Ajouter le produit au panier
        $cart->addProduct($product);

        // Marquer le produit comme vendu
        $product->setIssold(false);

        // Sauvegarder le panier et mettre à jour l'état du produit
        $entityManager->persist($cart);
        $entityManager->flush();

        $this->addFlash('success', 'Produit ajouté au panier avec succès.');

        // Rediriger vers la page du panier
        return $this->redirectToRoute('app_cart_show', [
            'id' => $cart->getId(),
        ]);
    }

    #[Route('/{id}', name: 'app_cart_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function showCart(int $id, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le panier par son ID
        $cart = $entityManager->getRepository(Cart::class)->find($id);
        if (!$cart) {
            throw $this->createNotFoundException("Panier non trouvé.");
        }

        // Vérifier que le panier appartient à l'utilisateur connecté
        /** @var User $connectedUser */
        $connectedUser = $this->getUser();
        if ($cart->getUser() !== $connectedUser) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce panier.");
        }

        // Rendre la vue avec le panier
        return $this->render('cart/show.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function removeFromCart(int $id, EntityManagerInterface $entityManager): Response
    {
        $connectedUser = $this->getUser();
        $cart = $connectedUser->getCarts()->first();

        if (!$cart) {
            throw $this->createNotFoundException("Panier non trouvé.");
        }

        $product = $entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException("Produit non trouvé.");
        }

        // Retirer le produit du panier
        $cart->removeProduct($product);

        // Marquer le produit comme non vendu
        $product->setIssold(true);

        // Sauvegarder les changements
        $entityManager->flush();

        $this->addFlash('success', 'Produit retiré du panier avec succès.');

        // Rediriger vers la page du panier
        return $this->redirectToRoute('app_cart_show', [
            'id' => $cart->getId(),
        ]);
    }

}
