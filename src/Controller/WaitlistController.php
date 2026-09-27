<?php

namespace App\Controller;

use App\Entity\WaitlistSubscriber;
use App\Repository\WaitlistSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WaitlistController extends AbstractController
{
    #[Route('/liste-attente', name: 'app_waitlist_subscribe', methods: ['POST'])]
    #[IsCsrfTokenValid('waitlist')]
    public function subscribe(Request $request, WaitlistSubscriberRepository $repository, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $payload = $request->getPayload();
        $email = mb_substr(trim($payload->getString('email')), 0, 180);

        // Champ invisible pour un humain : s'il est rempli, c'est un robot. On fait semblant d'accepter.
        if ('' !== $payload->getString('website')) {
            return $this->backWithSuccess($request);
        }

        if ('' === $email || count($validator->validate($email, new Email(mode: Email::VALIDATION_MODE_STRICT)))) {
            $this->addFlash('danger', 'Cette adresse e-mail ne semble pas valide.');

            return $this->redirectBack($request);
        }

        // Même réponse si l'adresse est déjà inscrite : on ne révèle pas qui l'est.
        if (!$repository->isSubscribed($email)) {
            $entityManager->persist(new WaitlistSubscriber($email));
            $entityManager->flush();
        }

        return $this->backWithSuccess($request);
    }

    /**
     * Le lien de l'e-mail mène à une page de confirmation (GET), et c'est le bouton (POST)
     * qui désinscrit : certains webmails ouvrent les liens tout seuls pour les analyser.
     */
    #[Route('/liste-attente/desinscription/{token}', name: 'app_waitlist_unsubscribe', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET', 'POST'])]
    public function unsubscribe(string $token, Request $request, WaitlistSubscriberRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $subscriber = $repository->findOneBy(['unsubscribeToken' => $token]);

        if ($subscriber && $request->isMethod('POST')) {
            $entityManager->remove($subscriber);
            $entityManager->flush();

            return $this->render('waitlist/unsubscribe.html.twig', ['done' => true, 'subscriber' => null]);
        }

        return $this->render('waitlist/unsubscribe.html.twig', ['done' => null === $subscriber, 'subscriber' => $subscriber]);
    }

    private function backWithSuccess(Request $request): Response
    {
        $this->addFlash('success', 'C\'est noté ! Vous serez prévenu(e) par e-mail dès la sortie du prochain drop.');

        return $this->redirectBack($request);
    }

    /** Revient sur la page d'où vient le formulaire, uniquement si c'est une page du site. */
    private function redirectBack(Request $request): Response
    {
        $referer = (string) $request->headers->get('referer');
        $parts = parse_url($referer);
        if ($referer && ($parts['host'] ?? null) === $request->getHost()) {
            $path = '/'.ltrim($parts['path'] ?? '', '/'); // jamais « //autre-site.com »
            $query = isset($parts['query']) ? '?'.$parts['query'] : '';

            return $this->redirect($path.$query.'#liste-attente');
        }

        return $this->redirectToRoute('home', ['_fragment' => 'liste-attente']);
    }
}
