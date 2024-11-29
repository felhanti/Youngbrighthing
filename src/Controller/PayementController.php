<?php

namespace App\Controller;


use Stripe\Stripe;
use App\Entity\Order;
use Stripe\Checkout\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class PayementController extends AbstractController
{
    private EntityManagerInterface $entityManagerInterface;
    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    #[Route("/order/create-session-stripe/{orderId}", name: "payement_stripe")]
    public function stripeCheckout(int $orderId, Request $request): RedirectResponse
    {
        // Récupérez votre clé secrète depuis les paramètres de configuration
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $order = $this->entityManagerInterface->getRepository(Order::class)->find($orderId);
        
        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        try {
            
            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'eur',
                            'unit_amount' => $order->getTotal() * 100, // Convertir en centimes
                            'product_data' => [
                                'name' => 'Commande #' . $order->getId(),
                            ],
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('order_summary', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('app_cart_show', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                // 'return_url' => $this->generateUrl('home'),
            ]);
            // Mettez à jour le statut de la commande si nécessaire
            // $order->setStatus('completed');
            // $this->entityManagerInterface->persist($order);
            // $this->entityManagerInterface->flush();

            // Redirection vers la page de paiement Stripe
            return new RedirectResponse(url: $checkout_session->url);
            

        } catch (\Exception $e) {
            // Gérez les erreurs Stripe
            $this->addFlash('error', 'Erreur lors de la création de la session de paiement : ' . $e->getMessage());
            return $this->redirectToRoute('home');
        }
    }
}