<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Product;
use Symfony\Component\Uid\Uuid;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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
        try {
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
            /** @var User $connectedUser */
            $connectedUser = $this->getUser();

            // Récupérer ou créer le panier de l'utilisateur
            $cart = null;
            $carts = $connectedUser->getCarts();
            
            if (count($carts) > 0) {
                $cart = $carts->first();
            } else {
                $cart = new Cart();
                $cart->setUser($connectedUser);
                $entityManager->persist($cart);
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

            // Sauvegarder les modifications
            $entityManager->flush();

            // Calculer le nouveau nombre d'articles dans le panier
            $cartCount = $cart->getProduct()->count();

            return new JsonResponse([
                'success' => true,
                'message' => 'Produit ajouté au panier avec succès !',
                'cartCount' => $cartCount,
                'productName' => $product->getName()
            ]);

        } catch (\Exception $e) {
            // Log l'erreur pour le debugging
            error_log('Erreur ajout panier: ' . $e->getMessage());
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'ajout au panier'
            ], 500);
        }
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
        /** @var User $connectedUser */
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

        /** @var User $connectedUser */
        $connectedUser = $this->getUser();

        // Vérifier que le panier appartient à l'utilisateur connecté
        if ($cart->getUser() !== $connectedUser) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce panier.");
        }

        // Vider le panier
        $cart->getProduct()->clear();

        $entityManager->flush();

        $this->addFlash('success', 'Le panier a été vidé avec succès.');

        return $this->redirectToRoute('app_cart_show', ['id' => $cart->getId()]);
    }
}