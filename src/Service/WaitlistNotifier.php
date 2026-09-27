<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\WaitlistSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Annonce un nouveau drop à toute la liste d'attente.
 */
class WaitlistNotifier
{
    public function __construct(
        private readonly WaitlistSubscriberRepository $subscribers,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /** @return int nombre d'e-mails envoyés */
    public function announce(Category $drop): int
    {
        $sent = 0;
        foreach ($this->subscribers->findAll() as $subscriber) {
            try {
                $email = (new TemplatedEmail())
                    ->to($subscriber->getEmail())
                    ->subject(sprintf('%s est disponible', $drop->getName()))
                    ->htmlTemplate('emails/drop_launch.html.twig')
                    ->context(['drop' => $drop, 'subscriber' => $subscriber]);
                // Bouton « se désabonner » natif de Gmail / Outlook.
                $email->getHeaders()->addTextHeader('List-Unsubscribe', sprintf('<%s>', $this->urlGenerator->generate(
                    'app_waitlist_unsubscribe',
                    ['token' => $subscriber->getUnsubscribeToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                )));
                $this->mailer->send($email);
                ++$sent;
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Annonce de drop non envoyée.', ['email' => $subscriber->getEmail(), 'error' => $e->getMessage()]);
            }
        }

        $drop->markWaitlistNotified();
        $this->entityManager->flush();

        return $sent;
    }
}
