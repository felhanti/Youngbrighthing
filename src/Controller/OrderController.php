<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Exception\ProductsUnavailableException;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Service\CartToOrderService;
use App\Service\OrderPaymentService;
use App\Service\StripeCheckoutService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    #[Route('/order/finalize', name: 'order_finalize', methods: ['POST'])]
    #[IsCsrfTokenValid('order_finalize')]
    public function finalize(#[CurrentUser] User $user, CartRepository $cartRepository, CartToOrderService $cartToOrder): Response
    {
        $cart = $cartRepository->getOrCreateForUser($user);

        if ($cart->getProduct()->isEmpty()) {
            $this->addFlash('danger', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart_show');
        }

        try {
            $order = $cartToOrder->createOrderFromCart($user, $cart);
        } catch (ProductsUnavailableException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('app_cart_show');
        }

        return $this->redirectToRoute('payment_stripe', ['id' => $order->getId()]);
    }

    #[Route('/order/summary/{id}', name: 'order_summary', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function summary(
        Order $order,
        Request $request,
        StripeCheckoutService $stripe,
        OrderPaymentService $payments,
        LoggerInterface $logger,
    ): Response {
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        // Retour de Stripe : on confirme tout de suite sans attendre le webhook,
        // mais uniquement si la session prouve le paiement de CETTE commande.
        $sessionId = $request->query->getString('session_id');
        if ('' !== $sessionId && Order::STATUS_PROCESSING === $order->getStatus()) {
            try {
                if ($stripe->isPaidSessionFor($stripe->retrieveSession($sessionId), $order)) {
                    $payments->confirmPayment($order);
                }
            } catch (\Throwable $e) {
                // Le webhook prendra le relais.
                $logger->warning('Vérification Stripe au retour impossible.', ['order' => $order->getId(), 'error' => $e->getMessage()]);
            }
        }

        return $this->render('order/summary.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/orders', name: 'order_list', methods: ['GET'])]
    public function list(#[CurrentUser] User $user, OrderRepository $orderRepository): Response
    {
        return $this->render('order/list.html.twig', [
            'orders' => $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']),
        ]);
    }
}
