<?php

namespace App\Controller;

use App\Service\CartToOrderService;
use App\Repository\OrderRepository;
use App\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private CartToOrderService $cartToOrderService;
    private CartRepository $cartRepository;

    public function __construct(CartToOrderService $cartToOrderService, CartRepository $cartRepository)
    {
        $this->cartToOrderService = $cartToOrderService;
        $this->cartRepository = $cartRepository;
    }

    #[Route("/order/finalize", name: "order_finalize")]
    public function finalizeOrder(): Response
    { 
        $user = $this->getUser();

        // Vérifie que l'utilisateur est connecté
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour finaliser votre commande.');
            return $this->redirectToRoute('app_login');
        }

        // Récupère le panier de l'utilisateur
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$cart || $cart->getProduct()->isEmpty()) {
            $this->addFlash('danger', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_show', ['id' => $cart->getId()]);
        }

        try {
            // Convertit le panier en commande
            $order = $this->cartToOrderService->createOrderFromCart($user, $cart); // Passer le panier et l'utilisateur ici

            $this->addFlash('success', 'Votre commande a été finalisée avec succès.');
            return $this->redirectToRoute('payement_stripe', ['orderId' => $order->getId()]);
        } catch (\Exception $e) {
            // En cas d'erreur, renvoie l'utilisateur à la vue du panier
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_cart_show', [
                'id' => $cart->getId()
            ]);
        }
    }

    #[Route("/order/summary/{id}", name: "order_summary")]
    public function orderSummary(int $id, OrderRepository $orderRepository): Response
    { 
        // Récupère la commande par son ID
        $order = $orderRepository->find($id);

        // Vérifie que la commande existe et appartient à l'utilisateur connecté
        if (!$order || $order->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Commande introuvable ou accès non autorisé.');
            return $this->redirectToRoute('home');
        }

        // Affiche le résumé de la commande
        return $this->render('order/summary.html.twig', [
            'order' => $order,
        ]);
    }
    #[Route("/orders", name: "order_list")]
    public function orderList(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();

        // Récupère toutes les commandes de l'utilisateur
        $orders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('order/list.html.twig', [
            'orders' => $orders,
        ]);
    }
}
