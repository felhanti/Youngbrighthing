<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Chaque client a un seul panier : on le déduit toujours de l'utilisateur connecté,
 * jamais d'un identifiant passé dans l'URL.
 */
#[Route('/cart')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_cart_show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user): Response
    {
        return $this->render('cart/show.html.twig', [
            'cart' => $this->cartRepository->getOrCreateForUser($user),
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(Product $product, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        if (!$this->isCsrfTokenValid('cart', $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['success' => false, 'message' => 'Session expirée, rechargez la page.'], Response::HTTP_FORBIDDEN);
        }

        if (!$product->isAvailable()) {
            return $this->json(['success' => false, 'message' => 'Ce produit n\'est plus disponible'], Response::HTTP_BAD_REQUEST);
        }

        $cart = $this->cartRepository->getOrCreateForUser($user);
        if ($cart->getProduct()->contains($product)) {
            return $this->json(['success' => false, 'message' => 'Ce produit est déjà dans votre panier'], Response::HTTP_BAD_REQUEST);
        }

        $cart->addProduct($product);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Produit ajouté au panier avec succès !',
            'cartCount' => $cart->getProduct()->count(),
            'productName' => $product->getName(),
        ]);
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"cart_remove" ~ args["product"].getId()'))]
    public function remove(Product $product, #[CurrentUser] User $user): Response
    {
        $this->cartRepository->getOrCreateForUser($user)->removeProduct($product);
        $this->entityManager->flush();

        $this->addFlash('success', 'Produit retiré du panier.');

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    #[IsCsrfTokenValid('cart_clear')]
    public function clear(#[CurrentUser] User $user): Response
    {
        $this->cartRepository->getOrCreateForUser($user)->getProduct()->clear();
        $this->entityManager->flush();

        $this->addFlash('success', 'Le panier a été vidé.');

        return $this->redirectToRoute('app_cart_show');
    }
}
