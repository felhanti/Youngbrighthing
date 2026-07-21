<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
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
    ): Response {
        $ordersByStatus = $orderRepository->countByStatus();

        return $this->render('admin/index.html.twig', [
            'revenue' => $orderRepository->sumTotalByStatus('completed'),
            'ordersByStatus' => $ordersByStatus,
            'ordersTotal' => array_sum($ordersByStatus),
            'recentOrders' => $orderRepository->findRecent(5),
            'productsTotal' => count($productRepository->findAll()),
            'productsAvailable' => count($productRepository->findBy(['is_sold' => true])),
            'usersTotal' => count($userRepository->findAll()),
        ]);
    }
}
