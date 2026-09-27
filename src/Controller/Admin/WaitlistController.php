<?php

namespace App\Controller\Admin;

use App\Repository\WaitlistSubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/waitlist')]
final class WaitlistController extends AbstractController
{
    #[Route(name: 'app_admin_waitlist_index', methods: ['GET'])]
    public function index(WaitlistSubscriberRepository $repository): Response
    {
        return $this->render('admin/waitlist/index.html.twig', [
            'subscribers' => $repository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/export.csv', name: 'app_admin_waitlist_export', methods: ['GET'])]
    public function export(WaitlistSubscriberRepository $repository): StreamedResponse
    {
        $response = new StreamedResponse(static function () use ($repository): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM : accents lisibles dans Excel
            fputcsv($out, ['email', 'inscription'], ';', escape: '');
            foreach ($repository->findBy([], ['createdAt' => 'ASC']) as $subscriber) {
                fputcsv($out, [$subscriber->getEmail(), $subscriber->getCreatedAt()->format('Y-m-d H:i')], ';', escape: '');
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="liste-attente.csv"');

        return $response;
    }
}
