<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use App\Service\CartToOrderService;
use App\Repository\OrderRepository;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private CartToOrderService $cartToOrderService;
    private CartRepository $cartRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(CartToOrderService $cartToOrderService, CartRepository $cartRepository, EntityManagerInterface $entityManager)
    {
        $this->cartToOrderService = $cartToOrderService;
        $this->cartRepository = $cartRepository;
        $this->entityManager = $entityManager;
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
    public function orderSummary(int $id, Request $request, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        if (!$order || $order->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Commande introuvable ou accès non autorisé.');
            return $this->redirectToRoute('home');
        }

        // Vérifie le paiement Stripe et marque la commande comme terminée
        $sessionId = $request->query->get('session_id');
        if ($sessionId && $order->getStatus() === 'processing') {
            try {
                Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
                $stripeSession = StripeSession::retrieve($sessionId);
                if ($stripeSession->payment_status === 'paid') {
                    $order->setStatus('completed');
                    $this->entityManager->flush();
                }
            } catch (\Exception) {
                // Session Stripe invalide, on garde le statut actuel
            }
        }

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
