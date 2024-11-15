<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\User;
use App\Form\CartType;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route(name: 'app_cart_index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(CartRepository $cartRepository): Response
    {
        return $this->render('cart/index.html.twig', [
            'carts' => $cartRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_cart_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cart = new Cart();
        $form = $this->createForm(CartType::class, $cart);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cart);
            $entityManager->flush();

            return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cart/new.html.twig', [
            'cart' => $cart,
            'form' => $form,
        ]);
    }

    #[Route('/cart/{id}', name: 'app_cart_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function showCart(int $id, EntityManagerInterface $entityManager): Response
    {
        $cart = $entityManager->getRepository(Cart::class)->find($id);
        if (!$cart) {
            throw $this->createNotFoundException("Panier non trouvé.");
        }

        $connectedUser = $this->getUser();
        if ($cart->getUser() !== $connectedUser) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce panier.");
        }

        return $this->render('cart/show.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cart_edit', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Request $request, Cart $cart, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CartType::class, $cart);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cart/edit.html.twig', [
            'cart' => $cart,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cart_delete', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(Request $request, Cart $cart, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $cart->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($cart);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addToCart(int $id, EntityManagerInterface $entityManager, Request $request, ProductRepository $productRepository, User $user): Response
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

        $cart->setDate(new \DateTime());

        // Sauvegarder le panier
        $entityManager->persist($cart);
        $entityManager->flush();

        // Rediriger vers la page du panier ou afficher un message de succès
        return $this->redirectToRoute('app_cart_show', [
            'id' => $cart->getId()
        ]);
    }
}
