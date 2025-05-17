<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Product;
use Symfony\Component\Uid\Uuid;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addToCart(int $id, EntityManagerInterface $entityManager, ProductRepository $productRepository): JsonResponse
    {
        // Récupérer le produit par son ID
        $product = $productRepository->find($id);
        if (!$product) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }

        // Vérifier si le produit est disponible
        if (!$product->getIssold()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce produit n\'est plus disponible'
            ], 400);
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

        // Vérifier si le produit n'est pas déjà dans le panier
        if ($cart->getProduct()->contains($product)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce produit est déjà dans votre panier'
            ], 400);
        }

        // Ajouter le produit au panier
        $cart->addProduct($product);

        // Sauvegarder le panier
        $entityManager->persist($cart);
        $entityManager->flush();

        // Calculer le nouveau nombre d'articles dans le panier
        $cartCount = $cart->getProduct()->count();

        return new JsonResponse([
            'success' => true,
            'message' => 'Produit ajouté au panier avec succès !',
            'cartCount' => $cartCount,
            'productName' => $product->getName()
        ]);
    }

    #[Route('/{id}', name: 'app_cart_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function showCart(int $id, EntityManagerInterface $entityManager): Response
    {
        $uuid = Uuid::v1();
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
            'uuid' => $uuid
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

    #[Route('/cart/clear/{id}', name: 'app_cart_clear', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function clearCart(int $id, EntityManagerInterface $entityManager): Response
    {
        $cart = $entityManager->getRepository(Cart::class)->find($id);

        if (!$cart) {
            throw $this->createNotFoundException("Panier non trouvé.");
        }

        $connectedUser = $this->getUser();

        // Vérifier que le panier appartient à l'utilisateur connecté
        if ($cart->getUser() !== $connectedUser) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce panier.");
        }

        // Remettre chaque produit comme non vendu
        foreach ($cart->getProduct() as $product) {
            $product->setIssold(true);
        }

        // Vider le panier
        $cart->getProduct()->clear();

        $entityManager->flush();

        $this->addFlash('success', 'Le panier a été vidé avec succès.');

        return $this->redirectToRoute('app_cart_show', ['id' => $cart->getId()]);
    }
}