<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/admin/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_admin_order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $status = $request->query->getString('status');
        $criteria = array_key_exists($status, Order::STATUS_LABELS) ? ['status' => $status] : [];

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orderRepository->findBy($criteria, ['createdAt' => 'DESC']),
            'currentStatus' => $criteria ? $status : null,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/ship', name: 'app_admin_order_ship', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"ship" ~ args["order"].getId()'))]
    public function ship(Order $order, Request $request, EntityManagerInterface $entityManager, OrderNotifier $notifier): Response
    {
        if (Order::STATUS_COMPLETED !== $order->getStatus()) {
            $this->addFlash('danger', 'Seule une commande payée et pas encore expédiée peut être expédiée.');

            return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
        }

        $order->ship(mb_substr($request->getPayload()->getString('tracking_number'), 0, 100));
        $entityManager->flush();
        $notifier->sendShippingNotification($order);

        $this->addFlash('success', 'Commande marquée comme expédiée, le client a été prévenu par e-mail.');

        return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
    }
}
