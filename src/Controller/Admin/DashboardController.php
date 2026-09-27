<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\WaitlistSubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        UserRepository $userRepository,
        WaitlistSubscriberRepository $waitlistRepository,
    ): Response {
        $ordersByStatus = $orderRepository->countByStatus();

        return $this->render('admin/index.html.twig', [
            'revenue' => $orderRepository->sumPaidTotal(),
            'ordersByStatus' => $ordersByStatus,
            'ordersTotal' => array_sum($ordersByStatus),
            'recentOrders' => $orderRepository->findRecent(5),
            'productsTotal' => $productRepository->count(),
            'productsAvailable' => $productRepository->count(['available' => true]),
            'usersTotal' => $userRepository->count(),
            'waitlistTotal' => $waitlistRepository->count(),
        ]);
    }
}
